<?php
// Local development router for php -S. Apache uses .htaccess.
$path = str_replace('\\', '/', rawurldecode(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH)));
$private = '~^/(config|database|includes|storage|tools)(/|$)|^/(db_connect|api/config)\\.php$|^/backup-before-php-refactor\\.zip$~';
if (preg_match($private, $path) || str_contains($path, '..')) {
    http_response_code(403);
    exit('Private file.');
}

$legacy = require dirname(__DIR__) . '/config/legacy-routes.php';
$old_path = ltrim($path, '/');
$target = $legacy[$old_path] ?? null;
if (str_starts_with($old_path, 'img/')) {
    $target = 'assets/images/' . substr($old_path, 4);
    $target = implode('/', array_map('rawurlencode', explode('/', $target)));
}
if ($target) {
    $query = parse_url($_SERVER['REQUEST_URI'], PHP_URL_QUERY);
    // Preserve a POST body when an old form submits to action.php.
    $status = $old_path === 'action.php' ? 307 : 302;
    header('Location: /' . $target . ($query ? '?' . $query : ''), true, $status);
    exit;
}
if ($path === '/') {
    require dirname(__DIR__) . '/index.php';
    return true;
}
return false;
