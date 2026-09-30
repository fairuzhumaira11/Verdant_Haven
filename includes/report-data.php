<?php
function compare_report_dates($first, $second) {
    return strcmp($second['date'], $first['date']);
}
function report_dates($from, $to) {
    foreach ([$from, $to] as $date) {
        $parsed = DateTimeImmutable::createFromFormat('!Y-m-d', $date);
        if (!$parsed || $parsed->format('Y-m-d') !== $date) throw new Exception('Choose valid report dates.');
    }
    if ($from > $to) throw new Exception('The from date must be on or before the to date.');
    return [$from, $to];
}
function sales_report($from, $to) {
    report_dates($from, $to);
    $orders = rows("SELECT o.*,u.name FROM orders o JOIN users u ON u.id=o.customer_id WHERE o.created_at>=? AND o.created_at<DATE_ADD(?,INTERVAL 1 DAY) AND o.status!='cancelled' ORDER BY o.created_at DESC",'ss',[$from,$to]);
    $services = rows("SELECT s.*,u.name FROM services s JOIN users u ON u.id=s.customer_id WHERE s.completed_at>=? AND s.completed_at<DATE_ADD(?,INTERVAL 1 DAY) AND s.status='completed' ORDER BY s.completed_at DESC",'ss',[$from,$to]);
    $data = ['title'=>'Sales & service report','period'=>date('d M Y', strtotime($from)) . ' to ' . date('d M Y', strtotime($to)),'plants'=>0,'services'=>0,'rows'=>[]];
    foreach ($orders as $order) {
        $data['plants'] += (float)$order['total'];
        $data['rows'][] = ['id'=>'ORD-' . $order['id'],'name'=>$order['name'],'category'=>'Plant sale','date'=>$order['created_at'],'amount'=>(float)$order['total'],'status'=>$order['status']];
    }
    foreach ($services as $service) {
        $data['services'] += (float)$service['fee'];
        $data['rows'][] = ['id'=>'SRV-' . $service['id'],'name'=>$service['name'],'category'=>'Garden service','date'=>$service['completed_at'],'amount'=>(float)$service['fee'],'status'=>$service['status']];
    }
    $data['total'] = $data['plants'] + $data['services'];
    usort($data['rows'],'compare_report_dates');
    return $data;
}
function report_panel($from, $to) {
    $data = sales_report($from, $to);
    ?><form class="filters report-filter" method="get" action="<?= h(url('pages/admin/dashboard.php')) ?>"><input type="hidden" name="view" value="reports"><label>From date<input type="date" name="from" value="<?= h($from) ?>" required></label><label>To date<input type="date" name="to" value="<?= h($to) ?>" required></label><button class="btn">Generate report</button></form>
    <div class="page-heading"><div><h2><?= h($data['title']) ?></h2><p><?= h($data['period']) ?> · Includes non-cancelled plant orders and completed service fees.</p></div><a class="btn" href="<?= h(url('pages/admin/report.php?' . http_build_query(['from'=>$from,'to'=>$to]))) ?>">Download PDF</a></div><div class="stats"><div class="card stat"><small>Total sales & fees</small><b><?= money($data['total']) ?></b></div><div class="card stat"><small>Plant sales</small><b><?= money($data['plants']) ?></b></div><div class="card stat"><small>Service fees</small><b><?= money($data['services']) ?></b></div></div><section class="panel"><h2>Revenue by source</h2><div class="report-bars"><?php foreach (['plants'=>'Plant sales','services'=>'Services'] as $key=>$label): $width = $data['total'] > 0 ? round($data[$key] / $data['total'] * 100,2) : 0; ?><div class="bar-line"><span><?= $label ?></span><div class="bar-track"><div class="bar-fill" style="width:<?= $width ?>%"></div></div><strong><?= money($data[$key]) ?></strong></div><?php endforeach; ?></div></section><section class="panel table-wrap"><table><thead><tr><th>Reference</th><th>Customer</th><th>Category</th><th>Date</th><th>Amount</th><th>Status</th></tr></thead><tbody><?php foreach ($data['rows'] as $row): ?><tr><td><?= h($row['id']) ?></td><td><?= h($row['name']) ?></td><td><?= h($row['category']) ?></td><td><?= h($row['date']) ?></td><td><?= money($row['amount']) ?></td><td><?= h($row['status']) ?></td></tr><?php endforeach; ?></tbody></table><?php if (!$data['rows']): ?><p class="empty">No sales in this period.</p><?php endif; ?></section>
<?php }
