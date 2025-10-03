<?php
require __DIR__ . '/../includes/db_connect.php';
require_once '../auth/auth.php';
requireRole('admin');

$order_id = $_GET['id'] ?? '';
if (!$order_id) {
    echo "ไม่พบออเดอร์นี้";
    exit;
}

// ดึงข้อมูล order, customer, service, payment
$sql = "SELECT o.*, c.fullname, c.email, s.service_name, s.slug, p.amount, p.payment_type, p.payment_status, p.slip_file
        FROM orders o
        LEFT JOIN customers c ON o.customer_id = c.customer_id
        LEFT JOIN services s ON o.service_id = s.service_id
        LEFT JOIN payments p ON o.order_id = p.order_id
        WHERE o.order_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $order_id);
$stmt->execute();
$order = $stmt->get_result()->fetch_assoc();

if (!$order) {
    echo "ไม่พบออเดอร์นี้";
    exit;
}

// ดึง deadline จากตาราง detail เฉพาะบริการ (ตัวอย่างสำหรับ poster)
$deadline = $order['due_date'] ?? '';
if ($order['slug'] === 'poster-design') {
    $sqlPoster = "SELECT due_date FROM poster_details WHERE poster_id = ?";
    $stmtPoster = $conn->prepare($sqlPoster);
    $stmtPoster->bind_param("i", $order_id);
    $stmtPoster->execute();
    $posterDetail = $stmtPoster->get_result()->fetch_assoc();
    if ($posterDetail && $posterDetail['due_date']) {
        $deadline = $posterDetail['due_date'];
    }
}
// ดึงรายละเอียดเพิ่มเติมสำหรับบริการเฉพาะ (เช่น poster)
$posterData = [];
if ($order['slug'] === 'poster-design') {
    $sqlPoster = "SELECT * FROM poster_details WHERE poster_id = ?";
    $stmtPoster = $conn->prepare($sqlPoster);
    $stmtPoster->bind_param("i", $order_id);
    $stmtPoster->execute();
    $resultPoster = $stmtPoster->get_result();
    if ($resultPoster && $resultPoster->num_rows > 0) {
        $posterData = $resultPoster->fetch_assoc();
    }
}
// อัพเดตสถานะออเดอร์
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['order_status'])) {
    $newStatus = $_POST['order_status'];
    $updateSql = "UPDATE orders SET status = ? WHERE order_id = ?";
    $updateStmt = $conn->prepare($updateSql);
    $updateStmt->bind_param("si", $newStatus, $order_id);
    $updateStmt->execute();
    // รีเฟรชหน้าเพื่อแสดงสถานะใหม่
    header("Location: order_detail.php?id=$order_id");
    exit;
}
?>

<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8">
    <title>รายละเอียดออเดอร์ #<?= htmlspecialchars($order['order_id']) ?></title>
    <link href="https://cdn.tailwindcss.com" rel="stylesheet">
</head>

