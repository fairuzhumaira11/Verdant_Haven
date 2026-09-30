<?php
require dirname(__DIR__, 2) . '/includes/app.php';
require dirname(__DIR__, 2) . '/includes/layout.php';
$plant = one('SELECT * FROM plants WHERE id=? AND active=1', 'i', [(int)query_input('id', '0', 20)]);
if (!$plant) {
    http_response_code(404);
    page_start('Plant not found');
    heading('Plant not found');
    page_end();
    exit;
}
$user = current_user();
$return = 'pages/customer/plant-details.php?id=' . $plant['id'];
page_start($plant['name']);
?><div class="grid two">
    <div><img class="detail-image" src="<?= h(url($plant['image_path'] ?: 'assets/images/main.avif')) ?>" alt="<?= h($plant['name']) ?>"></div>
    <section class="panel"><span class="eyebrow"><?= h($plant['category']) ?></span>
        <h1><?= h($plant['name']) ?></h1>
        <p class="space"><?= h($plant['description']) ?></p>
        <h2><?= money($plant['price']) ?></h2>
        <p class="muted"><?= (int)$plant['stock'] ?> in stock · Watering interval: <?= (int)$plant['watering_hours'] ?> hours</p>
        <?php if ($user && $user['role'] === 'customer'):
            if ($plant['stock'] > 0): post_form('cart_add', $return); ?><input type="hidden" name="id" value="<?= (int)$plant['id'] ?>"><label>Quantity<input type="number" name="quantity" min="1" max="<?= min(100, (int)$plant['stock']) ?>" value="1" required></label><button class="btn space">Add to cart</button></form><?php endif;
                                                                                                                                                                                                                                                                                                                $saved = one('SELECT plant_id FROM wishlist WHERE customer_id=? AND plant_id=?', 'ii', [$user['id'], $plant['id']]);
                                                                                                                                                                                                                                                                                                                post_form('wishlist', $return, 'space'); ?><input type="hidden" name="id" value="<?= (int)$plant['id'] ?>"><button class="btn secondary"><?= $saved ? 'Remove from saved flora' : 'Save plant' ?></button></form>
        <?php elseif (!$user): ?><a class="btn" href="<?= h(url('pages/auth/login.php')) ?>">Log in to buy</a><?php endif; ?>
    </section>
</div>
<section class="panel space">
    <h2>Care instructions</h2>
    <div class="grid">
        <div><span class="eyebrow">Light</span>
            <p><?= h($plant['care_light'] ?: 'See guide below') ?></p>
        </div>
        <div><span class="eyebrow">Water</span>
            <p><?= h($plant['care_water'] ?: 'Every ' . $plant['watering_hours'] . ' hours') ?></p>
        </div>
        <div><span class="eyebrow">Soil</span>
            <p><?= h($plant['care_soil'] ?: 'See guide below') ?></p>
        </div>
    </div>
    <p class="care-copy"><?= h($plant['care_instructions']) ?></p><?php if ($plant['care_file_path']): ?><a class="btn secondary" href="<?= h(url($plant['care_file_path'])) ?>">Download care guide (PDF)</a><?php endif; ?>
</section>
<?php page_end(); ?>