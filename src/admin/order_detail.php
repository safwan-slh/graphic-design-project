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

// ดึงรายละเอียดเพิ่มเติมสำหรับบริการเฉพาะ (เช่น poster)
$posterData = [];
if ($order['slug'] === 'poster-design' && !empty($order['ref_id'])) {
    $sqlPoster = "SELECT * FROM poster_details WHERE poster_id = ?";
    $stmtPoster = $conn->prepare($sqlPoster);
    $stmtPoster->bind_param("i", $order['ref_id']);
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

    // --- แจ้งเตือนลูกค้า ---
    require_once __DIR__ . '/../notifications/notify_helper.php';
    // ดึง customer_id ของ order นี้
    $customer_id = $order['customer_id'];
    // กำหนดข้อความแจ้งเตือน
    $orderCode = $order['order_code'] ?? $order_id;
    switch ($newStatus) {
        case 'pending':
            $msg = "ออเดอร์ #$orderCode ของคุณอยู่ระหว่างรอตรวจสอบ <span class='bg-yellow-100 text-yellow-800 text-xs font-medium px-2.5 py-0.5 rounded-full ml-1'>รอตรวจสอบ</span>";
            break;
        case 'in_progress':
            $msg = "ออเดอร์ #$orderCode ของคุณกำลังดำเนินการ <span class='bg-blue-100 text-blue-800 text-xs font-medium px-2.5 py-0.5 rounded-full ml-1'>กำลังดำเนินการ</span>";
            break;
        case 'completed':
            $msg = "ออเดอร์ #$orderCode ของคุณเสร็จสมบูรณ์แล้ว <span class='bg-green-100 text-green-800 text-xs font-medium px-2.5 py-0.5 rounded-full ml-1'>เสร็จสมบูรณ์</span>";
            break;
        case 'cancelled':
            $msg = "ออเดอร์ #$orderCode ของคุณถูกยกเลิก <span class='bg-red-100 text-red-800 text-xs font-medium px-2.5 py-0.5 rounded-full ml-1'>ยกเลิก</span>";
            break;
        default:
            $msg = "สถานะออเดอร์ #$orderCode ของคุณถูกอัปเดต";
    }
    $link = "/graphic-design/src/client/order_detail.php?order_id=" . $order_id;
    sendNotification($conn, $customer_id, $msg, $link, 0);

    // รีเฟรชหน้าเพื่อแสดงสถานะใหม่
    header("Location: order_detail.php?id=$order_id");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['work_files'])) {
    $uploadDir = $_SERVER['DOCUMENT_ROOT'] . '/graphic-design/uploads/orders/' . $order_id . '/';
    if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);

    $fileNote = $_POST['file_note'] ?? '';
    $version = $_POST['file_version'] ?? 'draft1';
    $uploadedBy = $_SESSION['admin_id'] ?? null;

    foreach ($_FILES['work_files']['name'] as $i => $name) {
        if ($_FILES['work_files']['error'][$i] === UPLOAD_ERR_OK) {
            $ext = pathinfo($name, PATHINFO_EXTENSION);
            $newName = 'work_' . $order_id . '_' . time() . "_$i." . $ext;
            $targetPath = $uploadDir . $newName;
            if (move_uploaded_file($_FILES['work_files']['tmp_name'][$i], $targetPath)) {
                $workUrl = '/graphic-design/uploads/orders/' . $order_id . '/' . $newName;
                $insertSql = "INSERT INTO work_files (order_id, file_name, file_path, uploaded_at, uploaded_by, note, version)
                              VALUES (?, ?, ?, NOW(), ?, ?, ?)";
                $insertStmt = $conn->prepare($insertSql);
                $insertStmt->bind_param("ississ", $order_id, $name, $workUrl, $uploadedBy, $fileNote, $version);
                $insertStmt->execute();
            }
        }
    }

    // --- แจ้งเตือนลูกค้าเมื่ออัปโหลดไฟล์งาน ---
    require_once __DIR__ . '/../notifications/notify_helper.php';
    $customer_id = $order['customer_id'];
    $orderCode = $order['order_code'] ?? $order_id;
    // สร้าง badge เวอร์ชัน
    switch ($version) {
        case 'draft1':
            $badge = "<span class='bg-yellow-100 text-yellow-800 text-xs font-medium px-2.5 py-0.5 rounded-full ml-1'>แบบร่างที่ 1</span>";
            break;
        case 'draft2':
            $badge = "<span class='bg-blue-100 text-blue-800 text-xs font-medium px-2.5 py-0.5 rounded-full ml-1'>แบบร่างที่ 2</span>";
            break;
        case 'final':
            $badge = "<span class='bg-green-100 text-green-800 text-xs font-medium px-2.5 py-0.5 rounded-full ml-1'>ฉบับสมบูรณ์</span>";
            break;
        default:
            $badge = "";
    }
    $msg = "แอดมินอัปโหลดไฟล์งานสำหรับออเดอร์ #$orderCode $badge";
    $link = "/graphic-design/src/client/order_detail.php?order_id=" . $order_id;
    sendNotification($conn, $customer_id, $msg, $link, 0);

    header("Location: order_detail.php?id=$order_id");
    exit;
}

// เพิ่มคอมเมนต์/ขอแก้ไขงาน
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['comment_version'], $_POST['comment_text'])) {
    $version = $_POST['comment_version'];
    $comment = trim($_POST['comment_text']);
    if ($comment !== '') {
        $isAdmin = false;
        if (isset($_SESSION['admin_id'])) {
            $commenter_id = $_SESSION['admin_id'];
            $isAdmin = true;
        } elseif (isset($_SESSION['customer_id'])) {
            $commenter_id = $_SESSION['customer_id'];
        } else {
            $commenter_id = $order['customer_id'];
        }
        $insertSql = "INSERT INTO work_comments (order_id, version, customer_id, comment, created_at) VALUES (?, ?, ?, ?, NOW())";
        $insertStmt = $conn->prepare($insertSql);
        $insertStmt->bind_param("isis", $order_id, $version, $commenter_id, $comment);
        $insertStmt->execute();

        // --- แจ้งเตือนแอดมินเมื่อ "ลูกค้า" คอมเมนต์ ---
        require_once __DIR__ . '/../notifications/notify_helper.php';
        $orderCode = $order['order_code'] ?? $order_id;
        // Badge เวอร์ชัน
        switch ($version) {
            case 'draft1':
                $badge = "<span class='bg-yellow-100 text-yellow-800 text-xs font-medium px-2.5 py-0.5 rounded-full ml-1'>แบบร่างที่ 1</span>";
                break;
            case 'draft2':
                $badge = "<span class='bg-blue-100 text-blue-800 text-xs font-medium px-2.5 py-0.5 rounded-full ml-1'>แบบร่างที่ 2</span>";
                break;
            case 'final':
                $badge = "<span class='bg-green-100 text-green-800 text-xs font-medium px-2.5 py-0.5 rounded-full ml-1'>ฉบับสมบูรณ์</span>";
                break;
            default:
                $badge = "";
        }
        $msg = "แอดมินคอมเมนต์ในออเดอร์ #$orderCode $badge";
        $link = "/graphic-design/src/client/order_detail.php?order_id=" . $order_id;
        sendNotification($conn, 1, $msg, $link, 0); // 1 = แจ้งเตือนแอดมิน

        header("Location: order_detail.php?id=$order_id");
        exit;
    }
}

