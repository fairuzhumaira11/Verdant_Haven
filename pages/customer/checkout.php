<?php
require dirname(__DIR__, 2) . '/includes/app.php';
require dirname(__DIR__, 2) . '/includes/layout.php';
require dirname(__DIR__, 2) . '/includes/commerce.php';
$user = require_roles(['customer']);
$items = cart_items($user['id']);
$totals = cart_totals($items);
if (empty($_SESSION['checkout_key'])) $_SESSION['checkout_key'] = bin2hex(random_bytes(32));
page_start('Cart & checkout');
heading('Your cart', 'Review your plants and choose delivery and payment.');
if (!$items): ?><div class="panel empty">
        <p>Your cart is empty.</p><a class="btn" href="<?= h(url('pages/customer/catalog.php')) ?>">Browse plants</a>
    </div>
<?php else: ?><div class="grid two">
        <section class="panel">
            <h2>Plants in your cart</h2><?php foreach ($items as $item): ?><article class="order-card">
                    <div class="row spread"><a href="<?= h(url('pages/customer/plant-details.php?id=' . $item['id'])) ?>"><?= h($item['name']) ?></a><strong><?= money($item['price'] * $item['quantity']) ?></strong></div>
                    <p class="muted"><?= money($item['price']) ?> each · <?= (int)$item['stock'] ?> currently in stock</p><?php post_form('cart_update', 'pages/customer/checkout.php', 'row'); ?><input type="hidden" name="id" value="<?= (int)$item['id'] ?>"><label>Quantity (0 removes)<input type="number" name="quantity" min="0" max="100" value="<?= (int)$item['quantity'] ?>" required></label><button class="btn secondary small">Update</button></form>
                </article><?php endforeach; ?><div class="totals">
                <div class="row spread"><span>Subtotal</span><strong><?= money($totals['subtotal']) ?></strong></div>
                <div class="row spread"><span>Delivery</span><strong><?= money($totals['delivery']) ?></strong></div>
                <div class="row spread total"><span>Total</span><strong><?= money($totals['total']) ?></strong></div>
            </div>
        </section>
        <section class="panel">
            <h2>Delivery & payment</h2><?php post_form('checkout', 'pages/customer/checkout.php'); ?><input type="hidden" name="checkout_key" value="<?= h($_SESSION['checkout_key']) ?>"><label>Delivery address<textarea name="address" maxlength="500" required placeholder="House, road, area, city"><?= h(old('address')) ?></textarea></label><label class="space">Payment method<select name="payment_method" required>
                    <option value="cod">Cash on delivery</option>
                    <option value="bkash">bKash — dummy payment</option>
                    <option value="nagad">Nagad — dummy payment</option>
                </select></label>
            <p class="muted space">bKash and Nagad open a test payment screen. No real money is charged. Cash on delivery stays pending.</p><button class="btn">Continue checkout</button></form>
        </section>
    </div><?php endif;
        page_end(); ?>