<?php
require_once __DIR__ . '/chat-functions.php';
function post_form($action, $return_to, $class = '', $files = false)
{
    echo '<form method="post" action="' . h(url('actions/handle.php')) . '" class="' . h($class) . '"' . ($files ? ' enctype="multipart/form-data"' : '') . '>';
    csrf_input();
    echo '<input type="hidden" name="action" value="' . h($action) . '"><input type="hidden" name="return_to" value="' . h($return_to) . '">';
}
function page_start($title, $refresh = false)
{
    $user = current_user();
    $chat_paths = ['customer' => 'pages/customer/chat.php', 'staff' => 'pages/staff/chat.php', 'gardener' => 'pages/gardener/chat.php'];
    $chat_path = $user && $user['status'] !== 'Inactive' ? ($chat_paths[$user['role']] ?? '') : '';
    $unread = $chat_path ? chat_unread($user) : ['total' => 0];
    $theme = $_SESSION['theme'] ?? $_COOKIE['vh_theme'] ?? 'dark';
    if (!in_array($theme, ['light', 'dark'])) $theme = 'dark';
    $return = ltrim(substr($_SERVER['SCRIPT_NAME'], strlen(BASE_PATH)), '/') . (!empty($_SERVER['QUERY_STRING']) ? '?' . $_SERVER['QUERY_STRING'] : '');
    $nav = [
        'customer' => ['My dashboard' => 'pages/customer/dashboard.php', 'Plant store' => 'pages/customer/catalog.php', 'Saved flora' => 'pages/customer/saved-plants.php', 'Cart & checkout' => 'pages/customer/checkout.php', 'Orders & services' => 'pages/customer/orders.php', 'Book garden care' => 'pages/customer/book-service.php', 'Chat with staff' => 'pages/customer/chat.php'],
        'admin' => ['Overview' => 'pages/admin/dashboard.php', 'Plant inventory' => 'pages/admin/dashboard.php?view=plants', 'Staff & gardeners' => 'pages/admin/dashboard.php?view=staff', 'Orders' => 'pages/admin/dashboard.php?view=orders', 'Services' => 'pages/admin/dashboard.php?view=services', 'Sales reports' => 'pages/admin/dashboard.php?view=reports'],
        'staff' => ['Overview' => 'pages/staff/dashboard.php', 'Plant orders' => 'pages/staff/dashboard.php?view=orders', 'Garden services' => 'pages/staff/dashboard.php?view=services', 'Stock management' => 'pages/staff/dashboard.php?view=stock', 'Live chat' => 'pages/staff/chat.php'],
        'gardener' => ['My visits' => 'pages/gardener/dashboard.php', 'Completed visits' => 'pages/gardener/dashboard.php?view=completed', 'Chat with staff' => 'pages/gardener/chat.php']
    ];
?>
    <!DOCTYPE html>
    <html lang="en" data-theme="<?= h($theme) ?>">

    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width,initial-scale=1">
        <title><?= h($title) ?> — Verdant Haven</title>
        <?php if ($refresh): ?>
            <meta http-equiv="refresh" content="60"><?php endif; ?>
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&family=Playfair+Display:ital,wght@0,500;0,700;1,400&display=swap" rel="stylesheet">
        <link rel="stylesheet" href="<?= h(url('assets/css/site.css')) ?>">
    </head>

    <body>
        <header class="topbar">
            <div class="wrap">
                <a class="brand" href="<?= h(url('index.php')) ?>"><svg viewBox="0 0 40 40" fill="none" aria-hidden="true">
                        <path d="M31 5C9 5 5 19 11 28c9 6 23 2 20-23Z" fill="currentColor" opacity=".75" />
                        <path d="M8 34 28 12M16 25l-1-9M21 20l8-1" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" />
                    </svg><span>Verdant Haven<small>Nursery & garden care</small></span></a>
                <div class="top-actions">
                    <?php post_form('theme', $return); ?><input type="hidden" name="theme" value="<?= $theme === 'dark' ? 'light' : 'dark' ?>"><button class="theme-btn" aria-label="Switch to <?= $theme === 'dark' ? 'light' : 'dark' ?> mode"><?= $theme === 'dark' ? '☀ Light ' : '☾ Dark ' ?></button></form>
                    <?php if ($chat_path): ?><a class="message-link" href="<?= h(url($chat_path)) ?>">Messages <span class="unread-badge" data-unread-total data-api="<?= h(url('api/index.php')) ?>" <?= $unread['total'] ? '' : 'hidden' ?>><?= (int)$unread['total'] ?></span></a><?php endif; ?>
                    <?php if ($user): ?><a class="user-name" href="<?= h(url(home($user['role']))) ?>"><?= h($user['name']) ?></a><?php post_form('logout', 'pages/auth/login.php'); ?><button class="btn secondary small">Log out</button></form>
                    <?php else: ?><a href="<?= h(url('pages/customer/catalog.php')) ?>">Catalog</a><a class="btn secondary small" href="<?= h(url('pages/auth/login.php')) ?>">Log in</a><a class="btn small" href="<?= h(url('pages/auth/signup.php')) ?>">Sign up</a><?php endif; ?>
                </div>
            </div>
        </header>
        <?php if ($user): ?><div class="app-shell">
                <aside class="sidebar">
                    <div class="role"><?= h($user['role'] === 'staff' ? 'Nursery staff' : $user['role']) ?></div>
                    <details open>
                        <summary>Navigation</summary>
                        <nav><?php foreach ($nav[$user['role']] ?? [] as $label => $path): ?><a href="<?= h(url($path)) ?>"><?= h($label) ?><?php if ($path === $chat_path): ?> <span class="unread-badge" data-unread-total <?= $unread['total'] ? '' : 'hidden' ?>><?= (int)$unread['total'] ?></span><?php endif; ?></a><?php endforeach; ?></nav>
                    </details>
                </aside><?php endif; ?>
            <main class="content <?= $user ? '' : 'public-content' ?>">
                <?php if (isset($_SESSION['flash'])): $flash = $_SESSION['flash'];
                    unset($_SESSION['flash']); ?><div class="notice <?= h($flash['kind']) ?>" role="status"><?= h($flash['message']) ?></div><?php endif;
                                                                                                                                                                                                    }
                                                                                                                                                                                                    function page_end()
                                                                                                                                                                                                    {
                                                                                                                                                                                                        $user = current_user();
                                                                                                                                                                                                        $chat_script = $user && in_array($user['role'], ['customer', 'staff', 'gardener']) ? '<script src="' . h(url('assets/js/chat-indicator.js')) . '" defer></script>' : '';
                                                                                                                                                                                                        echo '</main>' . ($user ? '</div>' : '') . $chat_script . '<footer>Verdant Haven · Plant nursery & garden care · Dhaka, Bangladesh</footer></body></html>';
                                                                                                                                                                                                        unset($_SESSION['old']);
                                                                                                                                                                                                    }
                                                                                                                                                                                                    function heading($title, $description = '')
                                                                                                                                                                                                    {
                                                                                                                                                                                                        echo '<div class="page-heading"><div><h1>' . h($title) . '</h1><p>' . h($description) . '</p></div></div>';
                                                                                                                                                                                                    }
                                                                                                                                                                                                    function plant_cards($plants, $return_to)
                                                                                                                                                                                                    {
                                                                                                                                                                                                        $user = current_user();
                                                                                                                                                                                                        if (!$plants) {
                                                                                                                                                                                                            echo '<div class="panel empty">No plants found. Try another search.</div>';
                                                                                                                                                                                                            return;
                                                                                                                                                                                                        }
                                                                                                                                                                                                        echo '<div class="grid">';
                                                                                                                                                                                                        foreach ($plants as $plant) { ?>
                    <article class="card plant-card">
                        <a href="<?= h(url('pages/customer/plant-details.php?id=' . $plant['id'])) ?>"><img src="<?= h(url($plant['image_path'] ?: 'assets/images/main.avif')) ?>" alt="<?= h($plant['name']) ?>" loading="lazy"></a>
                        <div class="plant-body"><span class="eyebrow"><?= h($plant['category']) ?></span>
                            <h3><?= h($plant['name']) ?></h3>
                            <p><?= h($plant['description']) ?></p>
                            <div class="price"><?= money($plant['price']) ?></div>
                            <div class="row"><a class="btn secondary small" href="<?= h(url('pages/customer/plant-details.php?id=' . $plant['id'])) ?>">Details & care</a>
                                <?php if ($user && $user['role'] === 'customer'):
                                                                                                                                                                                                                if ($plant['stock'] > 0): post_form('cart_add', $return_to); ?><input type="hidden" name="id" value="<?= (int)$plant['id'] ?>"><input type="hidden" name="quantity" value="1"><button class="btn small">Add to cart</button></form><?php else: ?><span class="badge alert">Out of stock</span><?php endif;
                                                                                                                                                                                                                                                                                                post_form('wishlist', $return_to); ?><input type="hidden" name="id" value="<?= (int)$plant['id'] ?>"><button class="btn secondary small"><?= !empty($plant['saved']) ? 'Unsave' : 'Save' ?></button></form>
                                <?php elseif (!$user): ?><a class="btn small" href="<?= h(url('pages/auth/login.php')) ?>">Log in to buy</a><?php endif; ?>
                            </div>
                        </div>
                    </article>
            <?php }
                                                                                                                                                                                                        echo '</div>';
                                                                                                                                                                                                    }
