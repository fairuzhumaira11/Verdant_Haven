<?php
require dirname(__DIR__, 2) . '/includes/app.php';
require dirname(__DIR__, 2) . '/includes/layout.php';
require dirname(__DIR__, 2) . '/includes/reminders.php';
require dirname(__DIR__, 2) . '/includes/history.php';
$user = require_roles(['customer']);
$plants = customer_reminders($user['id']);
$due_count = 0;
foreach ($plants as $plant) if ($plant['overdue']) $due_count++;
$order_count = one('SELECT COUNT(*) count FROM orders WHERE customer_id=?', 'i', [$user['id']]);
$visit_count = one("SELECT COUNT(*) count FROM services WHERE customer_id=? AND status IN ('requested','assigned')", 'i', [$user['id']]);
$recent_orders = rows('SELECT id,status,created_at,total FROM orders WHERE customer_id=? ORDER BY id DESC LIMIT 3', 'i', [$user['id']]);
$recent_visits = rows('SELECT s.*,g.name gardener_name,r.stars FROM services s LEFT JOIN users g ON g.id=s.gardener_id LEFT JOIN ratings r ON r.service_id=s.id WHERE s.customer_id=? ORDER BY s.id DESC LIMIT 3', 'i', [$user['id']]);
page_start('My dashboard', true);
heading('Hello, ' . $user['name'], 'Your plants, orders, and garden care in one place.');
?><div class="stats">
    <div class="card stat"><small>Watering due</small><b><?= $due_count ?></b></div>
    <div class="card stat"><small>Purchased plant groups</small><b><?= count($plants) ?></b></div>
    <div class="card stat"><small>Orders</small><b><?= (int)$order_count['count'] ?></b></div>
    <div class="card stat"><small>Upcoming services</small><b><?= (int)$visit_count['count'] ?></b></div>
</div>
<div class="overview-grid">
    <section class="panel" id="recent-orders">
        <div class="row spread"><h2>Recent plant orders</h2><a href="<?= h(url('pages/customer/orders.php')) ?>">View all</a></div>
        <?php if (!$recent_orders): ?><p class="muted">Your plant orders will appear here.</p><?php endif;
        foreach ($recent_orders as $order): ?><div class="overview-item">
            <div class="row spread"><strong>Order #<?= (int)$order['id'] ?></strong><span class="badge status-<?= h($order['status']) ?>"><?= h(ucfirst($order['status'])) ?></span></div>
            <small><?= h(date('d M Y, h:i A', strtotime($order['created_at']))) ?> · <?= money($order['total']) ?></small>
            <?php if ($order['status'] === 'dispatched'): ?><p class="due-label">Your order is on the way.</p><?php endif; ?>
            <a href="<?= h(url('pages/customer/track-order.php?id=' . $order['id'])) ?>">Track order</a>
        </div><?php endforeach; ?>
    </section>
    <section class="panel" id="garden-visits">
        <div class="row spread"><h2>Garden visits</h2><a href="<?= h(url('pages/customer/book-service.php')) ?>">Book a visit</a></div>
        <?php if (!$recent_visits): ?><p class="muted">Your booked visits will appear here.</p><?php endif;
        foreach ($recent_visits as $visit): ?><div class="overview-item">
            <div class="row spread"><strong><?= h(ucfirst(strtolower($visit['type']))) ?> visit</strong><span class="badge"><?= h(ucfirst($visit['status'])) ?></span></div>
            <small><?= h(date('d M Y, h:i A', strtotime($visit['scheduled_at']))) ?> · <?= h($visit['address']) ?></small>
            <p><?= h($visit['gardener_name'] ?: 'Gardener not assigned yet') ?></p>
            <?php if ($visit['status'] === 'completed' && !$visit['stars']): rate_popup($visit, 'pages/customer/dashboard.php');
            elseif ($visit['stars']): ?><small>Rated <?= (int)$visit['stars'] ?>/5</small><?php endif; ?>
        </div><?php endforeach; ?>
        <a class="btn secondary small space" href="<?= h(url('pages/customer/orders.php?tab=services')) ?>">All garden visits</a>
    </section>
</div>
<section id="watering">
    <div class="page-heading">
        <div>
            <h2>Your plants & watering reminders</h2>
            <p>This dashboard checks every minute while open. Mark a plant as watered to start its next interval.</p>
        </div><a class="btn secondary" href="<?= h(url('pages/customer/dashboard.php#watering')) ?>">Check now</a>
    </div>
    <?php if (!$plants): ?><div class="panel empty">
            <p>Your purchased plants and care guides will appear here.</p><a class="btn" href="<?= h(url('pages/customer/catalog.php')) ?>">Find your first plant</a>
        </div><?php else: ?><div class="watering-list"><?php foreach ($plants as $plant): ?><article class="card watering-card <?= $plant['overdue'] ? 'due' : '' ?>">
                    <div class="row"><img src="<?= h(url($plant['image_path'] ?: 'assets/images/main.avif')) ?>" alt="">
                        <div>
                            <h3><?= h($plant['name']) ?></h3><small><?= (int)$plant['quantity'] ?> plant<?= $plant['quantity'] > 1 ? 's' : '' ?> · Every <?= (int)$plant['watering_hours'] ?> hours</small>
                        </div>
                    </div>
                    <p class="<?= $plant['overdue'] ? 'due-label' : 'muted' ?>"><?= $plant['overdue'] ? 'Watering reminder: due now' : 'Next watering' ?><br><?= h(date('d M Y, h:i A', strtotime($plant['due_at']))) ?></p><small>Last watered: <?= $plant['last_watered_at'] ? h(date('d M Y, h:i A', strtotime($plant['last_watered_at']))) : 'Not recorded yet' ?></small>
                    <details class="care-details"><summary class="btn secondary small">View more</summary><p class="care-copy space"><?= h($plant['care_instructions']) ?></p><?php if ($plant['care_file_path']): ?><a href="<?= h(url($plant['care_file_path'])) ?>" download>Download care guide (PDF)</a><?php endif; ?></details>
                    <?php post_form('watered', 'pages/customer/dashboard.php'); ?><input type="hidden" name="order_item_id" value="<?= (int)$plant['order_item_id'] ?>"><input type="hidden" name="schedule_version" value="<?= h($plant['last_watered_at'] ?? 'never') ?>"><button class="btn small">I watered this plant</button></form>
                </article><?php endforeach; ?></div><?php endif; ?>
</section>
<div class="grid two space">
    <section class="panel">
        <h2>Need a little garden help?</h2>
        <p class="muted">Our gardeners can help with watering setup, trimming, repotting and maintenance.</p><a class="btn" href="<?= h(url('pages/customer/book-service.php')) ?>">Book a service</a>
    </section>
    <section class="panel">
        <h2>Follow your orders</h2>
        <p class="muted">View order progress, service notes and photos, and rate completed garden visits.</p><a class="btn secondary" href="<?= h(url('pages/customer/orders.php')) ?>">Orders & services</a>
    </section>
</div>
<?php page_end(); ?>