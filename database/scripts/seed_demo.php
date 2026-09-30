<?php
// Add sample people and their order and visit history. Safe to run again.
if (PHP_SAPI !== 'cli') { http_response_code(403); exit('CLI only'); }
date_default_timezone_set('Asia/Dhaka');
$conn = require dirname(__DIR__, 2) . '/includes/db_connect.php';

function seed_run($sql, $types = '', $values = []) {
    global $conn;
    $stmt = mysqli_prepare($conn, $sql);
    if ($types !== '') mysqli_stmt_bind_param($stmt, $types, ...$values);
    mysqli_stmt_execute($stmt);
    $affected = mysqli_stmt_affected_rows($stmt);
    mysqli_stmt_close($stmt);
    return $affected;
}
function seed_one($sql, $types = '', $values = []) {
    global $conn;
    $stmt = mysqli_prepare($conn, $sql);
    if ($types !== '') mysqli_stmt_bind_param($stmt, $types, ...$values);
    mysqli_stmt_execute($stmt);
    $row = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
    mysqli_stmt_close($stmt);
    return $row ?: null;
}

$domains = [
    'customer' => 'customer.verdanthaven.com',
    'staff' => 'staff.verdanthaven.com',
    'gardener' => 'gardener.veranthaven.com'
];
$passwords = ['customer' => 'Pass@customer', 'staff' => 'Pass@stuff', 'gardener' => 'Pass@garden'];
$accounts = [
    ['Nusrat Jahan', 'customer', '01700002001', null, null, 'Available'],
    ['Tanvir Rahman', 'customer', '01700002002', null, null, 'Available'],
    ['Farzana Akter', 'customer', '01700002003', null, null, 'Available'],
    ['Mehedi Hasan', 'customer', '01700002004', null, null, 'Available'],
    ['Sadia Islam', 'customer', '01700002005', null, null, 'Available'],
    ['Arif Hossain', 'customer', '01700002006', null, null, 'Available'],
    ['Shamima Begum', 'staff', '01700002007', 'Nursery Floor', 18000, 'Active Desk'],
    ['Rafiq Uddin', 'staff', '01700002008', 'Plant Dispatch', 17000, 'Active Desk'],
    ['Tasnim Chowdhury', 'staff', '01700002009', 'Customer Support', 19000, 'Active Desk'],
    ['Abdul Karim', 'gardener', '01700002010', 'Dhaka South', 15000, 'Available'],
    ['Mizanur Rahman', 'gardener', '01700002011', 'Dhaka North', 14500, 'Available'],
    ['Selina Parvin', 'gardener', '01700002012', 'Dhaka West', 15000, 'Available'],
    ['Jamal Hossain', 'gardener', '01700002013', 'Dhaka East', 14000, 'Available']
];
$orders = [
    ['key' => 'nusrat-home', 'customer' => 'nusratjahan', 'status' => 'delivered', 'method' => 'bkash', 'days_ago' => 12, 'address' => 'House 24, Road 7, Dhanmondi, Dhaka', 'items' => [['Ficus Rubber Plant', 1], ['Rose', 2]]],
    ['key' => 'tanvir-flat', 'customer' => 'tanvirrahman', 'status' => 'dispatched', 'method' => 'cod', 'days_ago' => 3, 'address' => 'House 18, Avenue 3, Mirpur DOHS, Dhaka', 'items' => [['Monstera Deliciosa', 1], ['Snake Plant (Sansevieria)', 1]]],
    ['key' => 'farzana-bonsai', 'customer' => 'farzanaakter', 'status' => 'processing', 'method' => 'nagad', 'days_ago' => 1, 'address' => 'House 6, Road 11, Uttara, Dhaka', 'items' => [['Bougainvillea Bonsai', 1]]],
    ['key' => 'mehedi-balcony', 'customer' => 'mehedihasan', 'status' => 'placed', 'method' => 'cod', 'days_ago' => 0, 'address' => 'House 9, Block C, Banasree, Dhaka', 'items' => [['Ficus Rubber Plant', 1], ['Pandanus', 1]]],
    ['key' => 'sadia-greenery', 'customer' => 'sadiaislam', 'status' => 'delivered', 'method' => 'bkash', 'days_ago' => 20, 'address' => 'House 31, Road 2, Mohammadpur, Dhaka', 'items' => [['Areca Palm', 1], ['Snake Plant (Sansevieria)', 1]]],
    ['key' => 'arif-roses', 'customer' => 'arifhossain', 'status' => 'dispatched', 'method' => 'cod', 'days_ago' => 2, 'address' => 'House 15, Road 4, Banani, Dhaka', 'items' => [['Rose', 1]]],
    ['key' => 'nusrat-bedroom', 'customer' => 'nusratjahan', 'status' => 'processing', 'method' => 'cod', 'days_ago' => 5, 'address' => 'House 24, Road 7, Dhanmondi, Dhaka', 'items' => [['Snake Plant (Sansevieria)', 1]]],
    ['key' => 'farzana-palm', 'customer' => 'farzanaakter', 'status' => 'placed', 'method' => 'nagad', 'days_ago' => 0, 'address' => 'House 6, Road 11, Uttara, Dhaka', 'items' => [['Areca Palm', 1]]]
];
$services = [
    ['customer' => 'nusratjahan', 'type' => 'TRIMMING', 'status' => 'completed', 'gardener' => 'abdulkarim', 'day' => -8, 'time' => '10:00', 'address' => 'House 24, Road 7, Dhanmondi, Dhaka', 'customer_notes' => 'Trim the rooftop rose plants.', 'gardener_notes' => 'Pruned the roses and removed dry branches.', 'fee' => 900, 'rating' => [5, 'Careful and punctual work.']],
    ['customer' => 'tanvirrahman', 'type' => 'WATERING', 'status' => 'assigned', 'gardener' => 'mizanurrahman', 'day' => 1, 'time' => '09:00', 'address' => 'House 18, Avenue 3, Mirpur DOHS, Dhaka', 'customer_notes' => 'Set up a watering routine for the balcony.', 'fee' => 1000],
    ['customer' => 'farzanaakter', 'type' => 'REPOTTING', 'status' => 'requested', 'gardener' => null, 'day' => 2, 'time' => '11:00', 'address' => 'House 6, Road 11, Uttara, Dhaka', 'customer_notes' => 'Move two plants into larger pots.', 'fee' => 1200],
    ['customer' => 'mehedihasan', 'type' => 'MAINTENANCE', 'status' => 'assigned', 'gardener' => 'jamalhossain', 'day' => 3, 'time' => '10:00', 'address' => 'House 9, Block C, Banasree, Dhaka', 'customer_notes' => 'Check the balcony plants and soil.', 'fee' => 2500],
    ['customer' => 'sadiaislam', 'type' => 'REPOTTING', 'status' => 'completed', 'gardener' => 'selinaparvin', 'day' => -13, 'time' => '15:00', 'address' => 'House 31, Road 2, Mohammadpur, Dhaka', 'customer_notes' => 'Repot the areca palm.', 'gardener_notes' => 'Repotted the palm with fresh soil and checked drainage.', 'fee' => 1200, 'rating' => [4, 'The palm looks healthy.']],
    ['customer' => 'arifhossain', 'type' => 'TRIMMING', 'status' => 'requested', 'gardener' => null, 'day' => 4, 'time' => '16:00', 'address' => 'House 15, Road 4, Banani, Dhaka', 'customer_notes' => 'Trim the front garden hedges.', 'fee' => 900],
    ['customer' => 'nusratjahan', 'type' => 'WATERING', 'status' => 'assigned', 'gardener' => 'abdulkarim', 'day' => 5, 'time' => '09:00', 'address' => 'House 24, Road 7, Dhanmondi, Dhaka', 'customer_notes' => 'Set up regular watering for the new plants.', 'fee' => 1000],
    ['customer' => 'farzanaakter', 'type' => 'WATERING', 'status' => 'completed', 'gardener' => 'mizanurrahman', 'day' => -3, 'time' => '14:00', 'address' => 'House 6, Road 11, Uttara, Dhaka', 'customer_notes' => 'Water the rooftop flowers.', 'gardener_notes' => 'Watered the flowers and checked the soil.', 'fee' => 1000]
];

