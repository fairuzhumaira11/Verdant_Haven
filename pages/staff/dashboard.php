<?php
require dirname(__DIR__, 2) . '/includes/app.php';
require dirname(__DIR__, 2) . '/includes/layout.php';
require dirname(__DIR__, 2) . '/includes/history.php';
$user = require_roles(['staff']);
$view = query_input('view', 'overview', 20);
$return = 'pages/staff/dashboard.php?view=' . rawurlencode($view);
page_start('Nursery staff');
if ($view === 'orders') {
    heading('Plant orders', 'Process orders, dispatch plants, and confirm delivery.');
    order_cards(rows('SELECT o.*,u.name customer_name FROM orders o JOIN users u ON u.id=o.customer_id ORDER BY o.id DESC'), 'staff', $return);
} elseif ($view === 'services') {
    heading('Garden services', 'Assign gardeners to customer service requests.');
    gardener_schedule();
    service_cards(service_rows($user), 'staff', $return);
} elseif ($view === 'stock') {
    heading('Stock management', 'Update the available quantity of each nursery plant.');
?><section class="panel table-wrap">
        <table>
            <thead>
                <tr>
                    <th>Plant</th>
                    <th>Category</th>
                    <th>Price</th>
                    <th>Update stock</th>
                </tr>
            </thead>
            <tbody><?php foreach (rows('SELECT * FROM plants ORDER BY name') as $plant): ?><tr>
                        <td><?= h($plant['name']) ?></td>
                        <td><?= h($plant['category']) ?></td>
                        <td><?= money($plant['price']) ?></td>
                        <td><?php post_form('stock', $return, 'row'); ?><input type="hidden" name="id" value="<?= (int)$plant['id'] ?>"><label>Available quantity<input type="number" name="stock" min="0" max="1000000" value="<?= (int)$plant['stock'] ?>" required></label><button class="btn small">Save stock</button></form>
                        </td>
                    </tr><?php endforeach; ?></tbody>
        </table>
    </section><?php
            } else {
                heading('Welcome, ' . $user['name'], 'Keep the nursery running smoothly.');
                $orders = one("SELECT COUNT(*) count FROM orders WHERE status IN ('placed','processing','dispatched')");
                $services = one("SELECT COUNT(*) count FROM services WHERE status='requested'");
                $low_stock = one('SELECT COUNT(*) count FROM plants WHERE active=1 AND stock<5');
                ?><div class="stats">
        <div class="card stat"><small>Open orders</small><b><?= (int)$orders['count'] ?></b></div>
        <div class="card stat"><small>Visits to assign</small><b><?= (int)$services['count'] ?></b></div>
        <div class="card stat"><small>Low stock plants</small><b><?= (int)$low_stock['count'] ?></b></div>
    </div>
    <div class="grid">
        <section class="panel">
            <h2>Process plant orders</h2>
            <p class="muted">Review customer orders and update delivery status.</p><a class="btn" href="<?= h(url('pages/staff/dashboard.php?view=orders')) ?>">Manage orders</a>
        </section>
        <section class="panel">
            <h2>Assign garden visits</h2>
            <p class="muted">Find a gardener for each customer service request.</p><a class="btn" href="<?= h(url('pages/staff/dashboard.php?view=services')) ?>">Manage services</a>
        </section>
        <section class="panel">
            <h2>Keep stock current</h2>
            <p class="muted">Update available plant quantities.</p><a class="btn secondary" href="<?= h(url('pages/staff/dashboard.php?view=stock')) ?>">Update stock</a>
        </section>
    </div><?php
            }
            page_end();
