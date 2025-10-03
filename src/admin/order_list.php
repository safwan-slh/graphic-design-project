<?php
// ตรวจสอบ error
error_reporting(E_ALL);
ini_set('display_errors', 1);
require __DIR__ . '/../includes/db_connect.php';
require_once '../auth/auth.php';
requireRole('admin'); // ให้เข้าหน้านี้ได้เฉพาะ admin

$fullname = $_SESSION['fullname'] ?? '';
$initial = mb_strtoupper(mb_substr(trim($fullname), 0, 1, 'UTF-8'), 'UTF-8');

// --- Filter ---
$service_id = $_GET['service_id'] ?? '';
$status = $_GET['status'] ?? '';
$date = $_GET['date'] ?? '';

// --- Query สำหรับ filter ---
$where = [];
$params = [];
$types = '';

if ($service_id) {
    $where[] = 'orders.service_id = ?';
    $params[] = $service_id;
    $types .= 'i';
}
if ($status) {
    $where[] = 'orders.order_status = ?';
    $params[] = $status;
    $types .= 's';
}
if ($date) {
    $where[] = 'DATE(orders.created_at) = ?';
    $params[] = $date;
    $types .= 's';
}

$whereSQL = $where ? 'WHERE ' . implode(' AND ', $where) : '';

// --- ดึงรายการบริการสำหรับ filter ---
$serviceRes = $conn->query("SELECT service_id, service_name FROM services");

// --- Query หลัก ---
$sql = "SELECT orders.*, customers.fullname, customers.email, services.service_name, payments.amount, payments.payment_type
        FROM orders
        LEFT JOIN customers ON orders.customer_id = customers.customer_id
        LEFT JOIN services ON orders.service_id = services.service_id
        LEFT JOIN payments ON orders.order_id = payments.order_id
        $whereSQL
        ORDER BY orders.created_at DESC";
$stmt = $conn->prepare($sql);
if ($params) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>รายการออเดอร์</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=IBM+Plex+Sans+Thai:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .font-thai {
            font-family: 'IBM Plex Sans Thai', sans-serif;
        }
    </style>
</head>

