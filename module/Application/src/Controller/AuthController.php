<?php

declare(strict_types=1);

namespace Application\Controller;

use Application\Helper\Access;
use Application\Model\AccountModel;
use Laminas\Mvc\Controller\AbstractActionController;
use Laminas\View\Model\ViewModel;

class AuthController extends AbstractActionController
{
    public function loginAction()
    {
        Access::startSession();
        $config = $this->getEvent()->getApplication()->getServiceManager()->get('config');
        $accounts = new AccountModel($config['personal_finance']['database']);
        $access = new Access($accounts);
        if ($access->account() !== null) {
            return $this->redirect()->toRoute('personal-finance');
        }
        $error = null;
        if ($this->getRequest()->isPost()) {
            if (!Access::validCsrf($this->params()->fromPost('csrf_token'))) {
                $this->getResponse()->setStatusCode(400);
                $error = 'Invalid request. Refresh the page and try again.';
            } else {
                $username = trim((string) $this->params()->fromPost('username', ''));
                $password = (string) $this->params()->fromPost('password', '');
                $account = $accounts->findByUsername($username);
                if ($account !== null && $account['status'] === 'active'
                    && $account['employment_status'] === 'active'
                    && password_verify($password, (string) $account['password_hash'])) {
                    session_regenerate_id(true);
                    $_SESSION['userStorage'] = ['id' => (int) $account['id']];
                    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
                    $accounts->recordLogin((int) $account['id']);
                    return $this->redirect()->toRoute('personal-finance');
                }
                $error = 'Invalid username or password.';
            }
        }
        return new ViewModel(['error' => $error, 'csrfToken' => Access::csrfToken()]);
    }

    public function logoutAction()
    {
        if (!$this->getRequest()->isPost() || !Access::validCsrf($this->params()->fromPost('csrf_token'))) {
            return $this->getResponse()->setStatusCode(400)->setContent('Invalid request.');
        }
        Access::startSession();
        $_SESSION = [];
        session_regenerate_id(true);
        return $this->redirect()->toRoute('login');
    }
}
