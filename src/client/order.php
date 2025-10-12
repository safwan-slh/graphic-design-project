<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
require __DIR__ . '/../includes/db_connect.php';
require_once '../auth/auth.php';
requireLogin();

$customer_id = $_SESSION['customer_id'];

// ดึงรายการ order ทั้งหมดของลูกค้า
$sql = "SELECT o.*, s.service_name, s.slug
        FROM orders o
        LEFT JOIN services s ON o.service_id = s.service_id
        WHERE o.customer_id = ?
        ORDER BY o.created_at DESC";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $customer_id);
$stmt->execute();
$result = $stmt->get_result();

$orders = [];
while ($row = $result->fetch_assoc()) {
    $orders[] = $row;
}

// ฟังก์ชันดึงรายละเอียดบริการแต่ละประเภท
function getOrderDetail($conn, $service_id, $ref_id)
{
    if ($service_id == 1) { // ตัวอย่าง: 1 = poster
        $stmt = $conn->prepare("SELECT * FROM poster_details WHERE poster_id = ?");
        $stmt->bind_param("i", $ref_id);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc();
    }
    // เพิ่มบริการอื่น ๆ เช่น logo_details, banner_details ตาม service_id
    return null;
}

// ฟังก์ชันดึงสถานะการชำระเงินล่าสุด
function getPaymentStatus($conn, $order_id)
{
    $stmt = $conn->prepare("SELECT payment_status FROM payments WHERE order_id = ? ORDER BY created_at DESC LIMIT 1");
    $stmt->bind_param("i", $order_id);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    return $result['payment_status'] ?? null;
}

// แปลสถานะออเดอร์เป็นภาษาไทย
function getOrderStatusTH($status)
{
    switch ($status) {
        case 'pending':
            return 'รอตรวจสอบ';
        case 'in_progress':
            return 'กำลังดำเนินการ';
        case 'completed':
            return 'เสร็จสมบูรณ์';
        case 'cancelled':
            return 'ยกเลิก';
        default:
            return $status;
    }
}
// ฟังก์ชันแปลสถานะการชำระเงินเป็นภาษาไทย
function getPaymentStatusTH($status)
{
    switch ($status) {
        case 'paid':
            return 'ชำระเงินแล้ว';
        case 'pending':
            return 'รอตรวจสอบ';
        case 'failed':
            return 'ไม่สำเร็จ';
        case 'cancelled':
            return 'ล้มเหลว';
        default:
            return $status;
    }
}
// ฟังก์ชันกำหนดคลาสสีตามสถานะ
function getPaymentStatusClass($status)
{
    switch ($status) {
        case 'paid':
            return 'text-green-600 text-xs font-medium bg-green-100 px-3 py-1 rounded-md';
        case 'pending':
            return 'text-yellow-600 text-xs font-medium bg-yellow-100 px-3 py-1 rounded-md';
        case 'failed':
            return 'text-red-600 text-xs font-medium bg-red-100 px-3 py-1 rounded-md';
        case 'cancelled':
            return 'text-red-600 text-xs font-medium bg-red-100 px-3 py-1 rounded-md';
        default:
            return 'text-gray-600 text-xs font-medium bg-gray-100 px-3 py-1 rounded-md';
    }
}
function getOrderStatusClass($status)
{
    switch ($status) {
        case 'pending':
            return 'text-yellow-600 text-xs bg-yellow-100 px-3 py-1 rounded-md';
        case 'in_progress':
            return 'text-blue-600 text-xs bg-blue-100 px-3 py-1 rounded-md';
        case 'completed':
            return 'text-green-600 text-xs bg-green-100 px-3 py-1 rounded-md';
        case 'cancelled':
            return 'text-red-600 text-xs bg-red-100 px-3 py-1 rounded-md';
        default:
            return 'text-gray-600 text-xs bg-gray-100 px-3 py-1 rounded-md';
    }
}

// สรุปจำนวนแต่ละสถานะ
$statusSummary = [
    'pending' => 0,
    'in_progress' => 0,
    'completed' => 0,
    'cancelled' => 0
];
foreach ($orders as $order) {
    if (isset($statusSummary[$order['status']])) {
        $statusSummary[$order['status']]++;
    }
}

