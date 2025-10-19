<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
error_reporting(E_ALL);
ini_set('display_errors', 1);
require __DIR__ . '/../includes/db_connect.php';
require_once '../auth/auth.php';
requireLogin();

$customer_id = $_SESSION['customer_id'];
$current = $_POST['current_password'];
$new = $_POST['new_password'];
$confirm = $_POST['confirm_password'];

if ($new !== $confirm) {
    // แจ้งเตือนรหัสผ่านใหม่ไม่ตรงกัน
    exit('รหัสผ่านใหม่ไม่ตรงกัน');
}

$stmt = $conn->prepare("SELECT password FROM customers WHERE customer_id=?");
$stmt->bind_param("i", $customer_id);
$stmt->execute();
$stmt->bind_result($hash);
$stmt->fetch();
$stmt->close();

if (!password_verify($current, $hash)) {
    // แจ้งเตือนรหัสผ่านเดิมไม่ถูกต้อง
    exit('รหัสผ่านเดิมไม่ถูกต้อง');
}

$new_hash = password_hash($new, PASSWORD_DEFAULT);
$stmt = $conn->prepare("UPDATE customers SET password=? WHERE customer_id=?");
$stmt->bind_param("si", $new_hash, $customer_id);
if ($stmt->execute()) {
    // กลับไปหน้าเดิมหรือ reload modal
    header("Location: " . $_SERVER['HTTP_REFERER']);
    exit;
} else {
    echo "เกิดข้อผิดพลาด: " . $stmt->error;
}
?>