<?php
// ตรวจสอบ error
error_reporting(E_ALL);
ini_set('display_errors', 1);
require __DIR__ . '/../includes/db_connect.php';
require_once '../auth/auth.php';
requireRole('admin'); // ให้เข้าหน้านี้ได้เฉพาะ admin

// ดึงสถิติจากฐานข้อมูล
$month = isset($_GET['month']) ? (int)$_GET['month'] : date('n');
$year = isset($_GET['year']) ? (int)$_GET['year'] : date('Y');
$date_filter = "AND MONTH(created_at) = $month AND YEAR(created_at) = $year";

// ดึงสถิติจากฐานข้อมูล (filter เฉพาะตารางที่มี created_at)
$review_pending = $conn->query("SELECT COUNT(*) FROM reviews WHERE is_approved = 0 $date_filter")->fetch_row()[0];
$review_approved = $conn->query("SELECT COUNT(*) FROM reviews WHERE is_approved = 1 $date_filter")->fetch_row()[0];
// ดึงรีวิวล่าสุด (ใช้ filter เดือน/ปี เดียวกับหน้า)
$latest_reviews = $conn->query("
    SELECT r.id, r.order_id, r.rating, r.comment, r.is_approved, r.created_at,
           o.order_code, COALESCE(c.fullname, '') AS customer_name
    FROM reviews r
    LEFT JOIN orders o ON r.order_id = o.order_id
    LEFT JOIN customers c ON r.customer_id = c.customer_id
    WHERE MONTH(r.created_at) = $month AND YEAR(r.created_at) = $year
    ORDER BY r.created_at DESC
    LIMIT 8
");
function getReviewStatusBadge($is_approved)
{
    if ((int)$is_approved === 1) {
        return '<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800 border border-green-300">อนุมัติ</span>';
    }
    return '<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-600 border border-yellow-300">รออนุมัติ</span>';
}


$latest_orders = $conn->query("
    SELECT o.order_code, o.customer_id, o.service_id, o.status, o.created_at,
        (SELECT version FROM work_files WHERE order_id = o.order_id ORDER BY created_at DESC LIMIT 1) AS version
    FROM orders o
    WHERE MONTH(o.created_at) = $month AND YEAR(o.created_at) = $year
    ORDER BY FIELD(o.status, 'pending', 'in_progress', 'completed', 'cancelled'), o.created_at DESC
LIMIT 6
");
function getVersionBadge($version)
{
    switch ($version) {
        case 'draft1':
            return '<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800 border border-yellow-300">Draft 1</span>';
        case 'draft2':
            return '<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800 border border-blue-300">Draft 2</span>';
        case 'final':
            return '<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800 border border-green-300">Final</span>';
        default:
            return '<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800 border border-gray-300">' . htmlspecialchars($version) . '</span>';
    }
}
function getOrderStatusTH($status)
{
    switch ($status) {
        case 'pending':
            return '<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800 border border-yellow-300">รอดำเนินการ</span>';
        case 'in_progress':
            return '<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800 border border-blue-300">กำลังดำเนินการ</span>';
        case 'completed':
            return '<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800 border border-green-300">เสร็จสมบูรณ์</span>';
        case 'cancelled':
            return '<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800 border border-red-300">ยกเลิก</span>';
        default:
            return $status;
    }
}
$version_labels = ['draft1' => 'Draft 1', 'draft2' => 'Draft 2', 'final' => 'Final'];
$version_counts = [];
foreach ($version_labels as $ver => $label) {
    $sql = "SELECT COUNT(*) AS total FROM work_files WHERE version = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $ver);
    $stmt->execute();
    $res = $stmt->get_result();
    $row = $res->fetch_assoc();
    $version_counts[] = (int)($row['total'] ?? 0);
}
//
$payment_pending = $conn->query("SELECT COUNT(*) FROM payments WHERE payment_status = 'pending' $date_filter")->fetch_row()[0];
$latest_payments = $conn->query("
    SELECT payment_id, order_id, payment_method, amount, payment_status, created_at
    FROM payments
    WHERE MONTH(created_at) = $month AND YEAR(created_at) = $year
    ORDER BY FIELD(payment_status, 'pending', 'failed', 'paid'), created_at DESC
    LIMIT 8
");
function getPaymentStatusBadge($status)
{
    switch ($status) {
        case 'paid':
            return '<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800 border border-green-300">สำเร็จ</span>';
        case 'pending':
            return '<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800 border border-yellow-300">รออนุมัติ</span>';
        case 'failed':
        default:
            return '<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800 border border-red-300">ล้มเหลว</span>';
    }
}
// ดึงข้อมูลสัดส่วนวิธีชำระเงิน
$method_labels = [];
$method_counts = [];
$res = $conn->query("SELECT payment_method, COUNT(*) as cnt FROM payments WHERE MONTH(created_at) = $month AND YEAR(created_at) = $year GROUP BY payment_method");
while ($row = $res->fetch_assoc()) {
    $method_labels[] = $row['payment_method'] ?: 'ไม่ระบุ';
    $method_counts[] = (int)$row['cnt'];
}

$services = $conn->query("SELECT service_id, service_name, base_price, price_unit, is_active, is_featured, created_at FROM services");

$customers = $conn->query("SELECT customer_id, fullname, email, phone, created_at, role FROM customers ORDER BY created_at DESC");
// ดึงข้อมูล portfolios ทั้งหมด (แสดงข้อมูลที่ยังไม่เคยแสดง เช่น client_name, project_date, tags, thumbnail_url)
$portfolios = $conn->query("SELECT portfolio_id, service_id, title, client_name, project_date, tags, image_url, thumbnail_url, is_featured, is_active, created_at FROM portfolios ORDER BY created_at DESC");
// ตารางที่ไม่มี created_at ไม่ต้อง filter
$payment_total = $conn->query("SELECT COUNT(*) FROM payments WHERE 1 $date_filter")->fetch_row()[0];
$income_total = $conn->query("SELECT SUM(amount) FROM payments WHERE payment_status = 'paid' $date_filter")->fetch_row()[0] ?? 0;
$service_total = $conn->query("SELECT COUNT(*) FROM services")->fetch_row()[0];
$customer_total = $conn->query("SELECT COUNT(*) FROM customers")->fetch_row()[0];
$order_total = $conn->query("SELECT COUNT(*) FROM orders WHERE 1 $date_filter")->fetch_row()[0];
$review_total = $conn->query("SELECT COUNT(*) FROM reviews WHERE 1 $date_filter")->fetch_row()[0];
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard</title>
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

<body class="font-thai bg-zinc-100">
    <?php include '../includes/sidebar.php'; ?>
    <div class=" ml-64 ">
        <!-- breadcrumb -->
        <?php
        $breadcrumb = ['Dashboard'];
        $breadcrumb_links = ['/admin/index.php'];
        include '../includes/admin_navbar.php';
        ?>
        <!-- <h2>Welcome Admin, <?php echo $_SESSION['fullname']; ?></h2> -->
        <div class="p-4">
            <div class="bg-white rounded-2xl p-4 ring-1 ring-gray-200 mb-4">
                <div class="flex items-center">
                    <h3 class="font-bold mb-4 text-zinc-700 text-xl tracking-tight">Dashboard</h3>
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-4">
                    <div class="flex items-center gap-4 p-2 bg-gray-50 rounded-2xl ring-1 ring-gray-200">
                        <div class="text-green-500 bg-zinc-900 rounded-xl p-3">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="size-6">
                                <path d="M12 7.5a2.25 2.25 0 1 0 0 4.5 2.25 2.25 0 0 0 0-4.5Z" />
                                <path fill-rule="evenodd" d="M1.5 4.875C1.5 3.839 2.34 3 3.375 3h17.25c1.035 0 1.875.84 1.875 1.875v9.75c0 1.036-.84 1.875-1.875 1.875H3.375A1.875 1.875 0 0 1 1.5 14.625v-9.75ZM8.25 9.75a3.75 3.75 0 1 1 7.5 0 3.75 3.75 0 0 1-7.5 0ZM18.75 9a.75.75 0 0 0-.75.75v.008c0 .414.336.75.75.75h.008a.75.75 0 0 0 .75-.75V9.75a.75.75 0 0 0-.75-.75h-.008ZM4.5 9.75A.75.75 0 0 1 5.25 9h.008a.75.75 0 0 1 .75.75v.008a.75.75 0 0 1-.75.75H5.25a.75.75 0 0 1-.75-.75V9.75Z" clip-rule="evenodd" />
                                <path d="M2.25 18a.75.75 0 0 0 0 1.5c5.4 0 10.63.722 15.6 2.075 1.19.324 2.4-.558 2.4-1.82V18.75a.75.75 0 0 0-.75-.75H2.25Z" />
                            </svg>
                        </div>
                        <div>
                            <div class="text-2xl font-bold"><?= number_format($income_total, 2) ?> บาท</div>
                            <div class="text-sm font-semibold text-gray-500">รายได้ทั้งหมด</div>
                        </div>
                    </div>
                    <div class="flex items-center gap-4 p-2 bg-gray-50 rounded-xl ring-1 ring-gray-200">
                        <div class="text-red-500 bg-zinc-900 rounded-xl p-3">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="size-6">
                                <path d="M4.5 3.75a3 3 0 0 0-3 3v.75h21v-.75a3 3 0 0 0-3-3h-15Z" />
                                <path fill-rule="evenodd" d="M22.5 9.75h-21v7.5a3 3 0 0 0 3 3h15a3 3 0 0 0 3-3v-7.5Zm-18 3.75a.75.75 0 0 1 .75-.75h6a.75.75 0 0 1 0 1.5h-6a.75.75 0 0 1-.75-.75Zm.75 2.25a.75.75 0 0 0 0 1.5h3a.75.75 0 0 0 0-1.5h-3Z" clip-rule="evenodd" />
                            </svg>
                        </div>
                        <div>
                            <div class="text-2xl font-bold"><?= $payment_pending ?></div>
                            <div class="text-sm font-semibold text-gray-500">ชำระเงินรออนุมัติ</div>
                        </div>
                    </div>
                    <div class="flex items-center gap-4 p-2 bg-gray-50 rounded-xl ring-1 ring-gray-200">
                        <div class="text-pink-500 bg-zinc-900 rounded-xl p-3">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="size-6">
                                <path d="M12.378 1.602a.75.75 0 0 0-.756 0L3 6.632l9 5.25 9-5.25-8.622-5.03ZM21.75 7.93l-9 5.25v9l8.628-5.032a.75.75 0 0 0 .372-.648V7.93ZM11.25 22.18v-9l-9-5.25v8.57a.75.75 0 0 0 .372.648l8.628 5.033Z" />
                            </svg>
                        </div>
                        <div>
                            <div class="text-2xl font-bold"><?= $order_total ?></div>
                            <div class="text-sm font-semibold text-gray-500">ออเดอร์ทั้งหมด</div>
                        </div>
                    </div>
                    <div class="flex items-center gap-4 p-2 bg-gray-50 rounded-xl ring-1 ring-gray-200">
                        <div class="text-yellow-500 bg-zinc-900 rounded-xl p-3">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="size-6">
                                <path fill-rule="evenodd" d="M10.788 3.21c.448-1.077 1.976-1.077 2.424 0l2.082 5.006 5.404.434c1.164.093 1.636 1.545.749 2.305l-4.117 3.527 1.257 5.273c.271 1.136-.964 2.033-1.96 1.425L12 18.354 7.373 21.18c-.996.608-2.231-.29-1.96-1.425l1.257-5.273-4.117-3.527c-.887-.76-.415-2.212.749-2.305l5.404-.434 2.082-5.005Z" clip-rule="evenodd" />
                            </svg>
                        </div>
                        <div>
                            <div class="text-2xl font-bold"><?= $review_total ?></div>
                            <div class="text-sm font-semibold text-gray-500">รีวิวทั้งหมด</div>
                        </div>
                    </div>
                    <div class="flex items-center gap-4 p-2 bg-gray-50 rounded-xl ring-1 ring-gray-200">
                        <div class="text-blue-500 bg-zinc-900 rounded-xl p-3">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="size-6">
                                <path d="M5.566 4.657A4.505 4.505 0 0 1 6.75 4.5h10.5c.41 0 .806.055 1.183.157A3 3 0 0 0 15.75 3h-7.5a3 3 0 0 0-2.684 1.657ZM2.25 12a3 3 0 0 1 3-3h13.5a3 3 0 0 1 3 3v6a3 3 0 0 1-3 3H5.25a3 3 0 0 1-3-3v-6ZM5.25 7.5c-.41 0-.806.055-1.184.157A3 3 0 0 1 6.75 6h10.5a3 3 0 0 1 2.683 1.657A4.505 4.505 0 0 0 18.75 7.5H5.25Z" />
                            </svg>
                        </div>
                        <div>
                            <div class="text-2xl font-bold"><?= $service_total ?></div>
                            <div class="text-sm font-semibold text-gray-500">บริการทั้งหมด</div>
                        </div>
                    </div>
                    <div class="flex items-center gap-4 p-2 bg-gray-50 rounded-xl ring-1 ring-gray-200">
                        <div class="text-purple-500 bg-zinc-900 rounded-xl p-3">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="size-6">
                                <path fill-rule="evenodd" d="M8.25 6.75a3.75 3.75 0 1 1 7.5 0 3.75 3.75 0 0 1-7.5 0ZM15.75 9.75a3 3 0 1 1 6 0 3 3 0 0 1-6 0ZM2.25 9.75a3 3 0 1 1 6 0 3 3 0 0 1-6 0ZM6.31 15.117A6.745 6.745 0 0 1 12 12a6.745 6.745 0 0 1 6.709 7.498.75.75 0 0 1-.372.568A12.696 12.696 0 0 1 12 21.75c-2.305 0-4.47-.612-6.337-1.684a.75.75 0 0 1-.372-.568 6.787 6.787 0 0 1 1.019-4.38Z" clip-rule="evenodd" />
                                <path d="M5.082 14.254a8.287 8.287 0 0 0-1.308 5.135 9.687 9.687 0 0 1-1.764-.44l-.115-.04a.563.563 0 0 1-.373-.487l-.01-.121a3.75 3.75 0 0 1 3.57-4.047ZM20.226 19.389a8.287 8.287 0 0 0-1.308-5.135 3.75 3.75 0 0 1 3.57 4.047l-.01.121a.563.563 0 0 1-.373.486l-.115.04c-.567.2-1.156.349-1.764.441Z" />
                            </svg>
                        </div>
                        <div>
                            <div class="text-2xl font-bold"><?= $customer_total ?></div>
                            <div class="text-sm font-semibold text-gray-500">ลูกค้าทั้งหมด</div>
                        </div>
                    </div>
                </div>
            </div>
            <?php include 'dashboard_charts.php'; ?>
            <div class="grid grid-cols-3 space-x-4">
                <div class="lg:col-span-2">
                    <div class="bg-white rounded-2xl p-4  ring-1 ring-gray-200 mb-4">
                        <div class="flex items-center justify-between align-top mb-4">
                            <h3 class="font-bold text-zinc-700 text-xl tracking-tight">รายการชำระเงินล่าสุด</h3>
                            <a href="/graphic-design/src/admin/payment_list.php" class="border transition font-medium rounded-xl text-sm px-5 py-1 text-center flex items-center justify-center bg-white text-gray-600 border-gray-300 hover:bg-gray-100">ดูทั้งหมด</a>
                        </div>
                        <div class="bg-gray-50 rounded-xl ring-1 ring-gray-200">
                            <table>
                                <!-- ตารางรายการชำระเงินล่าสุด -->
                                <table class="min-w-full text-sm">
                                    <thead>
                                        <tr class="bg-zinc-200 text-zinc-700">
                                            <th class="px-3 py-2 text-left rounded-tl-xl">#</th>
                                            <th class="px-3 py-2 text-left">Order</th>
                                            <th class="px-3 py-2 text-left">วิธีชำระเงิน</th>
                                            <th class="px-3 py-2 text-right">จำนวนเงิน</th>
                                            <th class="px-3 py-2 text-center">สถานะ</th>
                                            <th class="px-3 py-2 text-center rounded-tr-xl">วันที่</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if ($latest_payments->num_rows): ?>
                                            <?php while ($row = $latest_payments->fetch_assoc()): ?>
                                                <tr class="border-b last:border-b-0 hover:bg-zinc-100">
                                                    <td class="px-3 py-2"><?= htmlspecialchars($row['payment_id']) ?></td>
                                                    <td class="px-3 py-2"><?= htmlspecialchars($row['order_id']) ?></td>
                                                    <td class="px-3 py-2"><?= htmlspecialchars($row['payment_method'] ?: '-') ?></td>
                                                    <td class="px-3 py-2 text-right"><?= number_format($row['amount'], 2) ?></td>
                                                    <td class="px-3 py-2 text-center">
                                                        <?= getPaymentStatusBadge($row['payment_status']) ?>
                                                    </td>
                                                    <td class="px-3 py-2 text-center"><?= date('d/m/Y H:i', strtotime($row['created_at'])) ?></td>
                                                </tr>
                                            <?php endwhile; ?>
                                        <?php else: ?>
                                            <tr>
                                                <td colspan="6" class="text-center text-zinc-400 py-4">ไม่มีข้อมูล</td>
                                            </tr>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </table>
                        </div>
                    </div>
                </div>
                <div class="bg-white rounded-2xl p-4  ring-1 ring-gray-200 mb-4">
                    <div class="flex items-center">
                        <h3 class="font-bold mb-4 text-zinc-700 text-xl tracking-tight">สัดส่วนวิธีชำระเงิน</h3>
                    </div>
                    <div class="p-4 bg-gray-50 rounded-xl ring-1 ring-gray-200">
                        <!-- chart สัดส่วนวิธีชำระเงิน -->
                        <div class="flex flex-col items-center justify-center min-h-[240px]">
                            <canvas id="paymentMethodChart" width="400" height="240"></canvas>
                        </div>
                    </div>
                </div>
            </div>
            <div class="grid grid-cols-3 space-x-4">
                <div class="lg:col-span-2">
                    <div class="bg-white rounded-2xl p-4 ring-1 ring-gray-200 mb-4">
                        <div class="flex items-center justify-between align-top mb-4">
                            <h3 class="font-bold text-zinc-700 text-xl tracking-tight">ออเดอร์ล่าสุด</h3>
                            <a href="/graphic-design/src/admin/order_list.php" class="border transition font-medium rounded-xl text-sm px-5 py-1 text-center flex items-center justify-center bg-white text-gray-600 border-gray-300 hover:bg-gray-100">ดูทั้งหมด</a>
                        </div>
                        <div class="bg-gray-50 rounded-xl ring-1 ring-gray-200">
                            <table class="min-w-full text-sm">
                                <thead>
                                    <tr class="bg-zinc-200 text-zinc-700">
                                        <th class="px-3 py-2 text-left rounded-tl-xl">#ORDER ID</th>
                                        <th class="px-3 py-2 text-left">ลูกค้า</th>
                                        <th class="px-3 py-2 text-left">บริการ</th>
                                        <th class="px-3 py-2 text-left">เวอร์ชันงาน</th>
                                        <th class="px-3 py-2 text-center">สถานะ</th>
                                        <th class="px-3 py-2 text-center rounded-tr-xl">วันที่</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if ($latest_orders->num_rows): ?>
                                        <?php while ($row = $latest_orders->fetch_assoc()): ?>
                                            <tr class="border-b last:border-b-0 hover:bg-zinc-100">
                                                <td class="px-3 py-2"><?= htmlspecialchars($row['order_code']) ?></td>
                                                <td class="px-3 py-2"><?= htmlspecialchars($row['customer_id']) ?></td>
                                                <td class="px-3 py-2"><?= htmlspecialchars($row['service_id']) ?></td>
                                                <td class="px-3 py-2">
                                                    <?= getVersionBadge($row['version'] ?: 'ยังไม่มีไฟล์') ?>
                                                </td>
                                                <td class="px-3 py-2 text-center">
                                                    <?= getOrderStatusTH($row['status']) ?>
                                                </td>
                                                <td class="px-3 py-2 text-center"><?= date('d/m/Y H:i', strtotime($row['created_at'])) ?></td>
                                            </tr>
                                        <?php endwhile; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="7" class="text-center text-zinc-400 py-4">ไม่มีข้อมูล</td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                <div class="bg-white rounded-2xl p-4  ring-1 ring-gray-200 mb-4">
                    <div class="flex items-center">
                        <h3 class="font-bold mb-4 text-zinc-700 text-xl tracking-tight">จำนวนงานแต่ละเวอร์ชัน</h3>
                    </div>
                    <div class="p-4 bg-gray-50 rounded-xl ring-1 ring-gray-200">
                        <!-- chart สัดส่วนวิธีชำระเงิน -->
                        <div class="flex flex-col items-center justify-center min-h-[240px]">
                            <canvas id="workVersionChart" width="400" height="240"></canvas>
                        </div>
                    </div>
                </div>
            </div>
            <div class="bg-white rounded-2xl p-4 ring-1 ring-gray-200 mb-4">
                <div class="flex items-center justify-between align-top mb-4">
                    <h3 class="font-bold text-zinc-700 text-xl tracking-tight">รีวิวล่าสุด</h3>
                    <a href="/graphic-design/src/admin/review_list.php" class="border transition font-medium rounded-xl text-sm px-5 py-1 text-center flex items-center justify-center bg-white text-gray-600 border-gray-300 hover:bg-gray-100">ดูทั้งหมด</a>
                </div>
                <div class="bg-gray-50 rounded-xl ring-1 ring-gray-200 overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <thead>
                            <tr class="bg-zinc-200 text-zinc-700">
                                <th class="px-3 py-2 text-left">#ORDER REVIEW</th>
                                <th class="px-3 py-2 text-left">ลูกค้า</th>
                                <th class="px-3 py-2 text-center">คะแนน</th>
                                <th class="px-3 py-2 text-left">ความเห็น</th>
                                <th class="px-3 py-2 text-center">สถานะ</th>
                                <th class="px-3 py-2 text-center rounded-tr-xl">วันที่</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($latest_reviews && $latest_reviews->num_rows): ?>
                                <?php while ($r = $latest_reviews->fetch_assoc()): ?>
                                    <tr class="border-b last:border-b-0 hover:bg-zinc-100 align-top">
                                        <td class="px-3 py-2"><?= htmlspecialchars($r['order_code'] ?: '-') ?></td>
                                        <td class="px-3 py-2"><?= htmlspecialchars($r['customer_name'] ?: '-') ?></td>
                                        <td class="px-3 py-2 text-center"><?= (int)$r['rating'] ?>/5</td>
                                        <td class="px-3 py-2"><?= htmlspecialchars(mb_strimwidth($r['comment'] ?? '-', 0, 120, '...')) ?></td>
                                        <td class="px-3 py-2 text-center"><?= getReviewStatusBadge($r['is_approved']) ?></td>
                                        <td class="px-3 py-2 text-center"><?= date('d/m/Y H:i', strtotime($r['created_at'])) ?></td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="7" class="text-center text-zinc-400 py-4">ไม่มีข้อมูล</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="bg-white rounded-2xl p-4 ring-1 ring-gray-200 mb-4">
                <div class="flex items-center justify-between align-top mb-4">
                    <h3 class="font-bold text-zinc-700 text-xl tracking-tight">บริการทั้งหมด</h3>
                    <a href="/graphic-design/src/admin/service_list.php" class="border transition font-medium rounded-xl text-sm px-5 py-1 text-center flex items-center justify-center bg-white text-gray-600 border-gray-300 hover:bg-gray-100">ดูทั้งหมด</a>
                </div>
                <div class="bg-gray-50 rounded-xl ring-1 ring-gray-200 overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <thead>
                            <tr class="bg-zinc-200 text-zinc-700">
                                <th class="px-3 py-2 text-left rounded-tl-xl">#</th>
                                <th class="px-3 py-2 text-left">ชื่อบริการ</th>
                                <th class="px-3 py-2 text-right">ราคา</th>
                                <th class="px-3 py-2 text-right">หน่วยราคา</th>
                                <th class="px-3 py-2 text-center">สถานะ</th>
                                <th class="px-3 py-2 text-center">แนะนำ</th>
                                <th class="px-3 py-2 text-center rounded-tr-xl">วันที่สร้าง</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($services && $services->num_rows): ?>
                                <?php while ($s = $services->fetch_assoc()): ?>
                                    <tr class="border-b last:border-b-0 hover:bg-zinc-100">
                                        <td class="px-3 py-2"><?= htmlspecialchars($s['service_id']) ?></td>
                                        <td class="px-3 py-2"><?= htmlspecialchars($s['service_name']) ?></td>
                                        <td class="px-3 py-2 text-right"><?= number_format($s['base_price'], 2) ?></td>
                                        <td class="px-3 py-2 text-right"><?= htmlspecialchars($s['price_unit']) ?></td>
                                        <td class="px-3 py-2 text-center">
                                            <?php if ($s['is_active']): ?>
                                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800 border border-green-300">เปิดใช้งาน</span>
                                            <?php else: ?>
                                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-600 border border-red-300">ปิดใช้งาน</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="px-3 py-2 text-center">
                                            <?php if ($s['is_featured']): ?>
                                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800 border border-yellow-300">แนะนำ</span>
                                            <?php else: ?>
                                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800 border border-gray-300">-</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="px-3 py-2 text-center"><?= date('d/m/Y', strtotime($s['created_at'])) ?></td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="5" class="text-center text-zinc-400 py-4">ไม่มีข้อมูล</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="bg-white rounded-2xl p-4 ring-1 ring-gray-200 mb-4">
                <div class="flex items-center justify-between align-top mb-4">
                    <h3 class="font-bold text-zinc-700 text-xl tracking-tight">ลูกค้าทั้งหมด</h3>
                    <a href="/graphic-design/src/admin/customer_list.php" class="border transition font-medium rounded-xl text-sm px-5 py-1 text-center flex items-center justify-center bg-white text-gray-600 border-gray-300 hover:bg-gray-100">ดูทั้งหมด</a>
                </div>
                <div class="bg-gray-50 rounded-xl ring-1 ring-gray-200 overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <thead>
                            <tr class="bg-zinc-200 text-zinc-700">
                                <th class="px-3 py-2 text-left rounded-tl-xl">#</th>
                                <th class="px-3 py-2 text-left">ชื่อ-นามสกุล</th>
                                <th class="px-3 py-2 text-left">อีเมล</th>
                                <th class="px-3 py-2 text-left">เบอร์โทร</th>
                                <th class="px-3 py-2 text-center">สถานะ</th>
                                <th class="px-3 py-2 text-center rounded-tr-xl">วันที่สมัคร</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($customers && $customers->num_rows): ?>
                                <?php while ($c = $customers->fetch_assoc()): ?>
                                    <tr class="border-b last:border-b-0 hover:bg-zinc-100">
                                        <td class="px-3 py-2"><?= htmlspecialchars($c['customer_id']) ?></td>
                                        <td class="px-3 py-2"><?= htmlspecialchars($c['fullname']) ?></td>
                                        <td class="px-3 py-2"><?= htmlspecialchars($c['email']) ?></td>
                                        <td class="px-3 py-2"><?= htmlspecialchars($c['phone']) ?></td>
                                        <td class="px-3 py-2 text-center">
                                            <?php if ($c['role'] === 'admin'): ?>
                                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-purple-100 text-purple-800 border border-purple-300">ผู้ดูแลระบบ</span>
                                            <?php else: ?>
                                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800 border border-blue-300">ลูกค้า</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="px-3 py-2 text-center"><?= date('d/m/Y', strtotime($c['created_at'])) ?></td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="6" class="text-center text-zinc-400 py-4">ไม่มีข้อมูล</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="bg-white rounded-2xl p-4 ring-1 ring-gray-200 mb-4">
                <div class="flex items-center justify-between align-top mb-4">
                    <h3 class="font-bold text-zinc-700 text-xl tracking-tight">ผลงานร้านทั้งหมด</h3>
                    <a href="/graphic-design/src/admin/portfolio_list.php" class="border transition font-medium rounded-xl text-sm px-5 py-1 text-center flex items-center justify-center bg-white text-gray-600 border-gray-300 hover:bg-gray-100">ดูทั้งหมด</a>
                </div>
                <div class="bg-gray-50 rounded-xl ring-1 ring-gray-200 overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <thead>
                            <tr class="bg-zinc-200 text-zinc-700">
                                <th class="px-3 py-2 text-left rounded-tl-xl">#</th>
                                <th class="px-3 py-2 text-left">ชื่อผลงาน</th>
                                <th class="px-3 py-2 text-left">บริการ</th>
                                <th class="px-3 py-2 text-left">ลูกค้า</th>
                                <th class="px-3 py-2 text-left">Tags</th>
                                <th class="px-3 py-2 text-center">แนะนำ</th>
                                <th class="px-3 py-2 text-center">สถานะ</th>
                                <th class="px-3 py-2 text-center rounded-tr-xl">วันที่สร้าง</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($portfolios && $portfolios->num_rows): ?>
                                <?php while ($p = $portfolios->fetch_assoc()): ?>
                                    <tr class="border-b last:border-b-0 hover:bg-zinc-100">
                                        <td class="px-3 py-2"><?= htmlspecialchars($p['portfolio_id']) ?></td>
                                        <td class="px-3 py-2"><?= htmlspecialchars($p['title']) ?></td>
                                        <td class="px-3 py-2"><?= htmlspecialchars($p['service_id']) ?></td>
                                        <td class="px-3 py-2"><?= htmlspecialchars($p['client_name'] ?: '-') ?></td>
                                        <td class="px-3 py-2">
                                            <?php
                                            if (!empty($p['tags'])) {
                                                $tags = json_decode($p['tags'], true);
                                                if (is_array($tags)) {
                                                    foreach ($tags as $tag) {
                                                        echo '<span class="inline-block bg-zinc-200 text-zinc-700 rounded px-2 py-0.5 text-xs mr-1 mb-1">' . htmlspecialchars($tag) . '</span>';
                                                    }
                                                } else {
                                                    echo '-';
                                                }
                                            } else {
                                                echo '-';
                                            }
                                            ?>
                                        </td>
                                        <td class="px-3 py-2 text-center">
                                            <?php if ($p['is_featured']): ?>
                                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800 border border-yellow-300">แนะนำ</span>
                                            <?php else: ?>
                                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium text-gray-500 bg-gray-100">-</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="px-3 py-2 text-center">
                                            <?php if ($p['is_active']): ?>
                                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800 border border-green-300">เปิดใช้งาน</span>
                                            <?php else: ?>
                                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-600 border border-red-300">ปิดใช้งาน</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="px-3 py-2 text-center"><?= date('d/m/Y', strtotime($p['created_at'])) ?></td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="11" class="text-center text-zinc-400 py-4">ไม่มีข้อมูล</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        // กราฟ doughnut Acme-style (วิธีชำระเงิน)
        new Chart(document.getElementById('paymentMethodChart'), {
            type: 'doughnut',
            data: {
                labels: <?= json_encode($methods) ?>,
                datasets: [{
                    data: <?= json_encode($method_counts) ?>,
                    backgroundColor: [
                        '#09090b', '#71717a', '#a78bfa', '#f472b6', '#34d399', '#f87171'
                    ],
                    borderWidth: 2,
                    borderRadius: 100,
                    borderColor: '#fff'
                }]
            },
            options: {
                responsive: true,
                cutout: '70%',
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: {
                            color: '#374151',
                            font: {
                                size: 15,
                                weight: 'bold'
                            },
                            padding: 18,
                            boxWidth: 18
                        }
                    },
                    tooltip: {
                        backgroundColor: '#fff',
                        titleColor: '#71717a',
                        bodyColor: '#222',
                        borderColor: '#71717a',
                        borderWidth: 1,
                        padding: 12,
                        callbacks: {
                            label: ctx => ` ${ctx.label}: ${ctx.parsed} รายการ`
                        }
                    }
                },
                animation: {
                    animateRotate: true,
                    duration: 1200
                }
            }
        });

        new Chart(document.getElementById('workVersionChart'), {
            type: 'bar',
            data: {
                labels: <?= json_encode(array_values($version_labels)) ?>,
                datasets: [{
                    label: 'จำนวน',
                    data: <?= json_encode($version_counts) ?>,
                    backgroundColor: ['#d4d4d8', '#52525b', '#09090b'],
                    borderRadius: 20,
                    maxBarThickness: 48,
                    borderSkipped: false
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        display: false
                    },
                    tooltip: {
                        backgroundColor: '#fff',
                        titleColor: '#71717a',
                        bodyColor: '#222',
                        borderColor: '#71717a',
                        borderWidth: 1,
                        padding: 12,
                        callbacks: {
                            label: ctx => ` ${ctx.parsed.y} งาน`
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        grid: {
                            color: '#f3f4f6'
                        },
                        ticks: {
                            color: '#6b7280',
                            font: {
                                size: 13
                            }
                        }
                    },
                    x: {
                        grid: {
                            display: false
                        },
                        ticks: {
                            color: '#6b7280',
                            font: {
                                size: 13
                            }
                        }
                    }
                },
                animation: {
                    duration: 1200,
                    easing: 'easeOutQuart'
                }
            }
        });
    </script>
</body>

</html>