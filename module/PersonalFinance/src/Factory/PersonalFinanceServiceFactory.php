<?php

declare(strict_types=1);

namespace PersonalFinance\Factory;

use PDO;
use PersonalFinance\Repository\PersonalFinanceRepository;
use PersonalFinance\Service\PersonalFinanceService;
use Psr\Container\ContainerInterface;
use RuntimeException;

class PersonalFinanceServiceFactory
{
    public function __invoke(ContainerInterface $container): PersonalFinanceService
    {
        /** @var array<string, mixed> $config */
        $config = $container->get('config');
        /** @var array<string, mixed> $database */
        $database = $config['personal_finance']['database'] ?? [];

        if (($database['username'] ?? '') === '' || ($database['password'] ?? '') === '') {
            throw new RuntimeException('Personal finance database credentials are not configured.');
        }

        $dsn = sprintf(
            'mysql:host=%s;port=%d;dbname=%s;charset=utf8mb4',
            $database['host'] ?? '127.0.0.1',
            (int) ($database['port'] ?? 3306),
            $database['database'] ?? 'erp'
        );

        $connection = new PDO($dsn, (string) $database['username'], (string) $database['password'], [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]);

        return new PersonalFinanceService(new PersonalFinanceRepository($connection));
    }
}
