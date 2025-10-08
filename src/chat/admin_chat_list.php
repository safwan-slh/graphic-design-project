<?php
require_once __DIR__ . '/../includes/db_connect.php';

$totalUnread = 0;
// แชททั่วไป (order_id = 0 หรือ NULL)
$generalChats = [];
$sql = "SELECT cm.customer_id, cu.fullname, MAX(cm.created_at) as last_msg,
        SUM(CASE WHEN cm.sender_role='customer' AND cm.is_read=0 THEN 1 ELSE 0 END) as unread
        FROM chat_messages cm
        JOIN customers cu ON cm.customer_id = cu.customer_id
        WHERE (cm.order_id IS NULL OR cm.order_id=0)
        GROUP BY cm.customer_id
        ORDER BY last_msg DESC";
$result = $conn->query($sql);
while ($row = $result->fetch_assoc()) {
    $generalChats[] = $row;
    $totalUnread += $row['unread'];
}

// แชทตาม order
$orderChats = [];
$sql = "SELECT o.order_id, o.order_code, cu.customer_id, cu.fullname, MAX(cm.created_at) as last_msg,
        SUM(CASE WHEN cm.sender_role='customer' AND cm.is_read=0 THEN 1 ELSE 0 END) as unread
        FROM chat_messages cm
        JOIN orders o ON cm.order_id = o.order_id
        JOIN customers cu ON o.customer_id = cu.customer_id
        GROUP BY o.order_id
        ORDER BY last_msg DESC";
$result = $conn->query($sql);
while ($row = $result->fetch_assoc()) {
    $orderChats[] = $row;
    $totalUnread += $row['unread'];
}

echo json_encode([
    'success' => true,
    'generalChats' => $generalChats,
    'orderChats' => $orderChats,
    'totalUnread' => $totalUnread
]);