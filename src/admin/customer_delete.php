<?php
// ตรวจสอบ error
error_reporting(E_ALL);
ini_set('display_errors', 1);
require __DIR__ . '/../includes/db_connect.php';
require_once '../auth/auth.php';
requireRole('admin'); // ให้เข้าหน้านี้ได้เฉพาะ admin

// ตรวจสอบว่ามี ID ที่จะลบหรือไม่
if (!isset($_GET['id'])) {
    $toastType = 'error';
    $toastMessage = 'ไม่มี ID ลูกค้า';
    header("Location: customer_list.php?toastType=$toastType&toastMessage=" . urlencode($toastMessage));
    exit;
}

$customer_id = $_GET['id'];

// ตรวจสอบว่าลูกค้ามีอยู่จริงหรือไม่
$stmt = $conn->prepare("SELECT customer_id FROM customers WHERE customer_id = ?");
$stmt->bind_param("i", $customer_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    $toastType = 'error';
    $toastMessage = 'ไม่พบลูกค้าที่ต้องการลบ';
    header("Location: customer_list.php?toastType=$toastType&toastMessage=" . urlencode($toastMessage));
    exit;
}

// ลบลูกค้า
$stmt = $conn->prepare("DELETE FROM customers WHERE customer_id = ?");
$stmt->bind_param("i", $customer_id);
$stmt->execute();

if ($stmt->affected_rows > 0) {
    $toastType = 'success';
    $toastMessage = 'ลบลูกค้าเรียบร้อยแล้ว';
} else {
    $toastType = 'error';
    $toastMessage = 'เกิดข้อผิดพลาดในการลบข้อมูล';
}

header("Location: customer_list.php?toastType=$toastType&toastMessage=" . urlencode($toastMessage));
exit;
?>