<?php
// ตรวจสอบ error
error_reporting(E_ALL);
ini_set('display_errors', 1);
require __DIR__ . '/../includes/db_connect.php';
require_once __DIR__ . '/../notifications/notify_helper.php';
require_once '../auth/auth.php';
requireRole('admin'); // ให้เข้าหน้านี้ได้เฉพาะ admin

$paymentId = $_GET['id'] ?? 0;
$sql = "SELECT 
    p.*, 
    o.order_code, o.order_id, o.created_at AS order_date,
    c.fullname, c.phone, c.email
FROM payments p
LEFT JOIN orders o ON p.order_id = o.order_id
LEFT JOIN customers c ON o.customer_id = c.customer_id
WHERE p.payment_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $paymentId);
$stmt->execute();
$payment = $stmt->get_result()->fetch_assoc();

// อัปเดตสถานะการชำระเงิน
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    $newStatus = $_POST['payment_status'];
    $paymentId = $_POST['payment_id'];
    $rejectReason = $_POST['reject_reason'] ?? '';
    if ($newStatus === 'cancelled' && $rejectReason) {
        $stmt = $conn->prepare("UPDATE payments SET payment_status = ?, remark = ? WHERE payment_id = ?");
        $stmt->bind_param("ssi", $newStatus, $rejectReason, $paymentId);
    } else {
        $stmt = $conn->prepare("UPDATE payments SET payment_status = ? WHERE payment_id = ?");
        $stmt->bind_param("si", $newStatus, $paymentId);
    }
    $stmt->execute();

    // ส่งแจ้งเตือนไปยังลูกค้า
    $customerId = $payment['customer_id'];
    $orderCode = $payment['order_code'] ?? $payment['order_id'] ?? '-';
    $orderId = $payment['order_id'];
    notifyPaymentStatusToCustomer($conn, $customerId, $orderId, $orderCode, $newStatus, $rejectReason);
    header("Location: payment_detail.php?id=" . $paymentId);
    exit;
}
// var_dump($payment['slip_file']);
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
// ฟังก์ชันช่วยแปลงประเภทการชำระเป็น badge
function getPaymentTypeBadge($type)
{
    switch ($type) {
        case 'full':
            return '<span class="px-3 py-1 text-xs font-semibold text-purple-700 bg-purple-100 rounded-lg">เต็มจำนวน</span>';
        case 'partial':
            return '<span class="px-3 py-1 text-xs font-semibold text-yellow-700 bg-yellow-100 rounded-lg">บางส่วน</span>';
        default:
            return '<span class="px-3 py-1 text-xs font-semibold text-gray-700 bg-gray-100 rounded-lg">' . htmlspecialchars($type) . '</span>';
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment Detail</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=IBM+Plex+Sans+Thai:wght@300;400;500;600;700&display=swap"
        rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .font-thai {
            font-family: 'IBM Plex Sans Thai', sans-serif;
        }
    </style>
</head>

<body class="bg-zinc-100 font-thai">
    <?php include __DIR__ . '/../includes/sidebar.php'; ?>
    <div class="ml-64">
        <!-- breadcrumb -->
        <?php
        $breadcrumb = ['Dashboard', 'รายการชำระเงิน', 'รายละเอียดการชำระเงิน'];
        $breadcrumb_links = ['/graphic-design/src/admin/index.php', '/graphic-design/src/admin/payment_list.php'];
        include '../includes/admin_navbar.php';
        ?>
        <div class="p-6">
            <main class="">
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                    <!-- Left Column: Payment Details -->
                    <div class="lg:col-span-2 space-y-6">
                        <!-- Order Information -->
                        <div class="bg-white rounded-2xl mb-6 ring-1 ring-gray-200">
                            <div class="border-b bg-gray-50 rounded-t-2xl flex justify-between items-center">
                                <h2 class="text-md font-semibold p-2 pl-4 ml-2">ข้อมูลคำสั่งซื้อ</h2>
                            </div>
                            <div class="p-6">
                                <div class="grid grid-cols-2 gap-6">
                                    <div>
                                        <label class="text-gray-500 text-sm">เลขที่คำสั่งซื้อ</label>
                                        <p class="text-sm font-bold"><?= htmlspecialchars($payment['order_code'] ?? $payment['order_id'] ?? '-') ?></p>
                                    </div>
                                    <div>
                                        <label class="text-gray-500 text-sm">สถานะการชำระเงิน</label>
                                        <p class="text-sm font-bold"><?= getStatusBadge($payment['payment_status'] ?? '-') ?></p>
                                    </div>
                                    <div>
                                        <label class="text-gray-500 text-sm">วันที่สั่ง</label>
                                        <p class="text-sm font-bold"><?= htmlspecialchars($payment['order_date'] ?? '-') ?></p>
                                    </div>
                                    <div>
                                        <label class="text-gray-500 text-sm">กำหนดส่งงาน</label>
                                        <p class="text-sm font-bold"><?= htmlspecialchars($payment['due_date'] ?? '-') ?></p>
                                    </div>
                                </div>
                            </div>
                        </div>


                        <div class="bg-white rounded-2xl mb-6 ring-1 ring-gray-200">
                            <div class="border-b bg-gray-50 rounded-t-2xl">
                                <h2 class="text-md font-semibold p-2 pl-4 ml-2">ข้อมูลการชำระเงิน</h2>
                            </div>
                            <div class="p-6">
                                <div class="grid grid-cols-2 gap-6 mb-6">
                                    <div>
                                        <label class="text-gray-500 text-sm">วิธีการชำระเงิน</label>
                                        <div class="flex items-center space-x-3 mt-2">
                                            <div class="w-10 h-10 bg-blue-100 rounded-lg flex items-center justify-center">
                                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"
                                                    class="w-7 h-7 text-blue-600">
                                                    <path
                                                        d="M11.584 2.376a.75.75 0 0 1 .832 0l9 6a.75.75 0 1 1-.832 1.248L12 3.901 3.416 9.624a.75.75 0 0 1-.832-1.248l9-6Z" />
                                                    <path fill-rule="evenodd"
                                                        d="M20.25 10.332v9.918H21a.75.75 0 0 1 0 1.5H3a.75.75 0 0 1 0-1.5h.75v-9.918a.75.75 0 0 1 .634-.74A49.109 49.109 0 0 1 12 9c2.59 0 5.134.202 7.616.592a.75.75 0 0 1 .634.74Zm-7.5 2.418a.75.75 0 0 0-1.5 0v6.75a.75.75 0 0 0 1.5 0v-6.75Zm3-.75a.75.75 0 0 1 .75.75v6.75a.75.75 0 0 1-1.5 0v-6.75a.75.75 0 0 1 .75-.75ZM9 12.75a.75.75 0 0 0-1.5 0v6.75a.75.75 0 0 0 1.5 0v-6.75Z"
                                                        clip-rule="evenodd" />
                                                    <path d="M12 7.875a1.125 1.125 0 1 0 0-2.25 1.125 1.125 0 0 0 0 2.25Z" />
                                                </svg>
                                            </div>
                                            <div>
                                                <p class="font-semibold text-gray-900"><?= htmlspecialchars($payment['payment_method'] ?? '-') ?></p>
                                                <p class="text-sm text-gray-500"><?= htmlspecialchars($payment['payment_bank'] ?? '-') ?></p>
                                            </div>
                                        </div>
                                    </div>
                                    <div>
                                        <label class="text-gray-500 text-sm mb-5 block">ประเภทการชำระ</label>
                                        <?= getPaymentTypeBadge($payment['payment_type'] ?? 'เต็มจำนวน') ?>
                                    </div>
                                </div>
                                <!-- Amount Breakdown -->
                                <div class="space-y-3 rounded-xl bg-white p-4 shadow-sm ring-1 ring-gray-200">
                                    <h4 class="font-semibold text-gray-800">รายละเอียดธุรกรรม</h4>
                                    <!-- Project Details -->
                                    <div class="space-y-4">
                                        <!-- Pricing Breakdown -->
                                        <div class="p-4 bg-gray-50 ring-1 ring-gray-200 rounded-xl">
                                            <div class="grid grid-cols-2 gap-3 text-sm">
                                                <div>
                                                    <span class="text-gray-600">เลขที่อ้างอิง:</span>
                                                    <span class="font-mono font-semibold text-gray-900 ml-2"><?= htmlspecialchars($payment['reference_no'] ?? '-') ?></span>
                                                </div>
                                                <div>
                                                    <span class="text-gray-600">วันที่โอน:</span>
                                                    <span class="font-semibold text-gray-900 ml-2"><?= htmlspecialchars($payment['payment_date'] ?? '-') ?></span>
                                                </div>
                                                <div>
                                                    <span class="text-gray-600">จำนวนที่โอน:</span>
                                                    <span class="font-bold text-zinc-600 ml-2 text-lg"><?= htmlspecialchars($payment['amount'] ?? '฿0.00') ?></span>
                                                </div>
                                                <div>
                                                    <span class="text-gray-600">ธนาคารต้นทาง:</span>
                                                    <span class="font-semibold text-gray-900 ml-2">ธนาคารออมสิน</span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Payment Slip -->
                        <div class="bg-white rounded-2xl mb-6 ring-1 ring-gray-200">
                            <div class="border-b bg-gray-50 rounded-t-2xl">
                                <h2 class="text-md font-semibold p-2 pl-4 ml-2">สลิปการโอนเงิน</h2>
                            </div>
                            <div class="p-6">
                                <!-- Slip Image -->
                                <div class="border-2 border-dashed border-gray-300 rounded-xl p-4 bg-gray-50">
                                    <div class="text-center">
                                        <?php
                                        $slipImage = !empty($payment['slip_file'])
                                            ? (strpos($payment['slip_file'], '/graphic-design/') === 0
                                                ? $payment['slip_file']
                                                : '/graphic-design' . $payment['slip_file'])
                                            : 'https://via.placeholder.com/400x300?text=No+Image';
                                        ?>
                                        <img src="<?= htmlspecialchars($slipImage) ?>"
                                            alt="Payment Slip"
                                            class="mx-auto rounded-lg max-w-full h-auto cursor-pointer"
                                            onclick="openImageModal(this.src)">
                                        <div class="mt-4 flex items-center justify-center space-x-4">
                                            <button
                                                onclick="openImageModal('<?= htmlspecialchars($slipImage) ?>')"
                                                class="text-white bg-zinc-900 hover:bg-zinc-800 font-medium rounded-xl text-sm px-5 py-2 text-center transition-all duration-300 ease-in-out hover:scale-105 flex items-center justify-center">
                                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                        d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0zM10 7v3m0 0v3m0-3h3m-3 0H7">
                                                    </path>
                                                </svg>
                                                <span>ดูขนาดเต็ม</span>
                                            </button>
                                            <a href="<?= htmlspecialchars($slipImage) ?>" download target="_blank"
                                                class="bg-zinc-200 text-gray-700 hover:bg-zinc-300 font-medium rounded-xl text-sm px-5 py-2 text-center transition-all duration-300 ease-in-out hover:scale-105 flex items-center justify-center">
                                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                        d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path>
                                                </svg>
                                                <span>ดาวน์โหลด</span>
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Right Column: Actions & History -->
                    <div class="space-y-6">
                        <!-- ข้อมูลลูกค้า -->
                        <div class="bg-white rounded-2xl mb-6 ring-1 ring-gray-200">
                            <div class="border-b bg-gray-50 rounded-t-2xl">
                                <h2 class="text-md font-semibold p-2 pl-4 ml-2">ข้อมูลลูกค้า</h2>
                            </div>
                            <div class="p-6">
                                <div class="flex items-start space-x-3">
                                    <div
                                        class="w-12 h-12 bg-zinc-900 rounded-xl flex items-center justify-center flex-shrink-0">
                                        <span class="text-white font-bold text-lg">
                                            <?= !empty($payment['fullname']) ? mb_substr(trim($payment['fullname']), 0, 1, 'UTF-8') : '-' ?>
                                        </span>
                                    </div>
                                    <div>
                                        <p class="font-semibold text-gray-900"><?= htmlspecialchars($payment['fullname'] ?? '-') ?></p>
                                        <p class="text-sm text-gray-500">ลูกค้าใหม่</p>
                                    </div>
                                </div>
                                <div class="space-y-3 mt-4 text-sm bg-white flex flex-col justify-start p-3 ring-1 ring-zinc-200 rounded-2xl">
                                    <div class="flex items-center space-x-3 text-gray-700">
                                        <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor"
                                            viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z">
                                            </path>
                                        </svg>
                                        <span><?= htmlspecialchars($payment['phone'] ?? '-') ?></span>
                                    </div>
                                    <div class="flex items-center space-x-3 text-gray-700">
                                        <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor"
                                            viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M3 8l7.89 4.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z">
                                            </path>
                                        </svg>
                                        <span><?= htmlspecialchars($payment['email'] ?? '-') ?></span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Admin Actions -->
                        <div class="bg-white rounded-2xl mb-6 ring-1 ring-gray-200">
                            <div class="border-b bg-gray-50 rounded-t-2xl">
                                <h2 class="text-md font-semibold p-2 pl-4 ml-2">การดำเนินการ</h2>
                            </div>
                            <div class="p-6">
                                <!-- Quick Actions -->
                                <?php
                                $isActionDisabled = ($payment['payment_status'] !== 'pending');
                                ?>
                                <div class="space-y-3 mb-6">
                                    <button onclick="approvePayment()"
                                        class="w-full border transition font-medium rounded-xl text-sm px-5 py-2 text-center flex items-center justify-center bg-zinc-900 hover:bg-zinc-700 text-white border-zinc-900"
                                        <?= $isActionDisabled ? 'disabled style="opacity:0.5;cursor:not-allowed;"' : '' ?>>
                                        <span>อนุมัติการชำระเงิน</span>
                                    </button>

                                    <button onclick="rejectPayment()"
                                        class="w-full font-medium rounded-xl text-sm px-5 py-2 text-center flex items-center justify-center bg-red-600 text-white hover:bg-red-700 transition-all"
                                        <?= $isActionDisabled ? 'disabled style="opacity:0.5;cursor:not-allowed;"' : '' ?>>
                                        <span>ปฏิเสธการชำระเงิน</span>
                                    </button>
                                </div>
                                <div class="mt-4 bg-blue-50 border border-blue-200 rounded-xl p-2 text-xs text-blue-700">
                                    <svg class="w-4 h-4 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                    </svg>
                                    การเปลี่ยนสถานะจะส่งการแจ้งเตือนไปยังลูกค้าอัตโนมัติ
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </main>

            <!-- Hidden form for status update -->
            <form id="statusForm" method="post" style="display:none;">
                <input type="hidden" name="payment_id" value="<?= htmlspecialchars($paymentId) ?>">
                <input type="hidden" name="payment_status" id="statusInput">
                <input type="hidden" name="reject_reason" id="reasonInput">
                <input type="hidden" name="update_status" value="1">
            </form>

            <!-- Image Modal -->
            <div id="imageModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 backdrop-blur-md bg-opacity-50 hidden"
                onclick="closeImageModal()">
                <div class="relative max-w-4xl w-full">
                    <button onclick="closeImageModal()"
                        class="absolute top-4 right-4 bg-white rounded-full p-2 hover:bg-gray-100 transition-all">
                        <svg class="w-6 h-6 text-gray-800" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12">
                            </path>
                        </svg>
                    </button>
                    <img id="modalImage" src="" alt="Payment Slip" class="w-full h-auto rounded-lg shadow-2xl">
                </div>
            </div>

            <!-- Approval Confirmation Modal -->
            <div id="approvalModal"
                class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 backdrop-blur-md bg-opacity-50 hidden">
                <div class="bg-white rounded-3xl shadow-lg p-8 max-w-sm w-full text-center relative">
                    <div class="text-center">
                        <div class="w-16 h-16 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-4">
                            <svg class="w-8 h-8 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                        </div>
                        <h3 class="text-2xl font-bold text-gray-900 mb-2">ยืนยันการอนุมัติ?</h3>
                        <p class="text-gray-600 mb-6">คุณต้องการอนุมัติการชำระเงินนี้ใช่หรือไม่?</p>

                        <div class="space-y-3">
                            <button onclick="confirmApproval()"
                                class="w-full border transition font-medium rounded-xl text-sm px-5 py-2 text-center flex items-center justify-center bg-zinc-900 hover:bg-zinc-700 text-white border-zinc-900">
                                ยืนยันการอนุมัติ
                            </button>
                            <button onclick="closeApprovalModal()"
                                class="w-full border transition font-medium rounded-xl text-sm px-5 py-2 text-center flex items-center justify-center bg-white text-gray-600 border-gray-300 hover:bg-gray-100">
                                ยกเลิก
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Rejection Modal -->
            <div id="rejectionModal"
                class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 backdrop-blur-md bg-opacity-50 hidden">
                <div class="bg-white rounded-3xl shadow-lg p-8 max-w-sm w-full text-center relative">
                    <div class="text-center">
                        <div class="w-16 h-16 bg-red-100 rounded-full flex items-center justify-center mx-auto mb-4">
                            <svg class="w-8 h-8 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                        </div>
                        <h3 class="text-2xl font-bold text-gray-900 mb-2">ปฏิเสธการชำระเงิน</h3>
                        <p class="text-gray-600 mb-4">กรุณาระบุเหตุผลในการปฏิเสธ</p>

                        <textarea
                            rows="4"
                            class="block w-full rounded-2xl border border-gray-300 bg-gray-50 p-2.5 text-sm text-gray-900 mb-6"
                            placeholder="ระบุเหตุผล..." required id="rejectReason"></textarea>

                        <div class="space-y-3">
                            <button onclick="confirmRejection()"
                                class="w-full font-medium rounded-xl text-sm px-5 py-2 text-center flex items-center justify-center bg-red-600 text-white hover:bg-red-700 transition-all">
                                ยืนยันการปฏิเสธ
                            </button>
                            <button onclick="closeRejectionModal()"
                                class="w-full border transition font-medium rounded-xl text-sm px-5 py-2 text-center flex items-center justify-center bg-white text-gray-600 border-gray-300 hover:bg-gray-100">
                                ยกเลิก
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <script>
                // Image Modal Functions
                function openImageModal(src) {
                    document.getElementById('modalImage').src = src;
                    document.getElementById('imageModal').classList.remove('hidden');
                }

                function closeImageModal() {
                    document.getElementById('imageModal').classList.add('hidden');
                }

                // Approval Functions
                function approvePayment() {
                    document.getElementById('approvalModal').classList.remove('hidden');
                }

                function closeApprovalModal() {
                    document.getElementById('approvalModal').classList.add('hidden');
                }

                function confirmApproval() {
                    document.getElementById('statusInput').value = 'paid';
                    document.getElementById('reasonInput').value = '';
                    document.getElementById('statusForm').submit();
                }

                // Rejection Functions
                function rejectPayment() {
                    document.getElementById('rejectionModal').classList.remove('hidden');
                }

                function closeRejectionModal() {
                    document.getElementById('rejectionModal').classList.add('hidden');
                }

                function confirmRejection() {
                    const reason = document.querySelector('#rejectionModal textarea').value;
                    if (!reason.trim()) {
                        alert('กรุณาระบุเหตุผลในการปฏิเสธ');
                        return;
                    }
                    document.getElementById('statusInput').value = 'cancelled';
                    document.getElementById('reasonInput').value = reason;
                    document.getElementById('statusForm').submit();
                }

                // Auto-check validation items when page loads
                window.addEventListener('load', function() {
                    const checkboxes = document.querySelectorAll('input[type="checkbox"]');
                    checkboxes.forEach((checkbox, index) => {
                        setTimeout(() => {
                            checkbox.checked = true;
                        }, index * 500);
                    });
                });

                // Prevent modal from closing when clicking inside
                document.querySelectorAll('#approvalModal > div, #rejectionModal > div').forEach(el => {
                    el.addEventListener('click', function(e) {
                        e.stopPropagation();
                    });
                });
            </script>
</body>

</html>