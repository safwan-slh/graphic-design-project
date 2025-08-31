<?php
// ตรวจสอบ error
error_reporting(E_ALL);
ini_set('display_errors', 1);

require '../includes/db_connect.php';
require '../auth/auth.php';
requireRole('admin'); // ให้เข้าหน้านี้ได้เฉพาะ admin

// ตรวจสอบว่ามี ID ที่จะลบ
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header('Location: portfolio_list.php?error=invalid_id');
    exit;
}

$portfolioId = (int)$_GET['id'];

// ดึงข้อมูลผลงานที่จะลบ (เพื่อลบไฟล์ภาพ)
$portfolio = null;
$stmt = $conn->prepare("SELECT * FROM portfolios WHERE portfolio_id = ?");
$stmt->bind_param("i", $portfolioId);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    header('Location: portfolio_list.php?error=not_found');
    exit;
}

$portfolio = $result->fetch_assoc();

// ลบไฟล์ภาพถ้ามี
if ($portfolio['image_url'] && file_exists(ROOT_PATH . $portfolio['image_url'])) {
    unlink(ROOT_PATH . $portfolio['image_url']);
}

// ลบข้อมูลจากฐานข้อมูล
$stmt = $conn->prepare("DELETE FROM portfolios WHERE portfolio_id = ?");
$stmt->bind_param("i", $portfolioId);

if ($stmt->execute()) {
    header('Location: portfolio_list.php?success=deleted');
} else {
    header('Location: portfolio_list.php?error=delete_failed');
}
exit;