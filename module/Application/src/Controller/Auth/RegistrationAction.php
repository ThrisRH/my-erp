<?php

declare(strict_types=1);

namespace Application\Controller\Auth;

use Application\Controller\AuthController;
use Application\Helper\Access;
use Laminas\View\Model\ViewModel;
use PDOException;
use RuntimeException;

class RegistrationAction
{
    public function register(AuthController $controller): ViewModel
    {
        $error = null;
        $sent = false;
        $input = ['first_name' => '', 'last_name' => '', 'email' => ''];
        if ($controller->getRequest()->isPost()) {
            $input = [
                'first_name' => trim((string) $controller->params()->fromPost('first_name', '')),
                'last_name' => trim((string) $controller->params()->fromPost('last_name', '')),
                'email' => strtolower(trim((string) $controller->params()->fromPost('email', ''))),
            ];
            $password = (string) $controller->params()->fromPost('password', '');
            if (!Access::validCsrf($controller->params()->fromPost('csrf_token'))) {
                $error = 'Invalid request. Refresh the page and try again.';
                $controller->getResponse()->setStatusCode(400);
            } elseif ($input['first_name'] === '' || mb_strlen($input['first_name']) > 100
                || $input['last_name'] === '' || mb_strlen($input['last_name']) > 100
                || !filter_var($input['email'], FILTER_VALIDATE_EMAIL) || strlen($input['email']) > 255
                || strlen($password) < 12 || $password !== $controller->params()->fromPost('password_confirm')) {
                $error = 'Check your details. Passwords must match and contain at least 12 characters.';
            } elseif (!$controller->mailer()->available()) {
                $error = 'Registration is temporarily unavailable because email delivery is not configured.';
            } else {
                try {
                    $token = $controller->accounts()->register(
                        $input['first_name'], $input['last_name'], $input['email'], $password
                    );
                    $controller->mailer()->sendLink($input['email'], 'verify_email', $token);
                    $sent = true;
                } catch (PDOException $error) {
                    if ((string) $error->getCode() !== '23000') {
                        throw $error;
                    }
                    // Preserve a generic result for existing or pending email addresses.
                    $token = $controller->accounts()->requestVerification($input['email']);
                    if ($token !== null) {
                        try {
                            $controller->mailer()->sendLink($input['email'], 'verify_email', $token);
                        } catch (RuntimeException $deliveryError) {
                            $error = 'We could not send email right now. Please try again later.';
                        }
                    }
                    $sent = $error === null;
                } catch (RuntimeException $deliveryError) {
                    $error = 'We could not send email right now. Please try again later.';
                }
            }
        }
        return (new ViewModel([
            'error' => $error, 'sent' => $sent, 'input' => $input, 'csrfToken' => Access::csrfToken(),
        ]))->setTemplate('application/auth/register');
    }

    public function verify(AuthController $controller): ViewModel
    {
        $token = (string) $controller->params()->fromQuery('token', '');
        $verified = $controller->accounts()->consumeToken($token, 'verify_email');
        return (new ViewModel(['verified' => $verified]))->setTemplate('application/auth/verify-email');
    }
}
