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
            "SELECT a.id, a.employee_id, a.username, a.password_hash, a.permissions, a.is_admin, a.status,
                    a.email_verified_at, a.session_version, e.first_name, e.last_name, e.phone, e.job_title,
                    e.employee_code, e.work_email, e.employment_status
             FROM accounts a JOIN employees e ON e.id = a.employee_id WHERE a.username = :username LIMIT 1"
        );
        $statement->execute(['username' => $username]);
        return $statement->fetch() ?: null;
    }

    public function findById(int $id): ?array
    {
        $statement = $this->connection->prepare(
            "SELECT a.id, a.employee_id, a.username, a.password_hash, a.permissions, a.is_admin, a.status,
                    a.email_verified_at, a.session_version, e.first_name, e.last_name, e.phone, e.job_title,
                    e.employee_code, e.work_email, e.employment_status
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

    public function register(string $firstName, string $lastName, string $email, string $password): string
    {
        $this->connection->beginTransaction();
        try {
            $employee = $this->connection->prepare(
                'INSERT INTO employees (employee_code, first_name, last_name, work_email) VALUES (:code, :first_name, :last_name, :email)'
            );
            $employee->execute([
                'code' => 'SELF-' . strtoupper(bin2hex(random_bytes(6))),
                'first_name' => $firstName,
                'last_name' => $lastName,
                'email' => $email,
            ]);
            $employeeId = (int) $this->connection->lastInsertId();
            $account = $this->connection->prepare(
                "INSERT INTO accounts (employee_id, username, password_hash, status, permissions)
                 VALUES (:employee_id, :username, :password_hash, 'disabled', :permissions)"
            );
            $account->execute([
                'employee_id' => $employeeId,
                'username' => $email,
                'password_hash' => password_hash($password, PASSWORD_DEFAULT),
                'permissions' => json_encode([
                    'app.view', 'finance.view', 'finance.transaction.create', 'finance.transaction.update',
                    'finance.wallet.create', 'finance.category.create', 'finance.budget.manage',
                ], JSON_THROW_ON_ERROR),
            ]);
            $token = $this->issueToken((int) $this->connection->lastInsertId(), 'verify_email', 1440);
            $this->connection->commit();
            return $token;
        } catch (\Throwable $error) {
            $this->connection->rollBack();
            throw $error;
        }
    }

    public function requestPasswordReset(string $email): ?string
    {
        $statement = $this->connection->prepare(
            "SELECT a.id FROM accounts a JOIN employees e ON e.id = a.employee_id
             WHERE e.work_email = :email AND a.status = 'active' AND a.email_verified_at IS NOT NULL LIMIT 1"
        );
        $statement->execute(['email' => $email]);
        $id = $statement->fetchColumn();
        return $id === false ? null : $this->issueToken((int) $id, 'reset_password', 30);
    }

    public function requestVerification(string $email): ?string
    {
        $statement = $this->connection->prepare(
            "SELECT a.id FROM accounts a JOIN employees e ON e.id = a.employee_id
             WHERE e.work_email = :email AND a.status = 'disabled' AND a.email_verified_at IS NULL LIMIT 1"
        );
        $statement->execute(['email' => $email]);
        $id = $statement->fetchColumn();
        return $id === false ? null : $this->issueToken((int) $id, 'verify_email', 1440);
    }

    public function consumeToken(string $token, string $purpose, ?string $password = null): bool
    {
        if (!preg_match('/^[A-Za-z0-9_-]{43}$/', $token)) {
            return false;
        }
        $this->connection->beginTransaction();
        try {
            $statement = $this->connection->prepare(
                'SELECT id, account_id FROM account_tokens WHERE token_hash = :hash AND purpose = :purpose
                 AND used_at IS NULL AND expires_at > NOW() FOR UPDATE'
            );
            $statement->execute(['hash' => hash('sha256', $token), 'purpose' => $purpose]);
            $row = $statement->fetch(PDO::FETCH_ASSOC);
            if ($row === false) {
                $this->connection->rollBack();
                return false;
            }
            if ($purpose === 'verify_email') {
                $update = $this->connection->prepare(
                    "UPDATE accounts SET status = 'active', email_verified_at = NOW() WHERE id = :id AND status = 'disabled'"
                );
                $update->execute(['id' => $row['account_id']]);
            } elseif ($purpose === 'reset_password' && $password !== null) {
                $update = $this->connection->prepare('UPDATE accounts SET password_hash = :hash, session_version = session_version + 1 WHERE id = :id');
                $update->execute(['hash' => password_hash($password, PASSWORD_DEFAULT), 'id' => $row['account_id']]);
            } else {
                $this->connection->rollBack();
                return false;
            }
            if ($update->rowCount() !== 1) {
                $this->connection->rollBack();
                return false;
            }
            $used = $this->connection->prepare('UPDATE account_tokens SET used_at = NOW() WHERE account_id = :account_id AND purpose = :purpose AND used_at IS NULL');
            $used->execute(['account_id' => $row['account_id'], 'purpose' => $purpose]);
            $this->connection->commit();
            return true;
        } catch (\Throwable $error) {
            $this->connection->rollBack();
            throw $error;
        }
    }

    public function tokenIsValid(string $token, string $purpose): bool
    {
        if (!preg_match('/^[A-Za-z0-9_-]{43}$/', $token)) {
            return false;
        }
        $statement = $this->connection->prepare(
            'SELECT 1 FROM account_tokens WHERE token_hash = :hash AND purpose = :purpose
             AND used_at IS NULL AND expires_at > NOW() LIMIT 1'
        );
        $statement->execute(['hash' => hash('sha256', $token), 'purpose' => $purpose]);
        return $statement->fetchColumn() !== false;
    }

    public function updateProfile(int $employeeId, string $firstName, string $lastName, string $phone): void
    {
        $statement = $this->connection->prepare(
            'UPDATE employees SET first_name = :first_name, last_name = :last_name, phone = :phone WHERE id = :id'
        );
        $statement->execute(['first_name' => $firstName, 'last_name' => $lastName,
            'phone' => $phone === '' ? null : $phone, 'id' => $employeeId]);
    }

    public function changePassword(int $accountId, string $currentPassword, string $newPassword): bool
    {
        $this->connection->beginTransaction();
        try {
            $statement = $this->connection->prepare('SELECT password_hash FROM accounts WHERE id = :id FOR UPDATE');
            $statement->execute(['id' => $accountId]);
            $hash = $statement->fetchColumn();
            if ($hash === false || !password_verify($currentPassword, (string) $hash)) {
                $this->connection->rollBack();
                return false;
            }
            $update = $this->connection->prepare('UPDATE accounts SET password_hash = :hash, session_version = session_version + 1 WHERE id = :id');
            $update->execute(['hash' => password_hash($newPassword, PASSWORD_DEFAULT), 'id' => $accountId]);
            $delete = $this->connection->prepare('DELETE FROM account_tokens WHERE account_id = :id');
            $delete->execute(['id' => $accountId]);
            $this->connection->commit();
            return true;
        } catch (\Throwable $error) {
            $this->connection->rollBack();
            throw $error;
        }
    }

    private function issueToken(int $accountId, string $purpose, int $minutes): string
    {
        $token = rtrim(strtr(base64_encode(random_bytes(32)), '+/', '-_'), '=');
        $expire = (new \DateTimeImmutable('now'))->modify('+' . $minutes . ' minutes')->format('Y-m-d H:i:s');
        $delete = $this->connection->prepare('DELETE FROM account_tokens WHERE account_id = :id AND purpose = :purpose');
        $delete->execute(['id' => $accountId, 'purpose' => $purpose]);
        $insert = $this->connection->prepare(
            'INSERT INTO account_tokens (account_id, purpose, token_hash, expires_at) VALUES (:id, :purpose, :hash, :expires_at)'
        );
        $insert->execute(['id' => $accountId, 'purpose' => $purpose,
            'hash' => hash('sha256', $token), 'expires_at' => $expire]);
        return $token;
    }
}