$added = ['users' => 0, 'orders' => 0, 'services' => 0];
try {
    mysqli_begin_transaction($conn);
    $hashes = [];
    foreach ($passwords as $role => $password) $hashes[$role] = password_hash($password, PASSWORD_DEFAULT);
    $user_ids = [];
    foreach ($accounts as [$name, $role, $phone, $zone, $salary, $status]) {
        $short_name = strtolower(str_replace(' ', '', $name));
        $email = $short_name . '@' . $domains[$role];
        $added['users'] += seed_run('INSERT IGNORE INTO users(name,email,phone,password_hash,role,zone,salary,status) VALUES(?,?,?,?,?,?,?,?)', 'ssssssds', [$name, $email, $phone, $hashes[$role], $role, $zone, $salary, $status]);
        $user = seed_one('SELECT id,role FROM users WHERE email=?', 's', [$email]);
        if (!$user || $user['role'] !== $role) throw new Exception('Could not find the sample account for ' . $name . '. Check for a duplicate phone number.');
        $user_ids[$role][$short_name] = (int)$user['id'];
    }

    $plants = [];
    foreach ($orders as $order) {
        foreach ($order['items'] as [$name]) {
            if (isset($plants[$name])) continue;
            $plant = seed_one('SELECT id,name,price,watering_hours FROM plants WHERE name=? ORDER BY id LIMIT 1', 's', [$name]);
            if (!$plant) throw new Exception('Plant not found: ' . $name . '. Import database/seed.sql first.');
            $plants[$name] = $plant;
        }
    }

    foreach ($orders as $order) {
        $key = hash('sha256', 'verdant-demo-order:' . $order['key']);
        $existing = seed_one('SELECT id FROM orders WHERE checkout_key=?', 's', [$key]);
        if ($existing) continue;
        $customer_id = $user_ids['customer'][$order['customer']];
        $subtotal = 0;
        foreach ($order['items'] as [$name, $quantity]) $subtotal += (float)$plants[$name]['price'] * $quantity;
        $created = date('Y-m-d H:i:s', time() - $order['days_ago'] * 86400);
        $payment_status = $order['method'] === 'cod' ? 'pending_cod' : 'dummy_paid';
        $total = $subtotal + 100;
        seed_run('INSERT INTO orders(customer_id,status,payment_method,checkout_key,payment_status,address,subtotal,delivery_fee,total,created_at,updated_at) VALUES(?,?,?,?,?,?,?,?,?,?,?)',
            'isssssdddss', [$customer_id, $order['status'], $order['method'], $key, $payment_status, $order['address'], $subtotal, 100, $total, $created, $created]);
        $order_id = mysqli_insert_id($conn);
        $added['orders']++;
        foreach ($order['items'] as [$name, $quantity]) {
            $plant = $plants[$name];
            seed_run('INSERT INTO order_items(order_id,plant_id,plant_name,unit_price,quantity) VALUES(?,?,?,?,?)',
                'iisdi', [$order_id, $plant['id'], $plant['name'], $plant['price'], $quantity]);
            $item_id = mysqli_insert_id($conn);
            $due = date('Y-m-d H:i:s', strtotime($created) + (int)$plant['watering_hours'] * 3600);
            seed_run('INSERT INTO reminders(customer_id,plant_id,order_item_id,due_at) VALUES(?,?,?,?)',
                'iiis', [$customer_id, $plant['id'], $item_id, $due]);
        }
        if ($order['method'] !== 'cod') {
            $payment_id = 'VHDEMO-' . strtoupper(substr(hash('sha256', $order['key']), 0, 16));
            $transaction_id = 'VHTXN-' . strtoupper(substr(hash('sha256', 'paid:' . $order['key']), 0, 16));
            $completed = date('Y-m-d H:i:s', strtotime($created) + 300);
            seed_run("INSERT IGNORE INTO payments(payment_id,customer_id,order_id,provider,amount,status,transaction_id,wallet_last4,address,created_at,completed_at) VALUES(?,?,?,?,?,'success',?,?,?,?,?)",
                'siisdsssss', [$payment_id, $customer_id, $order_id, $order['method'], $total, $transaction_id, '0000', $order['address'], $created, $completed]);
        }
    }

    foreach ($services as $service) {
        $customer_id = $user_ids['customer'][$service['customer']];
        $existing = seed_one('SELECT id FROM services WHERE customer_id=? AND type=? AND address=? AND customer_notes=? LIMIT 1',
            'isss', [$customer_id, $service['type'], $service['address'], $service['customer_notes']]);
        if ($existing) {
            $service_id = $existing['id'];
        } else {
            $gardener_id = $service['gardener'] ? $user_ids['gardener'][$service['gardener']] : null;
            $scheduled = date('Y-m-d', strtotime('today') + $service['day'] * 86400) . ' ' . $service['time'] . ':00';
            $created = date('Y-m-d H:i:s', strtotime($scheduled) - 3 * 86400);
            $completed = $service['status'] === 'completed' ? date('Y-m-d H:i:s', strtotime($scheduled) + 7200) : null;
            seed_run('INSERT INTO services(customer_id,gardener_id,type,scheduled_at,address,customer_notes,gardener_notes,status,fee,completed_at,created_at) VALUES(?,?,?,?,?,?,?,?,?,?,?)',
                'iissssssdss', [$customer_id, $gardener_id, $service['type'], $scheduled, $service['address'], $service['customer_notes'], $service['gardener_notes'] ?? null, $service['status'], $service['fee'], $completed, $created]);
            $service_id = mysqli_insert_id($conn);
            $added['services']++;
        }
        if (!empty($service['rating']) && $service['status'] === 'completed') {
            $gardener_id = $user_ids['gardener'][$service['gardener']];
            seed_run('INSERT IGNORE INTO ratings(service_id,customer_id,gardener_id,stars,feedback) VALUES(?,?,?,?,?)',
                'iiiis', [$service_id, $customer_id, $gardener_id, $service['rating'][0], $service['rating'][1]]);
        }
    }
    mysqli_commit($conn);
    echo "Added {$added['users']} users, {$added['orders']} plant orders and {$added['services']} garden visits.\n";
} catch (Throwable $error) {
    mysqli_rollback($conn);
    fwrite(STDERR, 'Could not seed sample data: ' . $error->getMessage() . "\n");
    exit(1);
}