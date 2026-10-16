<?php

declare(strict_types=1);

namespace PersonalFinance;

use Laminas\Router\Http\Literal;
use Laminas\Router\Http\Segment;

return [
    'router' => [
        'routes' => [
            'personal-finance' => [
                'type' => Literal::class,
                'options' => [
                    'route' => '/personal-finance',
                    'defaults' => [
                        'controller' => Controller\PersonalFinanceController::class,
                        'action' => 'index',
                    ],
                ],
                'may_terminate' => true,
                'child_routes' => [
                    'action' => [
                        'type' => Segment::class,
                        'options' => [
                            'route' => '/:action',
                            'constraints' => [
                                'action' => '[a-zA-Z][a-zA-Z0-9_-]*',
                            ],
                        ],
                    ],
                ],
            ],
        ],
    ],
    'controllers' => [
        'invokables' => [
            Controller\PersonalFinanceController::class => Controller\PersonalFinanceController::class,
        ],
    ],
    'view_manager' => [
        'template_map' => [
            'personal-finance/dashboard/index' => __DIR__ . '/../view/personal-finance/dashboard/index.phtml',
        ],
        'template_path_stack' => [
            __DIR__ . '/../view',
        ],
    ],
];
