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
    </script>
</body>

</html>