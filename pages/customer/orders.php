<?php
require dirname(__DIR__, 2) . '/includes/app.php';
require dirname(__DIR__, 2) . '/includes/layout.php';
require dirname(__DIR__, 2) . '/includes/history.php';
$user = require_roles(['customer']);
$tab = ($_GET['tab'] ?? '') === 'services' ? 'services' : 'orders';
$return = 'pages/customer/orders.php?tab=' . $tab;
page_start('Orders & services');
heading('Your orders & garden visits', 'Track progress, view completion photos, and rate your gardener.');
?><div class="row space" style="margin-bottom:24px"><a class="btn <?= $tab === 'orders' ? '' : 'secondary' ?>" href="<?= h(url('pages/customer/orders.php')) ?>">Plant orders</a><a class="btn <?= $tab === 'services' ? '' : 'secondary' ?>" href="<?= h(url('pages/customer/orders.php?tab=services')) ?>">Garden services</a></div>
<?php if ($tab === 'services') service_cards(service_rows($user), 'customer', $return);
else order_cards(rows('SELECT * FROM orders WHERE customer_id=? ORDER BY id DESC', 'i', [$user['id']]), 'customer', $return);
page_end(); ?>