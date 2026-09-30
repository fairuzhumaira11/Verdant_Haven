<?php
require_once dirname(__DIR__) . '/includes/app.php';
header('Content-Type: application/json; charset=utf-8');
function json_response($data, $status = 200)
{
    header('Content-Type: application/json; charset=utf-8');
    http_response_code($status);
    echo json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    exit;
}
