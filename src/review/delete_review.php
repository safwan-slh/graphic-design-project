<?php
// filepath: /Applications/MAMP/htdocs/graphic-design/src/review/delete_review.php
require_once __DIR__ . '/../includes/db_connect.php';
session_start();

$review_id = $_GET['review_id'] ?? null;
$order_id = $_GET['order_id'] ?? null;
$customer_id = $_SESSION['customer_id'] ?? null;

if (!$review_id || !$order_id || !$customer_id) {
    header("Location: /graphic-design/src/client/poster_detail.php?order_id=$order_id&review=fail");
    exit;
}

// ตรวจสอบสิทธิ์: ให้ลบได้เฉพาะเจ้าของรีวิว
$stmt = $conn->prepare("SELECT * FROM reviews WHERE id=? AND customer_id=?");
$stmt->bind_param("ii", $review_id, $customer_id);
$stmt->execute();
$review = $stmt->get_result()->fetch_assoc();

if (!$review) {
    header("Location: /graphic-design/src/client/poster_detail.php?order_id=$order_id&review=fail");
    exit;
}

// ลบรีวิว (hard delete)
$stmt = $conn->prepare("DELETE FROM reviews WHERE id=? AND customer_id=?");
$stmt->bind_param("ii", $review_id, $customer_id);
$stmt->execute();

header("Location: /graphic-design/src/client/poster_detail.php?order_id=$order_id&review=deleted");
exit;