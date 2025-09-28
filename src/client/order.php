<?php
require '../auth/auth.php';
requireLogin();
require __DIR__ . '/../includes/db_connect.php';

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
            return 'ยกเลิก';
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

function getOrderProgressSteps($status)
{
    // กำหนดขั้นตอนและสถานะปัจจุบัน
    $steps = [
        ['label' => 'กำลังตรวจสอบ', 'key' => 'pending'],
        ['label' => 'กำลังออกแบบ', 'key' => 'in_progress'],
        ['label' => 'ส่งแบบร่าง', 'key' => 'waiting_approve'],
        ['label' => 'ส่งงานไฟล์สุดท้าย', 'key' => 'completed'],
    ];
    // หาค่า index ขั้นตอนปัจจุบัน
    switch ($status) {
        case 'pending':
            $current = 0;
            break;
        case 'in_progress':
            $current = 1;
            break;
        case 'waiting_approve':
            $current = 2;
            break;
        case 'completed':
            $current = 3;
            break;
        default:
            $current = 0;
    }
    return [$steps, $current];
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
?>

<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8">
    <title>รายการสั่งซื้อของฉัน | Graphic Design</title>
    <link href="https://fonts.googleapis.com/css2?family=IBM+Plex+Sans+Thai:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/flowbite/1.7.0/flowbite.min.css" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />
    <link href="../../dist/output.css" rel="stylesheet" />
    <style>
        .font-thai {
            font-family: 'IBM Plex Sans Thai', sans-serif;
        }
    </style>
    <script>
        function openModal(orderId) {
            document.getElementById('modal-' + orderId).classList.remove('hidden');
        }

        function closeModal(orderId) {
            document.getElementById('modal-' + orderId).classList.add('hidden');
        }
    </script>
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
        <div class=" text-zinc-900 bg-white rounded-2xl border border-slate-200 mb-2">
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
                <div class="grid grid-cols-2 md:grid-cols-4 gap-4 p-4 rounded-2xl">
                    <div class="bg-white flex items-center p-2 ring-1 ring-zinc-200 rounded-2xl">
                        <div class="mr-4 rounded-full text-yellow-600 bg-yellow-100 ring-1 ring-yellow-200 p-3">
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
                        <div class="mr-4 rounded-full text-blue-600 bg-blue-100 ring-1 ring-blue-200 p-3">
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
                        <div class="mr-4 rounded-full text-green-600 bg-green-100 ring-1 ring-green-200 p-3">
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
                        <div class="mr-4 rounded-full text-red-600 bg-red-100 ring-1 ring-red-200 p-3">
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
                    <div class="bg-white p-8 rounded-xl shadow text-center text-gray-500">ยังไม่มีรายการสั่งซื้อ</div>
                <?php else: ?>
                    <div class="grid grid-cols-1 md:grid-cols-4 gap-6 w-full">
                        <?php foreach ($filteredOrders as $order): ?>
                            <?php
                            $detail = getOrderDetail($conn, $order['service_id'], $order['ref_id']);
                            ?>
                            <!-- card -->
                            <div class="bg-white rounded-2xl shadow-sm p-4 space-y-2 cursor-pointer ring-1 ring-gray-200  transition-all duration-300 ease-in-out hover:scale-105">
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
                                <a onclick="event.stopPropagation(); openModal(<?= $order['order_id'] ?>);" class="text-white bg-zinc-900 hover:bg-zinc-800 font-medium rounded-xl text-sm px-5 py-2 text-center flex items-center justify-center">
                                    ดูรายละเอียด
                                </a>
                            </div>
                            <!-- Modal -->
                            <div id="modal-<?= $order['order_id'] ?>" class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-40 backdrop-blur-sm hidden">
                                <!-- modal content -->
                                <div class="bg-gray-100 rounded-xl shadow-lg w-full mx-10 max-h-[90vh] my-10 flex flex-col overflow-hidden">
                                    <!-- Modal header -->
                                    <div class="flex items-center justify-between p-2 md:p-5 border-b rounded-t border-gray-200 bg-white">
                                        <div class="flex items-center text-center">
                                            <p class="text-gray-600 text-sm py-1 ml-2">#<?php echo htmlspecialchars($order['order_code'] ?? ('#' . $order['order_id'])); ?></p>
                                        </div>
                                        <button onclick="closeModal(<?= $order['order_id'] ?>)" type="button" class="text-gray-400 bg-transparent hover:bg-red-100 hover:text-red-600 rounded-lg text-sm w-8 h-8 ms-auto inline-flex justify-center items-center">
                                            <svg class="w-3 h-3" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 14 14">
                                                <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m1 1 6 6m0 0 6 6M7 7l6-6M7 7l-6 6" />
                                            </svg>
                                        </button>
                                    </div>
                                    <!-- Modal body (scrollable) -->
                                    <div class="flex-1 overflow-y-auto p-10">
                                        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                                            <!-- Left Column - Order Details -->
                                            <div class="lg:col-span-2 space-y-6">

                                                <!-- Order Summary -->
                                                <div class="bg-white p-6 rounded-xl mb-6 ring-1 ring-gray-200">
                                                    <h2 class="text-lg font-semibold mb-4">สรุปคำสั่งงาน</h2>
                                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                                        <div>
                                                            <h3 class="font-medium text-gray-900 mb-2">ข้อมูลพื้นฐาน</h3>
                                                            <div class="space-y-2">
                                                                <p class="text-sm flex justify-between">
                                                                    <span class="text-zinc-600 font-medium">เลขที่งาน:</span>
                                                                    <span class="text-gray-500 text-sm">#<?= htmlspecialchars($order['order_code'] ?? ('#' . $order['order_id'])) ?></span>
                                                                </p>
                                                                <p class="text-sm flex justify-between">
                                                                    <span class="text-zinc-600 font-medium">วันที่สร้าง:</span>
                                                                    <span class="text-gray-500 text-sm flex justify-between"><?= date('d/m/Y', strtotime($order['created_at'])) ?></span>
                                                                </p>
                                                                <p class="text-sm flex justify-between">
                                                                    <span class="text-zinc-600 font-medium">กำหนดส่ง:</span>
                                                                    <?php if (!empty($detail['due_date'])): ?>
                                                                        <span>
                                                                            <span class="text-gray-500 text-sm"><?= date('d/m/Y', strtotime($detail['due_date'])) ?></span>
                                                                            <span class="text-sm text-blue-600">
                                                                                <?php
                                                                                // คำนวณวันคงเหลือ
                                                                                $now = new DateTime();
                                                                                $due = new DateTime($detail['due_date']);
                                                                                $interval = $now->diff($due);
                                                                                $daysLeft = (int)$interval->format('%r%a');
                                                                                if ($daysLeft >= 0) {
                                                                                    echo "(เหลือ $daysLeft วัน)";
                                                                                } else {
                                                                                    echo "(เลยกำหนด " . abs($daysLeft) . " วัน)";
                                                                                }
                                                                                ?>
                                                                            </span>
                                                                        <?php else: ?>
                                                                            <span class="text-sm text-gray-400">-</span>
                                                                        <?php endif; ?>
                                                                        </span>
                                                                </p>
                                                            </div>
                                                        </div>
                                                        <div>
                                                            <h3 class="font-medium text-gray-900 mb-2">รายละเอียดบริการ</h3>
                                                            <div class="space-y-2">
                                                                <p class="text-sm flex justify-between">
                                                                    <span class="text-zinc-600 font-medium">ประเภท:</span>
                                                                    <span class="text-gray-500 text-sm"><?= htmlspecialchars($order['service_name']) ?></span>
                                                                </p>
                                                                <?php if (!empty($detail['poster_type'])): ?>
                                                                    <p class="text-sm flex justify-between">
                                                                        <span class="text-zinc-600 font-medium">ประเภทโปสเตอร์:</span>
                                                                        <span class="text-gray-500 text-sm"><?= htmlspecialchars($detail['poster_type']) ?></span>
                                                                    </p>
                                                                <?php endif; ?>
                                                                <?php if (!empty($detail['design_count'])): ?>
                                                                    <p class="text-sm flex justify-between">
                                                                        <span class="text-zinc-600 font-medium">จำนวนแบบ:</span>
                                                                        <span class="text-gray-500 text-sm"><?= htmlspecialchars($detail['design_count']) ?></span> แบบ
                                                                    </p>
                                                                <?php endif; ?>
                                                                <?php if (!empty($detail['revision_limit'])): ?>
                                                                    <p class="text-sm flex justify-between">
                                                                        <span class="text-zinc-600 font-medium">แก้ไขได้:</span>
                                                                        <span class="text-gray-500 text-sm"><?= htmlspecialchars($detail['revision_limit']) ?></span> ครั้ง
                                                                    </p>
                                                                <?php endif; ?>
                                                                <?php if (!empty($detail['price'])): ?>
                                                                    <p class="text-sm flex justify-between">
                                                                        <span class="text-zinc-600 font-medium">ราคา:</span>
                                                                        <span class="text-gray-500 text-sm">฿<?= number_format($detail['price']) ?></span>
                                                                    </p>
                                                                <?php endif; ?>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>

                                                <!-- Design Submissions -->
                                                <div class="bg-white rounded-xl shadow-sm p-6 ring-1 ring-gray-200">
                                                    <h2 class="text-lg font-semibold mb-4">ไฟล์งานที่ได้รับ</h2>

                                                    <!-- Draft 1 -->
                                                    <div class="border border-gray-200 rounded-lg p-4 mb-4">
                                                        <div class="flex justify-between items-center mb-3">
                                                            <h3 class="font-medium">แบบร่างที่ 1</h3>
                                                            <span class="text-sm text-gray-500">ส่งเมื่อ 17 ส.ค. 2023, 14:30 น.</span>
                                                        </div>
                                                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4">
                                                            <div class="border border-gray-200 rounded-lg overflow-hidden">
                                                                <img src="https://images.unsplash.com/photo-1611162616305-c69b3fa7fbe0?ixlib=rb-4.0.3&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D&auto=format&fit=crop&w=1074&q=80"
                                                                    alt="Draft 1 - Concept A"
                                                                    class="w-full object-cover hover:opacity-90 cursor-pointer">
                                                            </div>
                                                            <div class="border border-gray-200 rounded-lg overflow-hidden">
                                                                <img src="https://images.unsplash.com/photo-1611162616475-465b2134c4a1?ixlib=rb-4.0.3&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D&auto=format&fit=crop&w=1170&q=80"
                                                                    alt="Draft 1 - Concept B"
                                                                    class="w-full object-cover hover:opacity-90 cursor-pointer">
                                                            </div>
                                                            <div class="border border-gray-200 rounded-lg overflow-hidden">
                                                                <img src="https://images.unsplash.com/photo-1611162616305-c69b3fa7fbe0?ixlib=rb-4.0.3&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D&auto=format&fit=crop&w=1074&q=80"
                                                                    alt="Draft 1 - Concept C"
                                                                    class="w-full object-cover hover:opacity-90 cursor-pointer">
                                                            </div>
                                                        </div>
                                                        <div class="bg-blue-50 p-4 rounded-lg mb-4">
                                                            <h4 class="font-medium text-blue-800 mb-2">ความคิดเห็นจากนักออกแบบ</h4>
                                                            <p class="text-blue-700">เราได้ออกแบบ 3 แบบตามความต้องการของคุณ แบบ A เน้นความทันสมัยด้วยเส้นสายเรขาคณิต แบบ B ใช้รูปทรงออร์แกนิกที่ดูเป็นมิตร ส่วนแบบ C ผสมผสานทั้งสองสไตล์ กรุณาเลือกแบบที่ชอบหรือระบุจุดที่ต้องการแก้ไข</p>
                                                        </div>
                                                    </div>
                                                    <div class="flex justify-end space-x-3">
                                                        <button class="font-medium rounded-xl text-sm px-5 py-2 text-center flex items-center justify-center border border-gray-300 text-gray-700 hover:bg-gray-100">
                                                            ขอแก้ไข
                                                        </button>
                                                        <button class="text-white bg-zinc-900 hover:bg-zinc-800 font-medium rounded-xl text-sm px-5 py-2 text-center flex items-center justify-center">
                                                            อนุมัติแบบนี้
                                                        </button>
                                                    </div>
                                                </div>


                                            </div>

                                            <!-- Right Column - Timeline & Actions -->
                                            <div class="space-y-6">
                                                <!-- Progress -->
                                                <?php list($steps, $currentStep) = getOrderProgressSteps($order['status']); ?>
                                                <div class="bg-white rounded-xl shadow-sm p-6 ring-1 ring-gray-200">
                                                    <div class="flex items-center justify-between mb-4">
                                                        <h2 class="text-lg font-bold text-gray-900">สถานะงาน</h2>
                                                        <?php if ($order['status'] === 'cancelled'): ?>
                                                            <span class="text-red-600 text-xs font-medium bg-red-100 px-2 py-1 rounded-md flex items-center">
                                                                ออเดอร์นี้ถูกยกเลิกแล้ว</span>
                                                        <?php elseif ($order['status'] === 'completed'): ?>
                                                            <span class="text-green-600 text-xs font-medium bg-green-100 px-2 py-1 rounded-md flex items-center">
                                                                ออเดอร์นี้สำเร็จแล้ว</span>
                                                        <?php endif; ?>
                                                    </div>
                                                    <div class="space-y-4">
                                                        <?php foreach ($steps as $i => $step): ?>
                                                            <div class="flex items-start">
                                                                <!-- จุดแสดงสถานะ -->
                                                                <div class="flex-shrink-0 w-4 h-4 mt-1 
                                                        <?= $i < $currentStep ? 'bg-zinc-950' : ($i == $currentStep ? 'bg-blue-500 ring ring-blue-200 ring-offset-2 ' : 'bg-gray-300') ?>
                                                        rounded-full"></div>
                                                                <!-- ขั้นตอน -->
                                                                <div class="ml-3">
                                                                    <p class="font-medium <?= $i == $currentStep ? 'text-zinc-950' : ($i < $currentStep ? 'text-zinc-950' : 'text-gray-300') ?>">
                                                                        <?= $step['label'] ?>
                                                                    </p>
                                                                    <?php if ($i == $currentStep): ?>
                                                                        <p class="text-blue-500 text-sm">ขั้นตอนปัจจุบัน</p>
                                                                    <?php endif; ?>
                                                                </div>
                                                            </div>
                                                        <?php endforeach; ?>
                                                    </div>
                                                </div>

                                                <?php if ($order['status'] !== 'cancelled'): ?>
                                                    <!-- Chat -->
                                                    <div class="bg-white rounded-xl shadow-sm p-6 ring-1 ring-gray-200">
                                                        <h2 class="text-lg font-bold text-gray-900 mb-4">แชทกับนักออกแบบ</h2>
                                                        <div class="space-y-4 mb-6">
                                                            <div class="flex items-start">
                                                                <div class="mr-10">
                                                                    <div class="bg-gray-100 rounded-xl py-2 px-4 inline-block">
                                                                        <p class="text-gray-800">สวัสดีครับ ผมส่งตัวอย่างโลโก้รอบแรกมาให้ดูครับ</p>
                                                                    </div>
                                                                    <p class="text-xs text-gray-500 mt-1">16/08/2023 14:30 น.</p>
                                                                </div>
                                                            </div>
                                                            <div class="flex items-start flex-row-reverse">
                                                                <div class="ml-10 ">
                                                                    <div class="bg-zinc-900 rounded-xl py-2 px-4 inline-block">
                                                                        <p class="text-white">สวัสดีค่ะ ชอบแนวทางนี้ค่ะ แต่ช่วยปรับสีฟ้าให้เข้มขึ้นหน่อยได้ไหมคะ</p>
                                                                    </div>
                                                                    <p class="text-xs text-gray-500 mt-1 text-right">16/08/2023 15:45 น.</p>
                                                                </div>
                                                            </div>
                                                            <div class="flex items-start">
                                                                <div class="mr-10">
                                                                    <div class="bg-gray-100 rounded-xl py-2 px-4 inline-block">
                                                                        <p class="text-gray-800">ได้ครับ เดี๋ยวผมปรับให้ครับ น่าจะเสร็จพรุ่งนี้เช้าครับ</p>
                                                                    </div>
                                                                    <p class="text-xs text-gray-500 mt-1">16/08/2023 16:20 น.</p>
                                                                </div>
                                                            </div>
                                                        </div>
                                                        <div class="border-t border-gray-200 pt-4">
                                                            <div class="flex items-start space-x-3">
                                                                <div class="flex-1 space-y-2">
                                                                    <div class="">
                                                                        <textarea rows="2" class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500" placeholder="พิมพ์ข้อความ..."></textarea>
                                                                    </div>
                                                                    <div class="">
                                                                        <button class="w-full text-white bg-zinc-900 hover:bg-zinc-800 font-medium rounded-xl text-sm px-5 py-2 text-center flex items-center justify-center">
                                                                            ส่งข้อความ
                                                                        </button>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                <?php endif; ?>

                                                <!-- Quick Actions -->
                                                <div class="bg-white rounded-xl shadow-sm p-6 ring-1 ring-gray-200">
                                                    <h2 class="text-lg font-semibold mb-4">การดำเนินการ</h2>
                                                    <div class="space-y-3">
                                                        <button class="w-full flex items-center justify-between p-3 border border-gray-200 rounded-lg hover:bg-gray-50">
                                                            <span>แจ้งปัญหาหรือคำถาม</span>
                                                            <i class="fas fa-question-circle text-gray-400"></i>
                                                        </button>
                                                        <?php if (in_array($order['status'], ['pending', 'in_progress'])): ?>
                                                            <button onclick="event.stopPropagation(); confirmCancel(<?= $order['order_id'] ?>, '<?= $order['status'] ?>');"
                                                                class="w-full flex items-center justify-between p-3 border border-red-200 text-red-600 rounded-lg hover:bg-red-50">
                                                                <span>ยกเลิกงาน</span>
                                                                <i class="fas fa-times text-red-400"></i>
                                                            </button>
                                                        <?php elseif (in_array($order['status'], ['completed', 'cancelled'])): ?>
                                                            <a href="/graphic-design/src/client/poster_details.php?service_id=1"
                                                                class="w-full flex items-center justify-between p-3 border border-blue-200 text-blue-600 rounded-lg hover:bg-blue-50">
                                                                <span>สั่งซ้ำ</span>
                                                                <i class="fas fa-redo text-blue-400"></i>
                                                            </a>
                                                        <?php endif; ?>
                                                    </div>
                                                </div>

                                                <?php if ($order['status'] !== 'completed'): ?>
                                                    <!-- Review -->
                                                    <div class="bg-white rounded-xl shadow-sm p-6 ring-1 ring-gray-200">
                                                        <h2 class="text-lg font-bold text-gray-900 mb-4">ให้คะแนนงานนี้</h2>
                                                        <p class="text-sm text-gray-500 mb-4">คุณพอใจกับงานออกแบบนี้หรือไม่?</p>
                                                        <div class="flex items-center mb-4">
                                                            <button class="text-gray-300 hover:text-yellow-400 focus:outline-none">
                                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8" viewBox="0 0 20 20" fill="currentColor">
                                                                    <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" />
                                                                </svg>
                                                            </button>
                                                            <button class="text-gray-300 hover:text-yellow-400 focus:outline-none">
                                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8" viewBox="0 0 20 20" fill="currentColor">
                                                                    <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" />
                                                                </svg>
                                                            </button>
                                                            <button class="text-gray-300 hover:text-yellow-400 focus:outline-none">
                                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8" viewBox="0 0 20 20" fill="currentColor">
                                                                    <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" />
                                                                </svg>
                                                            </button>
                                                            <button class="text-gray-300 hover:text-yellow-400 focus:outline-none">
                                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8" viewBox="0 0 20 20" fill="currentColor">
                                                                    <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" />
                                                                </svg>
                                                            </button>
                                                            <button class="text-gray-300 hover:text-yellow-400 focus:outline-none">
                                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8" viewBox="0 0 20 20" fill="currentColor">
                                                                    <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" />
                                                                </svg>
                                                            </button>
                                                        </div>
                                                        <textarea rows="3" class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 mb-4" placeholder="เขียนรีวิว..."></textarea>
                                                        <button class="w-full text-white bg-zinc-900 hover:bg-zinc-800 font-medium rounded-xl text-sm px-5 py-2 text-center flex items-center justify-center">
                                                            ส่งรีวิว
                                                        </button>
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                </div>
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

        <script src="https://cdn.jsdelivr.net/npm/flowbite@3.1.2/dist/flowbite.min.js"></script>
        <script>
            function openModal(orderId) {
                document.getElementById('modal-' + orderId).classList.remove('hidden');
                document.body.classList.add('overflow-hidden'); // ป้องกัน scroll
            }

            function closeModal(orderId) {
                document.getElementById('modal-' + orderId).classList.add('hidden');
                document.body.classList.remove('overflow-hidden'); // กลับมา scroll ได้
            }

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
                window.location = "order_cancel.php?order_id=" + cancelOrderId;
            };
        </script>
</body>

</html>