<body class="font-thai bg-zinc-100">
    <div class="max-w-3xl mx-auto mt-10 bg-white rounded-xl shadow-lg p-8">
        <h1 class="text-2xl font-bold mb-6 text-indigo-700">รายละเอียดออเดอร์ #<?= htmlspecialchars($order['order_id']) ?></h1>
        <div class="mb-6">
            <h2 class="font-semibold text-lg mb-2">ข้อมูลลูกค้า</h2>
            <p>ชื่อ: <?= htmlspecialchars($order['fullname']) ?></p>
            <p>อีเมล์: <?= htmlspecialchars($order['email']) ?></p>
        </div>
        <div class="mb-6">
            <h2 class="font-semibold text-lg mb-2">ข้อมูลบริการ</h2>
            <p>บริการ: <?= htmlspecialchars($order['service_name']) ?></p>
            <p>วันที่สั่ง: <?= htmlspecialchars($order['created_at']) ?></p>
            <p>สถานะ: <span class="font-bold"><?= htmlspecialchars($order['status']) ?></span></p>
            <p>วันที่ส่ง: <?= htmlspecialchars($deadline) ?></p>
        </div>
        <div class="mb-6">
            <h2 class="font-semibold text-lg mb-2">ข้อมูลการชำระเงิน</h2>
            <p>จำนวนเงิน: <?= $order['amount'] !== null ? number_format($order['amount'], 2) : '-' ?></p>
            <p>ประเภทชำระ: <?= $order['payment_type'] == 'full' ? 'เต็มจำนวน' : 'ครึ่งหนึ่ง' ?></p>
            <p>สถานะชำระเงิน: <?= htmlspecialchars($order['payment_status']) ?></p>
            <?php if ($order['slip_file']): ?>
                <p>สลิป: <a href="<?= htmlspecialchars($order['slip_file']) ?>" target="_blank" class="text-blue-600 underline">ดูสลิป</a></p>
            <?php endif; ?>
        </div>
        <div class="mb-6 flex gap-2">
            <a href="order_edit.php?id=<?= $order['order_id'] ?>" class="bg-indigo-600 text-white px-4 py-2 rounded-lg">แก้ไขออเดอร์</a>
            <a href="order_delete.php?id=<?= $order['order_id'] ?>" class="bg-red-600 text-white px-4 py-2 rounded-lg" onclick="return confirm('ลบออเดอร์นี้?')">ลบออเดอร์</a>
        </div>
        <?php if ($order['slug'] === 'poster-design' && $posterData): ?>
            <div class="mb-6">
                <h2 class="font-semibold text-lg mb-2">รายละเอียดสำหรับออกแบบโปสเตอร์</h2>
                <p><span class="font-bold">ชื่อโปรเจกต์:</span> <?= htmlspecialchars($posterData['project_name'] ?? '-') ?></p>
                <p><span class="font-bold">ประเภทโปสเตอร์:</span> <?= htmlspecialchars($posterData['poster_type'] ?? '-') ?></p>
                <p><span class="font-bold">วัตถุประสงค์:</span> <?= htmlspecialchars($posterData['objective'] ?? '-') ?></p>
                <p><span class="font-bold">กลุ่มเป้าหมาย:</span> <?= htmlspecialchars($posterData['target_audience'] ?? '-') ?></p>
                <p><span class="font-bold">ข้อความหลัก:</span> <?= htmlspecialchars($posterData['main_message'] ?? '-') ?></p>
                <p><span class="font-bold">เนื้อหา:</span> <?= nl2br(htmlspecialchars($posterData['content'] ?? '-')) ?></p>
                <p><span class="font-bold">ขนาด:</span> <?= htmlspecialchars($posterData['size'] ?? '-') ?></p>
                <p><span class="font-bold">สไตล์:</span> <?= htmlspecialchars($posterData['style'] ?? '-') ?></p>
                <p><span class="font-bold">สีหลัก:</span> <?= htmlspecialchars($posterData['color_primary'] ?? '-') ?></p>
                <p><span class="font-bold">สีรอง:</span> <?= htmlspecialchars($posterData['color_secondary'] ?? '-') ?></p>
                <p><span class="font-bold">สีเน้น:</span> <?= htmlspecialchars($posterData['color_accent'] ?? '-') ?></p>
                <p><span class="font-bold">ฟอนต์ที่ต้องการ:</span> <?= htmlspecialchars($posterData['preferred_fonts'] ?? '-') ?></p>
                <p><span class="font-bold">รหัสสี:</span> <?= htmlspecialchars($posterData['color_codes'] ?? '-') ?></p>
                <p><span class="font-bold">แนวตั้ง/แนวนอน:</span> <?= htmlspecialchars($posterData['orientation'] ?? '-') ?></p>
                <p><span class="font-bold">โหมดสี:</span> <?= htmlspecialchars($posterData['color_mode'] ?? '-') ?></p>
                <p><span class="font-bold">งบประมาณ:</span> <?= htmlspecialchars($posterData['budget_range'] ?? '-') ?></p>
                <p><span class="font-bold">วันส่งงาน:</span> <?= htmlspecialchars($posterData['due_date'] ?? '-') ?></p>
                <p><span class="font-bold">สิ่งที่ไม่ต้องการ:</span> <?= htmlspecialchars($posterData['avoid_elements'] ?? '-') ?></p>
                <p><span class="font-bold">ความต้องการพิเศษ:</span> <?= htmlspecialchars($posterData['special_requirements'] ?? '-') ?></p>
                <p><span class="font-bold">ลิงก์อ้างอิง:</span> <?= htmlspecialchars($posterData['reference_link'] ?? '-') ?></p>
            </div>
        <?php endif; ?>
        <div class="mb-6">
            <div class="mb-6">
                <form method="post" class="flex items-center gap-2">
                    <label for="order_status" class="font-semibold">อัปเดทสถานะ:</label>
                    <select name="order_status" id="order_status" class="border rounded px-2 py-1">
                        <option value="pending" <?= $order['status'] == 'pending' ? 'selected' : '' ?>>รอดำเนินการ</option>
                        <option value="in_progress" <?= $order['status'] == 'in_progress' ? 'selected' : '' ?>>กำลังดำเนินการ</option>
                        <option value="completed" <?= $order['status'] == 'completed' ? 'selected' : '' ?>>เสร็จสมบูรณ์</option>
                        <option value="cancelled" <?= $order['status'] == 'cancelled' ? 'selected' : '' ?>>ยกเลิก</option>
                    </select>
                    <button type="submit" class="bg-zinc-900 text-white px-4 py-2 rounded">บันทึก</button>
                </form>
            </div>
        </div>
</body>

</html>