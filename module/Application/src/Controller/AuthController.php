<?php

declare(strict_types=1);

namespace Application\Controller;

use Application\Controller\Auth\LoginAction;
use Application\Controller\Auth\LogoutAction;
use Application\Controller\Auth\ProfileAction;
use Application\Controller\Auth\RecoveryAction;
use Application\Controller\Auth\RegistrationAction;
use Application\Model\AccountModel;
use Application\Service\AccountMailer;
use Laminas\Mvc\Controller\AbstractActionController;

class AuthController extends AbstractActionController
{
    public function accounts(): AccountModel
    {
        $config = $this->getEvent()->getApplication()->getServiceManager()->get('config');
        return new AccountModel($config['personal_finance']['database']);
    }

    public function mailer(): AccountMailer
    {
        return new AccountMailer();
    }

    public function loginAction()
    {
        return (new LoginAction())->handle($this);
    }

    public function logoutAction()
    {
        return (new LogoutAction())->handle($this);
    }

    public function registerAction()
    {
        return (new RegistrationAction())->register($this);
    }

    public function verifyEmailAction()
    {
        return (new RegistrationAction())->verify($this);
    }

    public function forgotPasswordAction()
    {
        return (new RecoveryAction())->forgot($this);
    }

    public function resetPasswordAction()
    {
        return (new RecoveryAction())->reset($this);
    }

    public function profileAction()
    {
        return (new ProfileAction())->view($this);
    }

    public function updateProfileAction()
    {
        return (new ProfileAction())->update($this);
    }

    public function changePasswordAction()
    {
        return (new ProfileAction())->changePassword($this);
    }
}
