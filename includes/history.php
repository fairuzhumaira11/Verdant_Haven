<?php
function order_cards($orders, $role, $return_to) {
    if (!$orders) { echo '<div class="panel empty">No orders found.</div>'; return; }
    $allowed = ['placed'=>['processing','cancelled'], 'processing'=>['dispatched','cancelled'], 'dispatched'=>['delivered']];
    foreach ($orders as $order) {
        $items = rows('SELECT * FROM order_items WHERE order_id=?','i',[$order['id']]);
        $payment = one('SELECT transaction_id FROM payments WHERE order_id=?','i',[$order['id']]);
        ?><article class="panel order-card"><div class="row spread"><h2>Order #<?= (int)$order['id'] ?></h2><span class="badge status-<?= h($order['status']) ?>"><?= h(ucfirst($order['status'])) ?></span></div>
<p class="muted"><?= h($order['created_at']) ?> · <?= h($order['customer_name'] ?? '') ?></p><p>Delivery to: <?= h($order['address']) ?></p><ul><?php foreach ($items as $item): ?><li><?= h($item['plant_name']) ?> × <?= (int)$item['quantity'] ?> — <?= money($item['unit_price'] * $item['quantity']) ?></li><?php endforeach; ?></ul>
<div class="row spread"><strong>Total <?= money($order['total']) ?></strong><span><?= h(strtoupper($order['payment_method'])) ?> · <?= h(str_replace('_',' ',$order['payment_status'])) ?></span></div>
<?php if ($payment): ?><p class="muted">Dummy transaction: <?= h($payment['transaction_id']) ?></p><?php endif;
if (in_array($role,['staff','admin']) && isset($allowed[$order['status']])):
post_form('order_status',$return_to,'row space'); ?><input type="hidden" name="id" value="<?= (int)$order['id'] ?>"><label>Next status<select name="status"><?php foreach ($allowed[$order['status']] as $status): ?><option value="<?= $status ?>"><?= ucfirst($status) ?></option><?php endforeach; ?></select></label><button class="btn small">Update order</button></form>
<?php elseif ($role === 'customer'): ?><div class="row space"><a class="btn secondary small" href="<?= h(url('pages/customer/track-order.php?id=' . $order['id'])) ?>">Track order</a><a class="btn secondary small" href="<?= h(url('pages/customer/dashboard.php#watering')) ?>">Care & watering reminders</a></div><?php endif; ?></article>
<?php }
}
function service_rows($user, $filter = '') {
    $sql = 'SELECT s.*,c.name customer_name,c.phone customer_phone,g.name gardener_name,r.stars,r.feedback FROM services s JOIN users c ON c.id=s.customer_id LEFT JOIN users g ON g.id=s.gardener_id LEFT JOIN ratings r ON r.service_id=s.id WHERE 1=1';
    $types=''; $args=[];
    if ($user['role'] === 'customer') { $sql.=' AND s.customer_id=?'; $types.='i'; $args[]=$user['id']; }
    if ($user['role'] === 'gardener') { $sql.=' AND s.gardener_id=?'; $types.='i'; $args[]=$user['id']; }
    if (in_array($filter,['requested','assigned','completed','cancelled'])) { $sql.=' AND s.status=?'; $types.='s'; $args[]=$filter; }
    $sql .= $user['role'] === 'gardener' ? ' ORDER BY s.scheduled_at ASC' : ' ORDER BY s.scheduled_at DESC';
    return rows($sql,$types,$args);
}
function gardener_schedule() {
    $gardeners = rows("SELECT id,name,zone,status FROM users WHERE role='gardener' ORDER BY name");
    $visits = rows("SELECT gardener_id,type,scheduled_at,address FROM services WHERE status='assigned' AND scheduled_at>=DATE_SUB(NOW(),INTERVAL 2 HOUR) ORDER BY scheduled_at");
    $bookings = [];
    foreach ($visits as $visit) $bookings[$visit['gardener_id']][] = $visit;
    ?><section class="panel"><h2>Gardener schedules</h2><p class="muted">Each booked visit reserves two hours. Check the requested date and area before assigning.</p>
    <?php if (!$gardeners): ?><p class="empty">No gardeners have been added yet.</p><?php endif;
    foreach ($gardeners as $gardener):
        $scheduled = $bookings[$gardener['id']] ?? []; ?>
        <div class="schedule-row"><div class="row spread"><strong><?= h($gardener['name']) ?> <small><?= h($gardener['zone']) ?></small></strong><span class="badge"><?= $gardener['status'] === 'Inactive' || $gardener['status'] === 'On Leave' ? 'Unavailable' : ($scheduled ? 'Booked' : 'No visits booked') ?></span></div>
        <?php foreach (array_slice($scheduled, 0, 3) as $booking): ?><p class="muted"><?= h(date('d M Y, h:i A', strtotime($booking['scheduled_at']))) ?> · <?= h(ucfirst(strtolower($booking['type']))) ?> · <?= h($booking['address']) ?></p><?php endforeach;
        if (count($scheduled) > 3): ?><small>And <?= count($scheduled) - 3 ?> more visits.</small><?php endif; ?></div>
    <?php endforeach; ?></section>
<?php }
function rate_popup($visit, $return_to) {
    $popup = 'rate-visit-' . (int)$visit['id'];
    ?><button type="button" class="btn small" popovertarget="<?= h($popup) ?>">Rate gardener</button>
    <div class="rating-popover panel" id="<?= h($popup) ?>" popover><div class="row spread"><h2>Rate your gardener</h2><button type="button" class="btn secondary small" popovertarget="<?= h($popup) ?>" popovertargetaction="hide" aria-label="Close">Close</button></div>
    <p class="muted"><?= h($visit['gardener_name']) ?> · <?= h(ucfirst(strtolower($visit['type']))) ?> visit</p>
    <?php post_form('rate', $return_to, 'visit-form'); ?><input type="hidden" name="service_id" value="<?= (int)$visit['id'] ?>"><label>Rating<select name="stars" required><?php for ($stars=5; $stars>=1; $stars--): ?><option value="<?= $stars ?>"><?= $stars ?> star<?= $stars === 1 ? '' : 's' ?></option><?php endfor; ?></select></label><label>Feedback<textarea name="feedback" maxlength="2000"></textarea></label><button class="btn">Submit rating</button></form></div>
<?php }
function service_cards($services, $role, $return_to) {
    if (!$services) { echo '<div class="panel empty">No service visits found.</div>'; return; }
    $gardeners = in_array($role,['admin','staff']) ? rows("SELECT id,name,zone FROM users WHERE role='gardener' AND status IN ('Available','On Visit') ORDER BY name") : [];
    $bookings = [];
    if ($gardeners) {
        foreach (rows("SELECT id,gardener_id,scheduled_at FROM services WHERE status='assigned' AND scheduled_at>=DATE_SUB(NOW(),INTERVAL 2 HOUR)") as $booking) $bookings[$booking['gardener_id']][] = $booking;
    }
    foreach ($services as $visit) { ?>
<article class="panel order-card"><div class="row spread"><h2><?= h(ucfirst(strtolower($visit['type']))) ?> visit #<?= (int)$visit['id'] ?></h2><span class="badge"><?= h(ucfirst($visit['status'])) ?></span></div>
<p><strong><?= h(date('d M Y, h:i A',strtotime($visit['scheduled_at']))) ?></strong> · <?= money($visit['fee']) ?></p>
<p><?= h($visit['customer_name']) ?> · <?= h($visit['customer_phone']) ?><br>Address: <?= h($visit['address']) ?><br>Gardener: <?= h($visit['gardener_name'] ?: 'Awaiting assignment') ?></p>
<?php if ($visit['customer_notes']): ?><p class="care-copy">Customer notes: <?= h($visit['customer_notes']) ?></p><?php endif;
if ($visit['status'] === 'completed'): ?><p class="care-copy">Completed: <?= h($visit['completed_at']) ?><br>Gardener notes: <?= h($visit['gardener_notes']) ?></p><?php if ($visit['photo_path']): ?><a href="<?= h(url($visit['photo_path'])) ?>"><img class="visit-photo" src="<?= h(url($visit['photo_path'])) ?>" alt="Photo from completed garden visit"></a><?php endif; endif;
if (in_array($role,['admin','staff']) && in_array($visit['status'],['requested','assigned'])):
post_form('service_assign',$return_to,'row visit-form'); ?><input type="hidden" name="id" value="<?= (int)$visit['id'] ?>"><label>Assign gardener<select name="gardener_id" required><option value="">Choose a gardener</option><?php foreach ($gardeners as $gardener):
    $busy = false;
    foreach ($bookings[$gardener['id']] ?? [] as $booking) {
        if ($booking['id'] != $visit['id'] && abs(strtotime($booking['scheduled_at']) - strtotime($visit['scheduled_at'])) < 7200) $busy = true;
    }
    $booking_count = count($bookings[$gardener['id']] ?? []);
    ?><option value="<?= (int)$gardener['id'] ?>" <?= $visit['gardener_id'] == $gardener['id'] ? 'selected' : '' ?> <?= $busy ? 'disabled' : '' ?>><?= h($gardener['name'] . ' · ' . $gardener['zone']) ?> · <?= $busy ? 'Booked for this time' : ($booking_count ? 'Has other bookings' : 'No visits booked') ?></option><?php endforeach; ?></select></label><button class="btn small">Assign visit</button></form>
<?php elseif ($role === 'gardener' && $visit['status'] === 'assigned'):
post_form('service_complete',$return_to,'visit-form',true); ?><input type="hidden" name="id" value="<?= (int)$visit['id'] ?>"><label>Completion notes<textarea name="notes" maxlength="5000" required placeholder="Describe the work completed"></textarea></label><label>Visit photo (optional, max 5 MB)<input type="file" name="photo" accept="image/jpeg,image/png,image/webp"></label><button class="btn">Mark visit completed</button></form>
<?php elseif ($role === 'customer' && $visit['status'] === 'completed' && !$visit['stars']):
rate_popup($visit, $return_to);
endif;
if ($visit['stars']): ?><p class="space">Rating: <?= (int)$visit['stars'] ?>/5 · <?= h($visit['feedback']) ?></p><?php endif; ?></article>
<?php }
}
