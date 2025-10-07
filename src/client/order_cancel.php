<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
require __DIR__ . '/../includes/db_connect.php';
require_once '../auth/auth.php';
requireLogin();


$order_id = $_GET['order_id'] ?? null;
$customer_id = $_SESSION['customer_id'] ?? null;

if (!$order_id || !$customer_id) {
    die('ไม่พบข้อมูลออเดอร์หรือไม่ได้เข้าสู่ระบบ');
}

// ตรวจสอบว่าออเดอร์นี้เป็นของลูกค้าคนนี้จริง
$stmt = $conn->prepare("SELECT * FROM orders WHERE order_id = ? AND customer_id = ?");
$stmt->bind_param("ii", $order_id, $customer_id);
$stmt->execute();
$order = $stmt->get_result()->fetch_assoc();

if (!$order) {
    die('ไม่พบออเดอร์นี้ หรือคุณไม่มีสิทธิ์ยกเลิก');
}

// อนุญาตให้ยกเลิกเฉพาะสถานะ pending หรือ in_progress
if (!in_array($order['status'], ['pending', 'in_progress'])) {
    die('ไม่สามารถยกเลิกออเดอร์นี้ได้');
}

// อัปเดตสถานะเป็น cancelled
$stmt = $conn->prepare("UPDATE orders SET status = 'cancelled' WHERE order_id = ?");
$stmt->bind_param("i", $order_id);
if ($stmt->execute()) {
    header("Location: /graphic-design/src/client/order.php?cancel=success");
    exit;
} else {
    $success = false;
    $error = $stmt->error;
}
?>
<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8">
    <title>ยกเลิกออเดอร์</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
</head>

<body class="bg-gray-100 min-h-screen flex items-center justify-center">
    <div class="bg-white rounded-xl shadow-lg p-8 max-w-md w-full text-center">
        <?php if (!empty($success)): ?>
            <div class="text-green-600 text-2xl mb-4 font-bold">ยกเลิกออเดอร์สำเร็จ</div>
            <div class="mb-6">ออเดอร์ของคุณถูกยกเลิกเรียบร้อยแล้ว</div>
            <a href="order.php" class="inline-block bg-zinc-900 text-white px-6 py-2 rounded-xl font-semibold hover:bg-zinc-800 transition">กลับไปหน้ารายการสั่งซื้อ</a>
        <?php else: ?>
            <div class="text-red-600 text-2xl mb-4 font-bold">เกิดข้อผิดพลาด</div>
            <div class="mb-6"><?= htmlspecialchars($error ?? 'ไม่สามารถยกเลิกออเดอร์ได้') ?></div>
            <a href="order.php" class="inline-block bg-zinc-900 text-white px-6 py-2 rounded-xl font-semibold hover:bg-zinc-800 transition">กลับไปหน้ารายการสั่งซื้อ</a>
        <?php endif; ?>
    </div>
</body>

</html>