<?php
require dirname(__DIR__, 2) . '/includes/app.php';
require dirname(__DIR__, 2) . '/includes/layout.php';
require dirname(__DIR__, 2) . '/includes/payments.php';
$user = require_roles(['customer']);
$payment = one('SELECT * FROM payments WHERE payment_id=? AND customer_id=?', 'si', [query_input('payment_id', '', 80), $user['id']]);
if (!$payment) {
    http_response_code(404);
    page_start('Payment not found');
    heading('Payment not found');
    page_end();
    exit;
}
if ($payment['status'] === 'success') redirect('pages/customer/order-confirmed.php?id=' . $payment['order_id']);
$expired = $payment['status'] === 'created' && payment_expired($payment);
$return = 'pages/payments/dummy.php?payment_id=' . rawurlencode($payment['payment_id']);
page_start('Dummy payment');
?><section class="panel form-panel">
    <div class="payment-brand <?= h($payment['provider']) ?>"><?= $payment['provider'] === 'bkash' ? 'bKash' : 'Nagad' ?></div><span class="badge">Demo payment · No real transaction</span>
    <h1 class="space"><?= money($payment['amount']) ?></h1>
    <p class="muted">Payment reference: <?= h($payment['payment_id']) ?></p>
    <?php if ($payment['status'] === 'created' && !$expired): post_form('payment_execute', $return); ?><input type="hidden" name="payment_id" value="<?= h($payment['payment_id']) ?>"><label>Test wallet number<input type="tel" name="wallet" required pattern="01[3-9][0-9]{8}" maxlength="11" placeholder="01700000000"></label>
        <p class="muted space">Use a test mobile number. This simulator does not ask for a PIN or send an OTP.</p>
        <div class="row"><button class="btn" name="outcome" value="success">Simulate successful payment</button><button class="btn secondary" name="outcome" value="failed" formnovalidate>Simulate failure</button><button class="btn danger" name="outcome" value="cancelled" formnovalidate>Cancel</button></div>
        </form><?php else: ?><p>Payment <?= h($expired ? 'expired' : $payment['status']) ?>.</p><a class="btn" href="<?= h(url('pages/customer/checkout.php')) ?>">Return to checkout</a><?php endif; ?>
</section>
<?php page_end(); ?>