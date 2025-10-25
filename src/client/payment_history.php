<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
require __DIR__ . '/../includes/db_connect.php';
require_once '../auth/auth.php';
requireLogin();

$customer_id = $_SESSION['customer_id'] ?? 0;

$sql = "SELECT p.*, o.order_code, s.service_name 
        FROM payments p
        LEFT JOIN orders o ON p.order_id = o.order_id
        LEFT JOIN services s ON o.service_id = s.service_id
        WHERE p.customer_id = ?
        ORDER BY p.payment_date DESC";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $customer_id);
$stmt->execute();
$result = $stmt->get_result();

// ดึงยอดชำระเงินแต่ละเดือน (เฉพาะที่อนุมัติ)
$chartSql = "SELECT DATE_FORMAT(payment_date, '%Y-%m') AS ym, SUM(amount) AS total
             FROM payments
             WHERE customer_id = ? AND payment_status = 'paid'
             GROUP BY ym
             ORDER BY ym";
$chartStmt = $conn->prepare($chartSql);
$chartStmt->bind_param("i", $customer_id);
$chartStmt->execute();
$chartResult = $chartStmt->get_result();

// ยอดเงินรวมที่อนุมัติแล้ว
$totalApprovedSql = "SELECT SUM(amount) AS total_approved FROM payments WHERE customer_id = ? AND payment_status = 'paid'";
$totalApprovedStmt = $conn->prepare($totalApprovedSql);
$totalApprovedStmt->bind_param("i", $customer_id);
$totalApprovedStmt->execute();
$totalApprovedStmt->bind_result($totalApproved);
$totalApprovedStmt->fetch();
$totalApprovedStmt->close();

// ยอดเงินรวมทั้งหมด (ทุกสถานะ)
$totalAllSql = "SELECT SUM(amount) AS total_all FROM payments WHERE customer_id = ?";
$totalAllStmt = $conn->prepare($totalAllSql);
$totalAllStmt->bind_param("i", $customer_id);
$totalAllStmt->execute();
$totalAllStmt->bind_result($totalAll);
$totalAllStmt->fetch();
$totalAllStmt->close();

$chartLabels = [];
$chartData = [];
while ($row = $chartResult->fetch_assoc()) {
    $chartLabels[] = $row['ym'];
    $chartData[] = (float)$row['total'];
}

// ดึงจำนวนแต่ละสถานะ
$statusPieSql = "SELECT payment_status, COUNT(*) AS count
                 FROM payments
                 WHERE customer_id = ?
                 GROUP BY payment_status";
$statusPieStmt = $conn->prepare($statusPieSql);
$statusPieStmt->bind_param("i", $customer_id);
$statusPieStmt->execute();
$statusPieResult = $statusPieStmt->get_result();

$statusPieLabels = [];
$statusPieData = [];
$statusPieColors = [];
while ($row = $statusPieResult->fetch_assoc()) {
    if ($row['payment_status'] === 'paid') {
        $statusPieLabels[] = 'อนุมัติ';
        $statusPieColors[] = 'rgba(34,197,94,0.7)'; // เขียว
    } elseif ($row['payment_status'] === 'pending') {
        $statusPieLabels[] = 'รอตรวจสอบ';
        $statusPieColors[] = 'rgba(253,224,71,0.7)'; // เหลือง
    } elseif ($row['payment_status'] === 'cancelled') {
        $statusPieLabels[] = 'ถูกปฏิเสธ';
        $statusPieColors[] = 'rgba(239,68,68,0.7)'; // แดง
    } else {
        $statusPieLabels[] = $row['payment_status'];
        $statusPieColors[] = 'rgba(156,163,175,0.7)'; // เทา
    }
    $statusPieData[] = (int)$row['count'];
}

