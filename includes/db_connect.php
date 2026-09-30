<?php
// XAMPP database connection. Change config/database.php if your credentials differ.
$settings = require dirname(__DIR__) . '/config/database.php';
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
try {
    $conn = mysqli_connect($settings['host'], $settings['user'], $settings['password'], $settings['database'], (int)$settings['port']);
    mysqli_set_charset($conn, 'utf8mb4');
    mysqli_query($conn, "SET time_zone = '+06:00'");
} catch (mysqli_sql_exception $error) {
    error_log($error->getMessage());
    if (function_exists('json_response')) json_response(['error' => 'The database is unavailable. Start MySQL in XAMPP.'], 503);
    http_response_code(503);
    exit('Cannot connect to MySQL. Start MySQL in XAMPP, import database/schema.sql, and check config/database.php.');
}
return $conn;
