<?php
require dirname(__DIR__, 2) . '/includes/app.php';
require dirname(__DIR__, 2) . '/includes/staff-documents.php';
require_roles(['admin']);

$id = filter_var($_GET['id'] ?? null, FILTER_VALIDATE_INT);
if (!$id || $id < 1) {
    http_response_code(400);
    exit('Invalid staff member.');
}
$staff = one("SELECT id,nid_file_path FROM users WHERE id=? AND role IN ('staff','gardener')", 'i', [$id]);
$path = $staff ? nid_disk_path($staff['nid_file_path']) : null;
if (!$path || !is_file($path)) {
    http_response_code(404);
    exit('NID document not found.');
}
header('Content-Type: application/pdf');
header('Content-Disposition: attachment; filename="nid-staff-' . $id . '.pdf"');
header('Content-Length: ' . filesize($path));
readfile($path);
