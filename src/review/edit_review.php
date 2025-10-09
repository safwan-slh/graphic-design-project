<?php
require_once '../includes/db_connect.php';
session_start();

$review_id = $_POST['review_id'];
$order_id = $_POST['order_id'];
$rating = (int)$_POST['rating'];
$comment = trim($_POST['comment']);
$image = null;

// อัปโหลดรูปใหม่ (ถ้ามี)
if (!empty($_FILES['image']['name'])) {
    $target = $_SERVER['DOCUMENT_ROOT'] . "/graphic-design/uploads/reviews/$order_id/";
    if (!is_dir($target)) mkdir($target, 0777, true);
    $filename = uniqid() . '_' . basename($_FILES['image']['name']);
    $filepath = $target . $filename;
    if (move_uploaded_file($_FILES['image']['tmp_name'], $filepath)) {
        $image = "reviews/$order_id/" . $filename;
    }
}

if ($image) {
    $stmt = $conn->prepare("UPDATE reviews SET rating=?, comment=?, image=?, updated_at=NOW() WHERE id=? AND customer_id=?");
    $stmt->bind_param("issii", $rating, $comment, $image, $review_id, $_SESSION['customer_id']);
} else {
    $stmt = $conn->prepare("UPDATE reviews SET rating=?, comment=?, updated_at=NOW() WHERE id=? AND customer_id=?");
    $stmt->bind_param("isii", $rating, $comment, $review_id, $_SESSION['customer_id']);
}
$stmt->execute();

header("Location: /graphic-design/src/client/poster_detail.php?order_id=$order_id");
exit;