<?php

declare(strict_types=1);

namespace Application\Controller\Auth;

use Application\Controller\AuthController;
use Application\Helper\Access;
use Laminas\View\Model\ViewModel;
use RuntimeException;

class RecoveryAction
{
    public function forgot(AuthController $controller): ViewModel
    {
        $error = null;
        $sent = false;
        if ($controller->getRequest()->isPost()) {
            $email = strtolower(trim((string) $controller->params()->fromPost('email', '')));
            if (!Access::validCsrf($controller->params()->fromPost('csrf_token'))) {
                $error = 'Invalid request. Refresh the page and try again.';
                $controller->getResponse()->setStatusCode(400);
            } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $error = 'Enter a valid email address.';
            } elseif (!$controller->mailer()->available()) {
                $error = 'Password recovery is temporarily unavailable because email delivery is not configured.';
            } else {
                $token = $controller->accounts()->requestPasswordReset($email);
                try {
                    if ($token !== null) {
                        $controller->mailer()->sendLink($email, 'reset_password', $token);
                    }
                    $sent = true;
                } catch (RuntimeException $deliveryError) {
                    $error = 'We could not send email right now. Please try again later.';
                }
            }
        }
        return (new ViewModel(['error' => $error, 'sent' => $sent,
            'csrfToken' => Access::csrfToken()]))->setTemplate('application/auth/forgot-password');
    }

    public function reset(AuthController $controller): ViewModel
    {
        $token = (string) ($controller->getRequest()->isPost()
            ? $controller->params()->fromPost('token', '') : $controller->params()->fromQuery('token', ''));
        $valid = $controller->accounts()->tokenIsValid($token, 'reset_password');
        $error = null;
        $completed = false;
        if ($controller->getRequest()->isPost() && $valid) {
            $password = (string) $controller->params()->fromPost('password', '');
            if (!Access::validCsrf($controller->params()->fromPost('csrf_token'))) {
                $error = 'Invalid request. Refresh the page and try again.';
                $controller->getResponse()->setStatusCode(400);
            } elseif (strlen($password) < 12 || $password !== $controller->params()->fromPost('password_confirm')) {
                $error = 'Passwords must match and contain at least 12 characters.';
            } else {
                $completed = $controller->accounts()->consumeToken($token, 'reset_password', $password);
                if ($completed) {
                    Access::startSession();
                    $_SESSION = [];
                    session_regenerate_id(true);
                }
            }
        }
        return (new ViewModel(['token' => $token, 'valid' => $valid, 'completed' => $completed,
            'error' => $error, 'csrfToken' => Access::csrfToken()]))->setTemplate('application/auth/reset-password');
    }
}
