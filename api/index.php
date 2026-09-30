<?php
// Chat and dummy wallet endpoints.
// Page forms submit to actions/handle.php.
require __DIR__ . '/bootstrap.php';
require dirname(__DIR__) . '/includes/chat-functions.php';
require dirname(__DIR__) . '/includes/commerce.php';
try {
    $method = $_SERVER['REQUEST_METHOD'];
    $action = query_input('action', '', 40);
    if ($method === 'POST') {
        if (str_contains($_SERVER['CONTENT_TYPE'] ?? '', 'application/json')) {
            $body = json_decode(file_get_contents('php://input'), true);
            if (!is_array($body)) throw new Exception('Invalid JSON.');
            $_POST = $body;
        }
    }
    $user = current_user();
    if (!$user) json_response(['error' => 'Please log in.'], 401);
    if ($user['status'] === 'Inactive') json_response(['error' => 'Account inactive.'], 403);
    if ($method === 'POST') check_csrf();
    if ($action === 'me' && $method === 'GET') json_response(['user' => $user, 'csrf' => $_SESSION['csrf']]);
    if (in_array($action, ['contacts', 'messages', 'message_send', 'unread'])) {
        if (!in_array($user['role'], ['customer', 'staff', 'gardener'])) json_response(['error' => 'Access denied.'], 403);
        if ($method !== ($action === 'message_send' ? 'POST' : 'GET')) json_response(['error' => 'Method not allowed.'], 405);
        if ($action === 'contacts') json_response(['contacts' => chat_contacts($user)]);
        if ($action === 'unread') json_response(chat_unread($user));
        $contact_id = $method === 'POST' ? number_input('contactId', 1, PHP_INT_MAX) : (int)query_input('contactId', '0', 20);
        chat_contact($user, $contact_id);
        if ($action === 'messages' && $method === 'GET') {
            chat_mark_read($user['id'], $contact_id);
            $after = max(0, (int)query_input('after', '0', 20));
            $messages = rows('SELECT id,sender_id,recipient_id,body,created_at FROM messages WHERE id>? AND ((sender_id=? AND recipient_id=?) OR (sender_id=? AND recipient_id=?)) ORDER BY id LIMIT 100', 'iiiii', [$after, $user['id'], $contact_id, $contact_id, $user['id']]);
            json_response(['messages' => $messages]);
        }
        if ($action === 'message_send' && $method === 'POST') json_response(['message' => chat_send($user, $contact_id, input('message', 2000, true))]);
        json_response(['error' => 'Method not allowed.'], 405);
    }
    if (in_array($action, ['payment_create', 'payment_execute', 'payment_status'])) {
        if ($user['role'] !== 'customer') json_response(['error' => 'Access denied.'], 403);
        if ($action === 'payment_create' && $method === 'POST') json_response(['payment' => start_payment($user['id'], input('provider', 10, true), input('address', 500, true))]);
        if ($action === 'payment_execute' && $method === 'POST') json_response(['payment' => finish_payment($user['id'], input('payment_id', 80, true), input('outcome', 10, true), input('wallet', 11))]);
        if ($action === 'payment_status' && $method === 'GET') {
            $payment = one('SELECT payment_id,provider,amount,status,transaction_id,order_id,created_at FROM payments WHERE payment_id=? AND customer_id=?', 'si', [query_input('payment_id', '', 80), $user['id']]);
            if (!$payment) json_response(['error' => 'Payment not found.'], 404);
            $payment['expired'] = $payment['status'] === 'created' && payment_expired($payment);
            json_response(['payment' => $payment]);
        }
        json_response(['error' => 'Method not allowed.'], 405);
    }
    json_response(['error' => 'Endpoint not found.'], 404);
} catch (Throwable $error) {
    if ($error instanceof mysqli_sql_exception || !($error instanceof Exception)) {
        error_log($error->getMessage());
        json_response(['error' => 'Could not complete this request. Please try again.'], 500);
    }
    json_response(['error' => $error->getMessage()], 400);
}
