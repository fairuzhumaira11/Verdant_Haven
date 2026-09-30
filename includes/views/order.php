<?php
require dirname(__DIR__) . '/app.php';
require dirname(__DIR__) . '/layout.php';
require dirname(__DIR__) . '/history.php';
$user = require_roles(['customer']);
$order = one('SELECT * FROM orders WHERE id=? AND customer_id=?', 'ii', [(int)query_input('id', '0', 20), $user['id']]);
if (!$order) {
    http_response_code(404);
    page_start('Order not found');
    heading('Order not found');
    page_end();
    exit;
}
page_start($confirmation ? 'Order confirmed' : 'Track order');
heading($confirmation ? 'Thank you! Your order is confirmed.' : 'Track your order', $confirmation ? 'Your plants have been added to your dashboard with recurring watering reminders.' : 'Current status: ' . ucfirst($order['status']));
order_cards([$order], 'customer', 'pages/customer/orders.php');
if (!$confirmation): ?>
    <div class="panel">
        <h2>Delivery progress</h2>
        <div class="row"><?php foreach (['placed', 'processing', 'dispatched', 'delivered'] as $step): ?><span class="badge <?= $order['status'] === $step ? 'alert' : '' ?>"><?= ucfirst($step) ?><?= $order['status'] === $step ? ' · current' : '' ?></span><?php endforeach; ?></div><?php if ($order['status'] === 'cancelled'): ?><p class="space">This order was cancelled.</p><?php endif; ?>
    </div>
<?php endif;
page_end(); ?>