<?php
require_once __DIR__ . '/../includes/db_connect.php';
session_start();

$order_id = $_POST['order_id'] ?? null;
$customer_id = $_SESSION['customer_id'] ?? null;

if ($order_id && $customer_id) {
    // ลบเฉพาะ order ที่ยังไม่มี payment
    $sql = "DELETE FROM orders WHERE order_id = ? AND customer_id = ? AND order_id NOT IN (SELECT order_id FROM payments)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $order_id, $customer_id);
    $stmt->execute();
}
header("Location: /graphic-design/src/client/order.php");
exit;