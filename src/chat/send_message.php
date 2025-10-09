<?php
session_start();
require_once __DIR__ . '/../includes/db_connect.php';
require_once __DIR__ . '/../notifications/notify_helper.php';

$order_id = $_POST['order_id'] ?? null;
$customer_id = $_POST['customer_id'] ?? ($_SESSION['customer_id'] ?? null);
$message = trim($_POST['message'] ?? '');
$sender_id = $_SESSION['admin_id'] ?? ($_SESSION['customer_id'] ?? null); // รองรับทั้ง admin/customer
$sender_role = $_SESSION['role'] ?? (isset($_SESSION['admin_id']) ? 'admin' : 'customer');

if ($message && $sender_id) {
    // แชททั่วไป (สอบถาม)
    if ((!$order_id || $order_id == 0) && $customer_id) {
        $oid = 0;
        $stmt = $conn->prepare("INSERT INTO chat_messages (order_id, customer_id, sender_id, sender_role, message) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("iiiss", $oid, $customer_id, $sender_id, $sender_role, $message);
        $stmt->execute();

        // แจ้งเตือนลูกค้า (ถ้าคนส่งคือ admin)
        if ((!$order_id || $order_id == 0) && $customer_id) {
            // ... insert message ...
            if ($sender_role === 'admin') {
                notifyChat($conn, false, $customer_id, null, null, 'general');
            } else {
                notifyChat($conn, true, $customer_id, null, null, 'general');
            }
            echo json_encode(['success' => true]);
            exit;
        }
    }

    // แชท order ปกติ
    if ($order_id) {
        $stmt = $conn->prepare("INSERT INTO chat_messages (order_id, customer_id, sender_id, sender_role, message) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("iiiss", $order_id, $customer_id, $sender_id, $sender_role, $message);
        $stmt->execute();

        // --- แจ้งเตือนอีกฝั่ง ---
        $orderStmt = $conn->prepare("SELECT c.customer_id, c.fullname, o.order_code FROM orders o JOIN customers c ON o.customer_id = c.customer_id WHERE o.order_id = ?");
        $orderStmt->bind_param("i", $order_id);
        $orderStmt->execute();
        $orderRow = $orderStmt->get_result()->fetch_assoc();

        if ($sender_role === 'customer') {
            notifyChat($conn, true, $orderRow['customer_id'], $order_id, $orderRow['order_code'], 'order');
        } else {
            notifyChat($conn, false, $orderRow['customer_id'], $order_id, $orderRow['order_code'], 'order');
        }
        echo json_encode(['success' => true]);
        exit;
    }
}

echo json_encode(['success' => false, 'error' => 'ข้อมูลไม่ครบ']);
