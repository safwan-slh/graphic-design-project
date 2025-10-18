<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
require_once __DIR__ . '/../includes/db_connect.php';
require_once __DIR__ . '/../auth/auth.php';
requireLogin();

$order_id = $_GET['order_id'] ?? null;
if (!$order_id) {
    exit('ไม่พบรหัสออเดอร์');
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

// ดึง payment ล่าสุดของ order นี้
$payment = $conn->query("SELECT * FROM payments WHERE order_id = $order_id ORDER BY created_at DESC LIMIT 1")->fetch_assoc();

// ดึงรายละเอียดโปสเตอร์ (เช่น จากตาราง poster_details)
$detail = null;
if ($order && isset($order['ref_id'])) {
    $stmt = $conn->prepare("SELECT * FROM poster_details WHERE poster_id = ?");
    $stmt->bind_param("i", $order['ref_id']);
    $stmt->execute();
    $detail = $stmt->get_result()->fetch_assoc();
}
if (!$detail) $detail = [];

// ดึงไฟล์งานทั้งหมดของ order นี้
$filesSql = "SELECT * FROM work_files WHERE order_id = ? ORDER BY uploaded_at ASC";
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
    if ($status === 'cancelled') {
        return [[['label' => 'ออเดอร์ถูกยกเลิก', 'key' => 'cancelled']], 0];
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
// เพิ่มคอมเมนต์/ขอแก้ไขงาน
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['comment_version'], $_POST['comment_text'])) {
    $version = $_POST['comment_version'];
    $comment = trim($_POST['comment_text']);
    if ($comment !== '') {
        $customer_id = $order['customer_id'];
        $insertSql = "INSERT INTO work_comments (order_id, version, customer_id, comment, created_at) VALUES (?, ?, ?, ?, NOW())";
        $insertStmt = $conn->prepare($insertSql);
        $insertStmt->bind_param("isis", $order_id, $version, $customer_id, $comment);
        $insertStmt->execute();


        require_once __DIR__ . '/../notifications/notify_helper.php';
        $orderCode = $order['order_code'] ?? $order_id;
        notifyAdminCustomerComment($conn, $order_id, $orderCode, $version);

        header("Location: poster_detail.php?order_id=$order_id");
        exit;
    }
}

?>

<!DOCTYPE html>
<html lang="th">

