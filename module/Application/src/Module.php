<?php

declare(strict_types=1);

namespace Application;

use Application\Helper\Access;
use Application\Model\AccountModel;
use Laminas\Mvc\MvcEvent;

class Module
{
    public function onBootstrap(MvcEvent $event): void
    {
        $event->getApplication()->getEventManager()->attach(MvcEvent::EVENT_ROUTE, function (MvcEvent $routeEvent) {
            $match = $routeEvent->getRouteMatch();
            if ($match === null) {
                return;
            }
            $route = $match->getMatchedRouteName();
            if (in_array($route, ['login', 'register', 'verify-email', 'forgot-password', 'reset-password'], true)) {
                return;
            }
            $config = $routeEvent->getApplication()->getServiceManager()->get('config');
            $access = new Access(new AccountModel($config['personal_finance']['database']));
            $account = $access->account();
            if ($account === null) {
                $response = $routeEvent->getResponse();
                $response->setStatusCode(302);
                $response->getHeaders()->addHeaderLine('Location', '/login');
                $routeEvent->stopPropagation(true);
                return $response;
            }
            $permissions = [
                'home' => 'app.view',
                'application' => 'app.view',
                'personal-finance' => 'finance.view',
                'personal-finance/action' => [
                    'index' => 'finance.view',
                    'add' => 'finance.transaction.create',
                    'update' => 'finance.transaction.update',
                    'create-wallet' => 'finance.wallet.create',
                    'add-category' => 'finance.category.create',
                    'save-budget' => 'finance.budget.manage',
                    'clone-budget' => 'finance.budget.manage',
                ],
                'logout' => 'account.logout',
            ];
            $rule = $permissions[$route] ?? null;
            $permission = is_array($rule) ? ($rule[$match->getParam('action')] ?? null) : $rule;
            if (!in_array($route, ['logout', 'profile', 'profile-update', 'change-password'], true)
                && (!is_string($permission) || !$access->allows($permission, $account))) {
                $response = $routeEvent->getResponse();
                $response->setStatusCode(403);
                $response->getHeaders()->addHeaderLine('Content-Type', 'text/plain; charset=utf-8');
                $response->setContent('You do not have permission to access this page.');
                $routeEvent->stopPropagation(true);
                return $response;
            }
            $request = $routeEvent->getRequest();
            if ($request->isPost() && !Access::validCsrf($request->getPost('csrf_token'))) {
                $response = $routeEvent->getResponse();
                $response->setStatusCode(400);
                $response->setContent('Invalid request. Refresh the page and try again.');
                $routeEvent->stopPropagation(true);
                return $response;
            }
        }, -100);
    }

    public function getConfig(): array
    {
        /** @var array $config */
        $config = include __DIR__ . '/../config/module.config.php';
        return $config;
    }
}
