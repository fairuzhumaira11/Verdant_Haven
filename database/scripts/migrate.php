<?php
// Run once after updating an existing installation. Safe to run again.
if (PHP_SAPI !== 'cli') { http_response_code(403); exit('Run this file from the terminal.'); }
require dirname(__DIR__, 2) . '/includes/db_connect.php';

function add_column($table, $column, $definition) {
    global $conn;
    $result = mysqli_query($conn, "SHOW COLUMNS FROM $table LIKE '$column'");
    if (mysqli_num_rows($result) > 0) return false;
    mysqli_query($conn, "ALTER TABLE $table ADD COLUMN $column $definition");
    echo "Added $table.$column\n";
    return true;
}
try {
    if (add_column('plants', 'watering_hours', 'INT UNSIGNED NOT NULL DEFAULT 168')) {
        $legacy = mysqli_query($conn, "SHOW COLUMNS FROM plants LIKE 'reminder_days'");
        if (mysqli_num_rows($legacy)) mysqli_query($conn, 'UPDATE plants SET watering_hours=GREATEST(reminder_days,1)*24');
    }
    add_column('orders', 'checkout_key', 'CHAR(64) DEFAULT NULL UNIQUE');
    add_column('reminders', 'order_item_id', 'BIGINT UNSIGNED DEFAULT NULL UNIQUE');
    add_column('reminders', 'last_watered_at', 'DATETIME DEFAULT NULL');
    if (add_column('messages', 'read_at', 'DATETIME DEFAULT NULL')) {
        mysqli_query($conn, 'UPDATE messages SET read_at=created_at WHERE read_at IS NULL');
    }

    $constraint = mysqli_query($conn,"SELECT COUNT(*) count FROM information_schema.KEY_COLUMN_USAGE WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='reminders' AND COLUMN_NAME='order_item_id' AND REFERENCED_TABLE_NAME='order_items'");
    if (!mysqli_fetch_assoc($constraint)['count']) mysqli_query($conn,'ALTER TABLE reminders ADD FOREIGN KEY(order_item_id) REFERENCES order_items(id)');
    mysqli_query($conn, "CREATE TABLE IF NOT EXISTS payments (
        id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        payment_id VARCHAR(80) NOT NULL UNIQUE,
        customer_id BIGINT UNSIGNED NOT NULL,
        order_id BIGINT UNSIGNED DEFAULT NULL UNIQUE,
        provider ENUM('bkash','nagad') NOT NULL,
        amount DECIMAL(10,2) NOT NULL,
        status ENUM('created','success','failed','cancelled') NOT NULL DEFAULT 'created',
        transaction_id VARCHAR(80) DEFAULT NULL UNIQUE,
        wallet_last4 CHAR(4) DEFAULT NULL,
        cart_hash CHAR(64) DEFAULT NULL,
        address VARCHAR(500) DEFAULT NULL,
        created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        completed_at DATETIME DEFAULT NULL,
        FOREIGN KEY(customer_id) REFERENCES users(id),
        FOREIGN KEY(order_id) REFERENCES orders(id)
    ) ENGINE=InnoDB");
    echo "Migration complete. Existing accounts, orders and care instructions are preserved.\n";
} catch (mysqli_sql_exception $error) {
    fwrite(STDERR, "Migration failed: " . $error->getMessage() . "\n");
    exit(1);
}
