<?php
require dirname(__DIR__, 2) . '/includes/app.php';
require dirname(__DIR__, 2) . '/includes/layout.php';
require dirname(__DIR__, 2) . '/includes/history.php';
require dirname(__DIR__, 2) . '/includes/views/admin-forms.php';
require dirname(__DIR__, 2) . '/includes/report-data.php';
$user = require_roles(['admin']);
$view = query_input('view', 'overview', 20);
$return = 'pages/admin/dashboard.php?view=' . rawurlencode($view);
$record = null;
if (in_array($view, ['plant_form', 'staff_form']) && query_input('id', '0', 20) !== '0') {
    $record = $view === 'plant_form' ? one('SELECT * FROM plants WHERE id=?', 'i', [(int)query_input('id', '0', 20)]) : one("SELECT * FROM users WHERE id=? AND role IN ('staff','gardener')", 'i', [(int)query_input('id', '0', 20)]);
    if (!$record) {
        flash('Record not found.', 'error');
        redirect('pages/admin/dashboard.php');
    }
}
page_start('Admin dashboard');
if ($view === 'plant_form') plant_form($record);
elseif ($view === 'staff_form') staff_form($record);
elseif ($view === 'plants') {
    heading('Plant inventory', 'Manage your catalog, pricing, care guides, and watering schedules.');
?><a class="btn" href="<?= h(url('pages/admin/dashboard.php?view=plant_form')) ?>">+ Add plant</a>
    <section class="panel table-wrap space">
        <table>
            <thead>
                <tr>
                    <th>Plant</th>
                    <th>Category</th>
                    <th>Price</th>
                    <th>Stock</th>
                    <th>Water every</th>
                    <th>Visibility</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody><?php foreach (rows('SELECT * FROM plants ORDER BY id DESC') as $plant): ?><tr>
                        <td><?= h($plant['name']) ?></td>
                        <td><?= h($plant['category']) ?></td>
                        <td><?= money($plant['price']) ?></td>
                        <td><?= (int)$plant['stock'] ?></td>
                        <td><?= (int)$plant['watering_hours'] ?> hours</td>
                        <td><?= $plant['active'] ? 'Visible' : 'Hidden' ?></td>
                        <td><a class="btn secondary small" href="<?= h(url('pages/admin/dashboard.php?view=plant_form&id=' . $plant['id'])) ?>">Edit</a></td>
                    </tr><?php endforeach; ?></tbody>
        </table>
    </section>
<?php } elseif ($view === 'staff') {
    heading('Staff & gardeners', 'Create accounts, manage zones, salaries, and duty status.');
?><a class="btn" href="<?= h(url('pages/admin/dashboard.php?view=staff_form')) ?>">+ Add staff member</a>
    <section class="panel table-wrap space">
        <table>
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Role</th>
                    <th>Contact</th>
                    <th>Zone</th>
                    <th>Salary</th>
                    <th>Status</th>
                    <th>Rating</th>
                    <th>NID</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody><?php foreach (rows("SELECT u.*,(SELECT ROUND(AVG(stars),1) FROM ratings WHERE gardener_id=u.id) rating FROM users u WHERE role IN ('staff','gardener') ORDER BY id DESC") as $staff): ?><tr>
                        <td><?= h($staff['name']) ?></td>
                        <td><?= h($staff['role']) ?></td>
                        <td><?= h($staff['email']) ?><br><?= h($staff['phone']) ?></td>
                        <td><?= h($staff['zone']) ?></td>
                        <td><?= money($staff['salary']) ?></td>
                        <td><?= h($staff['status']) ?></td>
                        <td><?= h($staff['rating'] ?: '—') ?></td>
                        <td><?php if ($staff['nid_file_path']): ?><a href="<?= h(url('pages/admin/nid.php?id=' . $staff['id'])) ?>">Download PDF</a><?php else: ?>Not uploaded<?php endif; ?></td>
                        <td><a class="btn secondary small" href="<?= h(url('pages/admin/dashboard.php?view=staff_form&id=' . $staff['id'])) ?>">Edit</a></td>
                    </tr><?php endforeach; ?></tbody>
        </table>
    </section>
<?php } elseif ($view === 'orders') {
    heading('Plant orders', 'Process orders and manage delivery progress.');
    order_cards(rows('SELECT o.*,u.name customer_name FROM orders o JOIN users u ON u.id=o.customer_id ORDER BY o.id DESC'), 'admin', $return);
} elseif ($view === 'services') {
    heading('Garden service requests', 'Assign gardeners and review visit notes and customer ratings.');
    gardener_schedule();
    service_cards(service_rows($user), 'admin', $return);
} elseif ($view === 'reports') {
    heading('Sales & service reports', 'Review plant sales and garden service fees, and download your reports.');
    $from = query_input('from', date('Y-m-d', strtotime('-29 days')), 10);
    $to = query_input('to', date('Y-m-d'), 10);
    try {
        report_dates($from, $to);
    } catch (Exception $error) {
        flash($error->getMessage(), 'error');
        redirect('pages/admin/dashboard.php?view=reports');
    }
    report_panel($from, $to);
                                                                                                                                                                                                                                                                                    } else {
                                                                                                                                                                                                                                                                                        heading('Admin overview', 'Welcome, ' . $user['name'] . '. Here is what is happening at Verdant Haven.');
                                                                                                                                                                                                                                                                                        $stats = [
                                                                                                                                                                                                                                                                                            'Catalog plants' => one('SELECT COUNT(*) value FROM plants')['value'],
                                                                                                                                                                                                                                                                                            'Customers' => one("SELECT COUNT(*) value FROM users WHERE role='customer'")['value'],
                                                                                                                                                                                                                                                                                            'Open orders' => one("SELECT COUNT(*) value FROM orders WHERE status IN ('placed','processing','dispatched')")['value'],
                                                                                                                                                                                                                                                                                            'Open services' => one("SELECT COUNT(*) value FROM services WHERE status IN ('requested','assigned')")['value']
                                                                                                                                                                                                                                                                                        ];
                                                                                                                                                                                                                                                                                        ?><div class="stats"><?php foreach ($stats as $label => $value): ?><div class="card stat"><small><?= h($label) ?></small><b><?= (int)$value ?></b></div><?php endforeach; ?></div>
    <div class="grid">
        <section class="panel">
            <h2>Plant inventory</h2>
            <p class="muted">Add plants, set prices and stock, and configure customer watering reminders.</p><a class="btn" href="<?= h(url('pages/admin/dashboard.php?view=plants')) ?>">Manage plants</a>
        </section>
        <section class="panel">
            <h2>Your nursery team</h2>
            <p class="muted">Create gardener and nursery staff accounts and manage their duty status.</p><a class="btn secondary" href="<?= h(url('pages/admin/dashboard.php?view=staff')) ?>">Manage staff</a>
        </section>
    </div><?php report_panel(date('Y-m-d', strtotime('-29 days')), date('Y-m-d'));
                                                                                                                                                                                                                                                                                    }
                                                                                                                                                                                                                                                                                    page_end();
