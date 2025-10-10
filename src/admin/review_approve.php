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

        // ดึง customer_id ของรีวิว
        $stmt2 = $conn->prepare("SELECT customer_id, order_id FROM reviews WHERE id = ?");
        $stmt2->bind_param("i", $review_id);
        $stmt2->execute();
        $stmt2->bind_result($customer_id, $order_id);
        $stmt2->fetch();
        $stmt2->close();

        // แจ้งเตือนลูกค้า
        require_once '../notifications/notify_helper.php';
        notifyReviewApproveStatusToCustomer($conn, $customer_id, $order_id, $is_approved);
    }
    header("Location: review_list.php");
    exit;
}