// กรองคำสั่งซื้อตามสถานะ (ถ้ามี)
$selectedStatus = $_GET['status'] ?? null;
$statusLabels = [
    'pending' => 'รอตรวจสอบ',
    'in_progress' => 'กำลังดำเนินการ',
    'completed' => 'เสร็จสมบูรณ์',
    'cancelled' => 'ยกเลิก'
];

//
$highlightOrderId = $_GET['order_id'] ?? null;
?>

<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8">
    <title>รายการสั่งซื้อของฉัน | Graphic Design</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=IBM+Plex+Sans+Thai:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/flowbite/1.7.0/flowbite.min.css" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />
    <style>
        .font-thai {
            font-family: 'IBM Plex Sans Thai', sans-serif;
        }
    </style>
</head>

<body class="bg-gray-100 min-h-screen font-thai mt-10" id="drawer-disable-body-scrolling">
    <!-- Toast Notification -->
    <?php if (isset($_GET['cancel']) && $_GET['cancel'] === 'success'): ?>
        <div id="toast-cancel-success" class="fixed bottom-5 right-5 flex items-center w-auto max-w-xs p-2 mb-4 bg-white border border-gray-200 rounded-xl shadow-sm z-50 transition-opacity duration-300" role="alert">
            <div id="toast-icon" class="inline-flex items-center justify-center shrink-0 w-8 h-8 rounded-lg bg-green-100 text-green-500">
                <svg class="w-5 h-5" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 20 20">
                    <path d="M10 .5a9.5 9.5 0 1 0 9.5 9.5A9.51 9.51 0 0 0 10 .5Zm3.707 8.207-4 4a1 1 0 0 1-1.414 0l-2-2a1 1 0 0 1 1.414-1.414L9 10.586l3.293-3.293a1 1 0 0 1 1.414 1.414Z" />
                </svg>
            </div>
            <div id="toast-message" class="ml-3 text-sm font-normal">ยกเลิกออเดอร์สำเร็จ!</div>
        </div>
        <script>
            setTimeout(function() {
                document.getElementById('toast-cancel-success').style.display = 'none';
                // ลบ query string ออก (refresh แล้วจะไม่ขึ้น toast ซ้ำ)
                if (window.history.replaceState) {
                    const url = new URL(window.location);
                    url.searchParams.delete('cancel');
                    window.history.replaceState({}, document.title, url.pathname + url.search);
                }
            }, 3000);
        </script>
    <?php endif; ?>
    <?php require '../includes/navbar.php'; ?>
    <!-- Hero Section -->
    <div class="px-10 pt-10 mb-10">
        <div class=" text-zinc-900 bg-white rounded-2xl border border-slate-200 mb-8">
            <!-- Header -->
            <div class="flex items-center border-b border-gray-200 p-4">
                <div class="mr-4 rounded-xl bg-zinc-900 p-3">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="h-6 w-6 text-white">
                        <path d="M12.378 1.602a.75.75 0 0 0-.756 0L3 6.632l9 5.25 9-5.25-8.622-5.03ZM21.75 7.93l-9 5.25v9l8.628-5.032a.75.75 0 0 0 .372-.648V7.93ZM11.25 22.18v-9l-9-5.25v8.57a.75.75 0 0 0 .372.648l8.628 5.033Z" />
                    </svg>
                </div>
                <div class="">
                    <h1 class="flex items-center text-2xl font-bold text-zinc-900">
                        รายการสั่งซื้อของฉัน
                    </h1>
                    <p class="text-gray-600">
                        สำรวจผลงานการออกแบบกราฟิกที่เราได้สร้างให้กับลูกค้าทั้งในและต่างประเทศ ด้วยความคิดสร้างสรรค์และความเชี่ยวชาญ
                    </p>
                </div>
            </div>
            <div class="mx-auto text-center">
                <!-- Order Status Summary -->
                <div class="grid grid-cols-2 md:grid-cols-4 gap-4 p-4 rounded-2xl">
                    <div class="bg-white flex items-center p-2 ring-1 ring-zinc-200 rounded-2xl">
                        <div class="mr-4 rounded-xl text-yellow-600 bg-yellow-100 ring-1 ring-yellow-200 p-3">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="h-6 w-6">
                                <path fill-rule="evenodd" d="M2.25 12c0-5.385 4.365-9.75 9.75-9.75s9.75 4.365 9.75 9.75-4.365 9.75-9.75 9.75S2.25 17.385 2.25 12ZM12 8.25a.75.75 0 0 1 .75.75v3.75a.75.75 0 0 1-1.5 0V9a.75.75 0 0 1 .75-.75Zm0 8.25a.75.75 0 1 0 0-1.5.75.75 0 0 0 0 1.5Z" clip-rule="evenodd" />
                            </svg>
                        </div>
                        <div class="">
                            <h1 class="flex items-center font-bold text-zinc-900">
                                <?= $statusSummary['pending'] ?>
                            </h1>
                            <p class="text-gray-500 text-sm font-bold">
                                รอตรวจสอบ
                            </p>
                        </div>
                    </div>
                    <div class="bg-white flex items-center p-2 ring-1 ring-zinc-200 rounded-2xl">
                        <div class="mr-4 rounded-xl text-blue-600 bg-blue-100 ring-1 ring-blue-200 p-3">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="h-6 w-6">
                                <path fill-rule="evenodd" d="M12 2.25c-5.385 0-9.75 4.365-9.75 9.75s4.365 9.75 9.75 9.75 9.75-4.365 9.75-9.75S17.385 2.25 12 2.25Zm.53 5.47a.75.75 0 0 0-1.06 0l-3 3a.75.75 0 1 0 1.06 1.06l1.72-1.72v5.69a.75.75 0 0 0 1.5 0v-5.69l1.72 1.72a.75.75 0 1 0 1.06-1.06l-3-3Z" clip-rule="evenodd" />
                            </svg>
                        </div>
                        <div class="">
                            <h1 class="flex items-center font-bold text-zinc-900">
                                <?= $statusSummary['in_progress'] ?>
                            </h1>
                            <p class="text-gray-500 text-sm font-bold">
                                กำลังดำเนินการ
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
                            <h1 class="flex items-center font-bold text-zinc-900">
                                <?= $statusSummary['completed'] ?>
                            </h1>
                            <p class="text-gray-500 text-sm font-bold">
                                เสร็จสมบูรณ์
                            </p>
                        </div>
                    </div>
                    <div class="bg-white flex items-center p-2 ring-1 ring-zinc-200 rounded-2xl">
                        <div class="mr-4 rounded-xl text-red-600 bg-red-100 ring-1 ring-red-200 p-3">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="h-6 w-6">
                                <path fill-rule="evenodd" d="M12 2.25c-5.385 0-9.75 4.365-9.75 9.75s4.365 9.75 9.75 9.75 9.75-4.365 9.75-9.75S17.385 2.25 12 2.25Zm-1.72 6.97a.75.75 0 1 0-1.06 1.06L10.94 12l-1.72 1.72a.75.75 0 1 0 1.06 1.06L12 13.06l1.72 1.72a.75.75 0 1 0 1.06-1.06L13.06 12l1.72-1.72a.75.75 0 1 0-1.06-1.06L12 10.94l-1.72-1.72Z" clip-rule="evenodd" />
                            </svg>
                        </div>
                        <div class="">
                            <h1 class="flex items-center font-bold text-zinc-900">
                                <?= $statusSummary['cancelled'] ?>
                            </h1>
                            <p class="text-gray-500 text-sm font-bold">
                                ยกเลิก
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>


        <div class="bg-white items-center p-2 ring-1 ring-zinc-200 rounded-2xl">
            <!-- <status-filter> -->
            <div class="flex flex-wrap gap-2 items-center p-2 pb-4 mb-4 border-b border-gray-200">
                <a href="order.php"
                    class="border transition font-medium rounded-xl text-sm px-5 py-2 text-center flex items-center justify-center
                   <?= is_null($selectedStatus) ? 'bg-zinc-900 text-white border-zinc-900' : 'bg-white text-gray-600 border-gray-300 hover:bg-gray-100' ?>">
                    ทั้งหมด
                </a>
                <?php foreach ($statusLabels as $status => $label): ?>
                    <a href="order.php?status=<?= $status ?>"
                        class="border transition font-medium rounded-xl text-sm px-5 py-2 text-center flex items-center justify-center
                       <?= ($selectedStatus === $status) ? 'bg-zinc-900 text-white border-zinc-900' : 'bg-white text-gray-600 border-gray-300 hover:bg-gray-100' ?>">
                        <?= $label ?>
                    </a>
                <?php endforeach; ?>
            </div>
            <?php
            $filteredOrders = $selectedStatus
                ? array_filter($orders, fn($o) => $o['status'] === $selectedStatus)
                : $orders;
            ?>


            <div class="">
                <?php if (empty($filteredOrders)): ?>
                    <div class="bg-white p-8 rounded-xl text-center text-gray-500 ring-1 ring-gray-200">ยังไม่มีรายการสั่งซื้อ</div>
                <?php else: ?>
                    <div class="grid grid-cols-1 md:grid-cols-4 gap-6 w-full">
                        <?php foreach ($filteredOrders as $order): ?>
                            <?php
                            $detail = getOrderDetail($conn, $order['service_id'], $order['ref_id']);
                            ?>
                            <!-- card -->
                            <div class="bg-white rounded-2xl shadow-sm p-4 space-y-2 cursor-pointer ring-1 ring-gray-200 transition-all duration-300 ease-in-out hover:scale-105
                                <?= ($highlightOrderId == $order['order_id']) ? 'border-2 border-blue-500 ring-blue-200 animate-pulse' : '' ?>"
                                id="order-<?= $order['order_id'] ?>">
                                <!-- header -->
                                <div class="">
                                    <div class="flex items-center justify-between mb-4">
                                        <div class="font-semibold text-lg text-zinc-900">
                                            <?= htmlspecialchars($order['service_name']) ?>
                                        </div>
                                        <div class="flex space-x-2 item-center">
                                            <div class="flex items-center">
                                                <span class="px-3 py-1 rounded-full text-xs font-medium
                                                    <?= getOrderStatusClass($order['status']) ?>">
                                                    <?= getOrderStatusTH($order['status']) ?>
                                                </span>
                                            </div>
                                            <div class="">
                                                <button id="dropdownDefaultButton-<?= $order['order_id'] ?>" data-dropdown-toggle="cancel-<?= $order['order_id'] ?>" class="flex items-center p-1 text-sm font-medium text-center text-gray-400 bg-white rounded-lg hover:bg-gray-100 focus:ring-4 focus:outline-none focus:ring-gray-200" type="button">
                                                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="size-6">
                                                        <path fill-rule="evenodd" d="M10.5 6a1.5 1.5 0 1 1 3 0 1.5 1.5 0 0 1-3 0Zm0 6a1.5 1.5 0 1 1 3 0 1.5 1.5 0 0 1-3 0Zm0 6a1.5 1.5 0 1 1 3 0 1.5 1.5 0 0 1-3 0Z" clip-rule="evenodd" />
                                                    </svg>
                                                </button>
                                                <div id="cancel-<?= $order['order_id'] ?>" class="z-10 p-1 hidden bg-white divide-y divide-gray-100 rounded-lg shadow-lg w-44 ring-1 ring-gray-200">
                                                    <?php if (in_array($order['status'], ['pending', 'in_progress'])): ?>
                                                        <a onclick="event.stopPropagation(); confirmCancel(<?= $order['order_id'] ?>, '<?= $order['status'] ?>');"
                                                            class="flex items-center justify-between px-3 py-2 text-sm rounded-lg bg-red-100 text-red-600 hover:bg-red-200 transition-colors duration-200">
                                                            ยกเลิกออเดอร์
                                                            <svg class="w-3 h-3" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 14 14">
                                                                <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m1 1 6 6m0 0 6 6M7 7l6-6M7 7l-6 6" />
                                                            </svg>
                                                        </a>
                                                    <?php else: ?>
                                                        <span class="flex items-center px-3 py-2 text-sm rounded-lg bg-gray-100 text-red-600">
                                                            ไม่สามารถยกเลิกออเดอร์ได้
                                                        </span>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <!-- Details -->
                                <div class="">
                                    <div class="bg-gray-50 p-3 rounded-xl ring-1 ring-gray-200">
                                        <div class="">
                                            <div class="text-gray-500 text-sm mb-2 flex justify-between">
                                                <span class="text-zinc-600 font-medium">รหัสคำสั่งซื้อ:</span> <span>#<?= htmlspecialchars($order['order_code'] ?? ('#' . $order['order_id'])) ?></span>
                                            </div>
                                            <div class="text-gray-500 text-sm mb-2 flex justify-between">
                                                <span class="text-zinc-600 font-medium">วันที่สั่ง:</span> <?= date('d/m/Y', strtotime($order['created_at'])) ?>
                                            </div>
                                            <div class="text-gray-500 text-sm mb-2 flex justify-between">
                                                <span class="text-zinc-600 font-medium">วันที่ส่ง:</span> <?= !empty($detail['due_date']) ? date('d/m/Y', strtotime($detail['due_date'])) : '-' ?>
                                            </div>
                                            <!-- แสดงสถานะการชำระเงิน -->
                                            <?php
                                            $payment_status = getPaymentStatus($conn, $order['order_id']);
                                            ?>
                                            <div class="text-gray-500 text-sm mb-2 flex justify-between">
                                                <span class="text-zinc-600 font-medium">สถานะการชำระเงิน:</span>
                                                <?php if ($payment_status): ?>
                                                    <span class="<?= getPaymentStatusClass($payment_status) ?>">
                                                        <?= getPaymentStatusTH($payment_status) ?>
                                                    </span>
                                                <?php else: ?>
                                                    <span class="text-pink-600 text-xs font-medium bg-pink-100 px-3 py-1 rounded-md">
                                                        รอชำระเงิน
                                                    </span>
                                                <?php endif; ?>
                                            </div>
                                            <div class="text-gray-500 text-sm mb-2 flex justify-between">
                                                <span class="text-zinc-600 font-medium">เหลือเวลา:</span>
                                                <!-- Countdown -->
                                                <?php if (!empty($detail['due_date'])): ?>
                                                    <span class="text-blue-600 text-xs font-medium bg-blue-50 px-3 py-1 rounded-md">
                                                        <?php
                                                        // คำนวณวันคงเหลือ
                                                        $now = new DateTime();
                                                        $due = new DateTime($detail['due_date']);
                                                        $interval = $now->diff($due);
                                                        $daysLeft = (int)$interval->format('%r%a');
                                                        if ($daysLeft >= 0) {
                                                            echo "เหลือ $daysLeft วัน";
                                                        } else {
                                                            echo "เลยกำหนด " . abs($daysLeft) . " วัน";
                                                        }
                                                        ?>
                                                    </span>
                                                <?php else: ?>
                                                    <span class="text-sm text-gray-400">-</span>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <!-- Progress Bar -->
                                <div class="">
                                    <div class="flex items-center bg-gray-50 p-3 rounded-xl ring-1 ring-gray-200">
                                        <div class="flex-1">
                                            <div class="w-full bg-gray-200 rounded-full h-1.5">
                                                <div class="<?= $order['status'] == 'cancelled' ? 'bg-red-600' : 'bg-zinc-900' ?> h-1.5 rounded-full"
                                                    style="width:<?= $order['status'] == 'pending' ? '25%' : ($order['status'] == 'in_progress' ? '50%' : ($order['status'] == 'completed' ? '100%' : ($order['status'] == 'cancelled' ? '100%' : '0%'))) ?>">
                                                </div>
                                            </div>
                                            <div class="flex justify-between text-xs mt-2 text-gray-500">
                                                <span>รอตรวจสอบ</span>
                                                <span>กำลังดำเนินการ</span>
                                                <span>เสร็จสมบูรณ์</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <?php if (!$payment_status): ?>
                                    <form action="order_delete.php" method="post" class="flex gap-2 w-full" onsubmit="event.stopPropagation(); return confirm('ยืนยันลบออเดอร์นี้?');">
                                        <input type="hidden" name="order_id" value="<?= $order['order_id'] ?>">
                                        <a
                                            onclick="window.location='payment.php?order_id=<?= $order['order_id'] ?>'"
                                            class="text-white bg-zinc-900 hover:bg-zinc-800 font-medium rounded-xl text-sm px-5 py-2 flex-1 text-center flex items-center justify-center mb-2">
                                            ไปชำระเงิน
                                        </a>
                                        <button type="button"
                                            onclick="confirmDelete(<?= $order['order_id'] ?>)"
                                            class="text-white bg-red-600 hover:bg-red-700 font-medium rounded-xl text-sm px-5 py-2 flex-1 text-center flex items-center justify-center mb-2">
                                            ลบออเดอร์
                                        </button>
                                    </form>
                                <?php else: ?>
                                    <a href="order_detail.php?order_id=<?= $order['order_id'] ?>" class="text-white bg-zinc-900 hover:bg-zinc-800 font-medium rounded-xl text-sm px-5 py-2 text-center flex items-center justify-center">
                                        ดูรายละเอียด
                                    </a>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        <!-- Cancel Confirmation Modal -->
        <div id="cancelModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black backdrop-blur-sm bg-opacity-50 hidden">
            <div class="bg-white rounded-3xl shadow-lg p-5 max-w-sm w-full text-center">
                <div class="text-xl font-bold mb-4">ยืนยันการยกเลิกออเดอร์</div>
                <div id="cancelModalMsg" class="mb-6 text-gray-700"></div>
                <div class="flex justify-center gap-4">
                    <button onclick="closeCancelModal()" class="w-full font-medium rounded-xl text-sm px-5 py-2 text-center flex items-center justify-center bg-gray-200 hover:bg-gray-300">ยกเลิก</button>
                    <button id="confirmCancelBtn" class="w-full font-medium rounded-xl text-sm px-5 py-2 text-center flex items-center justify-center bg-red-600 text-white hover:bg-red-700 transition-all">ยืนยัน</button>
                </div>
            </div>
        </div>
        <!-- Delete Confirmation Modal -->
        <div id="deleteModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black backdrop-blur-sm bg-opacity-50 hidden">
            <div class="bg-white rounded-3xl shadow-lg p-5 max-w-sm w-full text-center">
                <div class="text-xl font-bold mb-4">ยืนยันการลบออเดอร์</div>
                <div class="mb-6 text-gray-700">คุณต้องการลบออเดอร์นี้ถาวรใช่หรือไม่? <br> (ไม่สามารถกู้คืนได้)</div>
                <form id="deleteOrderForm" method="post" action="order_delete.php">
                    <input type="hidden" name="order_id" id="deleteOrderId">
                    <div class="flex justify-center gap-4">
                        <button type="button" onclick="closeDeleteModal()" class="w-full font-medium rounded-xl text-sm px-5 py-2 text-center flex items-center justify-center bg-gray-200 hover:bg-gray-300">ยกเลิก</button>
                        <button type="submit" class="w-full font-medium rounded-xl text-sm px-5 py-2 text-center flex items-center justify-center bg-red-600 text-white hover:bg-red-700 transition-all">ยืนยันลบ</button>
                    </div>
                </form>
            </div>
        </div>

        <script src="https://cdn.jsdelivr.net/npm/flowbite@3.1.2/dist/flowbite.min.js"></script>
        <script>
            let cancelOrderId = null;

            function confirmCancel(orderId, status) {
                cancelOrderId = orderId;
                let msg = "คุณต้องการยกเลิกออเดอร์นี้ใช่หรือไม่?<br>";
                if (status === 'pending') msg += "คืนเงิน 100%";
                else if (status === 'in_progress') msg += "คืนเงิน 50%";
                else msg += "ไม่สามารถคืนเงินได้";
                document.getElementById('cancelModalMsg').innerHTML = msg;
                document.getElementById('cancelModal').classList.remove('hidden');
            }

            function closeCancelModal() {
                document.getElementById('cancelModal').classList.add('hidden');
            }
            document.getElementById('confirmCancelBtn').onclick = function() {
                this.disabled = true;
                window.location = "/graphic-design/src/notifications/cancel_order.php?order_id=" + cancelOrderId;
            };

            function confirmDelete(orderId) {
                document.getElementById('deleteOrderId').value = orderId;
                document.getElementById('deleteModal').classList.remove('hidden');
            }

            function closeDeleteModal() {
                document.getElementById('deleteModal').classList.add('hidden');
            }
        </script>
        <!-- scroll ไปยัง card -->
        <?php if ($highlightOrderId): ?>
            <script>
                window.onload = function() {
                    var el = document.getElementById('order-<?= $highlightOrderId ?>');
                    if (el) {
                        el.scrollIntoView({
                            behavior: 'smooth',
                            block: 'center'
                        });
                    }
                };
            </script>
        <?php endif; ?>
</body>

</html>