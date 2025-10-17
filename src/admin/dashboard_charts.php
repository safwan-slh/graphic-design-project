<?php
// filepath: /Applications/MAMP/htdocs/graphic-design/src/admin/dashboard_charts.php
require_once '../includes/db_connect.php';
require_once '../auth/auth.php';
requireRole('admin');

$year = isset($_GET['year']) ? (int)$_GET['year'] : date('Y');
$month = isset($_GET['month']) ? (int)$_GET['month'] : date('n');

// 1. รายได้รวมแต่ละวัน (Income Trend)
$days_in_month = cal_days_in_month(CAL_GREGORIAN, $month, $year);
$income_by_day = [];
$day_labels = [];
for ($d = 1; $d <= $days_in_month; $d++) {
    $sql = "SELECT SUM(amount) FROM payments WHERE payment_status = 'paid' AND DAY(created_at) = $d AND MONTH(created_at) = $month AND YEAR(created_at) = $year";
    $income_by_day[] = (float)($conn->query($sql)->fetch_row()[0] ?? 0);
    $day_labels[] = str_pad($d, 2, '0', STR_PAD_LEFT);
}

// 2. สัดส่วนวิธีชำระเงิน (Payment Method Distribution)
$methods = [];
$method_counts = [];
$res = $conn->query("SELECT payment_method, COUNT(*) as cnt FROM payments WHERE YEAR(created_at) = $year AND MONTH(created_at) = $month GROUP BY payment_method");
while ($row = $res->fetch_assoc()) {
    $methods[] = $row['payment_method'] ?: 'ไม่ระบุ';
    $method_counts[] = (int)$row['cnt'];
}

// 3. จำนวนการชำระเงินแต่ละสถานะ (pending, paid, failed)
$statuses = ['pending' => 'รออนุมัติ', 'paid' => 'สำเร็จ', 'failed' => 'ล้มเหลว'];
$status_counts = [];
foreach ($statuses as $status => $label) {
    $sql = "SELECT COUNT(*) FROM payments WHERE payment_status = '$status' AND YEAR(created_at) = $year";
    $status_counts[] = (int)($conn->query($sql)->fetch_row()[0] ?? 0);
}
?>
<div class="bg-white rounded-2xl p-4 w-full max-h-auto relative ring-1 ring-gray-200 mb-4">
    <div class="flex items-center justify-between align-top mb-4">
        <h3 class="font-bold text-zinc-700 text-xl tracking-tight">รายได้รวมแต่ละวัน</h3>
        <form method="get" class="flex gap-2 items-center flex-wrap">
            <select name="month" id="month" class="border transition font-medium rounded-xl text-sm px-5 py-0.5 text-center flex items-center justify-center bg-white text-gray-600 border-gray-300 hover:bg-gray-100">
                <?php for ($m = 1; $m <= 12; $m++): ?>
                    <option value="<?= $m ?>" <?= ($month == $m) ? 'selected' : '' ?>>
                        <?= date('F', mktime(0, 0, 0, $m, 1)) ?>
                    </option>
                <?php endfor; ?>
            </select>
            <select name="year" id="year" class="border transition font-medium rounded-xl text-sm px-5 py-0.5 text-center flex items-center justify-center bg-white text-gray-600 border-gray-300 hover:bg-gray-100">
                <?php for ($y = date('Y'); $y >= date('Y') - 5; $y--): ?>
                    <option value="<?= $y ?>" <?= ($year == $y) ? 'selected' : '' ?>><?= $y ?></option>
                <?php endfor; ?>
            </select>
            <button type="submit" class="border transition font-medium rounded-xl text-sm px-5 py-1.5 text-center flex items-center justify-center bg-zinc-900 text-white hover:bg-zinc-800">ดูข้อมูล</button>
        </form>
    </div>
    <div class="p-4 bg-gray-50 rounded-xl max-h-auto ring-1 ring-gray-200">
        <canvas id="incomeTrendChart" height="80" class="!max-w-full"></canvas>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    const days = <?= json_encode($day_labels) ?>;

    new Chart(document.getElementById('incomeTrendChart'), {
        type: 'line',
        data: {
            labels: days,
            datasets: [{
                label: 'รายได้ (บาท)',
                data: <?= json_encode($income_by_day) ?>,
                fill: true,
                borderColor: '#71717a',
                backgroundColor: ctx => {
                    const gradient = ctx.chart.ctx.createLinearGradient(0, 0, 0, 200);
                    gradient.addColorStop(0, 'rgba(24, 24, 27, 0.5)');
                    gradient.addColorStop(1, 'rgba(24, 24, 27, 0)');
                    return gradient;
                },
                tension: 0.4,
                pointRadius: 5,
                pointBackgroundColor: '#71717a',
                pointBorderWidth: 2,
                pointBorderColor: '#fff'
            }]
        },
        options: {
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
                        label: ctx => ` ${ctx.parsed.y.toLocaleString()} บาท`
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