<body class="font-thai bg-zinc-100">
    <?php include '../includes/sidebar.php'; ?>
    <div class="ml-64">
        <!-- breadcrumb -->
        <?php
        $breadcrumb = ['Dashboard', 'รายการออเดอร์'];
        $breadcrumb_links = ['/graphic-design/src/admin/index.php', '/graphic-design/src/admin/order_list.php'];
        include '../includes/admin_navbar.php';
        ?>
        <div class="p-6">
            <div class=" text-zinc-900 bg-white rounded-2xl border border-slate-200 mb-2">
                <!-- Header -->
                <div class="flex items-center p-4">
                    <div class="mr-4 rounded-xl bg-zinc-900 p-3">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="h-6 w-6 text-white">
                            <path d="M12 7.5a2.25 2.25 0 1 0 0 4.5 2.25 2.25 0 0 0 0-4.5Z" />
                            <path fill-rule="evenodd" d="M1.5 4.875C1.5 3.839 2.34 3 3.375 3h17.25c1.035 0 1.875.84 1.875 1.875v9.75c0 1.036-.84 1.875-1.875 1.875H3.375A1.875 1.875 0 0 1 1.5 14.625v-9.75ZM8.25 9.75a3.75 3.75 0 1 1 7.5 0 3.75 3.75 0 0 1-7.5 0ZM18.75 9a.75.75 0 0 0-.75.75v.008c0 .414.336.75.75.75h.008a.75.75 0 0 0 .75-.75V9.75a.75.75 0 0 0-.75-.75h-.008ZM4.5 9.75A.75.75 0 0 1 5.25 9h.008a.75.75 0 0 1 .75.75v.008a.75.75 0 0 1-.75.75H5.25a.75.75 0 0 1-.75-.75V9.75Z" clip-rule="evenodd" />
                            <path d="M2.25 18a.75.75 0 0 0 0 1.5c5.4 0 10.63.722 15.6 2.075 1.19.324 2.4-.558 2.4-1.82V18.75a.75.75 0 0 0-.75-.75H2.25Z" />
                        </svg>
                    </div>
                    <div class="">
                        <h1 class="flex items-center text-2xl font-bold text-zinc-900">
                            รายการออเดอร์ทั้งหมด
                        </h1>
                        <p class="text-gray-600">
                            จัดการและติดตามสถานะการออเดอร์ของลูกค้า
                        </p>
                    </div>
                </div>
            </div>
            <div class="flex items-center justify-between bg-white rounded-2xl mb-2 p-4 ring-1 ring-gray-200">
                <!-- Filter -->
                <form method="get" class="flex gap-2 items-end">
                    <div>
                        <label>บริการ</label>
                        <select name="service_id" class="border rounded px-2 py-1">
                            <option value="">ทั้งหมด</option>
                            <?php while ($srv = $serviceRes->fetch_assoc()): ?>
                                <option value="<?= $srv['service_id'] ?>" <?= $service_id == $srv['service_id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($srv['service_name']) ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <div>
                        <label>สถานะ</label>
                        <select name="status" class="border rounded px-2 py-1">
                            <option value="">ทั้งหมด</option>
                            <option value="pending" <?= $status == 'pending' ? 'selected' : '' ?>>รอดำเนินการ</option>
                            <option value="processing" <?= $status == 'processing' ? 'selected' : '' ?>>กำลังดำเนินการ</option>
                            <option value="completed" <?= $status == 'completed' ? 'selected' : '' ?>>เสร็จสมบูรณ์</option>
                        </select>
                    </div>
                    <div>
                        <label>วันที่สั่ง</label>
                        <input type="date" name="date" value="<?= htmlspecialchars($date) ?>" class="border rounded px-2 py-1">
                    </div>
                    <button type="submit" class="bg-zinc-900 text-white px-4 py-2 rounded">ค้นหา</button>
                </form>
            </div>

            <div class=" text-zinc-900 bg-white rounded-2xl border border-slate-200 overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead class="bg-gray-50 border-b border-gray-200">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Order Number</th>
                                <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">ลูกค้า</th>
                                <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">ชื่อบริการ</th>
                                <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">สถานะ</th>
                                <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">วันที่ส่ง</th>
                                <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">ประเภทชำระ</th>
                                <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">จำนวนเงิน</th>
                                <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">การดำเนินการ</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <?php while ($order = $result->fetch_assoc()):
                                // คำนวณวันเหลือก่อนส่ง
                                $deadline = $order['dua_date'] ?? $order['due_date'] ?? '';
                                $daysLeft = $deadline ? (strtotime($deadline) - strtotime(date('Y-m-d'))) / 86400 : '';
                            ?>
                                <tr class="hover:bg-gray-50 transition-colors">
                                    <td class="px-6 py-3 whitespace-nowrap">
                                        <div class="font-mono text-sm font-semibold text-indigo-600"><?= htmlspecialchars($order['order_code'] ?? $order['order_id']) ?></div>
                                        <div class="text-xs text-gray-500"><?= htmlspecialchars(date('Y-m-d', strtotime($order['created_at']))) ?></div>
                                    </td>
                                    <td class="px-6 py-3 whitespace-nowrap">
                                        <div class="flex items-center">
                                            <div class="flex-shrink-0 h-10 w-10 bg-blue-100 rounded-full flex items-center justify-center text-blue-600 font-medium"><?= $initial ?></div>
                                            <div class="ml-3">
                                                <h4 class="font-medium"><?= htmlspecialchars($order['fullname']) ?></h4>
                                                <p class="text-sm text-gray-500"><?= htmlspecialchars($order['email']) ?></p>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm">
                                        <?= htmlspecialchars($order['service_name']) ?><br>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm">
                                        <?= htmlspecialchars($order['status']) ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm">
                                        <?= htmlspecialchars($deadline) ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm">
                                        <?= $order['payment_type'] == 'full' ? 'เต็มจำนวน' : 'ครึ่งหนึ่ง' ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm">
                                        <?= $order['amount'] !== null ? number_format($order['amount'], 2) : '-' ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm">
                                        <a href="order_detail.php?id=<?= htmlspecialchars($order['order_id']) ?>">ดูรายละเอียด</a>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
</body>

</html>