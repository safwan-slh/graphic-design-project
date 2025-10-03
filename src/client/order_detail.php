<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
require __DIR__ . '/../includes/db_connect.php';
require_once '../auth/auth.php';
requireLogin();

$order_id = $_GET['order_id'] ?? null;
if (!$order_id) {
    die('ไม่พบ order_id');
}

// ดึงข้อมูล order
$stmt = $conn->prepare("SELECT o.*, s.service_name, s.slug FROM orders o LEFT JOIN services s ON o.service_id = s.service_id WHERE o.order_id = ?");
$stmt->bind_param("i", $order_id);
$stmt->execute();
$order = $stmt->get_result()->fetch_assoc();

if (!$order) {
    die('ไม่พบออเดอร์นี้');
}

// ตรวจสอบประเภทบริการ แล้ว include ไฟล์ที่เกี่ยวข้อง
switch ($order['service_id']) {
    case 1:
        include 'poster_detail.php';
        break;
    case 2:
        include 'logo_detail.php';
        break;
    // เพิ่ม case สำหรับบริการอื่น ๆ
    default:
        echo "ยังไม่รองรับบริการนี้";
}
?>