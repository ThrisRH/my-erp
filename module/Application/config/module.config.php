<?php

declare(strict_types=1);

namespace Application;

use Laminas\Router\Http\Literal;
use Laminas\Router\Http\Segment;
use Laminas\ServiceManager\Factory\InvokableFactory;

return [
    'router' => [
        'routes' => [
            'home' => [
                'type'    => Literal::class,
                'options' => [
                    'route'    => '/',
                    'defaults' => [
                        'controller' => Controller\IndexController::class,
                        'action'     => 'index',
                    ],
                ],
            ],
            'login' => [
                'type' => Literal::class,
                'options' => ['route' => '/login', 'defaults' => [
                    'controller' => Controller\AuthController::class, 'action' => 'login',
                ]],
            ],
            'logout' => [
                'type' => Literal::class,
                'options' => ['route' => '/logout', 'defaults' => [
                    'controller' => Controller\AuthController::class, 'action' => 'logout',
                ]],
            ],
            'register' => [
                'type' => Literal::class,
                'options' => ['route' => '/register', 'defaults' => [
                    'controller' => Controller\AuthController::class, 'action' => 'register',
                ]],
            ],
            'verify-email' => [
                'type' => Literal::class,
                'options' => ['route' => '/verify-email', 'defaults' => [
                    'controller' => Controller\AuthController::class, 'action' => 'verify-email',
                ]],
            ],
            'forgot-password' => [
                'type' => Literal::class,
                'options' => ['route' => '/forgot-password', 'defaults' => [
                    'controller' => Controller\AuthController::class, 'action' => 'forgot-password',
                ]],
            ],
            'reset-password' => [
                'type' => Literal::class,
                'options' => ['route' => '/reset-password', 'defaults' => [
                    'controller' => Controller\AuthController::class, 'action' => 'reset-password',
                ]],
            ],
            'profile' => [
                'type' => Literal::class,
                'options' => ['route' => '/profile', 'defaults' => [
                    'controller' => Controller\AuthController::class, 'action' => 'profile',
                ]],
            ],
            'profile-update' => [
                'type' => Literal::class,
                'options' => ['route' => '/profile/update', 'defaults' => [
                    'controller' => Controller\AuthController::class, 'action' => 'update-profile',
                ]],
            ],
            'change-password' => [
                'type' => Literal::class,
                'options' => ['route' => '/profile/password', 'defaults' => [
                    'controller' => Controller\AuthController::class, 'action' => 'change-password',
                ]],
            ],
            'application' => [
                'type'    => Segment::class,
                'options' => [
                    'route'    => '/application[/:action]',
                    'defaults' => [
                        'controller' => Controller\IndexController::class,
                        'action'     => 'index',
                    ],
                ],
            ],
        ],
    ],
    'controllers' => [
        'factories' => [
            Controller\IndexController::class => InvokableFactory::class,
            Controller\AuthController::class => InvokableFactory::class,
        ],
    ],
    'view_manager' => [
        'display_not_found_reason' => true,
        'display_exceptions'       => true,
        'doctype'                  => 'HTML5',
        'not_found_template'       => 'error/404',
        'exception_template'       => 'error/index',
        'template_map' => [
            'layout/layout'           => __DIR__ . '/../view/layout/layout.phtml',
            'application/index/index' => __DIR__ . '/../view/application/index/index.phtml',
            'application/auth/login' => __DIR__ . '/../view/application/auth/login.phtml',
            'application/auth/register' => __DIR__ . '/../view/application/auth/register.phtml',
            'application/auth/forgot-password' => __DIR__ . '/../view/application/auth/forgot-password.phtml',
            'application/auth/reset-password' => __DIR__ . '/../view/application/auth/reset-password.phtml',
            'application/auth/verify-email' => __DIR__ . '/../view/application/auth/verify-email.phtml',
            'application/auth/profile' => __DIR__ . '/../view/application/auth/profile.phtml',
            'error/404'               => __DIR__ . '/../view/error/404.phtml',
            'error/index'             => __DIR__ . '/../view/error/index.phtml',
        ],
        'template_path_stack' => [
            __DIR__ . '/../view',
        ],
    ],
];
