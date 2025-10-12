<?php
// filepath: /Applications/MAMP/htdocs/graphic-design/src/admin/edit_review_admin.php
require_once '../includes/db_connect.php';

$review_id = $_POST['review_id'];
$rating = (int)$_POST['rating'];
$comment = trim($_POST['comment']);
$image = null;

// อัปโหลดรูปใหม่ (ถ้ามี)
if (!empty($_FILES['image']['name'])) {
    $target = $_SERVER['DOCUMENT_ROOT'] . "/graphic-design/uploads/reviews/";
    if (!is_dir($target)) mkdir($target, 0777, true);
    $filename = uniqid() . '_' . basename($_FILES['image']['name']);
    $filepath = $target . $filename;
    if (move_uploaded_file($_FILES['image']['tmp_name'], $filepath)) {
        $image = "reviews/" . $filename;
    }
}

if ($image) {
    $stmt = $conn->prepare("UPDATE reviews SET rating=?, comment=?, image=?, updated_at=NOW() WHERE id=?");
    $stmt->bind_param("isssi", $rating, $comment, $image, $review_id);
} else {
    $stmt = $conn->prepare("UPDATE reviews SET rating=?, comment=?, updated_at=NOW() WHERE id=?");
    $stmt->bind_param("isi", $rating, $comment, $review_id);
}
$stmt->execute();

header("Location: /graphic-design/src/admin/review_list.php?edit=success");
exit;