<?php
// Simulated bKash and Nagad payments.
function payment_expired($payment)
{
    return strtotime($payment['created_at']) <= time() - 900;
}
function dummy_payment_create($customer_id, $provider, $amount)
{
    global $conn;
    if (!in_array($provider, ['bkash', 'nagad'])) throw new Exception('Choose bKash or Nagad.');
    if ($amount <= 0) throw new Exception('Invalid payment amount.');
    $payment_id = 'DEMO-' . strtoupper($provider) . '-' . bin2hex(random_bytes(12));
    run('INSERT INTO payments(payment_id,customer_id,provider,amount,status) VALUES(?,?,?,?,?)', 'sisds', [$payment_id, $customer_id, $provider, $amount, 'created']);
    return ['payment_id' => $payment_id, 'provider' => $provider, 'amount' => $amount, 'status' => 'created'];
}
function dummy_payment_execute($customer_id, $payment_id, $outcome, $wallet)
{
    $payment = one('SELECT * FROM payments WHERE payment_id=? AND customer_id=? FOR UPDATE', 'si', [$payment_id, $customer_id]);
    if (!$payment) throw new Exception('Payment not found.');
    if ($payment['status'] !== 'created') return $payment;
    if ($outcome === 'success' && payment_expired($payment)) throw new Exception('Payment expired. Return to checkout and start a new payment.');
    if (!in_array($outcome, ['success', 'failed', 'cancelled'])) throw new Exception('Invalid payment outcome.');
    if ($outcome === 'success' && !preg_match('/^01[3-9][0-9]{8}$/', $wallet)) throw new Exception('Enter an 11-digit test wallet number.');
    $transaction_id = $outcome === 'success' ? 'DEMO-TXN-' . strtoupper(bin2hex(random_bytes(8))) : null;
    run('UPDATE payments SET status=?,transaction_id=?,wallet_last4=?,completed_at=NOW() WHERE payment_id=? AND customer_id=?', 'ssssi', [$outcome, $transaction_id, $outcome === 'success' ? substr($wallet, -4) : null, $payment_id, $customer_id]);
    return one('SELECT * FROM payments WHERE payment_id=?', 's', [$payment_id]);
}
