<?php
// Shared helpers for pages and form submissions.
date_default_timezone_set('Asia/Dhaka');
session_set_cookie_params(['httponly' => true, 'samesite' => 'Lax', 'secure' => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off']);
session_start();
header('X-Content-Type-Options: nosniff');
header('Cache-Control: no-store');
require_once __DIR__ . '/db_connect.php';

// Resolve the project URL from the current entry file, regardless of page depth.
$projectDirectory = str_replace('\\', '/', dirname(__DIR__));
$scriptFile = str_replace('\\', '/', $_SERVER['SCRIPT_FILENAME'] ?? $projectDirectory . '/index.php');
$relativeScript = substr($scriptFile, strlen($projectDirectory) + 1);
$scriptUrl = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '/index.php');
$base = substr($scriptUrl, 0, max(0, strlen($scriptUrl) - strlen($relativeScript)));
define('BASE_PATH', rtrim($base, '/'));
if (empty($_SESSION['csrf'])) {
    $_SESSION['csrf'] = bin2hex(random_bytes(24));
}

function url($path = '') {
    $path = ltrim($path, '/');
    // Existing database records may still contain the old image folder name.
    if (str_starts_with($path, 'img/')) $path = 'assets/images/' . substr($path, 4);
    return BASE_PATH . '/' . $path;
}
function h($text) {
    return htmlspecialchars((string)$text, ENT_QUOTES, 'UTF-8');
}
function money($amount) {
    return 'BDT ' . number_format((float)$amount, 2);
}
// Use explicit parameter types: i = integer, d = number, s = text.
function run($sql, $types = '', $values = []) {
    global $conn;
    $stmt = mysqli_prepare($conn, $sql);
    if ($types !== '') {
        mysqli_stmt_bind_param($stmt, $types, ...$values);
    }
    mysqli_stmt_execute($stmt);
    return $stmt;
}
function rows($sql, $types = '', $values = []) {
    $stmt = run($sql, $types, $values);
    $result = mysqli_stmt_get_result($stmt);
    $data = mysqli_fetch_all($result, MYSQLI_ASSOC);
    mysqli_stmt_close($stmt);
    return $data;
}
function one($sql, $types = '', $values = []) {
    $data = rows($sql, $types, $values);
    return $data[0] ?? null;
}
function redirect($path) {
    header('Location: ' . url($path), true, 303);
    exit;
}
function home($role) {
    $pages = ['admin' => 'pages/admin/dashboard.php', 'staff' => 'pages/staff/dashboard.php', 'gardener' => 'pages/gardener/dashboard.php', 'customer' => 'pages/customer/dashboard.php'];
    return $pages[$role] ?? 'index.php';
}
function current_user() {
    if (empty($_SESSION['uid'])) return null;
    return one('SELECT id,name,email,phone,role,status FROM users WHERE id=?', 'i', [$_SESSION['uid']]);
}
function require_roles($roles) {
    $user = current_user();
    if (!$user || $user['status'] === 'Inactive') {
        unset($_SESSION['uid']);
        redirect('pages/auth/login.php');
    }
    if (!in_array($user['role'], $roles)) {
        http_response_code(403);
        exit('You do not have permission to open this page.');
    }
    return $user;
}
function csrf_input() {
    echo '<input type="hidden" name="csrf" value="' . h($_SESSION['csrf']) . '">';
}
function check_csrf() {
    $token = $_POST['csrf'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
    if (!is_string($token) || !hash_equals($_SESSION['csrf'], $token)) {
        throw new Exception('Your session expired. Refresh the page and try again.');
    }
}
function flash($message, $kind = 'success') {
    $_SESSION['flash'] = ['message' => $message, 'kind' => $kind];
}
function local_return($path, $fallback = 'index.php') {
    // Only accept a project-relative PHP page, optionally followed by a query string.
    if (!is_string($path) || !preg_match('~^[A-Za-z0-9_/-]+\\.php(?:\\?[^\\r\\n]*)?$~', $path) || str_contains($path, '..') || str_starts_with($path, '/')) return $fallback;
    return $path;
}
function query_input($key, $default = '', $max = 500) {
    $value = $_GET[$key] ?? $default;
    if (!is_string($value)) return $default;
    return trim(mb_substr($value, 0, $max, 'UTF-8'));
}
function password_input($required = true) {
    $password = $_POST['password'] ?? '';
    if (!is_string($password) || str_contains($password, "\0")) throw new Exception('Enter a valid password.');
    if (!$required && $password === '') return '';
    if (mb_strlen($password, 'UTF-8') < 8 || strlen($password) > 72) throw new Exception('Use a password of at least 8 characters and no more than 72 bytes.');
    return $password;
}
function input($key, $max = 500, $required = false) {
    $value = $_POST[$key] ?? '';
    if (!is_string($value)) throw new Exception('Invalid ' . $key . '.');
    $value = trim($value);
    if (mb_strlen($value, 'UTF-8') > $max || ($required && $value === '')) throw new Exception('Please enter a valid ' . str_replace('_', ' ', $key) . '.');
    return $value;
}
function number_input($key, $min, $max) {
    $number = filter_var($_POST[$key] ?? null, FILTER_VALIDATE_INT);
    if ($number === false || $number === null || $number < $min || $number > $max) throw new Exception('Invalid ' . str_replace('_', ' ', $key) . '.');
    return $number;
}
function old($key, $default = '') {
    $value = $_SESSION['old'][$key] ?? $default;
    return is_string($value) ? $value : $default;
}
function photo_upload($key, $folder, $pdf = false) {
    if (empty($_FILES[$key])) return null;
    $file = $_FILES[$key];
    if (!isset($file['error'], $file['size'], $file['tmp_name']) || !is_int($file['error']) || !is_string($file['tmp_name'])) throw new Exception('Invalid upload.');
    if ($file['error'] === UPLOAD_ERR_NO_FILE) return null;
    if ($file['error'] !== UPLOAD_ERR_OK || $file['size'] > 5 * 1024 * 1024) throw new Exception('Upload a file smaller than 5 MB.');
    if (!is_uploaded_file($file['tmp_name'])) throw new Exception('Invalid upload.');
    $info = finfo_open(FILEINFO_MIME_TYPE);
    $mime = finfo_file($info, $file['tmp_name']);
    finfo_close($info);
    $allowed = $pdf ? ['application/pdf' => 'pdf'] : ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
    if (!isset($allowed[$mime])) throw new Exception($pdf ? 'Use a PDF care guide.' : 'Use a JPG, PNG, or WebP photo.');
    $directory = dirname(__DIR__) . '/uploads/' . $folder;
    if (!is_dir($directory) && !@mkdir($directory, 0755, true) && !is_dir($directory)) throw new Exception('Could not create the upload folder.');
    $path = 'uploads/' . $folder . '/' . bin2hex(random_bytes(16)) . '.' . $allowed[$mime];
    if (!@move_uploaded_file($file['tmp_name'], dirname(__DIR__) . '/' . $path)) throw new Exception('Could not save upload.');
    return $path;
}
