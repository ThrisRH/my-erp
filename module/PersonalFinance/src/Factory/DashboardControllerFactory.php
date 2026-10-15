<?php

declare(strict_types=1);

namespace PersonalFinance\Factory;

use PersonalFinance\Controller\DashboardController;
use PersonalFinance\Service\PersonalFinanceService;
use Psr\Container\ContainerInterface;

class DashboardControllerFactory
{
    public function __invoke(ContainerInterface $container): DashboardController
    {
        return new DashboardController($container->get(PersonalFinanceService::class));
    }
}
