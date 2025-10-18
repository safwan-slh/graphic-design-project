<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);
require __DIR__ . '/../includes/db_connect.php';
require_once '../auth/auth.php';
requireLogin();

if (!isset($_SESSION['customer_id'])) {
    http_response_code(403);
    exit;
}

$customer_id = $_SESSION['customer_id'];
$fullname = trim($_POST['fullname']);
$phone = trim($_POST['phone']);

// อัปเดทข้อมูล
$stmt = $conn->prepare("UPDATE customers SET fullname=?, phone=? WHERE customer_id=?");
$stmt->bind_param("ssi", $fullname, $phone, $customer_id);
if ($stmt->execute()) {
    // กลับไปหน้าเดิมหรือ reload modal
    header("Location: " . $_SERVER['HTTP_REFERER']);
    exit;
} else {
    echo "เกิดข้อผิดพลาด: " . $stmt->error;
}
?>