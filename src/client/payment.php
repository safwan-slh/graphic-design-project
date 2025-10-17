<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
require __DIR__ . '/../includes/db_connect.php';
require_once '../auth/auth.php';
requireLogin();

// รับ order_id จาก query string
$order_id = $_GET['order_id'] ?? null;
if (!$order_id) {
    die('ไม่พบข้อมูลออเดอร์');
}

// ดึงข้อมูลออเดอร์
$stmt = $conn->prepare("SELECT o.*, s.service_name FROM orders o
    LEFT JOIN services s ON o.service_id = s.service_id
    WHERE o.order_id = ?");
$stmt->bind_param("i", $order_id);
$stmt->execute();
$order = $stmt->get_result()->fetch_assoc();
if (!$order) {
    die('ไม่พบข้อมูลออเดอร์');
}

// ดึงรายละเอียดโปสเตอร์ (ถ้าเป็น service_id = 1)
$detail = [];
if ($order['service_id'] == 1) {
    $stmt = $conn->prepare("SELECT * FROM poster_details WHERE poster_id = ?");
    $stmt->bind_param("i", $order['ref_id']);
    $stmt->execute();
    $detail = $stmt->get_result()->fetch_assoc();
}

// คำนวณราคา
if (!empty($detail['budget_range'])) {
    $parts = explode('-', $detail['budget_range']);
    if (count($parts) == 2) {
        $price = round(($parts[0] + $parts[1]) / 2, 2);
    } else {
        $price = $detail['budget_range'];
    }
} else {
    $price = 0;
}

// เมื่อมีการ submit ฟอร์ม
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $customer_id = $_SESSION['customer_id'];
    $amount = $_POST['amount'];
    $payment_type = $_POST['payment_type'];
    $deposit_remaining = ($payment_type === 'deposit') ? ($price - $amount) : null;
    $payment_method = $_POST['payment_method'];
    $payment_date = date('Y-m-d H:i:s');
    $remark = $_POST['remark'] ?? null;
    $reference_no = 'REF' . date('YmdHis') . rand(100, 999);
    $slip_file = null;

    // อัปโหลดสลิป
    if (isset($_FILES['slip_file']) && $_FILES['slip_file']['error'] === UPLOAD_ERR_OK) {
        $uploadDir = __DIR__ . '/../../uploads/payments/';
        if (!file_exists($uploadDir)) mkdir($uploadDir, 0777, true);
        $filename = uniqid('slip_') . '_' . basename($_FILES['slip_file']['name']);
        $targetPath = $uploadDir . $filename;
        if (move_uploaded_file($_FILES['slip_file']['tmp_name'], $targetPath)) {
            $slip_file = '/uploads/payments/' . $filename;
        } else {
            echo "Upload failed! Error code: " . $_FILES['slip_file']['error'];
        }
    }

    $is_retry = isset($_GET['retry']) && $_GET['retry'] == 1;

    // ดึง payment ล่าสุดของ order นี้
    $payment = $conn->query("SELECT * FROM payments WHERE order_id = $order_id ORDER BY created_at DESC LIMIT 1")->fetch_assoc();

    if ($is_retry && $payment) {
        // อัปเดทข้อมูลการชำระเงินเดิม
        $stmt = $conn->prepare("UPDATE payments SET 
            amount = ?, payment_type = ?, deposit_remaining = ?, payment_method = ?, payment_date = ?, payment_status = 'pending', reference_no = ?, slip_file = ?, remark = ?
            WHERE payment_id = ?");
        $stmt->bind_param(
            "dsdsssssi",
            $amount,
            $payment_type,
            $deposit_remaining,
            $payment_method,
            $payment_date,
            $reference_no,
            $slip_file,
            $remark,
            $payment['payment_id']
        );
        if ($stmt->execute()) {
            $success = "อัปเดทการชำระเงินเรียบร้อยแล้ว";
            require_once __DIR__ . '/../notifications/notify_helper.php';
            notifyPaymentUpdateToAdmin($conn, $order['order_code'], $payment['payment_id']);
        } else {
            $error = "เกิดข้อผิดพลาด: " . $stmt->error;
        }
    } else {
        // กรณีแจ้งชำระเงินครั้งแรก (INSERT)
        $stmt = $conn->prepare("INSERT INTO payments 
            (order_id, customer_id, amount, payment_type, deposit_remaining, payment_method, payment_date, payment_status, reference_no, slip_file, remark)
            VALUES (?, ?, ?, ?, ?, ?, ?, 'pending', ?, ?, ?)");
        $stmt->bind_param(
            "iidsdsssss",
            $order_id,
            $customer_id,
            $amount,
            $payment_type,
            $deposit_remaining,
            $payment_method,
            $payment_date,
            $reference_no,
            $slip_file,
            $remark
        );
        if ($stmt->execute()) {
            $success = "แจ้งชำระเงินเรียบร้อยแล้ว";
            require_once __DIR__ . '/../notifications/notify_helper.php';
            $payment_id = $stmt->insert_id;
            notifyPaymentToAdmin($conn, $order['order_code'], $payment_id);
        } else {
            $error = "เกิดข้อผิดพลาด: " . $stmt->error;
        }
    }
}

// ดึงข้อมูลการชำระเงินล่าสุดสำหรับออเดอร์นี้
$payment = $conn->query("SELECT * FROM payments WHERE order_id = $order_id ORDER BY created_at DESC LIMIT 1")->fetch_assoc();

$is_retry = isset($_GET['retry']) && $_GET['retry'] == 1;
?>

<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8">
    <title>แจ้งชำระเงิน</title>
    <title>แจ้งชำระเงิน | Graphic Design</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=IBM+Plex+Sans+Thai:wght@300;400;500;600;700&display=swap"
        rel="stylesheet">
    <style>
        .font-thai {
            font-family: 'IBM Plex Sans Thai', sans-serif;
        }

        .payment-method:hover {
            transform: translateY(-2px);
            transition: all 0.3s ease;
        }

        .selected-payment {
            background: #f4f4f5;
        }
    </style>
</head>

<body class="bg-zinc-100">
    <div class="mx-10 px-4 py-8">
        <form action="" method="POST" enctype="multipart/form-data">
            <div class="grid grid-cols-1 lg:grid-cols-12 gap-8">
                <!-- Payment Form -->
                <div class="lg:col-span-8">
                    <div class="bg-white rounded-xl border border-gray-200 shadow-sm ">
                        <!-- Header -->
                        <div class="mb-4 flex items-center border-b border-gray-200 px-4 py-4">
                            <div class="mr-4 rounded-xl bg-zinc-900 p-3">
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"
                                    class="h-6 w-6 text-white">
                                    <path d="M4.5 3.75a3 3 0 0 0-3 3v.75h21v-.75a3 3 0 0 0-3-3h-15Z" />
                                    <path fill-rule="evenodd"
                                        d="M22.5 9.75h-21v7.5a3 3 0 0 0 3 3h15a3 3 0 0 0 3-3v-7.5Zm-18 3.75a.75.75 0 0 1 .75-.75h6a.75.75 0 0 1 0 1.5h-6a.75.75 0 0 1-.75-.75Zm.75 2.25a.75.75 0 0 0 0 1.5h3a.75.75 0 0 0 0-1.5h-3Z"
                                        clip-rule="evenodd" />
                                </svg>
                            </div>
                            <div class="">
                                <h1 class="flex items-center text-xl font-bold text-zinc-900">
                                    การชำระเงิน
                                </h1>
                                <p class="text-gray-600">
                                    เลือกวิธีที่สะดวกสำหรับคุณ
                                </p>
                            </div>
                        </div>
                        <!-- Payment Methods -->
                        <div class="space-y-3 mb-4 rounded-xl bg-white p-4 m-4 shadow-sm ring-1 ring-gray-200">
                            <h3 class="text-lg font-semibold text-gray-800 mb-4">เลือกวิธีการชำระเงิน</h3>
                            <!-- Bank Transfer -->
                            <div class="payment-method ring-1 ring-gray-200 rounded-xl p-4 cursor-pointer transition-all"
                                onclick="selectPayment('bank')">
                                <div class="flex items-center justify-between">
                                    <div class="flex items-center space-x-4">
                                        <div class="w-12 h-12 bg-zinc-900 rounded-xl flex items-center justify-center">
                                            <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor"
                                                viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M12 21v-8.25M15.75 21v-8.25M8.25 21v-8.25M3 9l9-6 9 6m-1.5 12V10.332A48.36 48.36 0 0012 9.75c-2.551 0-5.056.2-7.5.582V21M3 21h18M12 6.75h.008v.008H12V6.75z">
                                                </path>
                                            </svg>
                                        </div>
                                        <div>
                                            <h3 class="font-semibold text-gray-800">โอนผ่านธนาคาร</h3>
                                            <p class="text-sm text-gray-500">ทุกธนาคารในไทย</p>
                                        </div>
                                    </div>
                                    <input type="radio" name="payment_method" value="bank" required
                                        class="w-5 h-5 text-indigo-600"
                                        <?= (isset($payment['payment_method']) && $payment['payment_method'] == 'bank') ? 'checked' : '' ?>>
                                </div>
                            </div>
                            <!-- PromptPay -->
                            <div class="payment-method ring-1 ring-gray-200 rounded-xl p-4 cursor-pointer transition-all"
                                onclick="selectPayment('promptpay')">
                                <div class="flex items-center justify-between">
                                    <div class="flex items-center space-x-4">
                                        <div class="w-12 h-12 bg-zinc-900 rounded-xl flex items-center justify-center">
                                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"
                                                fill="currentColor" class="w-6 h-6 text-white">
                                                <path fill-rule="evenodd"
                                                    d="M3 4.875C3 3.839 3.84 3 4.875 3h4.5c1.036 0 1.875.84 1.875 1.875v4.5c0 1.036-.84 1.875-1.875 1.875h-4.5A1.875 1.875 0 0 1 3 9.375v-4.5ZM4.875 4.5a.375.375 0 0 0-.375.375v4.5c0 .207.168.375.375.375h4.5a.375.375 0 0 0 .375-.375v-4.5a.375.375 0 0 0-.375-.375h-4.5Zm7.875.375c0-1.036.84-1.875 1.875-1.875h4.5C20.16 3 21 3.84 21 4.875v4.5c0 1.036-.84 1.875-1.875 1.875h-4.5a1.875 1.875 0 0 1-1.875-1.875v-4.5Zm1.875-.375a.375.375 0 0 0-.375.375v4.5c0 .207.168.375.375.375h4.5a.375.375 0 0 0 .375-.375v-4.5a.375.375 0 0 0-.375-.375h-4.5ZM6 6.75A.75.75 0 0 1 6.75 6h.75a.75.75 0 0 1 .75.75v.75a.75.75 0 0 1-.75.75h-.75A.75.75 0 0 1 6 7.5v-.75Zm9.75 0A.75.75 0 0 1 16.5 6h.75a.75.75 0 0 1 .75.75v.75a.75.75 0 0 1-.75.75h-.75a.75.75 0 0 1-.75-.75v-.75ZM3 14.625c0-1.036.84-1.875 1.875-1.875h4.5c1.036 0 1.875.84 1.875 1.875v4.5c0 1.035-.84 1.875-1.875 1.875h-4.5A1.875 1.875 0 0 1 3 19.125v-4.5Zm1.875-.375a.375.375 0 0 0-.375.375v4.5c0 .207.168.375.375.375h4.5a.375.375 0 0 0 .375-.375v-4.5a.375.375 0 0 0-.375-.375h-4.5Zm7.875-.75a.75.75 0 0 1 .75-.75h.75a.75.75 0 0 1 .75.75v.75a.75.75 0 0 1-.75.75h-.75a.75.75 0 0 1-.75-.75v-.75Zm6 0a.75.75 0 0 1 .75-.75h.75a.75.75 0 0 1 .75.75v.75a.75.75 0 0 1-.75.75h-.75a.75.75 0 0 1-.75-.75v-.75ZM6 16.5a.75.75 0 0 1 .75-.75h.75a.75.75 0 0 1 .75.75v.75a.75.75 0 0 1-.75.75h-.75a.75.75 0 0 1-.75-.75v-.75Zm9.75 0a.75.75 0 0 1 .75-.75h.75a.75.75 0 0 1 .75.75v.75a.75.75 0 0 1-.75.75h-.75a.75.75 0 0 1-.75-.75v-.75Zm-3 3a.75.75 0 0 1 .75-.75h.75a.75.75 0 0 1 .75.75v.75a.75.75 0 0 1-.75.75h-.75a.75.75 0 0 1-.75-.75v-.75Zm6 0a.75.75 0 0 1 .75-.75h.75a.75.75 0 0 1 .75.75v.75a.75.75 0 0 1-.75.75h-.75a.75.75 0 0 1-.75-.75v-.75Z"
                                                    clip-rule="evenodd" />
                                            </svg>
                                        </div>
                                        <div>
                                            <h3 class="font-semibold text-gray-800">PromptPay</h3>
                                            <p class="text-sm text-gray-500">QR Code Payment</p>
                                        </div>
                                    </div>
                                    <input type="radio" name="payment_method" value="promptpay" required
                                        class="w-5 h-5 text-indigo-600"
                                        <?= (isset($payment['payment_method']) && $payment['payment_method'] == 'promptpay') ? 'checked' : '' ?>>
                                </div>
                            </div>
                        </div>
                        <!-- Payment Details Form -->
                        <div id="payment-details" class="hidden mb-8">
                            <!-- Bank Transfer Info -->
                            <div id="bank-form"
                                class="hidden space-y-3 mb-4 rounded-xl bg-white p-4 m-4 shadow-sm ring-1 ring-gray-200">
                                <h3 class="text-lg font-semibold text-gray-800 mb-4">ข้อมูลการโอนเงิน</h3>
                                <div class=" bg-gray-50 ring-1 ring-gray-200 rounded-xl mb-4">
                                    <div class="border-b py-1 text-center">
                                        <h4 class="font-semibold text-gray-800">บัญชีสำหรับโอนเงิน</h4>
                                    </div>
                                    <div class="p-4">
                                        <div class="flex justify-between text-gray-600 text-sm mb-1">
                                            <span>ธนาคาร:</span>
                                            <span class="font-semibold">กสิกรไทย</span>
                                        </div>
                                        <div class="flex justify-between text-gray-600 text-sm mb-1">
                                            <span>เลขที่บัญชี:</span>
                                            <span class="font-semibold">123-4-56789-0</span>
                                        </div>
                                        <div class="flex justify-between text-gray-600 text-sm mb-1">
                                            <span>ชื่อบัญชี:</span>
                                            <span class="font-semibold">Poster Design Co.</span>
                                        </div>
                                        <div class="flex justify-between text-gray-600 text-sm mb-1">
                                            <span>ประเภทบัญชี:</span>
                                            <span class="font-semibold">ออมทรัพย์</span>
                                        </div>
                                    </div>
                                </div>
                                <div class="bg-amber-50 border border-amber-200 rounded-xl p-4">
                                    <div class="flex items-start space-x-3">
                                        <svg class="w-5 h-5 text-amber-600 mt-0.5 flex-shrink-0" fill="none"
                                            stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5C3.312 18.333 4.274 20 5.814 20z">
                                            </path>
                                        </svg>
                                        <div class="text-sm text-amber-800">
                                            <p class="font-semibold mb-1">คำแนะนำการโอนเงิน:</p>
                                            <ul class="space-y-1 text-xs">
                                                <li>• โอนตรงตามจำนวนเงินที่ระบุ</li>
                                                <li>• อัพโหลดสลิปการโอนเงินด้านล่าง</li>
                                                <li>• เราจะตรวจสอบภายใน 1-3 ชั่วโมง</li>
                                            </ul>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <!-- PromptPay QR -->
                            <div id="promptpay-form"
                                class="hidden space-y-3 mb-4 rounded-xl bg-white p-4 m-4 shadow-sm ring-1 ring-gray-200">
                                <h3 class="text-lg font-semibold text-gray-800 mb-4">ชำระผ่าน PromptPay</h3>
                                <div class="bg-zinc-50 border border-zinc-200 rounded-xl p-8 text-center">
                                    <div
                                        class="w-48 h-48 bg-white rounded-xl shadow-lg mx-auto mb-6 flex items-center justify-center">
                                        <div class="w-40 h-40 bg-gray-200 rounded-lg flex items-center justify-center">
                                            <img src="" alt="">
                                        </div>
                                    </div>
                                    <p class="text-zinc-800 font-semibold mb-2">สแกน QR Code นี้เพื่อชำระเงิน</p>
                                    <p class="text-sm text-zinc-600">จำนวนเงิน: <span class="font-bold">฿1,500.00</span></p>
                                </div>
                            </div>
                        </div>
                        <!-- Payment Details Form -->
                        <div id="payment-details" class="hidden mb-8">
                            <!-- Bank Transfer Info -->
                            <div id="bank-form"
                                class="hidden space-y-3 mb-4 rounded-xl bg-white p-4 m-4 shadow-sm ring-1 ring-gray-200">
                                <h3 class="text-lg font-semibold text-gray-800 mb-4">ข้อมูลการโอนเงิน</h3>
                                <div class=" bg-gray-50 ring-1 ring-gray-200 rounded-xl mb-4">
                                    <div class="border-b py-1 text-center">
                                        <h4 class="font-semibold text-gray-800">บัญชีสำหรับโอนเงิน</h4>
                                    </div>
                                    <div class="p-4">
                                        <div class="flex justify-between text-gray-600 text-sm mb-1">
                                            <span>ธนาคาร:</span>
                                            <span class="font-semibold">กสิกรไทย</span>
                                        </div>
                                        <div class="flex justify-between text-gray-600 text-sm mb-1">
                                            <span>เลขที่บัญชี:</span>
                                            <span class="font-semibold">123-4-56789-0</span>
                                        </div>
                                        <div class="flex justify-between text-gray-600 text-sm mb-1">
                                            <span>ชื่อบัญชี:</span>
                                            <span class="font-semibold">Poster Design Co.</span>
                                        </div>
                                        <div class="flex justify-between text-gray-600 text-sm mb-1">
                                            <span>ประเภทบัญชี:</span>
                                            <span class="font-semibold">ออมทรัพย์</span>
                                        </div>
                                    </div>
                                </div>
                                <div class="bg-amber-50 border border-amber-200 rounded-xl p-4">
                                    <div class="flex items-start space-x-3">
                                        <svg class="w-5 h-5 text-amber-600 mt-0.5 flex-shrink-0" fill="none"
                                            stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5C3.312 18.333 4.274 20 5.814 20z">
                                            </path>
                                        </svg>
                                        <div class="text-sm text-amber-800">
                                            <p class="font-semibold mb-1">คำแนะนำการโอนเงิน:</p>
                                            <ul class="space-y-1 text-xs">
                                                <li>• โอนตรงตามจำนวนเงินที่ระบุ</li>
                                                <li>• อัพโหลดสลิปการโอนเงินด้านล่าง</li>
                                                <li>• เราจะตรวจสอบภายใน 1-3 ชั่วโมง</li>
                                            </ul>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <!-- PromptPay QR -->
                            <div id="promptpay-form"
                                class="hidden space-y-3 mb-4 rounded-xl bg-white p-4 m-4 shadow-sm ring-1 ring-gray-200">
                                <h3 class="text-lg font-semibold text-gray-800 mb-4">ชำระผ่าน PromptPay</h3>
                                <div class="bg-zinc-50 border border-zinc-200 rounded-xl p-8 text-center">
                                    <div
                                        class="w-48 h-48 bg-white rounded-xl shadow-lg mx-auto mb-6 flex items-center justify-center">
                                        <div class="w-40 h-40 bg-gray-200 rounded-lg flex items-center justify-center">
                                            <img src="" alt="">
                                        </div>
                                    </div>
                                    <p class="text-zinc-800 font-semibold mb-2">สแกน QR Code นี้เพื่อชำระเงิน</p>
                                    <p class="text-sm text-zinc-600">จำนวนเงิน: <span class="font-bold">฿1,500.00</span></p>
                                </div>
                            </div>
                        </div>
                        <!-- QR Selection -->
                        <div class="space-y-3 mb-4 rounded-xl bg-white p-4 m-4 shadow-sm ring-1 ring-gray-200">
                            <h4 class="font-semibold text-gray-800">อัพโหลดสลิปการโอน</h4>
                            <?php if (!empty($payment['slip_file'])): ?>
                                <div class="mb-2">
                                    <span class="text-xs text-gray-500">สลิปเดิม:</span><br>
                                    <img src="/graphic-design<?= htmlspecialchars($payment['slip_file']) ?>"
                                        alt="slip"
                                        class="h-24 rounded-lg border mb-2"
                                        onerror="this.style.display='none';">
                                </div>
                            <?php endif; ?>
                            <div class="space-y-2">
                                <div class="border-2 border-dashed border-gray-300 bg-gray-50 rounded-xl p-6 text-center hover:border-gray-400 cursor-pointer transition-colors duration-300"
                                    onclick="document.getElementById('slip-upload').click()">
                                    <div id="upload-preview"></div>
                                    <input type="file" name="slip_file" accept="image/*" <?= empty($payment['slip_file']) ? 'required' : '' ?> class="hidden" id="slip-upload">
                                    <p class="text-sm text-gray-600">คลิกเพื่อเลือกไฟล์สลิป</p>
                                    <p class="text-xs text-gray-400">PNG, JPG (ขนาดไม่เกิน 5MB)</p>
                                </div>
                            </div>
                        </div>
                        <?php if ($is_retry): ?>
                        <div class="mb-4 rounded-xl bg-white p-4 m-4 shadow-sm ring-1 ring-gray-200">
                            <h4 class="font-semibold text-gray-800 mb-2">หมายเหตุจากแอดมิน:</h4>
                            <div class="bg-red-50 p-3 rounded-xl ring-1 ring-red-200 max-h-80 overflow-y-auto space-y-4">
                                <div class="text-red-600"><?= nl2br(htmlspecialchars($payment['remark'])) ?></div>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Order Summary -->
                <div class="lg:col-span-4">
                    <div class="bg-white rounded-xl border border-gray-200 shadow-sm sticky top-8">
                        <div class="border-b border-gray-200 p-4">
                            <h2 class="text-xl font-bold text-gray-800 ">สรุปคำสั่งซื้อ</h2>
                        </div>
                        <div class="space-y-3 rounded-xl bg-white p-4 m-4 shadow-sm ring-1 ring-gray-200">
                            <h4 class="font-semibold text-gray-800">รายละเอียดคำสั่งซื้อ</h4>
                            <!-- Project Details -->
                            <div class="space-y-4">
                                <!-- Pricing Breakdown -->
                                <div class="p-4 bg-gray-50 ring-1 ring-gray-200 rounded-xl">
                                    <div class="flex justify-between text-gray-600 text-sm mb-1">
                                        <span>ราคา</span>
                                        <span class="font-semibold">
                                            ฿<?= number_format($detail['budget_range'] ?? $order['budget_range'] ?? 0, 2) ?>
                                        </span>
                                    </div>
                                    <div class="flex justify-between text-gray-600 text-sm mb-1">
                                        <span>ส่วนลด</span>
                                        <span class="font-semibold">-฿0.00</span>
                                    </div>
                                    <div class="border-t pt-3">
                                        <div class="flex justify-between font-bold text-gray-800">
                                            <span>รวมทั้งสิ้น</span>
                                            <span class="text-md">฿<?= number_format(($detail['budget_range'] ?? $order['budget_range'] ?? 0) - 0, 2) ?></span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Payment Type Selection -->
                        <div class="space-y-3 mb-4 rounded-xl bg-white p-4 m-4 shadow-sm ring-1 ring-gray-200">
                            <h4 class="font-semibold text-gray-800">ประเภทการชำระเงิน</h4>
                            <div class="space-y-2">
                                <label
                                    class="flex items-center space-x-3 cursor-pointer p-3 border border-gray-200 rounded-lg hover:bg-gray-50">
                                    <input type="radio" name="payment_type" value="full" class="text-indigo-600" checked>
                                    <div>
                                        <span class="font-medium text-gray-800">ชำระเต็มจำนวน</span>
                                        <p class="text-sm text-gray-500">฿<?= number_format($detail['budget_range'] ?? $order['budget_range'] ?? 0, 2) ?></p>
                                    </div>
                                </label>
                                <label
                                    class="flex items-center space-x-3 cursor-pointer p-3 border border-gray-200 rounded-lg hover:bg-gray-50">
                                    <input type="radio" name="payment_type" value="deposit" disabled
                                        class="text-indigo-600">
                                    <div>
                                        <span class="font-medium text-gray-800">มัดจำ 50%
                                            <span class="text-red-500 text-xs"> (ยังไม่พร้อมใช้งาน)</span>
                                        </span>
                                        <p class="text-sm text-gray-500">฿<?= number_format(($detail['budget_range'] ?? $order['budget_range'] ?? 0) * 0.5, 2) ?> (เหลือ ฿<?= number_format(($detail['budget_range'] ?? $order['budget_range'] ?? 0) * 0.5, 2) ?>)</p>
                                    </div>
                                </label>
                            </div>
                        </div>
                        <input type="hidden" name="amount" value="<?= htmlspecialchars($price) ?>">

                        <!-- Support Contact -->
                        <div class="mb-4 rounded-xl bg-white p-4 m-4 shadow-sm ring-1 ring-gray-200">
                            <h4 class="font-semibold text-gray-800 mb-2">ต้องการความช่วยเหลือ?</h4>
                            <div class="space-y-2 text-sm text-gray-600">
                                <div class="flex items-center space-x-2">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z">
                                        </path>
                                    </svg>
                                    <span>088-123-4567</span>
                                </div>
                                <div class="flex items-center space-x-2">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M3 8l7.89 4.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z">
                                        </path>
                                    </svg>
                                    <span>support@posterdesign.com</span>
                                </div>
                                <div class="flex items-center space-x-2">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z">
                                        </path>
                                    </svg>
                                    <span>Line: @posterdesign</span>
                                </div>
                            </div>
                        </div>
                        <div class="mb-4 rounded-xl bg-white p-4 m-4 shadow-sm ring-1 ring-gray-200">
                            <label class="flex items-start space-x-3 cursor-pointer">
                                <input type="checkbox"
                                    class="w-5 h-5 text-indigo-600 border-gray-300 rounded focus:ring-indigo-500 mt-0.5"
                                    required>
                                <span class="text-sm text-gray-600">
                                    ฉันยอมรับ <a href="#" class="text-indigo-600 hover:underline">ข้อตกลงการใช้บริการ</a>
                                    และ
                                    <a href="#" class="text-indigo-600 hover:underline">นโยบายความเป็นส่วนตัว</a>
                                </span>
                            </label>
                        </div>
                        <div class="m-4">
                            <!-- Pay Button -->
                            <button type="submit" id="payButton"
                                class="w-full bg-zinc-900 text-white rounded-xl px-6 py-2 text-center flex items-center justify-center font-bold text-lg transition-all">
                                <?= $is_retry ? 'อัปเดทการชำระเงินใหม่' : 'ดำเนินการชำระเงิน' ?>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </div>

    <!-- Success Modal -->
    <div id="successModal"
        class="fixed inset-0 bg-black backdrop-blur-sm bg-opacity-50 hidden flex items-center justify-center z-50">
        <div class="bg-white rounded-3xl shadow-lg p-8 max-w-sm w-full text-center relative">
            <div class="w-16 h-16 bg-green-500 rounded-full flex items-center justify-center mx-auto mb-6">
                <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                </svg>
            </div>
            <h3 class="text-2xl font-bold text-gray-800 mb-4">ชำระเงินสำเร็จ!</h3>
            <p class="text-gray-600 mb-6">เราได้รับการชำระเงินของคุณแล้ว<br>จะเริ่มดำเนินการออกแบบในวันถัดไป</p>
            <div class="space-y-3 flex flex-col">
                <a href="/graphic-design/src/client/order.php"
                    class="w-full border transition font-medium rounded-xl text-sm px-5 py-2 text-center flex items-center justify-center bg-zinc-900 hover:bg-zinc-700 text-white border-zinc-900">
                    ดูสถานะคำสั่งซื้อ
                </a>
                <a href="/graphic-design/src/client/index.php"
                    class="w-full border transition font-medium rounded-xl text-sm px-5 py-2 text-center flex items-center justify-center bg-white text-gray-600 border-gray-300 hover:bg-gray-100"
                    onclick="closeModal()">
                    กลับหน้าหลัก
                </a>
            </div>
        </div>
    </div>
    <?php if (!empty($success)): ?>
        <script>
            window.addEventListener('DOMContentLoaded', function() {
                document.getElementById('successModal').classList.remove('hidden');
            });
        </script>
    <?php endif; ?>
    <script>
        // JavaScript สำหรับการเลือกวิธีการชำระเงิน
        let selectedPaymentMethod = null;

        function selectPayment(method) {
            selectedPaymentMethod = method;

            // Remove all selected classes
            document.querySelectorAll('.payment-method').forEach(el => {
                el.classList.remove('selected-payment');
            });

            // Add selected class to clicked method
            event.currentTarget.classList.add('selected-payment');

            // Check the radio button
            document.querySelector(`input[value="${method}"]`).checked = true;

            // Show payment details
            showPaymentDetails(method);

            // Enable pay button
            updatePayButton();
        }

        function showPaymentDetails(method) {
            // Hide all forms
            document.querySelectorAll('#card-form, #bank-form, #promptpay-form').forEach(el => {
                el.classList.add('hidden');
            });

            // Show payment details section
            document.getElementById('payment-details').classList.remove('hidden');

            // Show specific form
            document.getElementById(`${method}-form`).classList.remove('hidden');
        }

        function processPayment() {
            if (!selectedPaymentMethod) return;

            const payButton = document.getElementById('payButton');
            payButton.innerHTML = '<span class="flex items-center justify-center"><div class="animate-spin mr-2 h-5 w-5 border-2 border-white border-t-transparent rounded-full"></div>กำลังดำเนินการ...</span>';
            payButton.disabled = true;

            // Simulate payment processing
            setTimeout(() => {
                document.getElementById('successModal').classList.remove('hidden');
                payButton.innerHTML = 'ชำระเงินสำเร็จ';
            }, 3000);
        }

        function closeModal() {
            document.getElementById('successModal').classList.add('hidden');
        }

        // File upload handler
        document.getElementById('slip-upload')?.addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (file) {
                document.getElementById('upload-preview').innerHTML = `
                    <div class="mx-auto mb-3 w-12 h-12 bg-green-100 rounded-lg flex items-center justify-center">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"
                            class="size-6 text-green-600">
                            <path fill-rule="evenodd"
                                class="size-6"
                                d="M9 1.5H5.625c-1.036 0-1.875.84-1.875 1.875v17.25c0 1.035.84 1.875 1.875 1.875h12.75c1.035 0 1.875-.84 1.875-1.875V12.75A3.75 3.75 0 0 0 16.5 9h-1.875a1.875 1.875 0 0 1-1.875-1.875V5.25A3.75 3.75 0 0 0 9 1.5Zm6.61 10.936a.75.75 0 1 0-1.22-.872l-3.236 4.53L9.53 14.47a.75.75 0 0 0-1.06 1.06l2.25 2.25a.75.75 0 0 0 1.14-.094l3.75-5.25Z"
                                clip-rule="evenodd" />
                                <path
                                d="M12.971 1.816A5.23 5.23 0 0 1 14.25 5.25v1.875c0 .207.168.375.375.375H16.5a5.23 5.23 0 0 1 3.434 1.279 9.768 9.768 0 0 0-6.963-6.963Z" />
                                </svg>
                                </div>
                    <p class="bg-blue-100 text-blue-800 px-3 py-1 rounded-lg text-xs inline-block mr-2 mb-2">${file.name}</p>
                    <p class="text-xs text-gray-400">อัพโหลดสำเร็จ</p>
                `;
            }
        });

        // ดึงราคาจาก PHP
        const fullAmount = <?= json_encode($price) ?>;

        // ฟังก์ชันอัปเดตปุ่มตามประเภทการชำระเงิน
        function updatePayButton() {
            const payButton = document.getElementById('payButton');
            const paymentType = document.querySelector('input[name="payment_type"]:checked');
            let amount = fullAmount;

            if (paymentType && paymentType.value === 'deposit') {
                amount = fullAmount * 0.5;
            }

            payButton.innerHTML = `<?= $is_retry ? 'อัปเดทการชำระเงินใหม่' : 'ดำเนินการชำระเงิน' ?> <span class="ml-2">฿${amount.toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2})}</span>`;
        }

        // เพิ่ม event listener ให้ radio ทุกตัว
        document.querySelectorAll('input[name="payment_type"]').forEach(radio => {
            radio.addEventListener('change', updatePayButton);
        });

        // เรียกครั้งแรกเมื่อโหลดหน้า
        updatePayButton();

        // เพิ่ม event listener ให้ฟอร์ม
        document.querySelector('form').addEventListener('submit', function(e) {
            const payButton = document.getElementById('payButton');
            // เปลี่ยนข้อความและแสดงไอคอนหมุน
            payButton.innerHTML = `
        <span class="flex items-center justify-center">
            <svg class="animate-spin mr-2 h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"></path>
            </svg>
            กำลังดำเนินการ...
        </span>
    `;
            payButton.disabled = true;
            // หน่วงเวลา 1.5 วินาที ก่อน submit จริง
            e.preventDefault();
            setTimeout(() => {
                e.target.submit();
            }, 1500);
        });

        function hideToast() {
            const toast = document.getElementById('toast');
            toast.style.opacity = 0;
            setTimeout(() => toast.classList.add('hidden'), 300);
        }
    </script>
</body>

</html>