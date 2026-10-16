<?php

declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    exit(1);
}

$username = trim((string) getenv('ACCOUNT_USERNAME'));
$password = (string) getenv('ACCOUNT_PASSWORD');
$employeeId = filter_var(getenv('ACCOUNT_EMPLOYEE_ID') ?: null, FILTER_VALIDATE_INT);
$permissions = json_decode((string) getenv('ACCOUNT_PERMISSIONS'), true);
$allowed = [
    'app.view', 'finance.view', 'finance.transaction.create', 'finance.transaction.update',
    'finance.wallet.create', 'finance.category.create', 'finance.budget.manage',
];
if ($username === '' || strlen($password) < 12 || $employeeId === false || $employeeId === null || $employeeId < 1
    || !is_array($permissions) || array_values($permissions) !== $permissions
    || count(array_diff($permissions, $allowed)) > 0) {
    fwrite(STDERR, "Set ACCOUNT_USERNAME, ACCOUNT_PASSWORD (12+ characters), ACCOUNT_EMPLOYEE_ID, and ACCOUNT_PERMISSIONS as a JSON array of known permission keys.\n");
    exit(1);
}

require dirname(__DIR__) . '/vendor/autoload.php';
$database = require dirname(__DIR__) . '/config/autoload/global.php';
$db = $database['personal_finance']['database'];
$connection = new PDO(sprintf('mysql:host=%s;port=%d;dbname=%s;charset=utf8mb4',
    $db['host'], $db['port'], $db['database']), $db['username'], $db['password'], [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);
$statement = $connection->prepare('INSERT INTO accounts (employee_id, username, password_hash, permissions) VALUES (:employee_id, :username, :password_hash, :permissions)');
$statement->execute([
    'employee_id' => $employeeId,
    'username' => $username,
    'password_hash' => password_hash($password, PASSWORD_DEFAULT),
    'permissions' => json_encode($permissions, JSON_THROW_ON_ERROR),
]);
fwrite(STDOUT, "Employee account created.\n");
