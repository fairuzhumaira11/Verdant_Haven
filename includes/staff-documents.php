<?php
function nid_disk_path($stored) {
    if (!is_string($stored) || !preg_match('~^storage/nids/[a-f0-9]{32}\\.pdf$~D', $stored)) return null;
    return dirname(__DIR__) . '/' . $stored;
}

function nid_upload($key) {
    if (empty($_FILES[$key])) return null;
    $file = $_FILES[$key];
    if (!isset($file['error'], $file['size'], $file['tmp_name']) || !is_int($file['error']) || !is_string($file['tmp_name'])) throw new Exception('Invalid NID upload.');
    if ($file['error'] === UPLOAD_ERR_NO_FILE) return null;
    if ($file['error'] !== UPLOAD_ERR_OK || $file['size'] > 5 * 1024 * 1024) throw new Exception('Upload a NID PDF smaller than 5 MB.');
    if (!is_uploaded_file($file['tmp_name'])) throw new Exception('Invalid NID upload.');
    $info = finfo_open(FILEINFO_MIME_TYPE);
    $mime = finfo_file($info, $file['tmp_name']);
    finfo_close($info);
    $handle = fopen($file['tmp_name'], 'rb');
    $signature = $handle ? fread($handle, 5) : '';
    if ($handle) fclose($handle);
    if ($mime !== 'application/pdf' || $signature !== '%PDF-') throw new Exception('Use a PDF document for the NID.');
    $directory = dirname(__DIR__) . '/storage/nids';
    if (!is_dir($directory) && !@mkdir($directory, 0700, true) && !is_dir($directory)) throw new Exception('Could not create the NID folder.');
    $path = 'storage/nids/' . bin2hex(random_bytes(16)) . '.pdf';
    if (!@move_uploaded_file($file['tmp_name'], dirname(__DIR__) . '/' . $path)) throw new Exception('Could not save the NID PDF.');
    return $path;
}
