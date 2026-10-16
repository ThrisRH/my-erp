<?php

declare(strict_types=1);

namespace Application\Helper;

use Application\Model\AccountModel;

class Access
{
    private AccountModel $accounts;

    public function __construct(AccountModel $accounts)
    {
        $this->accounts = $accounts;
    }

    public static function startSession(): void
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            ini_set('session.use_strict_mode', '1');
            ini_set('session.use_only_cookies', '1');
            session_name('my_erp_session');
            session_set_cookie_params([
                'httponly' => true,
                'secure' => getenv('APP_COOKIE_SECURE') === '1'
                    || (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off'),
                'samesite' => 'Lax',
                'path' => '/',
            ]);
            session_start();
        }
    }

    public function account(): ?array
    {
        self::startSession();
        $id = filter_var($_SESSION['userStorage']['id'] ?? null, FILTER_VALIDATE_INT);
        if ($id === false || $id === null || $id < 1) {
            return null;
        }
        $account = $this->accounts->findById($id);
        if ($account === null || $account['status'] !== 'active' || $account['employment_status'] !== 'active') {
            unset($_SESSION['userStorage']);
            return null;
        }
        return $account;
    }

    public function allows(string $permission, ?array $account = null): bool
    {
        $account ??= $this->account();
        if ($account === null) {
            return false;
        }
        // The stored ID is verified against the database, never against a client-provided admin flag.
        if ((int) $account['id'] === $this->accounts->adminId()) {
            return true;
        }
        $permissions = json_decode((string) ($account['permissions'] ?? ''), true);
        return is_array($permissions) && in_array($permission, $permissions, true);
    }

    public static function csrfToken(): string
    {
        self::startSession();
        if (!isset($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }

    public static function validCsrf($token): bool
    {
        return is_string($token) && hash_equals(self::csrfToken(), $token);
    }
}
