<?php
require_once '../includes/db_connect.php';
session_start();

$order_id = $_POST['order_id'];
$customer_id = $_SESSION['customer_id'];
$rating = (int)$_POST['rating'];
$comment = trim($_POST['comment']);
$image = null;

// อัปโหลดรูป (ถ้ามี)
if (!empty($_FILES['image']['name'])) {
    // กำหนด path ฝั่งเซิร์ฟเวอร์
    $target = $_SERVER['DOCUMENT_ROOT'] . "/graphic-design/uploads/reviews/$order_id/";
    if (!is_dir($target)) mkdir($target, 0777, true);
    $filename = uniqid() . '_' . basename($_FILES['image']['name']);
    $filepath = $target . $filename;
    if (move_uploaded_file($_FILES['image']['tmp_name'], $filepath)) {
        // เก็บ path แบบ relative สำหรับแสดงผล
        $image = "reviews/$order_id/" . $filename;
    }
}

if ($order_id && $customer_id && $rating) {
    $stmt = $conn->prepare("INSERT INTO reviews (order_id, customer_id, rating, comment, image) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("iiiss", $order_id, $customer_id, $rating, $comment, $image);
    $stmt->execute();
    header("Location: /graphic-design/src/client/order_detail.php?order_id=$order_id&review=success");
    exit;
}
header("Location: /graphic-design/src/client/order_detail.php?order_id=$order_id&review=fail");
exit;