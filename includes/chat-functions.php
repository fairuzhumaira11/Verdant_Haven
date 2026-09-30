<?php
function chat_has_read_status() {
    static $ready = null;
    if ($ready === null) $ready = (bool)one("SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='messages' AND COLUMN_NAME='read_at'");
    return $ready;
}
function chat_unread($user) {
    if (!chat_has_read_status()) return ['total' => 0, 'contacts' => []];
    $roles = $user['role'] === 'staff' ? "u.role IN ('customer','gardener')" : "u.role='staff'";
    $counts = rows("SELECT m.sender_id,COUNT(*) total FROM messages m JOIN users u ON u.id=m.sender_id WHERE m.recipient_id=? AND m.read_at IS NULL AND u.status!='Inactive' AND $roles GROUP BY m.sender_id", 'i', [$user['id']]);
    $total = 0;
    foreach ($counts as $count) $total += (int)$count['total'];
    return ['total' => $total, 'contacts' => $counts];
}
function chat_mark_read($user_id, $contact_id) {
    if (!chat_has_read_status()) return;
    run('UPDATE messages SET read_at=NOW() WHERE recipient_id=? AND sender_id=? AND read_at IS NULL', 'ii', [$user_id, $contact_id]);
}
function chat_contacts($user) {
    if ($user['role'] === 'staff') return rows("SELECT id,name,role FROM users WHERE role IN ('customer','gardener') AND status!='Inactive' ORDER BY name");
    return rows("SELECT id,name,role FROM users WHERE role='staff' AND status!='Inactive' ORDER BY name");
}
function chat_contact($user, $contact_id) {
    $contact = one("SELECT id,name,role FROM users WHERE id=? AND status!='Inactive'", 'i', [$contact_id]);
    $allowed = ['customer'=>['staff'],'staff'=>['customer','gardener'],'gardener'=>['staff']];
    if (!$contact || !in_array($contact['role'], $allowed[$user['role']] ?? [])) throw new Exception('This chat contact is unavailable.');
    return $contact;
}
function chat_send($user, $contact_id, $message) {
    global $conn;
    chat_contact($user,$contact_id);
    $message = trim($message);
    if ($message === '' || mb_strlen($message, 'UTF-8') > 2000) throw new Exception('Enter a message of 1 to 2000 characters.');
    run('INSERT INTO messages(sender_id,recipient_id,body) VALUES(?,?,?)', 'iis', [$user['id'],$contact_id,$message]);
    return one('SELECT id,sender_id,recipient_id,body,created_at FROM messages WHERE id=?', 'i', [mysqli_insert_id($conn)]);
}
