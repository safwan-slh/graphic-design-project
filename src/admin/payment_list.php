<?php
// ตรวจสอบ error
error_reporting(E_ALL);
ini_set('display_errors', 1);
require __DIR__ . '/../includes/db_connect.php';
require_once '../auth/auth.php';
requireRole('admin'); // ให้เข้าหน้านี้ได้เฉพาะ admin

date_default_timezone_set('Asia/Bangkok');
$dateFilter = $_GET['date'] ?? '';
$customStart = $_GET['start'] ?? '';
$customEnd = $_GET['end'] ?? '';
// ตัวกรองสถานะการชำระเงิน
$statusFilter = $_GET['status'] ?? '';
// ตัวกรองวันที่
$where = '';
if ($dateFilter == 'today') {
    $where = "DATE(p.payment_date) = CURDATE()";
} elseif ($dateFilter == '7days') {
    $where = "p.payment_date >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)";
} elseif ($dateFilter == '30days') {
    $where = "p.payment_date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)";
} elseif ($dateFilter == 'custom' && $customStart && $customEnd) {
    $where = "p.payment_date BETWEEN '" . $conn->real_escape_string($customStart) . "' AND '" . $conn->real_escape_string($customEnd) . "'";
}

$perPage = 10; // จำนวนรายการต่อหน้า
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$offset = ($page - 1) * $perPage;

// นับจำนวนรายการทั้งหมด (ตาม filter)
$countSql = "SELECT COUNT(*) AS cnt FROM payments p";
if ($where) {
    $countSql .= " WHERE $where";
}
if ($statusFilter && in_array($statusFilter, ['paid', 'pending', 'cancelled'])) {
    $countSql .= $where ? " AND" : " WHERE";
    $countSql .= " p.payment_status = '" . $conn->real_escape_string($statusFilter) . "'";
}
$totalRows = $conn->query($countSql)->fetch_assoc()['cnt'] ?? 0;
$totalPages = ceil($totalRows / $perPage);

// ดึงข้อมูลรายการตามหน้า
$sql = "SELECT 
            p.*, 
            o.order_code, 
            c.fullname AS customer_name, 
            c.phone AS customer_phone
        FROM payments p
        LEFT JOIN orders o ON p.order_id = o.order_id
        LEFT JOIN customers c ON o.customer_id = c.customer_id";
if ($where) {
    $sql .= " WHERE $where";
}
if ($statusFilter && in_array($statusFilter, ['paid', 'pending', 'cancelled'])) {
    $sql .= $where ? " AND" : " WHERE";
    $sql .= " p.payment_status = '" . $conn->real_escape_string($statusFilter) . "'";
}
$sql .= " ORDER BY 
    CASE p.payment_status
        WHEN 'pending' THEN 1
        WHEN 'cancelled' THEN 2
        WHEN 'paid' THEN 3
        ELSE 4
    END,
    p.payment_date DESC
    LIMIT $perPage OFFSET $offset";

$result = $conn->query($sql);

// ดึงข้อมูลสรุปจากฐานข้อมูล
$totalIncome = $conn->query("SELECT SUM(amount) AS total FROM payments WHERE payment_status = 'paid'")->fetch_assoc()['total'] ?? 0;
$pendingCount = $conn->query("SELECT COUNT(*) AS cnt FROM payments WHERE payment_status = 'pending'")->fetch_assoc()['cnt'] ?? 0;
$paidCount = $conn->query("SELECT COUNT(*) AS cnt FROM payments WHERE payment_status = 'paid'")->fetch_assoc()['cnt'] ?? 0;
$totalCount = $conn->query("SELECT COUNT(*) AS cnt FROM payments")->fetch_assoc()['cnt'] ?? 0;

