<?php
require_once __DIR__ . '/../includes/db_connect.php';
session_start();

$order_id = $_POST['order_id'] ?? null;
$role = $_SESSION['role'] ?? 'customer';

if ($order_id && $role === 'customer') {
    // ลูกค้าอ่านแชท ให้ mark ข้อความที่ admin ส่งใน order นี้ว่าอ่านแล้ว
    $stmt = $conn->prepare("UPDATE chat_messages SET is_read=1 WHERE order_id=? AND sender_role='admin' AND is_read=0");
    $stmt->bind_param("i", $order_id);
    $stmt->execute();
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false]);
}