<?php
// ตรวจสอบ error
error_reporting(E_ALL);
ini_set('display_errors', 1);
require '../includes/db_connect.php';
require '../auth/auth.php';
requireRole('admin'); // ให้เข้าหน้านี้ได้เฉพาะ admin

// ตรวจสอบว่ามีการส่ง ID มาไหม
if (!isset($_GET['id'])) {
    header("Location: service_list.php");
    exit();
}

$id = intval($_GET['id']);

// ตรวจสอบว่ามีบริการนี้จริงหรือไม่
$check_stmt = $conn->prepare("SELECT service_id FROM services WHERE service_id = ?");
$check_stmt->bind_param("i", $id);
$check_stmt->execute();
$check_stmt->store_result();

if ($check_stmt->num_rows === 0) {
    // ไม่พบบริการที่จะลบ
    header("Location: service_list.php?error=service_not_found");
    exit();
}

// ลบบริการออกจากฐานข้อมูลโดยตรง (แทนที่จะใช้ soft delete)
$delete_stmt = $conn->prepare("DELETE FROM services WHERE service_id = ?");
$delete_stmt->bind_param("i", $id);

if ($delete_stmt->execute()) {
    // ลบสำเร็จ
    header("Location: service_list.php?success=service_deleted");
} else {
    // เกิดข้อผิดพลาดในการลบ
    header("Location: service_list.php?error=delete_failed");
}

exit();
?>