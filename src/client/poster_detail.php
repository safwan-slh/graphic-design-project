<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
require_once __DIR__ . '/../includes/db_connect.php';
require_once __DIR__ . '/../auth/auth.php';
requireLogin();
// ดึงรายละเอียดโปสเตอร์ (เช่น จากตาราง poster_details)
$stmt = $conn->prepare("SELECT * FROM poster_details WHERE poster_id = ?");
$stmt->bind_param("i", $order['ref_id']);
$stmt->execute();
$detail = $stmt->get_result()->fetch_assoc();

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
function getOrderStatusClass($status)
{
    switch ($status) {
        case 'pending':
            return 'text-yellow-600 text-sm font-bold bg-yellow-100 px-3 py-2 rounded-xl';
        case 'in_progress':
            return 'text-blue-600 text-sm font-bold bg-blue-100 px-3 py-2 rounded-xl';
        case 'completed':
            return 'text-green-600 text-sm font-bold bg-green-100 px-3 py-2 rounded-xl';
        case 'cancelled':
            return 'text-red-600 text-sm font-bold bg-red-100 px-3 py-2 rounded-xl';
        default:
            return 'text-gray-600 text-sm font-bold bg-gray-100 px-3 py-2 rounded-xl';
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
?>

<!DOCTYPE html>
<html lang="th">

<head>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta charset="UTF-8">
    <title>รายละเอียดงานโปสเตอร์ #<?= htmlspecialchars($order['order_code'] ?? $order['order_id']) ?></title>
    <link href="https://fonts.googleapis.com/css2?family=IBM+Plex+Sans+Thai:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/flowbite/1.7.0/flowbite.min.css" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        .font-thai {
            font-family: 'IBM Plex Sans Thai', sans-serif;
        }
    </style>
</head>

<body class="bg-gray-100 font-sans">
    <div class="flex h-screen">

        <!-- Main Content -->
        <div class="flex-1 p-8 overflow-y-auto">
            <!-- Header with back button -->
            <div class="flex items-center justify-between bg-white rounded-2xl mb-6 p-4 ring-1 ring-gray-200">
                <div class="flex items-center space-x-4">
                    <button class="p-1.5 text-gray-800 hover:bg-zinc-100 rounded-lg cursor-pointer hover:text-gray-800 ring-1 ring-gray-200" onclick="window.history.back()">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="w-6 h-6">
                            <path fill-rule="evenodd" d="M11.03 3.97a.75.75 0 0 1 0 1.06l-6.22 6.22H21a.75.75 0 0 1 0 1.5H4.81l6.22 6.22a.75.75 0 1 1-1.06 1.06l-7.5-7.5a.75.75 0 0 1 0-1.06l7.5-7.5a.75.75 0 0 1 1.06 0Z" clip-rule="evenodd" />
                        </svg>
                    </button>
                    <!-- Breadcrumb -->
                    <nav class="text-sm text-gray-500 p-1 rounded-lg ring-1 ring-gray-200">
                        <ol class="list-none p-0 inline-flex">
                            <li class="flex items-center">
                                <i class="fas fa-chevron-right mx-2 text-xs"></i>
                            </li>
                            <li class="flex items-center">
                                <a href="/graphic-design/src/client/index.php" class="hover:text-zinc-800 transition-colors hover:bg-zinc-100 p-1 rounded-lg">หน้าหลัก</a>
                                <i class="fas fa-chevron-right mx-2 text-xs"></i>
                            </li>
                            <li class="flex items-center">
                                <a href="/graphic-design/src/client/order.php" class="hover:text-zinc-800 transition-colors hover:bg-zinc-100 p-1 rounded-lg">คำสั่งซื้อ</a>
                                <i class="fas fa-chevron-right mx-2 text-xs"></i>
                            </li>
                            <li class="flex items-center">
                                <a class="text-zinc-800 transition-colors  hover:bg-zinc-100 p-1 rounded-lg">รายละเอียดงานโปสเตอร์</a>
                            </li>
                        </ol>
                    </nav>
                </div>
                <div class="">
                    <div class="flex items-center">
                        <span class="
                            <?= getOrderStatusClass($order['status']) ?>">
                            <?= getOrderStatusTH($order['status']) ?>
                        </span>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                <!-- Left Column - Order Details -->
                <div class="lg:col-span-2 space-y-6">
                    <!-- Order Summary -->
                    <div class="bg-white rounded-2xl mb-6 ring-1 ring-gray-200">
                        <div class="border-b bg-gray-50 rounded-t-2xl">
                            <h2 class="text-md font-semibold p-2 pl-4">สรุปคำสั่งงาน</h2>
                        </div>
                        <div class="p-6">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div class="bg-gray-50 p-3 rounded-2xl ring-1 ring-gray-200">
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
                                <div class="bg-gray-50 p-3 rounded-2xl ring-1 ring-gray-200">
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
                    </div>
                    <!-- Design Submissions -->
                    <div class="bg-white rounded-2xl mb-6 ring-1 ring-gray-200">
                        <div class="border-b bg-gray-50 rounded-t-2xl">
                            <h2 class="text-md font-semibold p-2 pl-4">ไฟล์งานที่ได้รับ</h2>
                        </div>
                        <div class="p-6 space-y-6">
                            <!-- Draft 1 -->
                            <div class="border border-gray-200 rounded-2xl p-4">
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
                </div>

                <!-- Right Column - Timeline & Actions -->
                <div class="space-y-6">
                    <!-- Progress -->
                    <?php list($steps, $currentStep) = getOrderProgressSteps($order['status']); ?>
                    <div class="bg-white rounded-2xl mb-6 ring-1 ring-gray-200">
                        <div class="border-b bg-gray-50 rounded-t-2xl flex items-center">
                            <h2 class="text-md font-semibold p-2 pl-4">สถานะงาน</h2>
                        </div>
                        <div class="p-6 space-y-4">
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
                        <div class="bg-white rounded-2xl mb-6 ring-1 ring-gray-200">
                            <div class="border-b bg-gray-50 rounded-t-2xl">
                                <h2 class="text-md font-semibold p-2 pl-4">แชทกับนักออกแบบ</h2>
                            </div>
                            <div class="p-2">
                                <div class="bg-gray-50 p-3 rounded-2xl ring-1 ring-gray-200">
                                    <div class="space-y-4">
                                        <div class="flex items-start">
                                            <div class="mr-10">
                                                <div class="bg-gray-200 rounded-2xl rounded-bl-none py-2 px-4 inline-block">
                                                    <p class="text-gray-800">สวัสดีครับ ผมส่งตัวอย่างโลโก้รอบแรกมาให้ดูครับ</p>
                                                </div>
                                                <p class="text-xs text-gray-500 mt-1">16/08/2023 14:30 น.</p>
                                            </div>
                                        </div>
                                        <div class="flex items-start flex-row-reverse">
                                            <div class="ml-10 ">
                                                <div class="bg-zinc-900 rounded-2xl rounded-br-none py-2 px-4 inline-block">
                                                    <p class="text-white">สวัสดีค่ะ ชอบแนวทางนี้ค่ะ แต่ช่วยปรับสีฟ้าให้เข้มขึ้นหน่อยได้ไหมคะ</p>
                                                </div>
                                                <p class="text-xs text-gray-500 mt-1 text-right">16/08/2023 15:45 น.</p>
                                            </div>
                                        </div>
                                        <div class="flex items-start">
                                            <div class="mr-10">
                                                <div class="bg-gray-200 rounded-2xl rounded-bl-none py-2 px-4 inline-block">
                                                    <p class="text-gray-800">ได้ครับ เดี๋ยวผมปรับให้ครับ น่าจะเสร็จพรุ่งนี้เช้าครับ</p>
                                                </div>
                                                <p class="text-xs text-gray-500 mt-1">16/08/2023 16:20 น.</p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="p-2">
                                <div class="flex items-start space-x-3">
                                    <div class="flex-1 space-y-2">
                                        <div class="">
                                            <textarea rows="2" class="block w-full rounded-2xl border border-gray-300 bg-gray-50 p-2.5 text-sm text-gray-900 focus:border-blue-500 focus:ring-blue-500" placeholder="พิมพ์ข้อความ..."></textarea>
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
                    <div class="bg-white rounded-2xl mb-6 ring-1 ring-gray-200">
                        <div class="border-b bg-gray-50 rounded-t-2xl">
                            <h2 class="text-md font-semibold p-2 pl-4">การดำเนินการ</h2>
                        </div>
                        <div class="p-6">
                            <div class="space-y-3">
                                <button class="w-full flex items-center justify-between p-3 border border-gray-200 rounded-2xl hover:bg-gray-50">
                                    <span>แจ้งปัญหาหรือคำถาม</span>
                                    <i class="fas fa-question-circle text-gray-400"></i>
                                </button>
                                <?php if (in_array($order['status'], ['pending', 'in_progress'])): ?>
                                    <button onclick="event.stopPropagation(); confirmCancel(<?= $order['order_id'] ?>, '<?= $order['status'] ?>');"
                                        class="w-full flex items-center justify-between p-3 border border-red-200 text-red-600 rounded-2xl hover:bg-red-50">
                                        <span>ยกเลิกงาน</span>
                                        <i class="fas fa-times text-red-400"></i>
                                    </button>
                                <?php elseif (in_array($order['status'], ['completed', 'cancelled'])): ?>
                                    <a href="/graphic-design/src/client/poster_form.php?service_id=1"
                                        class="w-full flex items-center justify-between p-3 border border-blue-200 text-blue-600 rounded-2xl hover:bg-blue-50">
                                        <span>สั่งซ้ำ</span>
                                        <i class="fas fa-redo text-blue-400"></i>
                                    </a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <?php if ($order['status'] !== 'completed'): ?>
                        <!-- Review -->
                        <div class="bg-white rounded-2xl mb-6 ring-1 ring-gray-200">
                            <div class="border-b bg-gray-50 rounded-t-2xl">
                                <h2 class="text-md font-semibold p-2 pl-4">ให้คะแนนงานนี้</h2>
                            </div>
                            <div class="p-6">
                                <p class="text-sm text-gray-500 mb-4">คุณพอใจกับงานออกแบบนี้หรือไม่?</p>
                                <div class="flex items-center">
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
                            </div>
                            <div class="p-2">
                                <div class="flex items-start space-x-3">
                                    <div class="flex-1 space-y-2">
                                        <div class="">
                                            <textarea rows="2" class="block w-full rounded-2xl border border-gray-300 bg-gray-50 p-2.5 text-sm text-gray-900 focus:border-blue-500 focus:ring-blue-500" placeholder="พิมพ์ข้อความ..."></textarea>
                                        </div>
                                        <div class="">
                                            <button class="w-full text-white bg-zinc-900 hover:bg-zinc-800 font-medium rounded-xl text-sm px-5 py-2 text-center flex items-center justify-center">
                                                ส่งรีวิว
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    <!-- Cancel Confirmation Modal -->
    <div id="cancelModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black backdrop-blur-sm bg-opacity-50 hidden">
        <div class="bg-white rounded-3xl shadow-lg p-5 max-w-sm w-full text-center">
            <div class="text-xl font-bold mb-4">ยืนยันการยกเลิกออเดอร์</div>
            <div id="cancelModalMsg" class="mb-6 text-gray-700"></div>
            <div class="flex justify-center gap-4">
                <button onclick="closeCancelModal()" class="w-full font-medium rounded-2xl text-sm px-5 py-2 text-center flex items-center justify-center bg-gray-200 hover:bg-gray-300">ยกเลิก</button>
                <button id="confirmCancelBtn" class="w-full font-medium rounded-2xl text-sm px-5 py-2 text-center flex items-center justify-center bg-red-600 text-white hover:bg-red-700 transition-all">ยืนยัน</button>
            </div>
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
            window.location = "order_cancel.php?order_id=" + cancelOrderId;
        };
    </script>
</body>

</html>