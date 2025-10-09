<?php
// filepath: /Applications/MAMP/htdocs/graphic-design/src/notifications/cancel_order.php
require_once __DIR__ . '/../includes/db_connect.php';
require_once __DIR__ . '/notify_helper.php';
session_start();

$order_id = $_GET['order_id'] ?? null;
$customer_id = $_SESSION['customer_id'] ?? null;

if ($order_id && $customer_id) {
    // อัปเดตสถานะออเดอร์
    $stmt = $conn->prepare("UPDATE orders SET status = 'cancelled' WHERE order_id = ? AND customer_id = ?");
    $stmt->bind_param("ii", $order_id, $customer_id);
    $stmt->execute();

    // ดึง order_code
    $stmtOrder = $conn->prepare("SELECT order_code FROM orders WHERE order_id = ?");
    $stmtOrder->bind_param("i", $order_id);
    $stmtOrder->execute();
    $order = $stmtOrder->get_result()->fetch_assoc();
    $orderCode = $order['order_code'] ?? $order_id;

    // เรียกใช้ฟังก์ชันแจ้งเตือน admin
    notifyOrderCancelledToAdmin($conn, $order_id, $orderCode);

    header("Location: /graphic-design/src/client/order.php?cancel=success");
    exit;
}
header("Location: /graphic-design/src/client/order.php?cancel=fail");
exit;