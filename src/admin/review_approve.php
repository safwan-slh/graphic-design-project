<?php
// filepath: /Applications/MAMP/htdocs/graphic-design/src/admin/review_approve.php
require_once '../includes/db_connect.php';
require_once '../auth/auth.php';
requireRole('admin');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $review_id = $_POST['review_id'] ?? null;
    $is_approved = isset($_POST['is_approved']) ? (int)$_POST['is_approved'] : null;

    if ($review_id !== null && $is_approved !== null) {
        $stmt = $conn->prepare("UPDATE reviews SET is_approved = ? WHERE id = ?");
        $stmt->bind_param("ii", $is_approved, $review_id);
        $stmt->execute();
    }
    header("Location: review_list.php");
    exit;
}
