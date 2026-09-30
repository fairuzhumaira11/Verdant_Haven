<?php
require __DIR__ . '/includes/app.php';
require __DIR__ . '/includes/layout.php';
$user = current_user();
$plants = rows('SELECT p.*,w.plant_id saved FROM plants p LEFT JOIN wishlist w ON w.plant_id=p.id AND w.customer_id=? WHERE p.active=1 ORDER BY p.id LIMIT 4', 'i', [$user['id'] ?? 0]);
page_start('Welcome');
?>
<section class="hero">
    <div><span class="eyebrow">Rooted in nature · Growing with you</span>
        <h1>A little green.<br>A whole lot of <em>life.</em></h1>
        <p>Bring nature home with beautiful plants, thoughtful care guides, and garden services from our team in Dhaka.</p>
        <div class="row space"><a class="btn" href="<?= h(url('pages/customer/catalog.php')) ?>">Explore plants</a><a class="btn secondary" href="<?= h(url('pages/customer/book-service.php')) ?>">Book garden care</a></div>
    </div><img src="<?= h(url('assets/images/main.avif')) ?>" alt="Lush greenery at Verdant Haven">
</section>
<div class="page-heading section-heading">
    <h2>Find your next green companion</h2><a href="<?= h(url('pages/customer/catalog.php')) ?>">View all plants →</a>
</div>
<?php plant_cards($plants, 'index.php'); ?>
<section id="care-tips">
    <h2 class="section-heading">A little care goes a long way</h2>
    <div class="grid">
        <article class="card">
            <h3>Water thoughtfully</h3>
            <p class="muted">Check the soil before watering. Your purchased plants have their own recurring watering reminders on your dashboard.</p>
        </article>
        <article class="card">
            <h3>Find the right light</h3>
            <p class="muted">Use each plant's care guide to choose a bright window, shaded balcony, or sunny rooftop.</p>
        </article>
        <article class="card">
            <h3>Let us help your garden</h3>
            <p class="muted">Book watering setup, trimming, repotting, or regular maintenance. Track your gardener's visit and completion notes.</p>
        </article>
    </div>
</section>
<?php page_end(); ?>