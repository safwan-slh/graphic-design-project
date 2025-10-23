<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
require __DIR__ . '/../includes/db_connect.php';
require_once '../auth/auth.php';
requireLogin();

// รับ slug หรือ service_id จาก URL
$service = null;
if (isset($_GET['slug'])) {
    $slug = $_GET['slug'];
    $stmt = $conn->prepare("SELECT * FROM services WHERE slug = ? AND is_active = 1 LIMIT 1");
    $stmt->bind_param("s", $slug);
    $stmt->execute();
    $result = $stmt->get_result();
    $service = $result->fetch_assoc();
} elseif (isset($_GET['service_id'])) {
    $service_id = $_GET['service_id'];
    $stmt = $conn->prepare("SELECT * FROM services WHERE service_id = ? AND is_active = 1 LIMIT 1");
    $stmt->bind_param("i", $service_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $service = $result->fetch_assoc();
}

if (!$service) {
    // ไม่พบข้อมูลบริการ
    http_response_code(404);
    echo "<h1>ไม่พบข้อมูลบริการ</h1>";
    exit;
}

// กำหนด mapping slug => ไฟล์ฟอร์ม
$form_pages = [
    'poster-design' => 'poster_form.php',
    'logo-design'   => 'logo_form.php',
    'banner-design' => 'banner_form.php',
    // เพิ่มบริการอื่น ๆ ตาม slug และไฟล์ฟอร์มที่ต้องการ
];

// Mapping slug หรือ service_name กับ SVG หรือ class ไอคอน
$serviceIcons = [
    'poster-design' => '<i class="fas fa-image"></i>',
    'logo-design' => '<i class="fas fa-pen-nib"></i>',
    'banner-design' => '<i class="fas fa-flag"></i>',
    // หรือจะใช้ SVG code ตรงนี้ก็ได้
];

// เลือกไฟล์ฟอร์มตาม slug (หรือจะใช้ service_id ก็ได้)
$form_page = isset($form_pages[$service['slug']]) ? $form_pages[$service['slug']] : '';

// ตรวจสอบว่าไฟล์ฟอร์มมีอยู่จริงหรือไม่
$form_available = $form_page && file_exists(__DIR__ . '/' . $form_page);
?>

<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title><?= htmlspecialchars($service['service_name']) ?> | Graphic-Design</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>

<body class="bg-gray-50 mt-10">
    <div class="">
        <div class="items-center bg-white rounded-2xl ring-1 ring-gray-200">
            <!-- Header -->
            <div class="mb-4 flex items-center border-b border-gray-200 p-4">
                <?php $icon = $serviceIcons[$service['slug']] ?? '<i class="fas fa-paint-brush"></i>'; ?>
                <div class="mr-4 rounded-xl bg-zinc-900 p-3 text-white text-2xl flex items-center justify-center">
                    <?= $icon ?>
                </div>
                <div class="">
                    <h1 class="flex items-center text-2xl font-bold text-zinc-900">
                        <?= htmlspecialchars($service['service_name']) ?>
                    </h1>
                    <p class="text-gray-600">
                        บริการออกแบบกราฟิกมืออาชีพสำหรับทุกความต้องการ
                    </p>
                </div>
            </div>

            <div class="p-6 md:p-8">
                <!-- Call to Action Bottom -->
                <div class="bg-gray-50 rounded-xl p-6 text-center ring-1 ring-gray-200">
                    <h3 class="text-xl font-semibold text-zinc-800 mb-2">พร้อมเริ่มโครงการออกแบบของคุณแล้วหรือยัง?</h3>
                    <p class="text-gray-600 mb-4">สั่งซื้อตอนนี้และรับงานออกแบบคุณภาพสูงจากมืออาชีพ</p>
                    <div class="mb-6">
                        <span class="text-gray-500">ราคาเริ่มต้น:</span>
                        <span class="text-1xl font-bold text-zinc-950"><?= number_format($service['base_price'], 2) ?></span>
                        <span class="text-gray-500">/ <?= htmlspecialchars($service['price_unit']) ?></span>
                    </div>
                    <?php if ($form_available): ?>
                        <a href="<?= $form_page . '?service_id=' . urlencode($service['service_id']) ?>"
                            class="border font-medium rounded-xl text-sm px-5 py-2 text-center flex items-center justify-center bg-zinc-900 hover:bg-zinc-700 text-white border-zinc-900">
                            <i class="fas fa-shopping-cart mr-2"></i>
                            เริ่มสั่งออกแบบเลย
                        </a>
                    <?php else: ?>
                        <div class="text-red-600 font-semibold text-sm py-3 bg-red-100 p-2 rounded-lg">
                            <i class="fas fa-exclamation-triangle mr-2"></i>
                            ขออภัยบริการนี้ยังไม่พร้อมใช้งาน
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
</body>

</html>