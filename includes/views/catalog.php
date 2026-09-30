<?php
require dirname(__DIR__) . '/app.php';
require dirname(__DIR__) . '/layout.php';
$user = current_user();
if ($saved_only) $user = require_roles(['customer']);
$search = query_input('search', '', 150);
$category = query_input('category', '', 20);
$sort = query_input('sort', 'name', 20);
$sorts = ['name' => 'p.name', 'price_low' => 'p.price ASC', 'price_high' => 'p.price DESC', 'new' => 'p.id DESC'];
if (!isset($sorts[$sort])) $sort = 'name';
$sql = 'SELECT p.*,w.plant_id saved FROM plants p LEFT JOIN wishlist w ON w.plant_id=p.id AND w.customer_id=? WHERE p.active=1';
$types = 'i';
$values = [$user['id'] ?? 0];
if ($saved_only) $sql .= ' AND w.plant_id IS NOT NULL';
if ($search !== '') {
    $sql .= ' AND p.name LIKE ?';
    $types .= 's';
    $values[] = '%' . $search . '%';
}
if (in_array($category, ['indoor', 'outdoor', 'fruit', 'flower'])) {
    $sql .= ' AND p.category=?';
    $types .= 's';
    $values[] = $category;
}
$sql .= ' ORDER BY ' . $sorts[$sort];
$plants = rows($sql, $types, $values);
$path = 'pages/customer/' . ($saved_only ? 'saved-plants.php' : 'catalog.php');
$return = $path . '?' . http_build_query(['search' => $search, 'category' => $category, 'sort' => $sort]);
page_start($saved_only ? 'Saved flora' : 'Plant store');
heading($saved_only ? 'Your saved flora' : 'Find your perfect plant', 'Browse indoor, outdoor, fruit, and flowering plants. Care guides are open to everyone.');
?><form method="get" class="filters"><label>Search plants<input name="search" value="<?= h($search) ?>" placeholder="Plant name"></label><label>Category<select name="category">
            <option value="">All categories</option><?php foreach (['indoor', 'outdoor', 'fruit', 'flower'] as $option): ?><option value="<?= $option ?>" <?= $category === $option ? 'selected' : '' ?>><?= ucfirst($option) ?></option><?php endforeach; ?>
        </select></label><label>Sort<select name="sort"><?php foreach (['name' => 'Name', 'price_low' => 'Price: low to high', 'price_high' => 'Price: high to low', 'new' => 'Newest'] as $key => $label): ?><option value="<?= $key ?>" <?= $sort === $key ? 'selected' : '' ?>><?= $label ?></option><?php endforeach; ?></select></label><button class="btn">Apply</button></form>
<?php plant_cards($plants, $return);
page_end(); ?>