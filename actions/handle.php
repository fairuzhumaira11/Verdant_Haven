<?php
$conn = require dirname(__DIR__) . '/includes/db_connect.php';
require dirname(__DIR__) . '/includes/app.php';
require dirname(__DIR__) . '/includes/commerce.php';
require dirname(__DIR__) . '/includes/chat-functions.php';
require dirname(__DIR__) . '/includes/staff-documents.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit('Submit a form to use this page.');
}
$action = $_POST['action'] ?? '';
$return = local_return($_POST['return_to'] ?? '', 'index.php');
$transaction = false;
$new_nid_file = null;
try {
    check_csrf();
    switch ($action) {
        case 'theme':
            $theme = input('theme');
            if (!in_array($theme, ['light', 'dark'])) throw new Exception('Invalid theme.');
            $_SESSION['theme'] = $theme;
            setcookie('vh_theme', $theme, ['expires' => time() + 31536000, 'path' => BASE_PATH . '/', 'httponly' => true, 'samesite' => 'Lax']);
            redirect($return);
            break;
        case 'register':
            $name = input('name', 120, true);
            $email = strtolower(input('email', 190, true));
            $phone = input('phone', 25, true);
            $password = password_input();
            if (!filter_var($email, FILTER_VALIDATE_EMAIL) || !preg_match('/^01[3-9][0-9]{8}$/', $phone)) throw new Exception('Use a valid email and an 11-digit mobile number.');
            if ($password !== ($_POST['confirm_password'] ?? '')) throw new Exception('Passwords do not match.');
            if (one('SELECT id FROM users WHERE email=?', 's', [$email])) throw new Exception('An account already uses this email address.');
            run("INSERT INTO users(name,email,phone,password_hash,role) VALUES(?,?,?,?,'customer')", 'ssss', [$name, $email, $phone, password_hash($password, PASSWORD_DEFAULT)]);
            session_regenerate_id(true);
            unset($_SESSION['reset_user_id'], $_SESSION['reset_email'], $_SESSION['reset_verified_at']);
            $_SESSION['uid'] = mysqli_insert_id($conn);
            $_SESSION['csrf'] = bin2hex(random_bytes(24));
            $return = home('customer');
            break;
        case 'login':
            $login = input('login', 190, true);
            $user = one('SELECT * FROM users WHERE email=? OR phone=?', 'ss', [$login, $login]);
            $password = $_POST['password'] ?? '';
            if (!is_string($password) || !$user || !password_verify($password, $user['password_hash'])) throw new Exception('Incorrect email, phone, or password.');
            if ($user['status'] === 'Inactive') throw new Exception('This account is inactive.');
            session_regenerate_id(true);
            unset($_SESSION['reset_user_id'], $_SESSION['reset_email'], $_SESSION['reset_verified_at']);
            $_SESSION['uid'] = $user['id'];
            $_SESSION['csrf'] = bin2hex(random_bytes(24));
            $return = home($user['role']);
            break;
        case 'logout':
            unset($_SESSION['uid'], $_SESSION['checkout_key'], $_SESSION['old'], $_SESSION['reset_user_id'], $_SESSION['reset_email'], $_SESSION['reset_verified_at']);
            session_regenerate_id(true);
            $_SESSION['csrf'] = bin2hex(random_bytes(24));
            redirect('pages/auth/login.php');
            break;
        case 'forgot':
            unset($_SESSION['reset_user_id'], $_SESSION['reset_email'], $_SESSION['reset_verified_at']);
            $email = strtolower(input('email', 190, true));
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) throw new Exception('Enter a valid email.');
            $account = one('SELECT id,status FROM users WHERE email=?', 's', [$email]);
            if (!$account) throw new Exception('No account uses this email address.');
            if ($account['status'] === 'Inactive') throw new Exception('This account is inactive. Contact nursery staff.');
            session_regenerate_id(true);
            $_SESSION['csrf'] = bin2hex(random_bytes(24));
            $_SESSION['reset_user_id'] = $account['id'];
            $_SESSION['reset_email'] = $email;
            $_SESSION['reset_verified_at'] = time();
            unset($_SESSION['old']);
            flash('Account found. You can now set a new password.');
            redirect('pages/auth/reset-password.php');
            break;
        case 'reset':
            $user_id = $_SESSION['reset_user_id'] ?? 0;
            $verified_at = $_SESSION['reset_verified_at'] ?? 0;
            $email = $_SESSION['reset_email'] ?? '';
            if (!$user_id || $verified_at < time() - 600) {
                unset($_SESSION['reset_user_id'], $_SESSION['reset_email'], $_SESSION['reset_verified_at']);
                throw new Exception('Verify your email address again.');
            }
            $password = password_input();
            if ($password !== ($_POST['confirm_password'] ?? '')) throw new Exception('Passwords do not match.');
            $account = one("SELECT id FROM users WHERE id=? AND email=? AND status!='Inactive'", 'is', [$user_id, $email]);
            if (!$account) throw new Exception('This account is unavailable. Verify your email address again.');
            run('UPDATE users SET password_hash=? WHERE id=?', 'si', [password_hash($password, PASSWORD_DEFAULT), $user_id]);
            unset($_SESSION['reset_user_id'], $_SESSION['reset_email'], $_SESSION['reset_verified_at']);
            session_regenerate_id(true);
            $_SESSION['csrf'] = bin2hex(random_bytes(24));
            $return = 'pages/auth/login.php';
            break;
        case 'plant_save':
            require_roles(['admin']);
            $id = isset($_POST['id']) ? number_input('id', 0, PHP_INT_MAX) : 0;
            $name = input('name', 150, true);
            $category = input('category', 20, true);
            if (!in_array($category, ['indoor', 'outdoor', 'fruit', 'flower'])) throw new Exception('Choose a plant category.');
            $price = filter_var($_POST['price'] ?? '', FILTER_VALIDATE_FLOAT);
            if ($price === false || $price < 0 || $price > 99999999) throw new Exception('Enter a valid price.');
            $stock = number_input('stock', 0, 1000000);
            $hours = number_input('watering_hours', 1, 8760);
            $description = input('description', 5000, true);
            $care = input('care_instructions', 5000, true);
            $light = input('care_light', 80);
            $soil = input('care_soil', 80);
            $water = input('care_water', 80);
            $active = isset($_POST['active']) ? 1 : 0;
            if ($id && !one('SELECT id FROM plants WHERE id=?', 'i', [$id])) throw new Exception('Plant not found.');
            $image = photo_upload('image', 'plants');
            $care_file = photo_upload('care_file', 'care', true);
            if ($id) {
                run(
                    'UPDATE plants SET name=?,category=?,price=?,stock=?,watering_hours=?,description=?,care_instructions=?,care_light=?,care_water=?,care_soil=?,active=?,image_path=COALESCE(?,image_path),care_file_path=COALESCE(?,care_file_path) WHERE id=?',
                    'ssdiisssssissi',
                    [$name, $category, $price, $stock, $hours, $description, $care, $light, $water, $soil, $active, $image, $care_file, $id]
                );
            } else {
                run(
                    'INSERT INTO plants(name,category,price,stock,watering_hours,description,care_instructions,care_light,care_water,care_soil,active,image_path,care_file_path) VALUES(?,?,?,?,?,?,?,?,?,?,?,?,?)',
                    'ssdiisssssiss',
                    [$name, $category, $price, $stock, $hours, $description, $care, $light, $water, $soil, $active, $image, $care_file]
                );
            }
            $return = 'pages/admin/dashboard.php?view=plants';
            break;
        case 'staff_save':
            require_roles(['admin']);
            $id = isset($_POST['id']) ? number_input('id', 0, PHP_INT_MAX) : 0;
            $name = input('name', 120, true);
            $email = strtolower(input('email', 190, true));
            $phone = input('phone', 25, true);
            $role = input('role', 20, true);
            $zone = input('zone', 100);
            $status = input('status', 30, true);
            $salary = filter_var($_POST['salary'] ?? '', FILTER_VALIDATE_FLOAT);
            if (!in_array($role, ['staff', 'gardener']) || !filter_var($email, FILTER_VALIDATE_EMAIL) || !preg_match('/^01[3-9][0-9]{8}$/', $phone) || $salary === false || $salary < 0 || $salary > 99999999) throw new Exception('Check the role, email, phone number, and salary.');
            if (!in_array($status, ['Available', 'On Visit', 'Active Desk', 'On Leave', 'Inactive'])) throw new Exception('Choose a valid duty status.');
            if (one('SELECT id FROM users WHERE email=? AND id<>?', 'si', [$email, $id])) throw new Exception('An account already uses this email address.');
            $password = password_input(!$id);
            $new_nid_file = nid_upload('nid_file');
            if ($id) {
                mysqli_begin_transaction($conn);
                $transaction = true;
                $staff = one("SELECT id,role,nid_file_path FROM users WHERE id=? AND role IN ('staff','gardener') FOR UPDATE", 'i', [$id]);
                if (!$staff) throw new Exception('Staff member not found.');
                if ($staff['role'] === 'gardener' && $role !== 'gardener' && one("SELECT id FROM services WHERE gardener_id=? AND status='assigned' LIMIT 1", 'i', [$id])) throw new Exception('This gardener has open visits. Reassign them before changing the role.');
                run('UPDATE users SET name=?,email=?,phone=?,role=?,zone=?,salary=?,status=?,nid_file_path=COALESCE(?,nid_file_path) WHERE id=?', 'sssssdssi', [$name, $email, $phone, $role, $zone, $salary, $status, $new_nid_file, $id]);
                if ($password !== '') run('UPDATE users SET password_hash=? WHERE id=?', 'si', [password_hash($password, PASSWORD_DEFAULT), $id]);
                mysqli_commit($conn);
                $transaction = false;
                if ($new_nid_file && $staff['nid_file_path']) {
                    $old_nid = nid_disk_path($staff['nid_file_path']);
                    if ($old_nid) @unlink($old_nid);
                }
            } else {
                run('INSERT INTO users(name,email,phone,password_hash,role,zone,salary,status,nid_file_path) VALUES(?,?,?,?,?,?,?,?,?)', 'ssssssdss', [$name, $email, $phone, password_hash($password, PASSWORD_DEFAULT), $role, $zone, $salary, $status, $new_nid_file]);
            }
            $return = 'pages/admin/dashboard.php?view=staff';
            break;
        case 'stock':
            require_roles(['admin', 'staff']);
            $id = number_input('id', 1, PHP_INT_MAX);
            $stock = number_input('stock', 0, 1000000);
            if (!one('SELECT id FROM plants WHERE id=?', 'i', [$id])) throw new Exception('Plant not found.');
            run('UPDATE plants SET stock=? WHERE id=?', 'ii', [$stock, $id]);
            break;
        case 'wishlist':
            $user = require_roles(['customer']);
            $id = number_input('id', 1, PHP_INT_MAX);
            if (!one('SELECT id FROM plants WHERE id=? AND active=1', 'i', [$id])) throw new Exception('Plant not found.');
            if (one('SELECT plant_id FROM wishlist WHERE customer_id=? AND plant_id=?', 'ii', [$user['id'], $id])) run('DELETE FROM wishlist WHERE customer_id=? AND plant_id=?', 'ii', [$user['id'], $id]);
            else run('INSERT IGNORE INTO wishlist(customer_id,plant_id) VALUES(?,?)', 'ii', [$user['id'], $id]);
            break;
        case 'cart_add':
        case 'cart_update':
            $user = require_roles(['customer']);
            $id = number_input('id', 1, PHP_INT_MAX);
            $quantity = number_input('quantity', $action === 'cart_add' ? 1 : 0, 100);
            mysqli_begin_transaction($conn);
            $transaction = true;
            $plant = one('SELECT stock,active FROM plants WHERE id=? FOR UPDATE', 'i', [$id]);
            if (!$plant) throw new Exception('Plant not found.');
            $current = one('SELECT quantity FROM cart_items WHERE customer_id=? AND plant_id=? FOR UPDATE', 'ii', [$user['id'], $id]);
            if ($action === 'cart_add') $quantity += (int)($current['quantity'] ?? 0);
            if ($quantity > 0 && (!$plant['active'] || $quantity > $plant['stock'] || $quantity > 100)) throw new Exception('That quantity is not available.');
            if ($quantity === 0) run('DELETE FROM cart_items WHERE customer_id=? AND plant_id=?', 'ii', [$user['id'], $id]);
            else run('INSERT INTO cart_items(customer_id,plant_id,quantity) VALUES(?,?,?) ON DUPLICATE KEY UPDATE quantity=VALUES(quantity)', 'iii', [$user['id'], $id, $quantity]);
            mysqli_commit($conn);
            $transaction = false;
            break;
        case 'checkout':
            $user = require_roles(['customer']);
            $address = input('address', 500, true);
            $method = input('payment_method', 10, true);
            if (!in_array($method, ['cod', 'bkash', 'nagad'])) throw new Exception('Choose a payment method.');
            $key = input('checkout_key', 64, true);
            if (!hash_equals($_SESSION['checkout_key'] ?? '', $key)) throw new Exception('Checkout was already submitted. Refresh your cart.');
            if ($method !== 'cod') {
                $payment = start_payment($user['id'], $method, $address);
                unset($_SESSION['checkout_key']);
                redirect('pages/payments/dummy.php?payment_id=' . rawurlencode($payment['payment_id']));
            }
            mysqli_begin_transaction($conn);
            $transaction = true;
            $items = cart_items($user['id'], true);
            $order_id = place_order($user['id'], $items, $address, $method, $key);
            mysqli_commit($conn);
            $transaction = false;
            unset($_SESSION['checkout_key']);
            $return = 'pages/customer/order-confirmed.php?id=' . $order_id;
            break;
        case 'payment_execute':
            $user = require_roles(['customer']);
            $payment = finish_payment($user['id'], input('payment_id', 80, true), input('outcome', 10, true), input('wallet', 11));
            if ($payment['status'] === 'success') {
                $return = 'pages/customer/order-confirmed.php?id=' . $payment['order_id'];
            } else {
                flash('Dummy payment ' . $payment['status'] . '. Your cart and stock were not changed.', 'error');
                redirect('pages/customer/checkout.php');
            }
            break;
        case 'order_status':
            require_roles(['staff', 'admin']);
            $id = number_input('id', 1, PHP_INT_MAX);
            $status = input('status', 20, true);
            mysqli_begin_transaction($conn);
            $transaction = true;
            $order = one('SELECT status FROM orders WHERE id=? FOR UPDATE', 'i', [$id]);
            $allowed = ['placed' => ['processing', 'cancelled'], 'processing' => ['dispatched', 'cancelled'], 'dispatched' => ['delivered']];
            if (!$order || !in_array($status, $allowed[$order['status']] ?? [])) throw new Exception('Invalid order status change. Refresh and try again.');
            run('UPDATE orders SET status=? WHERE id=?', 'si', [$status, $id]);
            if ($status === 'cancelled') {
                $items = rows('SELECT plant_id,quantity FROM order_items WHERE order_id=? ORDER BY plant_id', 'i', [$id]);
                foreach ($items as $item) run('UPDATE plants SET stock=stock+? WHERE id=?', 'ii', [$item['quantity'], $item['plant_id']]);
            }
            mysqli_commit($conn);
            $transaction = false;
            break;
        case 'service_book':
            $user = require_roles(['customer']);
            $type = input('type', 20, true);
            $fees = ['WATERING' => 1000, 'TRIMMING' => 900, 'REPOTTING' => 1200, 'MAINTENANCE' => 2500];
            if (!isset($fees[$type])) throw new Exception('Choose a garden service.');
            $when = input('scheduled_at', 16, true);
            $time = strtotime($when);
            if (!$time || $time <= time() || date('Y-m-d\\TH:i', $time) !== $when) throw new Exception('Choose a valid future visit date and time.');
            run('INSERT INTO services(customer_id,type,scheduled_at,address,customer_notes,fee) VALUES(?,?,?,?,?,?)', 'issssd', [$user['id'], $type, date('Y-m-d H:i:s', $time), input('address', 500, true), input('notes', 2000), $fees[$type]]);
            $return = 'pages/customer/orders.php?tab=services';
            break;
        case 'service_assign':
            require_roles(['admin', 'staff']);
            $id = number_input('id', 1, PHP_INT_MAX);
            $gardener_id = number_input('gardener_id', 1, PHP_INT_MAX);
            mysqli_begin_transaction($conn);
            $transaction = true;
            $gardener = one("SELECT id FROM users WHERE id=? AND role='gardener' AND status IN ('Available','On Visit') FOR UPDATE", 'i', [$gardener_id]);
            $service = one("SELECT * FROM services WHERE id=? AND status IN ('requested','assigned') FOR UPDATE", 'i', [$id]);
            if (!$gardener || !$service) throw new Exception('Choose an available gardener and an open service request.');
            $start = date('Y-m-d H:i:s', strtotime($service['scheduled_at']) - 7200);
            $end = date('Y-m-d H:i:s', strtotime($service['scheduled_at']) + 7200);
            if (one("SELECT id FROM services WHERE gardener_id=? AND scheduled_at>? AND scheduled_at<? AND status='assigned' AND id!=?", 'issi', [$gardener_id, $start, $end, $id])) throw new Exception('That gardener is booked near this visit time. Choose another gardener.');
            run("UPDATE services SET gardener_id=?,status='assigned' WHERE id=?", 'ii', [$gardener_id, $id]);
            mysqli_commit($conn);
            $transaction = false;
            break;
        case 'service_complete':
            $user = require_roles(['gardener']);
            $id = number_input('id', 1, PHP_INT_MAX);
            mysqli_begin_transaction($conn);
            $transaction = true;
            $service = one("SELECT id FROM services WHERE id=? AND gardener_id=? AND status='assigned' FOR UPDATE", 'ii', [$id, $user['id']]);
            if (!$service) throw new Exception('Assigned visit not found or already completed.');
            $notes = input('notes', 5000, true);
            $photo = photo_upload('photo', 'visits');
            run("UPDATE services SET status='completed',gardener_notes=?,photo_path=?,completed_at=NOW() WHERE id=?", 'ssi', [$notes, $photo, $id]);
            mysqli_commit($conn);
            $transaction = false;
            break;
        case 'rate':
            $user = require_roles(['customer']);
            $id = number_input('service_id', 1, PHP_INT_MAX);
            $stars = number_input('stars', 1, 5);
            $service = one("SELECT gardener_id FROM services WHERE id=? AND customer_id=? AND status='completed'", 'ii', [$id, $user['id']]);
            if (!$service || !$service['gardener_id']) throw new Exception('Completed visit not found.');
            run('INSERT INTO ratings(service_id,customer_id,gardener_id,stars,feedback) VALUES(?,?,?,?,?)', 'iiiis', [$id, $user['id'], $service['gardener_id'], $stars, input('feedback', 2000)]);
            break;
        case 'message_send':
            $user = require_roles(['customer', 'staff', 'gardener']);
            chat_send($user, number_input('contactId', 1, PHP_INT_MAX), input('message', 2000, true));
            break;
        case 'watered':
            $user = require_roles(['customer']);
            $item_id = number_input('order_item_id', 1, PHP_INT_MAX);
            mysqli_begin_transaction($conn);
            $transaction = true;
            $plant = one("SELECT r.id,r.last_watered_at,p.watering_hours FROM reminders r JOIN order_items oi ON oi.id=r.order_item_id JOIN orders o ON o.id=oi.order_id JOIN plants p ON p.id=r.plant_id
            WHERE r.order_item_id=? AND r.customer_id=? AND o.customer_id=? AND o.status!='cancelled' FOR UPDATE", 'iii', [$item_id, $user['id'], $user['id']]);
            if (!$plant) throw new Exception('Purchased plant not found.');
            if (input('schedule_version', 30) !== ($plant['last_watered_at'] ?? 'never')) throw new Exception('Watering was already recorded. Refresh your dashboard.');
            $now = date('Y-m-d H:i:s');
            run('UPDATE reminders SET last_watered_at=?,due_at=?,read_at=NULL WHERE id=?', 'ssi', [$now, watering_due($now, $plant['watering_hours']), $plant['id']]);
            mysqli_commit($conn);
            $transaction = false;
            flash('Watering recorded. Your next reminder has been scheduled.');
            redirect('pages/customer/dashboard.php#watering');
            break;
        default:
            throw new Exception('Unknown form action.');
    }
    unset($_SESSION['old']);
    flash('Saved successfully.');
    redirect($return);
} catch (Throwable $error) {
    if ($transaction) mysqli_rollback($conn);
    if ($new_nid_file) {
        $uploaded_nid = nid_disk_path($new_nid_file);
        if ($uploaded_nid) @unlink($uploaded_nid);
    }
    $message = $error->getMessage();
    if ($error instanceof mysqli_sql_exception) {
        error_log($message);
        $duplicates = ['register' => 'An account already uses this email or phone number.', 'staff_save' => 'Another account uses this email or phone number.', 'rate' => 'You have already rated this visit.', 'checkout' => 'This checkout has already been completed. Open your orders.'];
        $message = $error->getCode() === 1062 ? ($duplicates[$action] ?? 'This record already exists.') : 'Could not save your changes. Please try again.';
    } elseif (!($error instanceof Exception)) {
        error_log($message);
        $message = 'Could not complete this request. Please try again.';
    }
    $_SESSION['old'] = $_POST;
    unset($_SESSION['old']['password'], $_SESSION['old']['confirm_password'], $_SESSION['old']['csrf']);
    flash($message, 'error');
    redirect($return);
}