$statusText = [
    'pending' => '<span class="bg-yellow-100 text-yellow-800 px-2 py-1 rounded text-xs">รอตรวจสอบ</span>',
    'approved' => '<span class="bg-green-100 text-green-800 px-2 py-1 rounded text-xs">อนุมัติ</span>',
    'rejected' => '<span class="bg-red-100 text-red-800 px-2 py-1 rounded text-xs">ถูกปฏิเสธ</span>',
];
?>
<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ประวัติการชำระเงิน</title>
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

<body class="font-thai min-h-screen">
    <div class="p-1 space-y-2">
        <div class="">
            <div class="flex flex-row gap-2">
                <!-- กราฟแท่ง: 10 ส่วน -->
                <div class="bg-white rounded-2xl p-4 w-full max-h-auto relative ring-1 ring-gray-200" style="flex: 10 1 0%;">
                    <div class="flex items-center justify-between align-top mb-4">
                        <h3 class="font-bold text-zinc-700 text-xl tracking-tight">ยอดชำระเงินแต่ละเดือน</h3>
                    </div>
                    <div class="p-4 bg-gray-50 rounded-xl max-h-auto ring-1 ring-gray-200">
                        <canvas id="paymentBarChart" height="120"></canvas>
                    </div>
                </div>
                <div class="flex flex-col gap-2">
                    <div class="bg-white rounded-2xl p-4 w-full max-h-auto relative ring-1 ring-gray-200" style="flex: 2 1 0%; min-width:220px; max-width:320px;">
                        <div class="flex items-center justify-between align-top mb-4">
                            <h3 class="font-bold text-zinc-700 text-xl tracking-tight">สัดส่วนสถานะการชำระเงิน</h3>
                        </div>
                        <div class="p-4 bg-gray-50 rounded-xl max-h-auto ring-1 ring-gray-200">
                            <canvas id="paymentPieChart" height="120"></canvas>
                        </div>
                    </div>
                    <div class="bg-white rounded-2xl p-4 w-full max-h-auto relative ring-1 ring-gray-200">
                        <div class="flex items-center justify-between align-top mb-4">
                            <h3 class="font-bold text-zinc-700 text-xl tracking-tight">ยอดเงินที่อนุมัติแล้ว</h3>
                        </div>
                        <div class="p-4 bg-gray-50 rounded-xl max-h-auto ring-1 ring-gray-200">
                            <span class="text-2xl font-bold text-zinc-700"><?= number_format($totalApproved, 2) ?> บาท</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class=" bg-white items-center p-2 ring-1 ring-zinc-200 rounded-2xl">
            <div class="">
                <div class="my-2 flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                    <h1 class="text-xl font-bold text-zinc-900">ประวัติการชำระเงิน</h1>
                </div>
            </div>
            <div class="">
                <div class="overflow-x-auto bg-white rounded-2xl shadow ring-1 ring-gray-200">
                    <table class="min-w-full text-sm text-zinc-800">
                        <thead class="bg-zinc-50">
                            <tr class="border-b">
                                <th class="px-4 py-3 text-left font-semibold">No.</th>
                                <th class="px-4 py-3 text-left font-semibold">วันที่ชำระเงิน</th>
                                <th class="px-4 py-3 text-left font-semibold">เลขที่ออเดอร์</th>
                                <th class="px-4 py-3 text-left font-semibold">ชื่อบริการ</th>
                                <th class="px-4 py-3 text-right font-semibold">จำนวนเงิน</th>
                                <th class="px-4 py-3 text-center font-semibold">สถานะ</th>
                                <th class="px-4 py-3 text-left font-semibold">วิธีชำระเงิน</th>
                                <th class="px-4 py-3 text-center font-semibold">สลิป</th>
                                <th class="px-4 py-3 text-left font-semibold">หมายเหตุ</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php $i = 1;
                            while ($row = $result->fetch_assoc()): ?>
                                <tr class="border-b last:border-b-0 hover:bg-zinc-50 transition">
                                    <td class="px-4 py-2 text-center"><?= $i++ ?></td>
                                    <td class="px-4 py-2"><?= date('d/m/Y H:i', strtotime($row['payment_date'])) ?></td>
                                    <td class="px-4 py-2"><?= htmlspecialchars($row['order_code'] ?? '') ?></td>
                                    <td class="px-4 py-2"><?= htmlspecialchars($row['service_name'] ?? '') ?></td>
                                    <td class="px-4 py-2 text-right font-medium"><?= number_format($row['amount'], 2) ?> <span class="text-xs text-zinc-400">บาท</span></td>
                                    <td class="px-4 py-2 text-center">
                                        <?php
                                        $status = $row['payment_status'];
                                        if ($status === 'pending') {
                                            echo '<span class="inline-flex gap-1 items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800 border border-yellow-300">
                                                    รอตรวจสอบ
                                                </span>';
                                        } elseif ($status === 'paid') {
                                            echo '<span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800 border border-green-300">
                                                    อนุมัติ
                                                </span>';
                                        } elseif ($status === 'cancelled') {
                                            echo '<span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800 border border-red-300">
                                                    ถูกปฏิเสธ
                                                </span>';
                                        } else {
                                            echo htmlspecialchars($status ?? '');
                                        }
                                        ?>
                                    </td>
                                    <td class="px-4 py-2"><?= htmlspecialchars($row['payment_method'] ?? '') ?></td>
                                    <td class="px-4 py-2 text-center">
                                        <?php
                                        $slipImage = '';
                                        if (!empty($row['slip_file'])) {
                                            $slipImage = (strpos($row['slip_file'], '/graphic-design/') === 0)
                                                ? $row['slip_file']
                                                : '/graphic-design' . $row['slip_file'];
                                        }
                                        ?>
                                        <?php if (!empty($slipImage)): ?>
                                            <a href="<?= htmlspecialchars($slipImage) ?>" target="_blank" class="inline-flex items-center gap-1 text-blue-700 hover:underline">
                                                ดูสลิป
                                            </a>
                                        <?php else: ?>
                                            <span class="text-zinc-400">-</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="px-4 py-2"><?= htmlspecialchars($row['remark'] ?? '') ?></td>
                                </tr>
                                <?php endwhile; ?>
                                <tr class="bg-gray-50">
                                    <td colspan="3"></td>
                                    <td class="px-4 py-2 font-bold">ยอดเงินทั้งหมด</td>
                                    <td class="px-4 py-2 text-right font-bold"><?= number_format($totalAll, 2) ?>  <span class="text-xs font-medium text-zinc-400">บาท</span></td>
                                    <td colspan="4"></td>
                                </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        const ctx = document.getElementById('paymentBarChart').getContext('2d');
        const paymentBarChart = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: <?= json_encode($chartLabels) ?>,
                datasets: [{
                    label: 'ยอดชำระเงิน (บาท)',
                    data: <?= json_encode($chartData) ?>,
                    backgroundColor: '#09090b',
                    borderColor: '#09090b',
                    borderWidth: 1,
                    borderRadius: 10,
                    maxBarThickness: 40
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        display: false
                    }
                },
                scales: {
                    x: {
                        title: {
                            display: true,
                            text: 'เดือน'
                        }
                    },
                    y: {
                        title: {
                            display: true,
                            text: 'บาท'
                        },
                        beginAtZero: true
                    }
                }
            }
        });

        const ctxPie = document.getElementById('paymentPieChart').getContext('2d');
        const paymentPieChart = new Chart(ctxPie, {
            type: 'pie',
            data: {
                labels: <?= json_encode($statusPieLabels) ?>,
                datasets: [{
                    data: <?= json_encode($statusPieData) ?>,
                    backgroundColor: [
                        '#09090b', // เขียว
                        '#52525b', // เหลือง
                        '#d4d4d8', // แดง
                        '#f4f4f5' // เทา
                    ],
                    borderWidth: 1,
                    borderRadius: 4
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        position: 'bottom'
                    }
                }
            }
        });
    </script>
</body>

</html>