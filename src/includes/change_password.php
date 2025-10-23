<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require __DIR__ . '/../includes/db_connect.php';
require_once '../auth/auth.php';
requireLogin();

$customer_id = $_SESSION['customer_id'];
$current = $_POST['current_password'];
$new = $_POST['new_password'];
$confirm = $_POST['confirm_password'];

if ($new !== $confirm) {
    $_SESSION['change_password_error'] = 'รหัสผ่านใหม่ไม่ตรงกัน';
    header("Location: " . ($_SERVER['HTTP_REFERER'] ?? '/'));
    exit;
}

$stmt = $conn->prepare("SELECT password FROM customers WHERE customer_id=?");
$stmt->bind_param("i", $customer_id);
$stmt->execute();
$stmt->bind_result($hash);
$stmt->fetch();
$stmt->close();

if (!password_verify($current, $hash)) {
    $_SESSION['change_password_error'] = 'รหัสผ่านเดิมไม่ถูกต้อง';
    header("Location: " . ($_SERVER['HTTP_REFERER'] ?? '/'));
    exit;
}

$new_hash = password_hash($new, PASSWORD_DEFAULT);
$stmt = $conn->prepare("UPDATE customers SET password=? WHERE customer_id=?");
$stmt->bind_param("si", $new_hash, $customer_id);
if ($stmt->execute()) {
    $_SESSION['change_password_success'] = 'เปลี่ยนรหัสผ่านสำเร็จ';
    header("Location: " . ($_SERVER['HTTP_REFERER'] ?? '/'));
    exit;
} else {
    $_SESSION['change_password_error'] = "เกิดข้อผิดพลาด: " . $stmt->error;
    header("Location: " . ($_SERVER['HTTP_REFERER'] ?? '/'));
    exit;
}
?>