// ฟังก์ชันช่วยแปลงสถานะเป็น badge
function getStatusBadge($status)
{
    switch ($status) {
        case 'paid':
            return '<span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800">
                        <i class="fas fa-check-circle mr-1"></i> สำเร็จ
                    </span>';
        case 'pending':
            return '<span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">
                        <i class="fas fa-clock mr-1"></i> รอดำเนินการ
                    </span>';
        case 'cancelled':
            return '<span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-red-100 text-red-800">
                        <i class="fas fa-times-circle mr-1"></i> ล้มเหลว
                    </span>';
        default:
            return '<span class="px-3 py-1 bg-gray-100 text-gray-800 text-xs font-medium rounded-full flex items-center gap-1">
                <i class="fas fa-question-circle text-gray-400"></i> ' . htmlspecialchars($status) . '
            </span>';
    }
}
// ฟังก์ชันช่วยแปลงวิธีชำระเป็นไอคอน
function getPaymentMethodIcon($method)
{
    switch ($method) {
        case 'promptpay':
            return '<div class="flex items-center space-x-3">
                        <div class="w-8 h-8 bg-purple-100 rounded-lg flex items-center justify-center">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="w-5 h-5 text-purple-600">
                                <path fill-rule="evenodd" d="M3 4.875C3 3.839 3.84 3 4.875 3h4.5c1.036 0 1.875.84 1.875 1.875v4.5c0 1.036-.84 1.875-1.875 1.875h-4.5A1.875 1.875 0 0 1 3 9.375v-4.5ZM4.875 4.5a.375.375 0 0 0-.375.375v4.5c0 .207.168.375.375.375h4.5a.375.375 0 0 0 .375-.375v-4.5a.375.375 0 0 0-.375-.375h-4.5Zm7.875.375c0-1.036.84-1.875 1.875-1.875h4.5C20.16 3 21 3.84 21 4.875v4.5c0 1.036-.84 1.875-1.875 1.875h-4.5a1.875 1.875 0 0 1-1.875-1.875v-4.5Zm1.875-.375a.375.375 0 0 0-.375.375v4.5c0 .207.168.375.375.375h4.5a.375.375 0 0 0 .375-.375v-4.5a.375.375 0 0 0-.375-.375h-4.5ZM6 6.75A.75.75 0 0 1 6.75 6h.75a.75.75 0 0 1 .75.75v.75a.75.75 0 0 1-.75.75h-.75A.75.75 0 0 1 6 7.5v-.75Zm9.75 0A.75.75 0 0 1 16.5 6h.75a.75.75 0 0 1 .75.75v.75a.75.75 0 0 1-.75.75h-.75a.75.75 0 0 1-.75-.75v-.75ZM3 14.625c0-1.036.84-1.875 1.875-1.875h4.5c1.036 0 1.875.84 1.875 1.875v4.5c0 1.035-.84 1.875-1.875 1.875h-4.5A1.875 1.875 0 0 1 3 19.125v-4.5Zm1.875-.375a.375.375 0 0 0-.375.375v4.5c0 .207.168.375.375.375h4.5a.375.375 0 0 0 .375-.375v-4.5a.375.375 0 0 0-.375-.375h-4.5Zm7.875-.75a.75.75 0 0 1 .75-.75h.75a.75.75 0 0 1 .75.75v.75a.75.75 0 0 1-.75.75h-.75a.75.75 0 0 1-.75-.75v-.75Zm6 0a.75.75 0 0 1 .75-.75h.75a.75.75 0 0 1 .75.75v.75a.75.75 0 0 1-.75.75h-.75a.75.75 0 0 1-.75-.75v-.75ZM6 16.5a.75.75 0 0 1 .75-.75h.75a.75.75 0 0 1 .75.75v.75a.75.75 0 0 1-.75.75h-.75a.75.75 0 0 1-.75-.75v-.75Zm9.75 0a.75.75 0 0 1 .75-.75h.75a.75.75 0 0 1 .75.75v.75a.75.75 0 0 1-.75.75h-.75a.75.75 0 0 1-.75-.75v-.75Zm-3 3a.75.75 0 0 1 .75-.75h.75a.75.75 0 0 1 .75.75v.75a.75.75 0 0 1-.75.75h-.75a.75.75 0 0 1-.75-.75v-.75Zm6 0a.75.75 0 0 1 .75-.75h.75a.75.75 0 0 1 .75.75v.75a.75.75 0 0 1-.75.75h-.75a.75.75 0 0 1-.75-.75v-.75Z" clip-rule="evenodd" />
                            </svg>
                        </div>
                        <span class="text-xs font-bold text-gray-800">PromptPay</span>
                    </div>';
        case 'bank':
            return '<div class="flex items-center space-x-3">
                        <div class="w-8 h-8 bg-blue-100 rounded-lg flex items-center justify-center">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="w-5 h-5 text-blue-600">
                            <path d="M11.584 2.376a.75.75 0 0 1 .832 0l9 6a.75.75 0 1 1-.832 1.248L12 3.901 3.416 9.624a.75.75 0 0 1-.832-1.248l9-6Z" />
                            <path fill-rule="evenodd" d="M20.25 10.332v9.918H21a.75.75 0 0 1 0 1.5H3a.75.75 0 0 1 0-1.5h.75v-9.918a.75.75 0 0 1 .634-.74A49.109 49.109 0 0 1 12 9c2.59 0 5.134.202 7.616.592a.75.75 0 0 1 .634.74Zm-7.5 2.418a.75.75 0 0 0-1.5 0v6.75a.75.75 0 0 0 1.5 0v-6.75Zm3-.75a.75.75 0 0 1 .75.75v6.75a.75.75 0 0 1-1.5 0v-6.75a.75.75 0 0 1 .75-.75ZM9 12.75a.75.75 0 0 0-1.5 0v6.75a.75.75 0 0 0 1.5 0v-6.75Z" clip-rule="evenodd" />
                            <path d="M12 7.875a1.125 1.125 0 1 0 0-2.25 1.125 1.125 0 0 0 0 2.25Z" />
                            </svg>
                        </div>
                        <span class="text-xs font-bold text-gray-800">ธนาคาร</span>
                    </div>';
        default:
            return '<div class="flex items-center space-x-3">
                        <div class="w-9 h-9 bg-gray-100 rounded-lg flex items-center justify-center">
                            <i class="fa-solid fa-money-bill-wave text-gray-600"></i>
                        </div>
                        <span class="text-xs font-bold text-gray-800">อื่นๆ</span>
                    </div>';
    }
}
// ฟังก์ชันช่วยแปลงประเภทการชำระเป็น badge
function getPaymentTypeBadge($type)
{
    switch ($type) {
        case 'full':
            return '<span class="px-3 py-1 text-xs font-medium text-green-700 bg-green-100 rounded-full">เต็มจำนวน</span>';
        case 'partial':
            return '<span class="px-3 py-1 text-xs font-medium text-yellow-700 bg-yellow-100 rounded-full">บางส่วน</span>';
        default:
            return '<span class="px-3 py-1 text-xs font-medium text-gray-700 bg-gray-100 rounded-full">' . htmlspecialchars($type) . '</span>';
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment List</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=IBM+Plex+Sans+Thai:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .font-thai {
            font-family: 'IBM Plex Sans Thai', sans-serif;
        }
    </style>
</head>

<body class="bg-zinc-100 font-thai">
    <?php include '../includes/sidebar.php'; ?>
    <div class="ml-64">
        <!-- breadcrumb -->
        <?php
        $breadcrumb = ['Dashboard', 'รายการชำระเงิน'];
        $breadcrumb_links = ['/graphic-design/src/admin/index.php', '/graphic-design/src/admin/payment_list.php'];
        include '../includes/admin_navbar.php';
        ?>
        <div class="p-6">
            <div class=" text-zinc-900 bg-white rounded-2xl border border-slate-200 mb-2">
                <!-- Header -->
                <div class="flex items-center border-b border-gray-200 p-4">
                    <div class="mr-4 rounded-xl bg-zinc-900 p-3">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="h-6 w-6 text-white">
                            <path d="M12 7.5a2.25 2.25 0 1 0 0 4.5 2.25 2.25 0 0 0 0-4.5Z" />
                            <path fill-rule="evenodd" d="M1.5 4.875C1.5 3.839 2.34 3 3.375 3h17.25c1.035 0 1.875.84 1.875 1.875v9.75c0 1.036-.84 1.875-1.875 1.875H3.375A1.875 1.875 0 0 1 1.5 14.625v-9.75ZM8.25 9.75a3.75 3.75 0 1 1 7.5 0 3.75 3.75 0 0 1-7.5 0ZM18.75 9a.75.75 0 0 0-.75.75v.008c0 .414.336.75.75.75h.008a.75.75 0 0 0 .75-.75V9.75a.75.75 0 0 0-.75-.75h-.008ZM4.5 9.75A.75.75 0 0 1 5.25 9h.008a.75.75 0 0 1 .75.75v.008a.75.75 0 0 1-.75.75H5.25a.75.75 0 0 1-.75-.75V9.75Z" clip-rule="evenodd" />
                            <path d="M2.25 18a.75.75 0 0 0 0 1.5c5.4 0 10.63.722 15.6 2.075 1.19.324 2.4-.558 2.4-1.82V18.75a.75.75 0 0 0-.75-.75H2.25Z" />
                        </svg>
                    </div>
                    <div class="">
                        <h1 class="flex items-center text-2xl font-bold text-zinc-900">
                            สรุปการชำระเงินทั้งหมด
                        </h1>
                        <p class="text-gray-600">
                            จัดการและติดตามสถานะการชำระเงินของลูกค้า
                        </p>
                    </div>
                </div>
                <div class="mx-auto text-center p-4">
                    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                        <div class="bg-white flex items-center p-2 ring-1 ring-zinc-200 rounded-2xl">
                            <div class="mr-4 rounded-xl text-purple-600 bg-purple-100 ring-1 ring-purple-200 p-3">
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="h-6 w-6">
                                    <path fill-rule="evenodd" d="M2.625 6.75a1.125 1.125 0 1 1 2.25 0 1.125 1.125 0 0 1-2.25 0Zm4.875 0A.75.75 0 0 1 8.25 6h12a.75.75 0 0 1 0 1.5h-12a.75.75 0 0 1-.75-.75ZM2.625 12a1.125 1.125 0 1 1 2.25 0 1.125 1.125 0 0 1-2.25 0ZM7.5 12a.75.75 0 0 1 .75-.75h12a.75.75 0 0 1 0 1.5h-12A.75.75 0 0 1 7.5 12Zm-4.875 5.25a1.125 1.125 0 1 1 2.25 0 1.125 1.125 0 0 1-2.25 0Zm4.875 0a.75.75 0 0 1 .75-.75h12a.75.75 0 0 1 0 1.5h-12a.75.75 0 0 1-.75-.75Z" clip-rule="evenodd" />
                                </svg>
                            </div>
                            <div class="">
                                <h1 class="flex items-center text-2xl font-bold text-purple-700">
                                    <?= $totalCount ?>
                                </h1>
                                <p class="text-gray-500 text-sm font-bold">
                                    จำนวนรายการทั้งหมด
                                </p>
                            </div>
                        </div>
                        <div class="bg-white flex items-center p-2 ring-1 ring-zinc-200 rounded-2xl">
                            <div class="mr-4 rounded-xl text-yellow-600 bg-yellow-100 ring-1 ring-yellow-200 p-3">
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="h-6 w-6">
                                    <path fill-rule="evenodd" d="M12 2.25c-5.385 0-9.75 4.365-9.75 9.75s4.365 9.75 9.75 9.75 9.75-4.365 9.75-9.75S17.385 2.25 12 2.25Zm.53 5.47a.75.75 0 0 0-1.06 0l-3 3a.75.75 0 1 0 1.06 1.06l1.72-1.72v5.69a.75.75 0 0 0 1.5 0v-5.69l1.72 1.72a.75.75 0 1 0 1.06-1.06l-3-3Z" clip-rule="evenodd" />
                                </svg>
                            </div>
                            <div class="">
                                <h1 class="flex items-center text-2xl font-bold text-yellow-700">
                                    <?= $pendingCount ?>
                                </h1>
                                <p class="text-gray-500 text-sm font-bold">
                                    รอดำเนินการ
                                </p>
                            </div>
                        </div>
                        <div class="bg-white flex items-center p-2 ring-1 ring-zinc-200 rounded-2xl">
                            <div class="mr-4 rounded-xl text-green-600 bg-green-100 ring-1 ring-green-200 p-3">
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="h-6 w-6">
                                    <path fill-rule="evenodd" d="M2.25 12c0-5.385 4.365-9.75 9.75-9.75s9.75 4.365 9.75 9.75-4.365 9.75-9.75 9.75S2.25 17.385 2.25 12Zm13.36-1.814a.75.75 0 1 0-1.22-.872l-3.236 4.53L9.53 12.22a.75.75 0 0 0-1.06 1.06l2.25 2.25a.75.75 0 0 0 1.14-.094l3.75-5.25Z" clip-rule="evenodd" />
                                </svg>
                            </div>
                            <div class="">
                                <h1 class="flex items-center text-2xl font-bold text-green-700">
                                    <?= $paidCount ?>
                                </h1>
                                <p class="text-gray-500 text-sm font-bold">
                                    ชำระเงินสำเร็จ
                                </p>
                            </div>
                        </div>
                        <div class="bg-white flex items-center p-2 ring-1 ring-zinc-200 rounded-2xl">
                            <div class="mr-4 rounded-xl text-blue-600 bg-blue-100 ring-1 ring-blue-200 p-3">
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="h-6 w-6">
                                    <path d="M18.375 2.25c-1.035 0-1.875.84-1.875 1.875v15.75c0 1.035.84 1.875 1.875 1.875h.75c1.035 0 1.875-.84 1.875-1.875V4.125c0-1.036-.84-1.875-1.875-1.875h-.75ZM9.75 8.625c0-1.036.84-1.875 1.875-1.875h.75c1.036 0 1.875.84 1.875 1.875v11.25c0 1.035-.84 1.875-1.875 1.875h-.75a1.875 1.875 0 0 1-1.875-1.875V8.625ZM3 13.125c0-1.036.84-1.875 1.875-1.875h.75c1.036 0 1.875.84 1.875 1.875v6.75c0 1.035-.84 1.875-1.875 1.875h-.75A1.875 1.875 0 0 1 3 19.875v-6.75Z" />
                                </svg>
                            </div>
                            <div class="">
                                <h1 class="flex items-center text-2xl font-bold text-blue-700">
                                    ฿<?= number_format($totalIncome, 2) ?>
                                </h1>
                                <p class="text-gray-500 text-sm font-bold">
                                    รายได้ทั้งหมด
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="flex items-center justify-between bg-white rounded-2xl mb-2 p-4 ring-1 ring-gray-200">
                <!-- Date Filter -->
                <?php
                $dateFilter = $_GET['date'] ?? 'today';
                $customStart = $_GET['start'] ?? '';
                $customEnd = $_GET['end'] ?? '';
                ?>
                <div class="flex items-center justify-between">
                    <div class="flex items-center space-x-2">
                        <a href="?date=today" class="border transition font-medium rounded-xl text-sm px-5 py-2 text-center flex items-center justify-center <?= $dateFilter == 'today' ? 'bg-zinc-900 text-white border-zinc-900' : 'bg-white text-gray-600 border-gray-300 hover:bg-gray-100' ?>">วันนี้</a>
                        <a href="?date=7days" class="border transition font-medium rounded-xl text-sm px-5 py-2 text-center flex items-center justify-center <?= $dateFilter == '7days' ? 'bg-zinc-900 text-white border-zinc-900' : 'bg-white text-gray-600 border-gray-300 hover:bg-gray-100' ?>">7 วันล่าสุด</a>
                        <a href="?date=30days" class="border transition font-medium rounded-xl text-sm px-5 py-2 text-center flex items-center justify-center <?= $dateFilter == '30days' ? 'bg-zinc-900 text-white border-zinc-900' : 'bg-white text-gray-600 border-gray-300 hover:bg-gray-100' ?>">30 วัน</a>
                        <form method="get" class="inline-flex items-center space-x-2 ">
                            <input type="hidden" name="date" value="custom">
                            <input type="date" name="start" value="<?= htmlspecialchars($customStart) ?>" class="border transition font-medium rounded-xl text-sm px-5 py-2 text-center flex items-center justify-center border-gray-300 hover:bg-gray-100">
                            <span>-</span>
                            <input type="date" name="end" value="<?= htmlspecialchars($customEnd) ?>" class="border transition font-medium rounded-xl text-sm px-5 py-2 text-center flex items-center justify-center border-gray-300 hover:bg-gray-100">
                            <button type="submit" class="border transition font-medium rounded-xl text-sm px-5 py-2 text-center flex items-center justify-center bg-zinc-900 text-white border-zinc-900">ตกลง</button>
                        </form>
                    </div>
                </div>
                <div class="text-sm text-gray-500 p-2 rounded-lg ring-1 ring-gray-200">
                    อัพเดทล่าสุด: <span class="font-semibold"><?= date('d M Y H:i') ?></span>
                </div>
            </div>
            <!-- Payment Transactions Table -->
            <div class=" text-zinc-900 bg-white rounded-2xl border border-slate-200 overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-200">
                    <div class="flex items-center justify-between">
                        <h3 class="text-lg font-bold text-gray-800">รายการชำระเงินทั้งหมด</h3>
                        <div class="flex items-center space-x-3">
                            <form method="get" class="flex items-center space-x-3">
                                <select name="status" class="border transition font-medium rounded-xl text-sm px-5 py-2 text-center flex items-center justify-center bg-white text-gray-600 border-gray-300 hover:bg-gray-100" onchange="this.form.submit()">
                                    <option value="">ทั้งหมด</option>
                                    <option value="paid" <?= $statusFilter == 'paid' ? 'selected' : '' ?>>สำเร็จ</option>
                                    <option value="pending" <?= $statusFilter == 'pending' ? 'selected' : '' ?>>รอดำเนินการ</option>
                                    <option value="cancelled" <?= $statusFilter == 'cancelled' ? 'selected' : '' ?>>ล้มเหลว</option>
                                </select>
                            </form>
                        </div>
                    </div>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead class="bg-gray-50 border-b border-gray-200">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Order Number</th>
                                <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">ลูกค้า</th>
                                <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">วิธีชำระ</th>
                                <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">ประเภท</th>
                                <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">จำนวนเงิน</th>
                                <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">สถานะ</th>
                                <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">วันที่</th>
                                <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">การดำเนินการ</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <?php while ($payment = $result->fetch_assoc()): ?>
                                <tr class="hover:bg-gray-50 transition-colors">
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="font-mono text-sm font-semibold text-indigo-600"><?= htmlspecialchars($payment['order_code'] ?? $payment['order_id']) ?></div>
                                    </td>
                                    <td class="px-6 py-4">
                                        <div class="text-sm font-medium text-gray-900"><?= htmlspecialchars($payment['customer_name'] ?? '-') ?></div>
                                        <div class="text-xs text-gray-500"><?= htmlspecialchars($payment['customer_phone'] ?? '-') ?></div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="">
                                            <?= getPaymentMethodIcon($payment['payment_method']) ?>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <?= getPaymentTypeBadge($payment['payment_type'] ?? '-') ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm font-bold text-gray-900">฿<?= htmlspecialchars($payment['amount'] ?? '-') ?></div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="">
                                            <?= getStatusBadge($payment['payment_status'] ?? '-') ?>
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        <?= !empty($payment['payment_date']) ? date('d/m/Y', strtotime($payment['payment_date'])) : '-' ?><br>
                                        <span class="text-xs">
                                            <?= !empty($payment['payment_date']) ? date('H:i', strtotime($payment['payment_date'])) : '-' ?>
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm">
                                        <a href="payment_detail.php?id=<?= htmlspecialchars($payment['id'] ?? $payment['payment_id'] ?? '') ?>" class="text-indigo-600 hover:text-indigo-900 font-medium">ดูรายละเอียด</a>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div class="px-6 py-4 border-t border-gray-200 flex items-center justify-between">
                    <div class="text-sm text-gray-500 p-2 rounded-lg ring-1 ring-gray-200">
                        แสดง <span class="font-semibold"><?= ($offset + 1) ?></span> -
                        <span class="font-semibold"><?= min($offset + $perPage, $totalRows) ?></span>
                        จาก <span class="font-semibold"><?= $totalRows ?></span> รายการ
                    </div>
                    <div class="flex items-center space-x-2">
                        <a href="?<?= http_build_query(array_merge($_GET, ['page' => $page - 1])) ?>"
                            class="border transition font-medium rounded-xl text-sm px-5 py-2 text-center flex items-center justify-center <?= $page <= 1 ? 'opacity-50 pointer-events-none' : '' ?>">
                            ก่อนหน้า
                        </a>
                        <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                            <a href="?<?= http_build_query(array_merge($_GET, ['page' => $i])) ?>"
                                class="border transition font-medium rounded-xl text-sm px-5 py-2 text-center flex items-center justify-center <?= $page == $i ? 'bg-zinc-900 text-white border-zinc-900' : 'bg-white text-gray-600 border-gray-300 hover:bg-gray-100' ?>">
                                <?= $i ?>
                            </a>
                        <?php endfor; ?>
                        <a href="?<?= http_build_query(array_merge($_GET, ['page' => $page + 1])) ?>"
                            class="border transition font-medium rounded-xl text-sm px-5 py-2 text-center flex items-center justify-center <?= $page >= $totalPages ? 'opacity-50 pointer-events-none' : '' ?>">
                            ถัดไป
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>

</html>