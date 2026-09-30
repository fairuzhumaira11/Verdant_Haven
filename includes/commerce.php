<?php
require_once __DIR__ . '/payments.php';
require_once __DIR__ . '/reminders.php';

function cart_items($customer_id, $lock = false) {
    $sql = 'SELECT p.*,c.quantity FROM cart_items c JOIN plants p ON p.id=c.plant_id WHERE c.customer_id=? ORDER BY p.id';
    if ($lock) $sql .= ' FOR UPDATE';
    return rows($sql, 'i', [$customer_id]);
}
function cart_totals($items) {
    $subtotal = 0;
    foreach ($items as $item) $subtotal += (float)$item['price'] * (int)$item['quantity'];
    $delivery = count($items) > 0 ? 100 : 0;
    return ['subtotal' => round($subtotal, 2), 'delivery' => $delivery, 'total' => round($subtotal + $delivery, 2)];
}
function cart_hash($items) {
    $text = '';
    foreach ($items as $item) $text .= $item['id'] . ':' . $item['quantity'] . ':' . $item['price'] . ';';
    return hash('sha256', $text);
}
function check_cart($items) {
    if (!$items || count($items) > 30) throw new Exception('Your cart is empty or has too many different plants.');
    foreach ($items as $item) {
        if (!$item['active'] || $item['quantity'] < 1 || $item['quantity'] > $item['stock']) throw new Exception($item['name'] . ' is unavailable in that quantity. Update your cart.');
    }
}
function place_order($customer_id, $items, $address, $method, $checkout_key) {
    global $conn;
    // Called inside a transaction after the cart and stock rows are locked.
    check_cart($items);
    $totals = cart_totals($items);
    run('INSERT INTO orders(customer_id,payment_method,payment_status,address,subtotal,delivery_fee,total,checkout_key) VALUES(?,?,?,?,?,?,?,?)',
        'isssddds', [$customer_id, $method, $method === 'cod' ? 'pending_cod' : 'dummy_paid', $address, $totals['subtotal'], $totals['delivery'], $totals['total'], $checkout_key]);
    $order_id = mysqli_insert_id($conn);
    foreach ($items as $item) {
        run('INSERT INTO order_items(order_id,plant_id,plant_name,unit_price,quantity) VALUES(?,?,?,?,?)', 'iisdi', [$order_id, $item['id'], $item['name'], $item['price'], $item['quantity']]);
        $order_item_id = mysqli_insert_id($conn);
        run('UPDATE plants SET stock=stock-? WHERE id=?', 'ii', [$item['quantity'], $item['id']]);
        $due = watering_due(date('Y-m-d H:i:s'), $item['watering_hours']);
        run('INSERT INTO reminders(customer_id,plant_id,order_item_id,due_at) VALUES(?,?,?,?)', 'iiis', [$customer_id, $item['id'], $order_item_id, $due]);
    }
    run('DELETE FROM cart_items WHERE customer_id=?', 'i', [$customer_id]);
    return $order_id;
}
function start_payment($customer_id, $provider, $address) {
    global $conn;
    mysqli_begin_transaction($conn);
    try {
        $items = cart_items($customer_id, true);
        check_cart($items);
        $totals = cart_totals($items);
        $payment = dummy_payment_create($customer_id, $provider, $totals['total']);
        run('UPDATE payments SET cart_hash=?,address=? WHERE payment_id=?', 'sss', [cart_hash($items), $address, $payment['payment_id']]);
        mysqli_commit($conn);
        return $payment;
    } catch (Throwable $error) {
        mysqli_rollback($conn);
        throw $error;
    }
}
function finish_payment($customer_id, $payment_id, $outcome, $wallet) {
    global $conn;
    mysqli_begin_transaction($conn);
    try {
        $payment = one('SELECT * FROM payments WHERE payment_id=? AND customer_id=? FOR UPDATE', 'si', [$payment_id, $customer_id]);
        if (!$payment) throw new Exception('Payment not found.');
        if ($payment['status'] !== 'created') {
            mysqli_commit($conn);
            return $payment;
        }
        $items = [];
        if ($outcome === 'success') {
            $items = cart_items($customer_id, true);
            check_cart($items);
            $totals = cart_totals($items);
            if ($payment['cart_hash'] !== cart_hash($items) || (float)$payment['amount'] !== $totals['total']) throw new Exception('Your cart or its prices changed. Start a new payment.');
        }
        $payment = dummy_payment_execute($customer_id, $payment_id, $outcome, $wallet);
        if ($payment['status'] === 'success') {
            $order_id = place_order($customer_id, $items, $payment['address'], $payment['provider'], hash('sha256', $payment_id));
            run('UPDATE payments SET order_id=? WHERE payment_id=?', 'is', [$order_id, $payment_id]);
            $payment['order_id'] = $order_id;
        }
        mysqli_commit($conn);
        return $payment;
    } catch (Throwable $error) {
        mysqli_rollback($conn);
        throw $error;
    }
}
