<?php
// filepath: /Applications/MAMP/htdocs/graphic-design/src/admin/delete_review.php
require_once '../includes/db_connect.php';
require_once '../auth/auth.php';
requireRole('admin');

$review_id = $_GET['review_id'] ?? null;

if ($review_id) {
    // ลบไฟล์รูปรีวิว (ถ้ามี)
    $stmt = $conn->prepare("SELECT image FROM reviews WHERE id = ?");
    $stmt->bind_param("i", $review_id);
    $stmt->execute();
    $stmt->bind_result($image);
    $stmt->fetch();
    $stmt->close();

    if ($image && file_exists($_SERVER['DOCUMENT_ROOT'] . '/graphic-design/uploads/' . $image)) {
        unlink($_SERVER['DOCUMENT_ROOT'] . '/graphic-design/uploads/' . $image);
    }

    // ลบรีวิวออกจากฐานข้อมูล
    $stmt = $conn->prepare("DELETE FROM reviews WHERE id = ?");
    $stmt->bind_param("i", $review_id);
    $stmt->execute();
}

header("Location: review_list.php?delete=success");
exit;