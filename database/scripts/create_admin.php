<?php
if (PHP_SAPI !== 'cli') { http_response_code(403); exit('CLI only'); }
if (count($argv) !== 5) {
    exit("Usage: php database/scripts/create_admin.php \"Full Name\" email@example.com phone password\n");
}
$name = $argv[1]; $email = strtolower($argv[2]); $phone = $argv[3]; $password = $argv[4];
if ($name === '' || mb_strlen($name, 'UTF-8') > 120 || !filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 190 || !preg_match('/^01[3-9][0-9]{8}$/', $phone) || mb_strlen($password, 'UTF-8') < 12 || strlen($password) > 72 || str_contains($password, "\0")) {
    exit("Use a name, valid email, 11-digit mobile number and password of at least 12 characters and no more than 72 bytes.\n");
}
require dirname(__DIR__, 2) . '/includes/db_connect.php';
try {
    $existing = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) count FROM users WHERE role='admin'"));
    if ($existing['count'] > 0) {
        fwrite(STDERR, "An administrator account already exists.\n");
        exit(1);
    }
    $hash = password_hash($password, PASSWORD_DEFAULT);
    $stmt = mysqli_prepare($conn, "INSERT INTO users(name,email,phone,password_hash,role,status) VALUES(?,?,?,?, 'admin','Active Desk')");
    mysqli_stmt_bind_param($stmt, 'ssss', $name, $email, $phone, $hash);
    mysqli_stmt_execute($stmt);
    echo "Admin created.\n";
} catch (mysqli_sql_exception $error) {
    fwrite(STDERR, "Could not create admin: " . $error->getMessage() . "\n");
    exit(1);
}
