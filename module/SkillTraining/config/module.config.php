<?php

declare(strict_types=1);

namespace SkillTraining;

use Laminas\Router\Http\Literal;
use Laminas\Router\Http\Segment;

return [
    'router' => [
        'routes' => [
            'skill-training' => [
                'type' => Literal::class,
                'options' => [
                    'route' => '/skill-training',
                    'defaults' => [
                        'controller' => Controller\SkillTrainingController::class,
                        'action' => 'index',
                    ],
                ],
                'may_terminate' => true,
                'child_routes' => [
                    'action' => [
                        'type' => Segment::class,
                        'options' => [
                            'route' => '/:action',
                            'constraints' => ['action' => '[a-zA-Z][a-zA-Z0-9_-]*'],
                        ],
                    ],
                ],
            ],
        ],
    ],
    'controllers' => [
        'invokables' => [
            Controller\SkillTrainingController::class => Controller\SkillTrainingController::class,
        ],
    ],
    'view_manager' => [
        'template_map' => [
            'skill-training/index' => __DIR__ . '/../view/skill-training/index.phtml',
        ],
        'template_path_stack' => [__DIR__ . '/../view'],
    ],
];
