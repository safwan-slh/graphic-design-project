<?php
session_start();
require_once __DIR__ . '/../includes/db_connect.php';
require_once __DIR__ . '/../notifications/notify_helper.php';

$order_id = $_POST['order_id'] ?? null;
$message = trim($_POST['message'] ?? '');
$sender_id = $_SESSION['customer_id'] ?? null;
$sender_role = $_SESSION['role'] ?? 'customer';
$customer_id = $_SESSION['customer_id'] ?? null;

if ($message && $sender_id) {
    // ถ้าเป็นสอบถามทั่วไป (order_id = 0 หรือ null)
    if (!$order_id || $order_id == 0) {
        $stmt = $conn->prepare("INSERT INTO chat_messages (order_id, customer_id, sender_id, sender_role, message) VALUES (?, ?, ?, ?, ?)");
        $oid = 0;
        $stmt->bind_param("iiiss", $oid, $customer_id, $sender_id, $sender_role, $message);
        $stmt->execute();

        // แจ้งเตือน admin
        $msg = "ลูกค้าส่งข้อความใหม่ในแชทสอบถามทั่วไป";
        $link = "/graphic-design/src/admin/general_chat.php?customer_id=" . $customer_id;
        sendNotification($conn, 1, $msg, $link, 1, 'chat'); // 1 = แอดมิน

        echo json_encode(['success' => true]);
        exit;
    }

    // กรณีแชทตาม order ปกติ
    $stmt = $conn->prepare("INSERT INTO chat_messages (order_id, customer_id, sender_id, sender_role, message) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("iiiss", $order_id, $customer_id, $sender_id, $sender_role, $message);
    $stmt->execute();

    // --- แจ้งเตือนอีกฝั่ง ---
    $orderStmt = $conn->prepare("SELECT c.customer_id, c.fullname, o.order_code FROM orders o JOIN customers c ON o.customer_id = c.customer_id WHERE o.order_id = ?");
    $orderStmt->bind_param("i", $order_id);
    $orderStmt->execute();
    $orderRow = $orderStmt->get_result()->fetch_assoc();

    if ($sender_role === 'customer') {
        $msg = "ลูกค้าส่งข้อความใหม่ในออเดอร์ #{$orderRow['order_code']}";
        $link = "/graphic-design/src/admin/order_detail.php?id=" . $order_id;
        sendNotification($conn, 1, $msg, $link, 1, 'chat');
    } else {
        $msg = "ทีมงานส่งข้อความใหม่ในออเดอร์ #{$orderRow['order_code']}";
        $link = "/graphic-design/src/client/poster_detail.php?order_id=" . $order_id;
        sendNotification($conn, $orderRow['customer_id'], $msg, $link, 0, 'chat');
    }

    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'error' => 'ข้อมูลไม่ครบ']);
}