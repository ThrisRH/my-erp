<?php

declare(strict_types=1);

namespace Application\Controller\Auth;

use Application\Controller\AuthController;
use Application\Helper\Access;
use Laminas\View\Model\ViewModel;

class LoginAction
{
    public function handle(AuthController $controller)
    {
        Access::startSession();
        $accounts = $controller->accounts();
        $access = new Access($accounts);
        if ($access->account() !== null) {
            return $controller->redirect()->toRoute('personal-finance');
        }
        $error = null;
        if ($controller->getRequest()->isPost()) {
            if (!Access::validCsrf($controller->params()->fromPost('csrf_token'))) {
                $controller->getResponse()->setStatusCode(400);
                $error = 'Invalid request. Refresh the page and try again.';
            } else {
                $username = trim((string) $controller->params()->fromPost('username', ''));
                $password = (string) $controller->params()->fromPost('password', '');
                $account = $accounts->findByUsername($username);
                if ($account !== null && $account['status'] === 'active'
                    && $account['employment_status'] === 'active'
                    && password_verify($password, (string) $account['password_hash'])) {
                    session_regenerate_id(true);
                    $_SESSION['userStorage'] = [
                        'id' => (int) $account['id'], 'version' => (int) $account['session_version'],
                    ];
                    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
                    $accounts->recordLogin((int) $account['id']);
                    return $controller->redirect()->toRoute('personal-finance');
                }
                $error = 'Invalid username or password.';
            }
        }
        return (new ViewModel(['error' => $error, 'csrfToken' => Access::csrfToken()]))
            ->setTemplate('application/auth/login');
    }
}
