<?php
require_once __DIR__ . '/../includes/db_connect.php';
session_start();

$order_id = $_GET['order_id'] ?? null;
$customer_id = $_SESSION['customer_id'] ?? null;

if ($order_id !== null) {
    if ($order_id == 0 && $customer_id) {
        // แชททั่วไป
        $stmt = $conn->prepare("SELECT * FROM chat_messages WHERE (order_id IS NULL OR order_id=0) AND customer_id=? ORDER BY created_at ASC");
        $stmt->bind_param("i", $customer_id);
    } else {
        // แชทตาม order
        $stmt = $conn->prepare("SELECT * FROM chat_messages WHERE order_id = ? ORDER BY created_at ASC");
        $stmt->bind_param("i", $order_id);
    }
    $stmt->execute();
    $result = $stmt->get_result();
    $messages = [];
    while ($row = $result->fetch_assoc()) {
        $messages[] = $row;
    }
    $stmt->close();
    echo json_encode(['success' => true, 'messages' => $messages]);
    exit;
}
echo json_encode(['success' => false, 'error' => 'ไม่พบ order_id']);