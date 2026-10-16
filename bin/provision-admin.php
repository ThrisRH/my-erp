<?php

declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    exit(1);
}

$username = trim((string) getenv('ADMIN_USERNAME'));
$password = (string) getenv('ADMIN_PASSWORD');
$email = trim((string) getenv('ADMIN_EMAIL'));
$employeeId = filter_var(getenv('ADMIN_EMPLOYEE_ID') ?: null, FILTER_VALIDATE_INT);
if ($username === '' || strlen($password) < 12
    || (($employeeId === false || $employeeId === null || $employeeId < 1)
        && !filter_var($email, FILTER_VALIDATE_EMAIL))) {
    fwrite(STDERR, "Set ADMIN_USERNAME, ADMIN_PASSWORD (12+ characters), and either ADMIN_EMPLOYEE_ID or ADMIN_EMAIL.\n");
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

$connection->beginTransaction();
try {
    if ($connection->query('SELECT id FROM accounts WHERE is_admin = 1 LIMIT 1')->fetchColumn() !== false) {
        throw new RuntimeException('An administrator already exists. No account was changed.');
    }
    if ($employeeId === false || $employeeId === null || $employeeId < 1) {
        $statement = $connection->prepare('INSERT INTO employees (employee_code, first_name, last_name, work_email) VALUES (:code, :first_name, :last_name, :email)');
        $statement->execute([
            'code' => 'ADMIN-' . bin2hex(random_bytes(4)),
            'first_name' => 'System',
            'last_name' => 'Administrator',
            'email' => $email,
        ]);
        $employeeId = (int) $connection->lastInsertId();
    }
    $statement = $connection->prepare('INSERT INTO accounts (employee_id, username, password_hash, permissions, is_admin) VALUES (:employee_id, :username, :password_hash, JSON_ARRAY(), 1)');
    $statement->execute([
        'employee_id' => $employeeId,
        'username' => $username,
        'password_hash' => password_hash($password, PASSWORD_DEFAULT),
    ]);
    $connection->commit();
    fwrite(STDOUT, "Administrator account created.\n");
} catch (Throwable $error) {
    $connection->rollBack();
    fwrite(STDERR, $error->getMessage() . "\n");
    exit(1);
}