// ฟังก์ชันสำหรับแสดงสถานะออเดอร์เป็นภาษาไทย
function getOrderStatusTH($status)
{
    switch ($status) {
        case 'pending':
            return '<span class="bg-yellow-100 text-yellow-800 text-xs font-medium me-2 px-2.5 py-0.5 rounded-full">รอดำเนินการ</span>';
        case 'in_progress':
            return '<span class="bg-blue-100 text-blue-800 text-xs font-medium me-2 px-2.5 py-0.5 rounded-full">กำลังดำเนินการ</span>';
        case 'completed':
            return '<span class="bg-green-100 text-green-800 text-xs font-medium me-2 px-2.5 py-0.5 rounded-full">เสร็จสมบูรณ์</span>';
        case 'cancelled':
            return '<span class="bg-red-100 text-red-800 text-xs font-medium me-2 px-2.5 py-0.5 rounded-full">ยกเลิก</span>';
        default:
            return $status;
    }
}
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
// ฟังก์ชันสำหรับแสดงขั้นตอนความคืบหน้าของงาน
function getWorkVersionSteps($version)
{
    $steps = [
        ['label' => 'แบบร่างที่ 1', 'key' => 'draft1'],
        ['label' => 'แบบร่างที่ 2', 'key' => 'draft2'],
        ['label' => 'ฉบับสมบูรณ์', 'key' => 'final'],
    ];
    $current = 0;
    foreach ($steps as $i => $step) {
        if ($step['key'] === $version) {
            $current = $i;
            break;
        }
    }
    return [$steps, $current];
}
// ฟังก์ชันสำหรับแสดงขั้นตอนความคืบหน้าของออเดอร์
function getOrderProgressSteps($status)
{
    if ($status === 'cancelled') {
        return [[['label' => 'ยกเลิกออเดอร์', 'key' => 'cancelled']], 0];
    }
    // กำหนดขั้นตอนและสถานะปัจจุบัน
    $steps = [
        ['label' => 'กำลังตรวจสอบ', 'key' => 'pending'],
        ['label' => 'กำลังออกแบบ', 'key' => 'in_progress'],
        ['label' => 'เสร็จสมบูรณ์', 'key' => 'completed'],
    ];
    // หาค่า index ขั้นตอนปัจจุบัน
    switch ($status) {
        case 'pending':
            $current = 0;
            break;
        case 'in_progress':
            $current = 1;
            break;
        case 'completed':
            $current = 2;
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
    <meta charset="UTF-8">
    <title>รายละเอียดออเดอร์ #<?= htmlspecialchars($order['order_id']) ?></title>
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
    <?php include __DIR__ . '/../includes/sidebar.php'; ?>
    <div class="ml-64">
        <!-- breadcrumb -->
        <?php
        $breadcrumb = ['Dashboard', 'รายการออเดอร์', 'รายละเอียดออเดอร์'];
        $breadcrumb_links = ['/graphic-design/src/admin/index.php', '/graphic-design/src/admin/order_list.php', ''];
        include '../includes/admin_navbar.php';
        ?>
        <div class="p-6">
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                <!-- Left Column - Order Details -->
                <div class="lg:col-span-2 space-y-6">
                    <div class="bg-white rounded-2xl mb-6 ring-1 ring-gray-200">
                        <div class="border-b bg-gray-50 rounded-t-2xl">
                            <h2 class="text-md font-semibold p-2 pl-4">รายละเอียดออเดอร์</h2>
                        </div>
                        <div class="p-6">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div class="bg-gray-50 p-3 rounded-2xl ring-1 ring-gray-200">
                                    <h3 class="font-medium text-gray-900 mb-2">ข้อมูลลูกค้า</h3>
                                    <div class="space-y-2">
                                        <p class="text-sm flex justify-between">
                                            <span class="text-zinc-600 font-medium">ชื่อ:</span>
                                            <span class="text-gray-500 text-sm"><?= htmlspecialchars($order['fullname']) ?></span>
                                        </p>
                                        <p class="text-sm flex justify-between">
                                            <span class="text-zinc-600 font-medium">อีเมล์:</span>
                                            <span class="text-gray-500 text-sm"><?= htmlspecialchars($order['email']) ?></span>
                                        </p>
                                        <p class="text-sm flex justify-between">
                                            <span class="text-zinc-600 font-medium">เบอร์:</span>
                                            <span class="text-gray-500 text-sm">
                                                <?= isset($order['phone']) && $order['phone'] !== null ? htmlspecialchars($order['phone']) : '-' ?>
                                            </span>
                                        </p>
                                    </div>
                                </div>

                                <div class="bg-gray-50 p-3 rounded-2xl ring-1 ring-gray-200">
                                    <h3 class="font-medium text-gray-900 mb-2">ข้อมูลบริการ</h3>
                                    <div class="space-y-2">
                                        <p class="text-sm flex justify-between">
                                            <span class="text-zinc-600 font-medium">ประเภท:</span>
                                            <span class="text-gray-500 text-sm"><?= htmlspecialchars($order['service_name']) ?></span>
                                        </p>
                                        <p class="text-sm flex justify-between">
                                            <span class="text-zinc-600 font-medium">ชื่อโปรเจค:</span>
                                            <span class="text-gray-500 text-sm"><?= htmlspecialchars($posterData['project_name']) ?></span>
                                        </p>
                                        <p class="text-sm flex justify-between">
                                            <span class="text-zinc-600 font-medium">ขนาด:</span>
                                            <span class="text-gray-500 text-sm">
                                                <?php
                                                if (isset($posterData['size']) && $posterData['size'] === 'custom') {
                                                    $width = $posterData['custom_width'] ?? '';
                                                    $height = $posterData['custom_height'] ?? '';
                                                    if ($width && $height) {
                                                        echo htmlspecialchars($width . ' x ' . $height);
                                                    } else {
                                                        echo 'กำหนดเอง';
                                                    }
                                                } else {
                                                    echo htmlspecialchars($posterData['size'] ?? '-');
                                                }
                                                ?>
                                            </span>
                                        </p>
                                    </div>
                                </div>

                                <div class="bg-gray-50 p-3 rounded-2xl ring-1 ring-gray-200">
                                    <h3 class="font-medium text-gray-900 mb-2">ข้อมูลระยะเวลา</h3>
                                    <div class="space-y-2">
                                        <p class="text-sm flex justify-between">
                                            <span class="text-zinc-600 font-medium">วันที่สั่ง:</span>
                                            <span class="text-gray-500 text-sm"><?= htmlspecialchars(date('d-m-Y', strtotime($order['created_at']))) ?></span>
                                        </p>
                                        <p class="text-sm flex justify-between">
                                            <span class="text-zinc-600 font-medium">วันที่ส่ง:</span>
                                            <span class="text-gray-500 text-sm">
                                                <?php
                                                if (isset($order['due_date']) && $order['due_date']) {
                                                    echo htmlspecialchars(date('d-m-Y', strtotime($order['due_date'])));
                                                } else {
                                                    echo '-';
                                                }
                                                ?>
                                            </span>
                                        </p>
                                        <p class="text-sm flex justify-between">
                                            <span class="text-zinc-600 font-medium">ระยะเวลาที่เหลือ:</span>
                                            <?php if (!empty($order['due_date'])): ?>
                                                <span class="text-blue-600 text-xs font-medium bg-blue-50 px-3 py-1 rounded-md">
                                                    <?php
                                                    // คำนวณวันคงเหลือ
                                                    $now = new DateTime();
                                                    $due = new DateTime($order['due_date']);
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
                                        </p>
                                    </div>
                                </div>

                                <div class="bg-gray-50 p-3 rounded-2xl ring-1 ring-gray-200">
                                    <h3 class="font-medium text-gray-900 mb-2">ข้อมูลการชำระเงิน</h3>
                                    <div class="space-y-2">
                                        <p class="text-sm flex justify-between">
                                            <span class="text-zinc-600 font-medium">จำนวนเงิน:</span>
                                            <span class="text-gray-500 text-sm"><?= htmlspecialchars($order['amount'] ?? '-') ?></span>
                                        </p>
                                        <p class="text-sm flex justify-between">
                                            <span class="text-zinc-600 font-medium">ประเภทชำระ:</span>
                                            <span class="text-gray-500 text-sm"><?= $order['payment_type'] == 'full' ? 'เต็มจำนวน' : '-' ?></span>
                                        </p>
                                        <p class="text-sm flex justify-between">
                                            <span class="text-zinc-600 font-medium">สถานะการชำระ:</span>
                                            <?php if ($order['payment_status']): ?>
                                                <span class="<?= getPaymentStatusClass($order['payment_status']) ?>">
                                                    <?= getPaymentStatusTH($order['payment_status']) ?>
                                                </span>
                                            <?php else: ?>
                                                <span class="text-pink-600 text-xs font-medium bg-pink-100 px-3 py-1 rounded-md">
                                                    รอชำระเงิน
                                                </span>
                                            <?php endif; ?>
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="bg-white rounded-2xl mb-6 ring-1 ring-gray-200">
                        <div class="border-b bg-gray-50 rounded-t-2xl">
                            <h2 class="text-md font-semibold p-2 pl-4">รายละเอียดสำหรับออกแบบโปสเตอร์</h2>
                        </div>
                        <div class="p-6">
                            <?php if ($order['slug'] === 'poster-design' && $posterData): ?>
                                <details class="mb-4" open>
                                    <summary class="cursor-pointer font-semibold text-gray-800 mb-2">ข้อมูลโครงการ</summary>
                                    <div class="bg-gray-50 p-4 rounded-xl ring-1 ring-gray-200 mt-2">
                                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                            <div>
                                                <p class="mb-2 block text-sm font-medium text-gray-500">ชื่อโครงการ</p>
                                                <p class="font-medium"><?= htmlspecialchars($posterData['project_name'] ?? '-') ?></p>
                                            </div>
                                            <div>
                                                <p class="mb-2 block text-sm font-medium text-gray-500">ประเภทโปสเตอร์</p>
                                                <p class="font-medium"><?= htmlspecialchars($posterData['poster_type'] ?? '-') ?></p>
                                            </div>
                                            <div class="md:col-span-2">
                                                <p class="mb-2 block text-sm font-medium text-gray-500">วัตถุประสงค์</p>
                                                <p class="font-medium"><?= htmlspecialchars($posterData['objective'] ?? '-') ?></p>
                                            </div>
                                            <div>
                                                <p class="mb-2 block text-sm font-medium text-gray-500">กลุ่มเป้าหมาย</p>
                                                <p class="font-medium"><?= htmlspecialchars($posterData['target_audience'] ?? '-') ?></p>
                                            </div>
                                            <div class="md:col-span-2">
                                                <p class="mb-2 block text-sm font-medium text-gray-500">ข้อความหลัก</p>
                                                <p class="font-medium"><?= htmlspecialchars($posterData['main_message'] ?? '-') ?></p>
                                            </div>
                                            <div class="md:col-span-2">
                                                <p class="mb-2 block text-sm font-medium text-gray-500">เนื้อหาที่ต้องการ</p>
                                                <p class="font-medium"><?= htmlspecialchars($posterData['content'] ?? '-') ?></p>
                                            </div>
                                        </div>
                                    </div>
                                </details>
                                <details class="mb-4">
                                    <summary class="cursor-pointer font-semibold text-gray-800 mb-2">ข้อมูลเทคนิค</summary>
                                    <div class="bg-gray-50 p-4 rounded-xl ring-1 ring-gray-200 mt-2">
                                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                            <div>
                                                <p class="mb-2 block text-sm font-medium text-gray-500">ขนาดโปสเตอร์</p>
                                                <p class="font-medium">
                                                    <?php
                                                    if (isset($posterData['size']) && $posterData['size'] === 'custom') {
                                                        $width = $posterData['custom_width'] ?? '';
                                                        $height = $posterData['custom_height'] ?? '';
                                                        if ($width && $height) {
                                                            echo htmlspecialchars($width . ' x ' . $height);
                                                        } else {
                                                            echo 'กำหนดเอง';
                                                        }
                                                    } else {
                                                        echo htmlspecialchars($posterData['size'] ?? '-');
                                                    }
                                                    ?>
                                                </p>
                                            </div>
                                            <div>
                                                <p class="mb-2 block text-sm font-medium text-gray-500">การวางแนว</p>
                                                <p class="font-medium">
                                                    <?php
                                                    echo htmlspecialchars($posterData['orientation'] ?? '-');
                                                    ?>
                                                </p>
                                            </div>
                                            <div>
                                                <p class="mb-2 block text-sm font-medium text-gray-500">สไตล์ที่ต้องการ</p>
                                                <p class="font-medium">
                                                    <?php
                                                    echo htmlspecialchars($posterData['style'] ?? '-');
                                                    ?>
                                                </p>
                                            </div>
                                            <div>
                                                <p class="mb-2 block text-sm font-medium text-gray-500">โหมดสี</p>
                                                <p class="font-medium">
                                                    <?php
                                                    echo htmlspecialchars($posterData['color_mode'] ?? '-');
                                                    ?>
                                                </p>
                                            </div>
                                            <div>
                                                <p class="mb-2 block text-sm font-medium text-gray-500">สีหลัก</p>
                                                <div class="flex items-center">
                                                    <div class="w-6 h-6 rounded-lg mr-2 border border-gray-300" style="background-color: <?php echo htmlspecialchars($posterData['color_primary'] ?? '#ffffff'); ?>"></div>
                                                    <p class="font-medium"><?php echo htmlspecialchars($posterData['color_primary'] ?? '-'); ?></p>
                                                </div>
                                            </div>
                                            <div>
                                                <p class="mb-2 block text-sm font-medium text-gray-500">สีรอง</p>
                                                <div class="flex items-center">
                                                    <div class="w-6 h-6 rounded-lg mr-2 border border-gray-300" style="background-color: <?php echo htmlspecialchars($posterData['color_secondary'] ?? '#ffffff'); ?>"></div>
                                                    <p class="font-medium"><?php echo htmlspecialchars($posterData['color_secondary'] ?? '-'); ?></p>
                                                </div>
                                            </div>
                                            <div>
                                                <p class="mb-2 block text-sm font-medium text-gray-500">สีเน้น</p>
                                                <div class="flex items-center">
                                                    <div class="w-6 h-6 rounded-lg mr-2 border border-gray-300" style="background-color: <?php echo htmlspecialchars($posterData['color_accent'] ?? '#ffffff'); ?>"></div>
                                                    <p class="font-medium"><?php echo htmlspecialchars($posterData['color_accent'] ?? '-'); ?></p>
                                                </div>
                                            </div>
                                            <div>
                                                <p class="mb-2 block text-sm font-medium text-gray-500">ฟอนต์ที่ต้องการ</p>
                                                <p class="font-medium">
                                                    <?php
                                                    echo htmlspecialchars($posterData['font'] ?? '-');
                                                    ?>
                                                </p>
                                            </div>
                                            <div class="md:col-span-2">
                                                <p class="mb-2 block text-sm font-medium text-gray-500">โค้ดสีที่ต้องการ</p>
                                                <p class="font-medium">
                                                    <?php
                                                    echo htmlspecialchars($posterData['color_code'] ?? '-');
                                                    ?>
                                                </p>
                                            </div>
                                        </div>
                                    </div>
                                </details>
                                <details class="mb-4">
                                    <summary class="cursor-pointer font-semibold text-gray-800 mb-2">ข้อมูลการดำเนินงาน</summary>
                                    <div class="bg-gray-50 p-4 rounded-xl ring-1 ring-gray-200 mt-2">
                                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                            <div>
                                                <p class="mb-2 block text-sm font-medium text-gray-500">งบประมาณ</p>
                                                <p class="font-medium"><?php echo htmlspecialchars($posterData['budget_range'] ?? '-') ?></p>
                                            </div>
                                            <div>
                                                <p class="mb-2 block text-sm font-medium text-gray-500">วันที่ต้องการรับงาน</p>
                                                <p class="font-medium"><?php echo htmlspecialchars($posterData['due_date'] ?? '-') ?></p>
                                            </div>
                                            <div class="md:col-span-2">
                                                <p class="mb-2 block text-sm font-medium text-gray-500">สิ่งที่ไม่ต้องการ</p>
                                                <p class="font-medium"><?php echo htmlspecialchars($posterData['avoid_elements'] ?? '-') ?></p>
                                            </div>
                                            <div class="md:col-span-2">
                                                <p class="mb-2 block text-sm font-medium text-gray-500">ข้อกำหนดพิเศษ</p>
                                                <p class="font-medium"><?php echo htmlspecialchars($posterData['special_requirements'] ?? '-') ?></p>
                                            </div>
                                        </div>
                                    </div>
                                </details>
                                <details>
                                    <summary class="cursor-pointer font-semibold text-gray-800 mb-2">ไฟล์และประกอบเพิ่มเติม</summary>
                                    <div class="bg-gray-50 p-4 rounded-xl ring-1 ring-gray-200 mt-2">
                                        <?php
                                        function showMultiFilePreview($filePaths, $label = '')
                                        {
                                            if (!$filePaths || $filePaths === '-') return '-';
                                            $paths = explode(',', $filePaths);
                                            $html = '';
                                            foreach ($paths as $filePath) {
                                                $filePath = trim($filePath);
                                                if (!$filePath) continue;
                                                // เติม /graphic-design ข้างหน้าถ้ายังไม่มี
                                                if (strpos($filePath, '/graphic-design/') !== 0 && strpos($filePath, '/uploads/') === 0) {
                                                    $filePath = '/graphic-design' . $filePath;
                                                }
                                                $ext = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
                                                $isImage = in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp']);
                                                if ($isImage) {
                                                    $html .= '<a href="' . htmlspecialchars($filePath) . '" target="_blank">
                                                                <img src="' . htmlspecialchars($filePath) . '" alt="' . htmlspecialchars($label) . '" class="w-32 h-32 object-cover rounded border mb-2 mr-2">
                                                              </a>';
                                                } else {
                                                    $html .= '<a href="' . htmlspecialchars($filePath) . '" target="_blank" class="text-blue-600 underline">'
                                                        . htmlspecialchars(basename($filePath)) .
                                                        '</a><br>';
                                                }
                                            }
                                            return $html ?: '-';
                                        }
                                        ?>
                                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                            <div>
                                                <p class="mb-2 block text-sm font-medium text-gray-500">ไฟล์โลโก้</p>
                                                <div class="font-medium text-sm flex flex-wrap">
                                                    <?= showMultiFilePreview($posterData['logo_file'] ?? '', 'โลโก้') ?>
                                                </div>
                                            </div>
                                            <div>
                                                <p class="mb-2 block text-sm font-medium text-gray-500">ไฟล์รูปภาพ</p>
                                                <div class="font-medium text-sm flex flex-wrap">
                                                    <?= showMultiFilePreview($posterData['image_file'] ?? '', 'รูปภาพ') ?>
                                                </div>
                                            </div>
                                            <div>
                                                <p class="mb-2 block text-sm font-medium text-gray-500">ไฟล์ตัวอย่าง</p>
                                                <div class="font-medium text-sm flex flex-wrap">
                                                    <?= showMultiFilePreview($posterData['reference_file'] ?? '', 'ไฟล์ตัวอย่าง') ?>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </details>

                            <?php endif; ?>
                        </div>
                    </div>
                    <?php
                    // ดึงไฟล์ทั้งหมดของ order นี้
                    $filesSql = "SELECT * FROM work_files WHERE order_id = ? ORDER BY uploaded_at DESC";
                    $filesStmt = $conn->prepare($filesSql);
                    $filesStmt->bind_param("i", $order_id);
                    $filesStmt->execute();
                    $filesResult = $filesStmt->get_result();

                    // แยกไฟล์ตามเวอร์ชัน
                    $draft1 = [];
                    $draft2 = [];
                    $final = [];
                    while ($file = $filesResult->fetch_assoc()) {
                        if ($file['version'] === 'draft1') {
                            $draft1[] = $file;
                        } elseif ($file['version'] === 'draft2') {
                            $draft2[] = $file;
                        } elseif ($file['version'] === 'final') {
                            $final[] = $file;
                        }
                    }
                    // ฟังก์ชันแสดงไฟล์ preview
                    function showFileList($files)
                    {
                        if (empty($files)) {
                            return '<div class="text-gray-500">ยังไม่มีไฟล์งานในเวอร์ชันนี้</div>';
                        }
                        $html = '<ul class="list-disc pl-6">';
                        foreach ($files as $file) {
                            $ext = strtolower(pathinfo($file['file_path'], PATHINFO_EXTENSION));
                            $isImage = in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp']);
                            $html .= '<li>';
                            if ($isImage) {
                                $html .= '<a href="' . htmlspecialchars($file['file_path']) . '" target="_blank">
                                <img src="' . htmlspecialchars($file['file_path']) . '" alt="' . htmlspecialchars($file['file_name']) . '" class="w-32 h-32 object-cover rounded border mb-2">
                              </a>';
                            } else {
                                $html .= '<a href="' . htmlspecialchars($file['file_path']) . '" target="_blank" class="text-blue-600 underline">'
                                    . htmlspecialchars($file['file_name']) .
                                    '</a>';
                            }
                            $html .= ' (อัปโหลดเมื่อ ' . htmlspecialchars($file['uploaded_at']) . ')';
                            if ($file['note']) {
                                $html .= '<br><span class="text-gray-500">หมายเหตุ: ' . htmlspecialchars($file['note']) . '</span>';
                            }
                            $html .= '</li>';
                        }
                        $html .= '</ul>';
                        return $html;
                    }
                    ?>

                    <?php
                    $filesSql = "SELECT * FROM work_files WHERE order_id = ? ORDER BY uploaded_at DESC";
                    $filesStmt = $conn->prepare($filesSql);
                    $filesStmt->bind_param("i", $order_id);
                    $filesStmt->execute();
                    $filesResult = $filesStmt->get_result();
                    ?>
                    <div class="bg-white rounded-2xl mb-6 ring-1 ring-gray-200">
                        <div class="border-b bg-gray-50 rounded-t-2xl">
                            <h2 class="text-md font-semibold p-2 pl-4">ไฟล์งานที่ได้รับ</h2>
                        </div>
                        <div class="p-6 space-y-6">
                            <!-- Draft 1 -->
                            <?php if (!empty($draft1)): ?>
                                <div class="border border-gray-200 rounded-2xl p-4">
                                    <div class="flex justify-between items-center mb-3">
                                        <h3 class="font-medium">แบบร่างที่ 1</h3>
                                        <span class="text-sm text-gray-500">
                                            ส่งเมื่อ <?= htmlspecialchars(date('d M Y, H:i', strtotime($draft1[0]['uploaded_at']))) ?> น.
                                        </span>
                                    </div>
                                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4">
                                        <?php foreach ($draft1 as $file): ?>
                                            <div class="border border-gray-200 rounded-lg overflow-hidden">
                                                <?php
                                                $ext = strtolower(pathinfo($file['file_path'], PATHINFO_EXTENSION));
                                                $isImage = in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp']);
                                                if ($isImage): ?>
                                                    <img src="<?= htmlspecialchars($file['file_path']) ?>"
                                                        alt="<?= htmlspecialchars($file['file_name']) ?>"
                                                        class="object-cover rounded border hover:opacity-90 cursor-pointer"
                                                        style="aspect-ratio: 1/1;"
                                                        onclick="openImageModal('<?= htmlspecialchars($file['file_path']) ?>')">
                                                <?php else: ?>
                                                    <a href="<?= htmlspecialchars($file['file_path']) ?>" target="_blank" class="text-blue-600 underline">
                                                        <?= htmlspecialchars($file['file_name']) ?>
                                                    </a>
                                                <?php endif; ?>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                    <?php if ($draft1[0]['note']): ?>
                                        <div class="bg-gray-100 p-2 rounded-2xl mb-4 ring-1 ring-zinc-200">
                                            <h4 class="font-medium text-zinc-800 mb-2">ความคิดเห็นจากนักออกแบบ</h4>
                                            <div class="bg-white p-4 rounded-xl ring-1 ring-zinc-200">
                                                <p class="text-zinc-500"><?= htmlspecialchars($draft1[0]['note']) ?></p>
                                            </div>
                                        </div>
                                    <?php endif; ?>

                                    <!-- แสดงคอมเมนต์ -->
                                    <?php
                                    $version = 'draft1';
                                    $commentSql = "SELECT wc.*, c.fullname, c.role
                                    FROM work_comments wc
                                    LEFT JOIN customers c ON wc.customer_id = c.customer_id
                                    WHERE wc.order_id = ? AND wc.version = ?
                                    ORDER BY wc.created_at ASC";
                                    $commentStmt = $conn->prepare($commentSql);
                                    $commentStmt->bind_param("is", $order_id, $version);
                                    $commentStmt->execute();
                                    $commentResult = $commentStmt->get_result();
                                    if ($commentResult && $commentResult->num_rows > 0):
                                    ?>
                                        <div class="mt-4 space-y-2">
                                            <h4 class="font-medium text-zinc-900 mb-1">คอมเมนต์ล่าสุด</h4>
                                            <div id="commentBox" class="mt-4 space-y-2 max-h-96 overflow-y-auto p-2 border border-gray-200 rounded-2xl">
                                                <?php while ($row = $commentResult->fetch_assoc()): ?>
                                                    <div class="<?= $row['role'] === 'admin' ? 'bg-gray-50' : 'bg-gray-50' ?> p-3 rounded-2xl ring-1 ring-zinc-200">
                                                        <p class="text-zinc-700"><?= htmlspecialchars($row['comment']) ?></p>
                                                        <p class="text-xs mt-1 text-gray-500">
                                                            โดย <span class="<?= $row['role'] === 'admin' ? 'text-blue-500' : 'text-yellow-500' ?>"><?= htmlspecialchars($row['fullname'] ?? 'ไม่ระบุ') ?>
                                                                (<?= $row['role'] === 'admin' ? 'แอดมิน' : 'ลูกค้า' ?>)
                                                            </span>
                                                            | ส่งเมื่อ <?= htmlspecialchars(date('d M Y, H:i', strtotime($row['created_at']))) ?>
                                                        </p>
                                                    </div>
                                                <?php endwhile; ?>
                                            </div>
                                        </div>
                                    <?php endif; ?>
                                    <!-- ฟอร์มคอมเมนต์ -->
                                    <form method="post" class="mt-4" id="commentFormDraft1">
                                        <input type="hidden" name="comment_version" value="draft1">
                                        <label class="block text-sm font-medium text-gray-700 mb-2">คอมเมนต์/ขอแก้ไขงาน:</label>
                                        <textarea name="comment_text" rows="2" required class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-xl block w-full p-2 mb-2" placeholder="ระบุรายละเอียดที่ต้องการคอมเมนต์หรือขอแก้ไข"></textarea>
                                        <div class="flex justify-end">
                                            <button type="submit" class="text-white bg-zinc-900 hover:bg-zinc-800 font-medium rounded-xl text-sm px-5 py-2 text-center flex items-center justify-center">ส่งคอมเมนต์</button>
                                        </div>
                                    </form>
                                </div>
                            <?php else: ?>
                                <div class="border border-gray-200 rounded-2xl p-8 text-center text-gray-400 text-sm">
                                    ขณะนี้ยังไม่มีไฟล์งานแบบร่างที่ 1<br>กรุณาอัปโหลดไฟล์งานให้ลูกค้าตรวจสอบ
                                </div>
                            <?php endif; ?>

                            <!-- Draft 2 -->
                            <?php if (!empty($draft2)): ?>
                                <div class="border border-gray-200 rounded-2xl p-4">
                                    <div class="flex justify-between items-center mb-3">
                                        <h3 class="font-medium">แบบร่างที่ 2</h3>
                                        <span class="text-sm text-gray-500">
                                            ส่งเมื่อ <?= htmlspecialchars(date('d M Y, H:i', strtotime($draft2[0]['uploaded_at']))) ?> น.
                                        </span>
                                    </div>
                                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4">
                                        <?php foreach ($draft2 as $file): ?>
                                            <div class="border border-gray-200 rounded-lg overflow-hidden">
                                                <?php
                                                $ext = strtolower(pathinfo($file['file_path'], PATHINFO_EXTENSION));
                                                $isImage = in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp']);
                                                if ($isImage): ?>
                                                    <img src="<?= htmlspecialchars($file['file_path']) ?>"
                                                        alt="<?= htmlspecialchars($file['file_name']) ?>"
                                                        class="object-cover rounded border hover:opacity-90 cursor-pointer"
                                                        style="aspect-ratio: 1/1;"
                                                        onclick="openImageModal('<?= htmlspecialchars($file['file_path']) ?>')">
                                                <?php else: ?>
                                                    <a href="<?= htmlspecialchars($file['file_path']) ?>" target="_blank" class="text-blue-600 underline">
                                                        <?= htmlspecialchars($file['file_name']) ?>
                                                    </a>
                                                <?php endif; ?>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                    <?php if ($draft2[0]['note']): ?>
                                        <div class="bg-gray-100 p-2 rounded-2xl mb-4 ring-1 ring-zinc-200">
                                            <h4 class="font-medium text-zinc-800 mb-2">ความคิดเห็นจากนักออกแบบ</h4>
                                            <div class="bg-white p-4 rounded-xl ring-1 ring-zinc-200">
                                                <p class="text-zinc-500"><?= htmlspecialchars($draft2[0]['note']) ?></p>
                                            </div>
                                        </div>
                                    <?php endif; ?>
                                    <?php
                                    $version = 'draft2'; // สำหรับ Draft 2
                                    $commentSql = "SELECT wc.*, c.fullname, c.role
                                    FROM work_comments wc
                                    LEFT JOIN customers c ON wc.customer_id = c.customer_id
                                    WHERE wc.order_id = ? AND wc.version = ?
                                    ORDER BY wc.created_at ASC ";
                                    $commentStmt = $conn->prepare($commentSql);
                                    $commentStmt->bind_param("is", $order_id, $version);
                                    $commentStmt->execute();
                                    $commentResult = $commentStmt->get_result();
                                    if ($commentResult && $commentResult->num_rows > 0):
                                    ?>
                                        <div class="mt-4 space-y-2">
                                            <h4 class="font-medium text-zinc-900 mb-1">คอมเมนต์ล่าสุด</h4>
                                            <div id="commentBox" class="mt-4 space-y-2 max-h-96 overflow-y-auto p-2 border border-gray-200 rounded-2xl">
                                                <?php while ($row = $commentResult->fetch_assoc()): ?>
                                                    <div class="<?= $row['role'] === 'admin' ? 'bg-gray-50' : 'bg-gray-50' ?> p-3 rounded-2xl ring-1 ring-zinc-200">
                                                        <p class="text-zinc-700"><?= htmlspecialchars($row['comment']) ?></p>
                                                        <p class="text-xs mt-1 text-gray-500">
                                                            โดย <span class="<?= $row['role'] === 'admin' ? 'text-blue-500' : 'text-yellow-500' ?>"><?= htmlspecialchars($row['fullname'] ?? 'ไม่ระบุ') ?>
                                                                (<?= $row['role'] === 'admin' ? 'แอดมิน' : 'ลูกค้า' ?>)
                                                            </span>
                                                            | ส่งเมื่อ <?= htmlspecialchars(date('d M Y, H:i', strtotime($row['created_at']))) ?>
                                                        </p>
                                                    </div>
                                                <?php endwhile; ?>
                                            </div>
                                        </div>
                                    <?php endif; ?>
                                    <form method="post" class="mt-4">
                                        <input type="hidden" name="comment_version" value="draft2"> <!-- เปลี่ยนเป็น draft2 หรือ final ตามเวอร์ชัน -->
                                        <label class="block text-sm font-medium text-gray-700 mb-2">คอมเมนต์/ขอแก้ไขงาน:</label>
                                        <textarea name="comment_text" rows="2" required class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-xl block w-full p-2 mb-2" placeholder="ระบุรายละเอียดที่ต้องการคอมเมนต์หรือขอแก้ไข"></textarea>
                                        <div class="flex justify-end">
                                            <button type="submit" class="bg-zinc-900 hover:bg-zinc-800 text-white font-medium rounded-xl text-sm px-5 py-2">ส่งคอมเมนต์</button>
                                        </div>
                                    </form>
                                </div>
                            <?php endif; ?>

                            <!-- Final -->
                            <?php if (!empty($final)): ?>
                                <div class="border border-gray-200 rounded-2xl p-4">
                                    <div class="flex justify-between items-center mb-3">
                                        <h3 class="font-medium">ฉบับสมบูรณ์</h3>
                                        <span class="text-sm text-gray-500">
                                            ส่งเมื่อ <?= htmlspecialchars(date('d M Y, H:i', strtotime($final[0]['uploaded_at']))) ?> น.
                                        </span>
                                    </div>
                                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4">
                                        <?php foreach ($final as $file): ?>
                                            <div class="border border-gray-200 rounded-lg overflow-hidden">
                                                <?php
                                                $ext = strtolower(pathinfo($file['file_path'], PATHINFO_EXTENSION));
                                                $isImage = in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp']);
                                                if ($isImage): ?>
                                                    <img src="<?= htmlspecialchars($file['file_path']) ?>"
                                                        alt="<?= htmlspecialchars($file['file_name']) ?>"
                                                        class="object-cover rounded border hover:opacity-90 cursor-pointer"
                                                        style="aspect-ratio: 1/1;"
                                                        onclick="openImageModal('<?= htmlspecialchars($file['file_path']) ?>')">
                                                <?php else: ?>
                                                    <a href="<?= htmlspecialchars($file['file_path']) ?>" target="_blank" class="text-blue-600 underline">
                                                        <?= htmlspecialchars($file['file_name']) ?>
                                                    </a>
                                                <?php endif; ?>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                    <?php if ($final[0]['note']): ?>
                                        <div class="bg-gray-100 p-2 rounded-2xl mb-4 ring-1 ring-zinc-200">
                                            <h4 class="font-medium text-zinc-800 mb-2">ความคิดเห็นจากนักออกแบบ</h4>
                                            <div class="bg-white p-4 rounded-xl ring-1 ring-zinc-200">
                                                <p class="text-zinc-500"><?= htmlspecialchars($final[0]['note']) ?></p>
                                            </div>
                                        </div>
                                    <?php endif; ?>
                                    <?php
                                    $version = 'final'; // สำหรับ Final
                                    $commentSql = "SELECT wc.*, c.fullname, c.role
                                    FROM work_comments wc
                                    LEFT JOIN customers c ON wc.customer_id = c.customer_id
                                    WHERE wc.order_id = ? AND wc.version = ?
                                    ORDER BY wc.created_at ASC ";
                                    $commentStmt = $conn->prepare($commentSql);
                                    $commentStmt->bind_param("is", $order_id, $version);
                                    $commentStmt->execute();
                                    $commentResult = $commentStmt->get_result();
                                    if ($commentResult && $commentResult->num_rows > 0):
                                    ?>
                                        <div class="mt-4 space-y-2">
                                            <h4 class="font-medium text-zinc-900 mb-1">คอมเมนต์ล่าสุด</h4>
                                            <div id="commentBox" class="mt-4 space-y-2 max-h-96 overflow-y-auto p-2 border border-gray-200 rounded-2xl">
                                                <?php while ($row = $commentResult->fetch_assoc()): ?>
                                                    <div class="<?= $row['role'] === 'admin' ? 'bg-gray-50' : 'bg-gray-50' ?> p-3 rounded-2xl ring-1 ring-zinc-200">
                                                        <p class="text-zinc-700"><?= htmlspecialchars($row['comment']) ?></p>
                                                        <p class="text-xs mt-1 text-gray-500">
                                                            โดย <span class="<?= $row['role'] === 'admin' ? 'text-blue-500' : 'text-yellow-500' ?>"><?= htmlspecialchars($row['fullname'] ?? 'ไม่ระบุ') ?>
                                                                (<?= $row['role'] === 'admin' ? 'แอดมิน' : 'ลูกค้า' ?>)
                                                            </span>
                                                            | ส่งเมื่อ <?= htmlspecialchars(date('d M Y, H:i', strtotime($row['created_at']))) ?>
                                                        </p>
                                                    </div>
                                                <?php endwhile; ?>
                                            </div>
                                        </div>
                                    <?php endif; ?>
                                    <form method="post" class="mt-4">
                                        <input type="hidden" name="comment_version" value="final"> <!-- เปลี่ยนเป็น draft2 หรือ final ตามเวอร์ชัน -->
                                        <label class="block text-sm font-medium text-gray-700 mb-2">คอมเมนต์/ขอแก้ไขงาน:</label>
                                        <textarea name="comment_text" rows="2" required class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-xl block w-full p-2 mb-2" placeholder="ระบุรายละเอียดที่ต้องการคอมเมนต์หรือขอแก้ไข"></textarea>
                                        <div class="flex justify-end">
                                            <button type="submit" class="bg-zinc-900 hover:bg-zinc-800 text-white font-medium rounded-xl text-sm px-5 py-2">ส่งคอมเมนต์</button>
                                        </div>
                                    </form>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <!-- Right Column - Summary -->
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
                    <div class="bg-white rounded-2xl mb-6 ring-1 ring-gray-200">
                        <div class="border-b bg-gray-50 rounded-t-2xl flex items-center justify-between">
                            <h2 class="text-md font-semibold p-2 pl-4">อัปเดทสถานะ</h2>
                            <span class="px-3 py-1 rounded-full text-xs font-medium">
                                <?= getOrderStatusTH($order['status']) ?? '' ?>
                            </span>
                        </div>
                        <div class=" p-4">
                            <form method="post">
                                <div class="flex items-start space-x-3">
                                    <div class="flex-1 space-y-4">
                                        <div class="">
                                            <select name="order_status" id="order_status" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-xl focus:ring-blue-500 focus:border-blue-500 block w-full p-2">
                                                <option value="pending" <?= $order['status'] == 'pending' ? 'selected' : '' ?>>รอตรวจสอบ</option>
                                                <option value="in_progress" <?= $order['status'] == 'in_progress' ? 'selected' : '' ?>>กำลังดำเนินการ</option>
                                                <option value="completed" <?= $order['status'] == 'completed' ? 'selected' : '' ?>>เสร็จสมบูรณ์</option>
                                                <option value="cancelled" <?= $order['status'] == 'cancelled' ? 'selected' : '' ?>>ยกเลิก</option>
                                            </select>
                                        </div>
                                        <div class="">
                                            <button type="submit" class="w-full text-white bg-zinc-900 hover:bg-zinc-800 font-medium rounded-xl text-sm px-5 py-2 text-center flex items-center justify-center">อัปเดทสถานะ</button>
                                        </div>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                    <?php
                    // หาค่าเวอร์ชันล่าสุดจากไฟล์งาน (เช่น draft2, final)
                    $latestVersion = !empty($final) ? 'final' : (!empty($draft2) ? 'draft2' : 'draft1');
                    list($versionSteps, $currentVersionStep) = getWorkVersionSteps($latestVersion);
                    ?>
                    <div class="bg-white rounded-2xl mb-6 ring-1 ring-gray-200">
                        <div class="border-b bg-gray-50 rounded-t-2xl flex items-center">
                            <h2 class="text-md font-semibold p-2 pl-4">สถานะเวอร์ชันงาน</h2>
                        </div>
                        <div class="p-6 space-y-4">
                            <?php foreach ($versionSteps as $i => $step): ?>
                                <div class="flex items-start">
                                    <div class="flex-shrink-0 w-4 h-4 mt-1
                    <?= $i < $currentVersionStep ? 'bg-zinc-950' : ($i == $currentVersionStep ? 'bg-blue-500 ring ring-blue-200 ring-offset-2 ' : 'bg-gray-300') ?>
                    rounded-full"></div>
                                    <div class="ml-3">
                                        <p class="font-medium <?= $i == $currentVersionStep ? 'text-zinc-950' : ($i < $currentVersionStep ? 'text-zinc-950' : 'text-gray-300') ?>">
                                            <?= $step['label'] ?>
                                        </p>
                                        <?php if ($i == $currentVersionStep): ?>
                                            <p class="text-blue-500 text-sm">เวอร์ชันปัจจุบัน</p>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <!-- Upload Work Files -->
                    <div class="bg-white rounded-2xl mb-6 ring-1 ring-gray-200">
                        <div class="border-b bg-gray-50 rounded-t-2xl">
                            <h2 class="text-md font-semibold p-2 pl-4">อัปโหลดไฟล์งาน</h2>
                        </div>
                        <div class="p-6">
                            <form method="post" enctype="multipart/form-data" id="workUploadForm">
                                <div class="flex flex-col space-y-4">
                                    <div>
                                        <label for="work_files" class="mb-2 block text-sm font-medium text-gray-500">เลือกไฟล์งาน (เลือกได้หลายไฟล์):</label>
                                        <input type="file" name="work_files[]" id="work_files" multiple required
                                            class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-xl focus:ring-blue-500 focus:border-blue-500 block w-full p-2"
                                            accept=".pdf,.jpg,.png,.zip,.rar">
                                    </div>

                                    <div id="extraInputs" class="hidden">
                                        <div class="fade-in">
                                            <label class="mb-2 block text-sm font-medium text-gray-500">หมายเหตุ:</label>
                                            <input type="text" name="file_note"
                                                class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-xl focus:ring-blue-500 focus:border-blue-500 block w-full p-2"
                                                placeholder="เพิ่มหมายเหตุเกี่ยวกับไฟล์นี้">
                                        </div>
                                        <div class="fade-in">
                                            <label class="mb-2 block text-sm font-medium text-gray-500">เวอร์ชัน:</label>
                                            <select name="file_version"
                                                class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-xl focus:ring-blue-500 focus:border-blue-500 block w-full p-2">
                                                <option value="draft1">แบบร่างที่ 1</option>
                                                <option value="draft2">แบบร่างที่ 2</option>
                                                <option value="final">ฉบับสมบูรณ์</option>
                                            </select>
                                        </div>
                                    </div>

                                    <button type="submit"
                                        class="w-full text-white bg-zinc-900 hover:bg-zinc-800 font-medium rounded-xl text-sm px-5 py-2 text-center flex items-center justify-center transition-colors">
                                        อัปโหลด
                                    </button>
                                </div>
                            </form>

                            <script>
                                document.addEventListener('DOMContentLoaded', function() {
                                    const fileInput = document.getElementById('work_files');
                                    const extraInputs = document.getElementById('extraInputs');

                                    // ตรวจสอบสถานะเริ่มต้น
                                    if (fileInput.files.length === 0) {
                                        extraInputs.classList.add('hidden');
                                    }

                                    fileInput.addEventListener('change', function(e) {
                                        if (e.target.files.length > 0) {
                                            extraInputs.classList.remove('hidden');
                                        } else {
                                            extraInputs.classList.add('hidden');
                                        }
                                    });
                                });
                            </script>
                        </div>
                    </div>
                    <!-- Chat -->
                    <div class="bg-white rounded-2xl mb-6 ring-1 ring-gray-200">
                        <div class="border-b bg-gray-50 rounded-t-2xl">
                            <h2 class="text-md font-semibold p-2 pl-4">แชทกับลูกค้า</h2>
                        </div>
                        <div class="p-2">
                            <div id="chatBox" class="bg-gray-50 p-3 rounded-2xl ring-1 ring-gray-200 max-h-80 overflow-y-auto space-y-4">
                                <!-- ข้อความแชทจะถูกเติมที่นี่ด้วย JS -->
                                <div class="text-gray-400 text-center" id="chatLoading">กำลังโหลด...</div>
                            </div>
                        </div>
                        <div class="p-2">
                            <form id="chatForm" class="flex items-start space-x-3">
                                <div class="flex-1 space-y-2">
                                    <textarea id="chatInput" rows="2" required class="block w-full rounded-2xl border border-gray-300 bg-gray-50 p-2.5 text-sm text-gray-900 focus:border-blue-500 focus:ring-blue-500" placeholder="พิมพ์ข้อความ..."></textarea>
                                    <button type="submit" class="w-full text-white bg-zinc-900 hover:bg-zinc-800 font-medium rounded-xl text-sm px-5 py-2 text-center flex items-center justify-center">
                                        ส่งข้อความ
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Image Modal -->
    <div id="imageModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 backdrop-blur-md bg-opacity-50 hidden"
        onclick="closeImageModal()">
        <div class="relative max-w-4xl w-full" onclick="event.stopPropagation();">
            <button onclick="closeImageModal()"
                class="absolute top-4 right-4 bg-white rounded-full p-2 hover:bg-gray-100 transition-all">
                <svg class="w-6 h-6 text-gray-800" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12">
                    </path>
                </svg>
            </button>
            <img id="modalImage" src="" alt="Work File" class="w-full h-auto rounded-2xl shadow-2xl mb-4">
            <div class="flex justify-center">
                <a id="downloadImageBtn" href="#" download class="bg-zinc-900 hover:bg-zinc-800 text-white font-medium rounded-xl text-sm px-5 py-2">
                    ดาวน์โหลดรูปภาพ
                </a>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            var commentBox = document.getElementById('commentBox');
            if (commentBox) {
                commentBox.scrollTop = commentBox.scrollHeight;
            }
        });

        function openImageModal(src) {
            document.getElementById('modalImage').src = src;
            document.getElementById('downloadImageBtn').href = src;
            document.getElementById('imageModal').classList.remove('hidden');
        }

        function closeImageModal() {
            document.getElementById('imageModal').classList.add('hidden');
        }
    </script>
        <!-- Floating Chat Button Script -->
    <script>
        const orderId = <?= (int)$order_id ?>;
        const chatBox = document.getElementById('chatBox');
        const chatForm = document.getElementById('chatForm');
        const chatInput = document.getElementById('chatInput');

        function fetchChat() {
            fetch('/graphic-design/src/chat/get_messages.php?order_id=' + orderId)
                .then(res => res.json())
                .then(data => {
                    chatBox.innerHTML = '';
                    if (data.success && data.messages.length > 0) {
                        data.messages.forEach(msg => {
                            const isAdmin = msg.sender_role === 'admin';
                            chatBox.innerHTML += `
                        <div class="flex ${isAdmin ? 'flex-row-reverse' : ''} items-start">
                            <div class="${isAdmin ? 'text-right' : 'text-left'}">
                                <div class="${isAdmin ? 'bg-zinc-900 text-white rounded-2xl rounded-br-none' : 'bg-gray-200 text-gray-800 rounded-2xl rounded-bl-none'} py-2 px-4 inline-block">
                                    <p>${msg.message.replace(/\n/g, '<br>')}</p>
                                </div>
                                <p class="text-xs text-gray-500 mt-1 ${isAdmin ? 'text-right' : ''}">
                                    ${new Date(msg.created_at).toLocaleString('th-TH', { hour12: false })}
                                </p>
                            </div>
                        </div>
                    `;
                        });
                        chatBox.scrollTop = chatBox.scrollHeight;
                    } else {
                        chatBox.innerHTML = '<div class="text-gray-400 text-center">ยังไม่มีข้อความในแชทนี้</div>';
                    }
                });
        }
        fetchChat();
        setInterval(fetchChat, 4000);

        chatForm.onsubmit = function(e) {
            e.preventDefault();
            const msg = chatInput.value.trim();
            if (!msg) return;
            fetch('/graphic-design/src/chat/send_message.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded'
                    },
                    body: `order_id=${orderId}&message=${encodeURIComponent(msg)}`
                })
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        chatInput.value = '';
                        fetchChat();
                    }
                });
        };
    </script>
</body>

</html>