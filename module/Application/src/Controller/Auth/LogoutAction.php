<?php

declare(strict_types=1);

namespace Application\Controller\Auth;

use Application\Controller\AuthController;
use Application\Helper\Access;

class LogoutAction
{
    public function handle(AuthController $controller)
    {
        if (!$controller->getRequest()->isPost()
            || !Access::validCsrf($controller->params()->fromPost('csrf_token'))) {
            return $controller->getResponse()->setStatusCode(400)->setContent('Invalid request.');
        }
        Access::startSession();
        $_SESSION = [];
        session_regenerate_id(true);
        return $controller->redirect()->toRoute('login');
    }
}
