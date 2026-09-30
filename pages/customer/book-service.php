<?php
require dirname(__DIR__, 2) . '/includes/app.php';
require dirname(__DIR__, 2) . '/includes/layout.php';
$user = require_roles(['customer']);
$services = ['WATERING' => ['Watering setup', 1000, 'Set up a practical watering routine for your garden.'], 'TRIMMING' => ['Trimming', 900, 'Keep shrubs and plants healthy and neatly shaped.'], 'REPOTTING' => ['Repotting', 1200, 'Give growing plants a new home and fresh soil.'], 'MAINTENANCE' => ['Garden maintenance', 2500, 'A full care visit for your garden.']];
page_start('Book garden care');
heading('A healthier garden starts here', 'Choose a service and a visit time. Nursery staff will assign a gardener.');
?><div class="grid"><?php foreach ($services as $type => $service): ?><article class="card">
            <h3><?= h($service[0]) ?></h3>
            <p class="muted"><?= h($service[2]) ?></p>
            <p class="service-price"><?= money($service[1]) ?></p><a class="btn secondary small" href="<?= h(url('pages/customer/book-service.php?type=' . $type . '#booking')) ?>">Choose service</a>
        </article><?php endforeach; ?></div>
<section class="panel form-panel space" id="booking">
    <h2>Book your visit</h2>
    <p>Dates and times use Bangladesh time (Asia/Dhaka).</p><?php post_form('service_book', 'pages/customer/book-service.php'); ?><div class="form-grid"><label>Service<select name="type" required><?php foreach ($services as $type => $service): ?><option value="<?= $type ?>" <?= old('type', $_GET['type'] ?? '') === $type ? 'selected' : '' ?>><?= h($service[0]) ?> — <?= money($service[1]) ?></option><?php endforeach; ?></select></label><label>Visit date & time<input type="datetime-local" name="scheduled_at" value="<?= h(old('scheduled_at')) ?>" min="<?= date('Y-m-d\\TH:i', time() + 60) ?>" required></label><label class="full">Service address<textarea name="address" maxlength="500" required><?= h(old('address')) ?></textarea></label><label class="full">Notes for your gardener<textarea name="notes" maxlength="2000"><?= h(old('notes')) ?></textarea></label></div>
    <div class="form-actions"><button class="btn">Book garden visit</button></div>
    </form>
</section>
<?php page_end(); ?>