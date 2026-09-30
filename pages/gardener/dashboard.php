<?php
require dirname(__DIR__, 2) . '/includes/app.php';
require dirname(__DIR__, 2) . '/includes/layout.php';
require dirname(__DIR__, 2) . '/includes/history.php';
$user = require_roles(['gardener']);
$completed = ($_GET['view'] ?? '') === 'completed';
$visits = service_rows($user, $completed ? 'completed' : 'assigned');
$return = 'pages/gardener/dashboard.php' . ($completed ? '?view=completed' : '');
$rating = one('SELECT ROUND(AVG(stars),1) average,COUNT(*) count FROM ratings WHERE gardener_id=?', 'i', [$user['id']]);
page_start('Gardener visits');
heading($completed ? 'Your completed visits' : 'Your assigned visits', 'Hello, ' . $user['name'] . '. View addresses, visit times, and customer notes.');
?><div class="stats">
    <div class="card stat"><small><?= $completed ? 'Completed visits' : 'Assigned visits' ?></small><b><?= count($visits) ?></b></div>
    <div class="card stat"><small>Your customer rating</small><b><?= h($rating['average'] ?: '—') ?><small> / 5</small></b></div>
</div>
<?php service_cards($visits, 'gardener', $return);
page_end(); ?>