<head>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta charset="UTF-8">
    <title>รายละเอียดงานโปสเตอร์</title>
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
                            <?= getOrderStatusClass($order['status']) ?? '' ?>">
                            <?= getOrderStatusTH($order['status']) ?? '' ?>
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
                                    <h3 class="font-medium text-gray-900 mb-2">ข้อมูลบริการ</h3>
                                    <div class="space-y-2">
                                        <p class="text-sm flex justify-between">
                                            <span class="text-zinc-600 font-medium">ประเภท:</span>
                                            <span class="text-gray-500 text-sm"><?= htmlspecialchars($order['service_name'] ?? '-') ?></span>
                                        </p>
                                        <p class="text-sm flex justify-between">
                                            <span class="text-zinc-600 font-medium">ชื่อโปรเจค:</span>
                                            <span class="text-gray-500 text-sm"><?= htmlspecialchars($detail['project_name'] ?? '-') ?></span>
                                        </p>
                                        <p class="text-sm flex justify-between">
                                            <span class="text-zinc-600 font-medium">ขนาด:</span>
                                            <span class="text-gray-500 text-sm">
                                                <?php
                                                if (isset($detail['size']) && $detail['size'] === 'custom') {
                                                    $width = $detail['custom_width'] ?? '';
                                                    $height = $detail['custom_height'] ?? '';
                                                    if ($width && $height) {
                                                        echo htmlspecialchars($width . ' x ' . $height);
                                                    } else {
                                                        echo 'กำหนดเอง';
                                                    }
                                                } else {
                                                    echo htmlspecialchars($detail['size'] ?? '-');
                                                }
                                                ?>
                                            </span>
                                        </p>
                                    </div>
                                </div>
                            </div>
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
                                    ขณะนี้ยังไม่มีไฟล์งานแบบร่างที่ 1<br>กรุณารอทีมงานอัปโหลดไฟล์งานให้คุณเร็ว ๆ นี้
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

                    <div class="bg-white rounded-2xl mb-6 ring-1 ring-gray-200">
                        <div class="border-b bg-gray-50 rounded-t-2xl">
                            <h2 class="text-md font-semibold p-2 pl-4">หมายเหตุจากแอดมิน</h2>
                        </div>
                        <div class="p-2">
                            <div class="bg-red-50 p-3 rounded-2xl ring-1 ring-red-200 max-h-80 overflow-y-auto space-y-4">
                                <div class="text-red-600"><?= nl2br(htmlspecialchars($payment['remark'])) ?></div>
                            </div>
                        </div>
                        <div class="p-2">
                            <form class="flex items-start space-x-3">
                                <div class="flex-1 space-y-2">
                                    <a href="/graphic-design/src/client/payment.php?order_id=<?= $order_id ?>&retry=1"
                                        class="w-full font-medium rounded-xl text-sm px-5 py-2 text-center flex items-center justify-center bg-red-600 text-white hover:bg-red-700 transition-all">
                                        อัปเดทการชำระเงินใหม่
                                    </a>
                                </div>
                            </form>
                        </div>
                    </div>

                    <!-- Chat -->
                    <div class="bg-white rounded-2xl mb-6 ring-1 ring-gray-200">
                        <div class="border-b bg-gray-50 rounded-t-2xl">
                            <h2 class="text-md font-semibold p-2 pl-4">แชทกับนักออกแบบ</h2>
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

                    <!-- Review -->
                    <?php
                    $review = $conn->query("SELECT * FROM reviews WHERE order_id={$order['order_id']} AND customer_id={$_SESSION['customer_id']}")->fetch_assoc();
                    $isEdit = $review ? true : false;
                    ?>
                    <?php if ($order['status'] === 'completed'): ?>
                        <div id="reviewForm" class="bg-white rounded-2xl mb-6 ring-1 ring-gray-200" <?= $isEdit ? 'style="display:none;"' : '' ?>>
                            <div class="border-b bg-gray-50 rounded-t-2xl">
                                <h2 class="text-md font-semibold p-2 pl-4"><?= $isEdit ? 'แก้ไขรีวิว' : 'ให้คะแนนงานนี้' ?></h2>
                            </div>
                            <form method="post" action="/graphic-design/src/review/<?= $isEdit ? 'edit_review.php' : 'submit_review.php' ?>" enctype="multipart/form-data">
                                <input type="hidden" name="order_id" value="<?= (int)$order_id ?>">
                                <?php if ($isEdit): ?>
                                    <input type="hidden" name="review_id" value="<?= $review['id'] ?>">
                                <?php endif; ?>
                                <div class="p-6 pb-0">
                                    <div class="bg-gray-50 p-3 rounded-2xl ring-1 ring-gray-200 items-center flex flex-col">
                                        <p class="text-sm text-gray-500 mb-4">คุณพอใจกับงานออกแบบนี้หรือไม่?</p>
                                        <!-- Star Rating -->
                                        <div class="flex items-center space-x-1 mb-2" id="starRating">
                                            <?php for ($i = 1; $i <= 5; $i++): ?>
                                                <button type="button" class="star text-3xl <?= ($isEdit && $i <= $review['rating']) ? 'text-yellow-400' : 'text-gray-300' ?>" data-value="<?= $i ?>">&#9733;</button>
                                            <?php endfor; ?>
                                            <input type="hidden" name="rating" id="ratingInput" value="<?= $isEdit ? $review['rating'] : '' ?>" required>
                                        </div>
                                        <script>
                                            document.addEventListener('DOMContentLoaded', function() {
                                                const stars = document.querySelectorAll('#starRating .star');
                                                const ratingInput = document.getElementById('ratingInput');
                                                stars.forEach(star => {
                                                    star.addEventListener('click', function() {
                                                        const val = this.getAttribute('data-value');
                                                        ratingInput.value = val;
                                                        stars.forEach((s, idx) => {
                                                            s.classList.toggle('text-yellow-400', idx < val);
                                                            s.classList.toggle('text-gray-300', idx >= val);
                                                        });
                                                    });
                                                });
                                            });
                                        </script>
                                    </div>
                                </div>
                                <div class="p-6">
                                    <div class="flex items-start space-x-3">
                                        <div class="flex-1 space-y-2">
                                            <div>
                                                <label class="block text-sm font-medium text-gray-700">อัปโหลดรูปภาพ (ถ้ามี)</label>
                                                <input type="file" name="image" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-xl block w-full" accept=".jpg,.jpeg,.png">
                                                <?php if ($isEdit && $review['image']): ?>
                                                    <div class="mt-2">
                                                        <img src="/graphic-design/uploads/<?= htmlspecialchars($review['image']) ?>" alt="รีวิว" style="max-width:100px;">
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                            <div>
                                                <label class="block text-sm font-medium text-gray-700">แสดงความคิดเห็น</label>
                                                <textarea name="comment" rows="2" class="block w-full rounded-2xl border border-gray-300 bg-gray-50 p-2.5 text-sm text-gray-900" placeholder="พิมพ์ข้อความรีวิว..."><?= $isEdit ? htmlspecialchars($review['comment']) : '' ?></textarea>
                                            </div>
                                            <div>
                                                <button type="submit" class="w-full text-white bg-zinc-900 hover:bg-zinc-800 font-medium rounded-xl text-sm px-5 py-2 text-center flex items-center justify-center">
                                                    <?= $isEdit ? 'บันทึกการแก้ไข' : 'ส่งรีวิว' ?>
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </form>
                        </div>
                    <?php endif; ?>

                    <?php
                    $review = $conn->query("SELECT * FROM reviews WHERE order_id={$order['order_id']}")->fetch_assoc();
                    if ($review):
                    ?>
                        <div class="bg-white rounded-2xl mb-6 ring-1 ring-gray-200">
                            <div class="border-b bg-gray-50 rounded-t-2xl flex justify-between items-center">
                                <h2 class="text-md font-semibold p-2 pl-4">รีวิวออเดอร์ของคุณ</h2>
                                <!-- ปุ่ม Dropdown -->
                                <div class="relative inline-block text-left">
                                    <button id="dropdownReviewBtn" type="button" class="p-1 hover:bg-gray-200 rounded-lg mr-4" onclick="toggleReviewDropdown()">
                                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="size-6">
                                            <path fill-rule="evenodd" d="M4.5 12a1.5 1.5 0 1 1 3 0 1.5 1.5 0 0 1-3 0Zm6 0a1.5 1.5 0 1 1 3 0 1.5 1.5 0 0 1-3 0Zm6 0a1.5 1.5 0 1 1 3 0 1.5 1.5 0 0 1-3 0Z" clip-rule="evenodd" />
                                        </svg>
                                    </button>
                                    <div id="dropdownReviewMenu" class="hidden absolute right-0 mt-2 w-44 bg-white divide-y rounded-xl shadow-md border ring-1 ring-gray-200 z-50">
                                        <ul class="space-y-2 p-2 py-2 text-sm text-gray-700">
                                            <li>
                                                <a href="#reviewForm" onclick="event.preventDefault(); showReviewForm();" class="flex items-center justify-between px-3 py-2 rounded-lg bg-zinc-50 text-blue-600 hover:bg-zinc-100 transition-colors duration-200 ring-1 ring-gray-200">
                                                    แก้ไข
                                                    <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor">
                                                        <path d="M21.731 2.269a2.625 2.625 0 0 0-3.712 0l-1.157 1.157 3.712 3.712 1.157-1.157a2.625 2.625 0 0 0 0-3.712ZM19.513 8.199l-3.712-3.712-12.15 12.15a5.25 5.25 0 0 0-1.32 2.214l-.8 2.685a.75.75 0 0 0 .933.933l2.685-.8a5.25 5.25 0 0 0 2.214-1.32L19.513 8.2Z" />
                                                    </svg>
                                                </a>
                                            </li>
                                            <li>
                                                <a href="/graphic-design/src/review/delete_review.php?review_id=<?= $review['id'] ?>&order_id=<?= $order_id ?>"
                                                    onclick="return confirm('ยืนยันลบรีวิวนี้?');"
                                                    class="flex items-center justify-between px-3 py-2 rounded-lg bg-zinc-50 text-red-600 hover:bg-zinc-100 transition-colors duration-200 ring-1 ring-gray-200">
                                                    ลบ
                                                    <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor">
                                                        <path fill-rule="evenodd" d="M16.5 4.478v.227a48.816 48.816 0 0 1 3.878.512.75.75 0 1 1-.256 1.478l-.209-.035-1.005 13.07a3 3 0 0 1-2.991 2.77H8.084a3 3 0 0 1-2.991-2.77L4.087 6.66l-.209.035a.75.75 0 0 1-.256-1.478A48.567 48.567 0 0 1 7.5 4.705v-.227c0-1.564 1.213-2.9 2.816-2.951a52.662 52.662 0 0 1 3.369 0c1.603.051 2.815 1.387 2.815 2.951Zm-6.136-1.452a51.196 51.196 0 0 1 3.273 0C14.39 3.05 15 3.684 15 4.478v.113a49.488 49.488 0 0 0-6 0v-.113c0-.794.609-1.428 1.364-1.452Zm-.355 5.945a.75.75 0 1 0-1.5.058l.347 9a.75.75 0 1 0 1.499-.058l-.346-9Zm5.48.058a.75.75 0 1 0-1.498-.058l-.347 9a.75.75 0 0 0 1.5.058l.345-9Z" clip-rule="evenodd" />
                                                    </svg>
                                                </a>
                                            </li>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                            <div class="p-4">
                                <div class="bg-gray-50 p-3 rounded-2xl ring-1 ring-gray-200">
                                    <div class="flex"><?= str_repeat('<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="size-6 text-yellow-400">
                                        <path fill-rule="evenodd" d="M10.788 3.21c.448-1.077 1.976-1.077 2.424 0l2.082 5.006 5.404.434c1.164.093 1.636 1.545.749 2.305l-4.117 3.527 1.257 5.273c.271 1.136-.964 2.033-1.96 1.425L12 18.354 7.373 21.18c-.996.608-2.231-.29-1.96-1.425l1.257-5.273-4.117-3.527c-.887-.76-.415-2.212.749-2.305l5.404-.434 2.082-5.005Z" clip-rule="evenodd" />
                                        </svg>
                                        ', $review['rating']) ?>
                                    </div>
                                    <div class="py-2">
                                        <label class="block text-sm font-medium text-gray-700">ความคิดเห็น:</label>
                                        <div class="block text-sm font-medium text-gray-400"><?= htmlspecialchars($review['comment']) ?></div>
                                    </div>
                                    <div class="bg-gray-50 rounded-2xl overflow-hidden">
                                        <?php if ($review['image']): ?>
                                            <img src="/graphic-design/uploads/<?= htmlspecialchars($review['image']) ?>" alt="รีวิว" style="max-width: auto; max-height: 400px;"
                                                class="object-cover rounded border hover:opacity-90 cursor-pointer"
                                                style="aspect-ratio: 1/1;"
                                                onclick="openImageModal('/graphic-design/uploads/<?= htmlspecialchars($review['image']) ?>')">
                                        <?php endif; ?>
                                    </div>
                                    <div class="py-2">
                                        <p class="text-xs text-gray-400">อัปเดตเมื่อ: <?= $review['updated_at'] ?></p>
                                    </div>
                                </div>
                                <div class="mt-2">
                                    <?php if ($review && $review['is_approved'] == 0 && !empty($review['reason'])): ?>
                                        <div class="bg-red-50 border border-red-200 text-red-700 rounded-xl p-3">
                                            <strong>รีวิวของคุณไม่ได้รับการอนุมัติ</strong><br>
                                            เหตุผล: <?= htmlspecialchars($review['reason']) ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    <?php if ($payment && $payment['payment_status'] === 'cancelled'): ?>
        <div id="paymentFailedModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 backdrop-blur-sm bg-opacity-50">
            <div class="bg-white rounded-3xl shadow-lg p-8 max-w-sm w-full text-center relative">
                <!-- ปุ่มปิดมุมขวาบน -->
                <button
                    class="absolute top-2 right-2 bg-zinc-900 text-white rounded-full p-2 ring-1 ring-gray-200 shadow-md hover:bg-zinc-700 transition-all duration-300 ease-in-out hover:scale-105"
                    onclick="document.getElementById('paymentFailedModal').style.display='none';"
                    aria-label="ปิด">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
                <div class="text-xl font-bold mb-4 text-red-600">การชำระเงินไม่สำเร็จ</div>
                <div class="mb-6 text-gray-700">
                    กรุณาตรวจสอบข้อมูลและอัปเดทการชำระเงินใหม่
                </div>
                <a href="/graphic-design/src/client/payment.php?order_id=<?= $order_id ?>&retry=1"
                    class="w-full font-medium rounded-xl text-sm px-5 py-2 text-center flex items-center justify-center bg-red-600 text-white hover:bg-red-700 transition-all">
                    อัปเดทการชำระเงินใหม่
                </a>
            </div>
        </div>
    <?php endif; ?>

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
                    <i class="fa fa-download mr-2"></i> ดาวน์โหลดรูปภาพ
                </a>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/flowbite@3.1.2/dist/flowbite.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            var commentBox = document.getElementById('commentBox');
            if (commentBox) {
                commentBox.scrollTop = commentBox.scrollHeight;
            }
        });

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

        function openImageModal(src) {
            document.getElementById('modalImage').src = src;
            document.getElementById('downloadImageBtn').href = src;
            document.getElementById('imageModal').classList.remove('hidden');
        }

        function closeImageModal() {
            document.getElementById('imageModal').classList.add('hidden');
        }
    </script>
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
                            const isMe = msg.sender_role === 'customer';
                            chatBox.innerHTML += `
                        <div class="flex ${isMe ? 'flex-row-reverse' : ''} items-start">
                            <div class="${isMe ? 'text-right' : 'text-left'}">
                                <div class="${isMe ? 'bg-zinc-900 text-white rounded-xl' : 'bg-gray-200 text-gray-800 rounded-xl'} py-2 px-4 text-xs inline-block">
                                    <p>${msg.message.replace(/\n/g, '<br>')}</p>
                                </div>
                                <p class="text-xs text-gray-400 mt-1 ${isMe ? 'text-right' : ''}">
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
    <script>
        function showReviewForm() {
            document.getElementById('reviewForm').style.display = 'block';
            document.getElementById('dropdownReviewMenu').classList.add('hidden');
            // scroll ไปที่ฟอร์ม
            document.getElementById('reviewForm').scrollIntoView({
                behavior: 'smooth',
                block: 'center'
            });
        }

        function toggleReviewDropdown() {
            document.getElementById('dropdownReviewMenu').classList.toggle('hidden');
        }

        function scrollToReviewForm() {
            const form = document.querySelector('form[action*="edit_review.php"], form[action*="submit_review.php"]');
            if (form) form.scrollIntoView({
                behavior: 'smooth',
                block: 'center'
            });
            document.getElementById('dropdownReviewMenu').classList.add('hidden');
        }
        document.addEventListener('click', function(e) {
            if (!document.getElementById('dropdownReviewBtn').contains(e.target) &&
                !document.getElementById('dropdownReviewMenu').contains(e.target)) {
                document.getElementById('dropdownReviewMenu').classList.add('hidden');
            }
        });
    </script>
</body>

</html>