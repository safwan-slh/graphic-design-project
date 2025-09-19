<?php
require __DIR__ . '/../includes/db_connect.php';

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
    'poster-design' => 'poster_details.php',
    'logo-design'   => 'logo_details.php',
    'banner-design' => 'banner_details.php',
    // เพิ่มบริการอื่น ๆ ตาม slug และไฟล์ฟอร์มที่ต้องการ
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
    <?php include __DIR__ . '/../includes/navbar.php'; ?>

    <div class="container mx-auto px-4 py-8 md:py-12 pt-10">
        <!-- Breadcrumb -->
        <nav class="mb-6 md:mb-8 text-sm text-gray-500">
            <ol class="list-none p-0 inline-flex">
                <li class="flex items-center">
                    <a href="/graphic-design/src/client/index.php" class="hover:text-zinc-800 transition-colors">หน้าหลัก</a>
                    <i class="fas fa-chevron-right mx-2 text-xs"></i>
                </li>
                <li class="flex items-center">
                    <a href="/graphic-design/src/client/services.php" class="hover:text-zinc-800 transition-colors">บริการทั้งหมด</a>
                    <i class="fas fa-chevron-right mx-2 text-xs"></i>
                </li>
                <li class="text-zinc-900"><?= htmlspecialchars($service['service_name']) ?></li>
            </ol>
        </nav>

        <div class="form-card mx-8 mt-8 rounded-xl bg-white shadow-sm ring-1 ring-gray-200">

            <!-- Header -->
            <div class="mb-4 flex items-center border-b border-gray-200 px-8 py-6">
                <div class="mr-4 rounded-xl bg-zinc-900 p-3">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="h-6 w-6 text-white">
                        <path fill-rule="evenodd"
                            d="M5.625 1.5c-1.036 0-1.875.84-1.875 1.875v17.25c0 1.035.84 1.875 1.875 1.875h12.75c1.035 0 1.875-.84 1.875-1.875V12.75A3.75 3.75 0 0 0 16.5 9h-1.875a1.875 1.875 0 0 1-1.875-1.875V5.25A3.75 3.75 0 0 0 9 1.5H5.625ZM7.5 15a.75.75 0 0 1 .75-.75h7.5a.75.75 0 0 1 0 1.5h-7.5A.75.75 0 0 1 7.5 15Zm.75 2.25a.75.75 0 0 0 0 1.5H12a.75.75 0 0 0 0-1.5H8.25Z"
                            clip-rule="evenodd" />
                        <path
                            d="M12.971 1.816A5.23 5.23 0 0 1 14.25 5.25v1.875c0 .207.168.375.375.375H16.5a5.23 5.23 0 0 1 3.434 1.279 9.768 9.768 0 0 0-6.963-6.963Z" />
                    </svg>
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
                <div class="bg-zinc-100 rounded-xl p-6 text-center">
                    <h3 class="text-xl font-semibold text-zinc-800 mb-2">พร้อมเริ่มโครงการออกแบบของคุณแล้วหรือยัง?</h3>
                    <p class="text-gray-600 mb-4">สั่งซื้อตอนนี้และรับงานออกแบบคุณภาพสูงจากมืออาชีพ</p>
                    <div class="mb-6">
                        <span class="text-gray-500">ราคาเริ่มต้น:</span>
                        <span class="text-1xl font-bold text-zinc-950"><?= number_format($service['base_price'], 2) ?></span>
                        <span class="text-gray-500">/ <?= htmlspecialchars($service['price_unit']) ?></span>
                    </div>
                    <?php if ($form_available): ?>
                        <a href="<?= $form_page . '?service_id=' . urlencode($service['service_id']) ?>"
                            class="text-white bg-zinc-900 hover:bg-zinc-800 font-medium rounded-full text-sm px-4 py-3 text-center transition-all duration-300 ease-in-out hover:scale-105">
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