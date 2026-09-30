<?php
// One repeating watering schedule per purchased order item.
// Calculate the next watering date using the plant's current hour interval.
function watering_due($start, $hours)
{
    return date('Y-m-d H:i:s', strtotime($start) + (int)$hours * 3600);
}
function customer_reminders($customer_id)
{
    $plants = rows("SELECT oi.id order_item_id,oi.quantity,p.id plant_id,p.name,p.image_path,p.care_instructions,p.care_file_path,p.watering_hours,o.created_at,r.last_watered_at,r.id reminder_id
        FROM order_items oi JOIN orders o ON o.id=oi.order_id JOIN plants p ON p.id=oi.plant_id
        LEFT JOIN reminders r ON r.order_item_id=oi.id
        WHERE o.customer_id=? AND o.status!='cancelled' ORDER BY oi.id DESC", 'i', [$customer_id]);
    foreach ($plants as &$plant) {
        $start = $plant['last_watered_at'] ?: $plant['created_at'];
        $plant['due_at'] = watering_due($start, $plant['watering_hours']);
        $plant['overdue'] = strtotime($plant['due_at']) <= time();
        if (!$plant['reminder_id']) {
            // This also creates schedules for purchases made before the migration.
            run('INSERT IGNORE INTO reminders(customer_id,plant_id,order_item_id,due_at) VALUES(?,?,?,?)', 'iiis', [$customer_id, $plant['plant_id'], $plant['order_item_id'], $plant['due_at']]);
        } else {
            run('UPDATE reminders SET due_at=? WHERE id=? AND last_watered_at<=>?', 'sis', [$plant['due_at'], $plant['reminder_id'], $plant['last_watered_at']]);
        }
    }
    unset($plant);
    return $plants;
}
