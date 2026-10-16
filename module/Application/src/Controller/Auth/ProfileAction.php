<?php

declare(strict_types=1);

namespace Application\Controller\Auth;

use Application\Controller\AuthController;
use Application\Helper\Access;
use Laminas\View\Model\ViewModel;

class ProfileAction
{
    public function view(AuthController $controller): ViewModel
    {
        $account = (new Access($controller->accounts()))->account();
        return (new ViewModel([
            'account' => $account,
            'notice' => (string) $controller->params()->fromQuery('notice', ''),
            'error' => null,
            'csrfToken' => Access::csrfToken(),
        ]))->setTemplate('application/auth/profile');
    }

    public function update(AuthController $controller)
    {
        if (!$controller->getRequest()->isPost()) {
            return $controller->redirect()->toRoute('profile');
        }
        $accounts = $controller->accounts();
        $account = (new Access($accounts))->account();
        $firstName = trim((string) $controller->params()->fromPost('first_name', ''));
        $lastName = trim((string) $controller->params()->fromPost('last_name', ''));
        $phone = trim((string) $controller->params()->fromPost('phone', ''));
        if ($account === null || $firstName === '' || mb_strlen($firstName) > 100
            || $lastName === '' || mb_strlen($lastName) > 100
            || strlen($phone) > 30 || ($phone !== '' && !preg_match('/^[0-9+().\s-]+$/', $phone))) {
            return $this->errorView($controller, 'Check your name and phone number.');
        }
        $accounts->updateProfile((int) $account['employee_id'], $firstName, $lastName, $phone);
        return $controller->redirect()->toRoute('profile', [], ['query' => ['notice' => 'profile-updated']]);
    }

    public function changePassword(AuthController $controller)
    {
        if (!$controller->getRequest()->isPost()) {
            return $controller->redirect()->toRoute('profile');
        }
        $accounts = $controller->accounts();
        $account = (new Access($accounts))->account();
        $newPassword = (string) $controller->params()->fromPost('new_password', '');
        if ($account === null || strlen($newPassword) < 12
            || $newPassword !== $controller->params()->fromPost('password_confirm')) {
            return $this->errorView($controller, 'New passwords must match and contain at least 12 characters.');
        }
        if (!$accounts->changePassword((int) $account['id'],
            (string) $controller->params()->fromPost('current_password', ''), $newPassword)) {
            return $this->errorView($controller, 'Current password is incorrect.');
        }
        Access::startSession();
        session_regenerate_id(true);
        $_SESSION['userStorage']['version'] = (int) $account['session_version'] + 1;
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        return $controller->redirect()->toRoute('profile', [], ['query' => ['notice' => 'password-updated']]);
    }

    private function errorView(AuthController $controller, string $message): ViewModel
    {
        $account = (new Access($controller->accounts()))->account();
        return (new ViewModel(['account' => $account, 'notice' => '', 'error' => $message,
            'csrfToken' => Access::csrfToken()]))->setTemplate('application/auth/profile');
    }
}
