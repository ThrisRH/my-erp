<?php

declare(strict_types=1);

namespace Application\Model;

use PDO;

class AccountModel
{
    private PDO $connection;

    public function __construct(array $database)
    {
        $dsn = sprintf('mysql:host=%s;port=%d;dbname=%s;charset=utf8mb4',
            $database['host'] ?? '127.0.0.1', (int) ($database['port'] ?? 3306), $database['database'] ?? 'erp');
        $this->connection = new PDO($dsn, (string) ($database['username'] ?? ''), (string) ($database['password'] ?? ''), [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]);
    }

    public function findByUsername(string $username): ?array
    {
        $statement = $this->connection->prepare(
            "SELECT a.id, a.employee_id, a.password_hash, a.permissions, a.is_admin, a.status,
                    e.first_name, e.last_name, e.employee_code, e.work_email, e.employment_status
             FROM accounts a JOIN employees e ON e.id = a.employee_id WHERE a.username = :username LIMIT 1"
        );
        $statement->execute(['username' => $username]);
        return $statement->fetch() ?: null;
    }

    public function findById(int $id): ?array
    {
        $statement = $this->connection->prepare(
            "SELECT a.id, a.employee_id, a.permissions, a.is_admin, a.status,
                    e.first_name, e.last_name, e.employee_code, e.work_email, e.employment_status
             FROM accounts a JOIN employees e ON e.id = a.employee_id WHERE a.id = :id LIMIT 1"
        );
        $statement->execute(['id' => $id]);
        return $statement->fetch() ?: null;
    }

    public function adminId(): ?int
    {
        $id = $this->connection->query('SELECT id FROM accounts WHERE is_admin = 1 LIMIT 1')->fetchColumn();
        return $id === false ? null : (int) $id;
    }

    public function recordLogin(int $id): void
    {
        $statement = $this->connection->prepare('UPDATE accounts SET last_login_at = NOW() WHERE id = :id');
        $statement->execute(['id' => $id]);
    }
}
