<?php
require_once __DIR__ . '/../includes/db_connect.php';
session_start();

$order_id = $_POST['order_id'] ?? null;
$customer_id = $_POST['customer_id'] ?? null;
$role = $_SESSION['role'] ?? 'customer';

if ($order_id && $role === 'customer') {
    // ลูกค้าอ่านแชท order: mark ข้อความที่ admin ส่งว่าอ่านแล้ว
    $stmt = $conn->prepare("UPDATE chat_messages SET is_read=1 WHERE order_id=? AND sender_role='admin' AND is_read=0");
    $stmt->bind_param("i", $order_id);
    $stmt->execute();
    echo json_encode(['success' => true]);
    exit;
} elseif ($order_id && $role === 'admin') {
    // แอดมินอ่านแชท order: mark ข้อความที่ลูกค้าส่งว่าอ่านแล้ว
    $stmt = $conn->prepare("UPDATE chat_messages SET is_read=1 WHERE order_id=? AND sender_role='customer' AND is_read=0");
    $stmt->bind_param("i", $order_id);
    $stmt->execute();
    echo json_encode(['success' => true]);
    exit;
} elseif ($customer_id && $role === 'admin') {
    // แอดมินอ่านแชททั่วไป: mark ข้อความที่ลูกค้าส่งว่าอ่านแล้ว
    $stmt = $conn->prepare("UPDATE chat_messages SET is_read=1 WHERE (order_id IS NULL OR order_id=0) AND customer_id=? AND sender_role='customer' AND is_read=0");
    $stmt->bind_param("i", $customer_id);
    $stmt->execute();
    echo json_encode(['success' => true]);
    exit;
} elseif ($customer_id && $role === 'customer') {
    // ลูกค้าอ่านแชททั่วไป: mark ข้อความที่ admin ส่งว่าอ่านแล้ว
    $stmt = $conn->prepare("UPDATE chat_messages SET is_read=1 WHERE (order_id IS NULL OR order_id=0) AND customer_id=? AND sender_role='admin' AND is_read=0");
    $stmt->bind_param("i", $customer_id);
    $stmt->execute();
    echo json_encode(['success' => true]);
    exit;
}

echo json_encode(['success' => false]);