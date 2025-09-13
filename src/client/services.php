<?php
require __DIR__ . '/../includes/db_connect.php';

// Section 1: ดึงข้อมูลบริการที่แนะนำ (is_featured = 1)
$featured_sql = "SELECT * FROM services 
                 WHERE is_featured = 1 AND is_active = 1 
                 ORDER BY created_at DESC LIMIT 3";
$featured_result = $conn->query($featured_sql);

// Section 2: ดึงข้อมูลบริการทั้งหมดที่ active
$active_sql = "SELECT * FROM services 
               WHERE is_active = 1 
               ORDER BY is_featured DESC, created_at DESC";
$active_result = $conn->query($active_sql);


// Section 3: ข้อมูลจุดเด่นของเว็บ (Hardcoded)
$advantages = [
    "ทีมงานมืออาชีพมากประสบการณ์",
    "งานออกแบบเฉพาะบุคคล 100%",
    "ราคาคุ้มค่า พร้อมบริการหลังการขาย",
    "ส่งงานตรงเวลา ไม่ผิดนัด",
    "รับประกันคุณภาพงาน"
];
?>

<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>บริการของเรา | Graphic-Design</title>
    <link href="../../dist/output.css" rel="stylesheet" />
</head>

<body class="bg-gray-50">
    <!-- Navigation -->
    <?php
    include __DIR__ . '/../includes/navbar.php';
    ?>

    <!-- Hero Section -->
    <div class="bg-gradient-to-r from-zinc-500 via-stone-600 to-zinc-900 py-20 text-white">
        <div class="container mx-auto px-4 pt-5 text-center">
            <h1 class="text-3xl md:text-5xl font-bold mb-4">บริการออกแบบกราฟิก</h1>
            <p class="text-xl md:text-2xl max-w-3xl mx-auto">
                เราพร้อมสร้างสรรค์งานออกแบบที่ช่วยให้ธุรกิจของคุณโดดเด่น
            </p>
        </div>
    </div>

    <!-- Section 1: บริการแนะนำ -->
    <div class="container mx-auto px-4 py-16">
        <div class="mb-16">
            <div class="flex items-center justify-between mb-8">
                <h2 class="text-2xl font-bold text-gray-800">บริการแนะนำ</h2>
                <div class="h-px flex-1 bg-gray-300 ml-4"></div>
            </div>

            <div class="grid md:grid-cols-3 gap-8">
                <?php while ($service = $featured_result->fetch_assoc()): ?>
                    <div class="bg-white border border-gray-200 rounded-xl shadow-sm overflow-hidden transform hover:scale-105 transition duration-300 grid grid-rows-[auto_1fr_auto] h-full">
                        <div class="p-6">
                            <span class="bg-gradient-to-r from-yellow-400 to-yellow-600 text-white text-sm font-medium px-2.5 py-0.5 rounded-md mb-3 inline-block shadow-sm">
                                <i class="fas fa-star mr-1"></i> บริการแนะนำ
                            </span>
                            <!-- ชื่อบริการและแท็ก -->
                            <div class="flex justify-between items-start mb-3">
                                <h3 class="text-xl font-bold text-zinc-800">
                                    <?= htmlspecialchars($service['service_name']) ?>
                                </h3>
                            </div>
                            <!-- คำอธิบาย - ใช้ grid row ที่ 2 -->
                            <div class="mb-4 h-full">
                                <p class="text-gray-500 line-clamp-3 h-full">
                                    <?= nl2br(htmlspecialchars($service['short_description'])) ?>
                                </p>
                            </div>
                        </div>
                        <div class="p-6">
                            <p class="text-gray-500 text-sm">เริ่มต้นที่</p>
                            <div class="flex justify-between items-center mt-2">
                                <p class="text-2xl font-bold text-zinc-950">
                                    <?= number_format($service['base_price'], 2) ?>
                                    <span class="text-gray-500 text-sm ml-1">/ <?= htmlspecialchars($service['price_unit']) ?></span>
                                </p>
                                <a href="service_detail.php?slug=<?= urlencode($service['slug']) ?>"
                                    class="text-white bg-zinc-900 hover:bg-zinc-800 font-medium rounded-full text-sm px-4 py-2 text-center">
                                    สั่งออกแบบ
                                </a>
                            </div>
                        </div>
                    </div>
                <?php endwhile; ?>
            </div>
        </div>

        <!-- Section 2: บริการทั้งหมด -->
        <div class="mb-16">
            <div class="flex items-center justify-between mb-8">
                <h2 class="text-2xl font-bold text-gray-800">บริการทั้งหมด</h2>
                <div class="h-px flex-1 bg-gray-300 ml-4"></div>
            </div>
            <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-8">
                <?php while ($service = $active_result->fetch_assoc()): ?>
                    <div class="bg-white group relative border border-gray-200 rounded-xl shadow-sm overflow-hidden hover:shadow-md transition-all duration-300 grid grid-rows-[auto_1fr_auto] h-full">
                        <!-- ส่วนเนื้อหาการ์ด -->
                        <div class="p-6">
                            <!-- ชื่อบริการและแท็ก -->
                            <div class="flex justify-between items-start mb-3">
                                <h3 class="text-xl font-bold text-zinc-800">
                                    <?= htmlspecialchars($service['service_name']) ?>
                                </h3>
                            </div>

                            <!-- คำอธิบาย - ใช้ grid row ที่ 2 -->
                            <div class="mb-4 h-full">
                                <p class="text-gray-500 line-clamp-3 h-full">
                                    <?= nl2br(htmlspecialchars($service['short_description'])) ?>
                                </p>
                            </div>
                        </div>

                        <!-- ส่วนราคาและปุ่ม - ใช้ grid row ที่ 3 -->
                        <div class=" p-6">
                            <p class="text-gray-500 text-sm">เริ่มต้นที่</p>
                            <div class="flex justify-between items-center mt-2">
                                <p class="text-2xl font-bold text-zinc-950">
                                    <?= number_format($service['base_price'], 2) ?>
                                    <span class="text-gray-500 text-sm ml-1">/ <?= htmlspecialchars($service['price_unit']) ?></span>
                                </p>
                                <a href="service_detail.php?service_id=<?= urlencode($service['service_id']) ?>"
                                    class="text-white bg-zinc-900 hover:bg-zinc-800 font-medium rounded-full text-sm px-4 py-2 text-center">
                                    สั่งออกแบบ
                                </a>
                            </div>
                        </div>

                        <!-- Overlay effect เมื่อ hover -->
                        <div class="absolute inset-0 bg-black bg-opacity-0 group-hover:bg-opacity-5 transition-all duration-300"></div>
                    </div>
                <?php endwhile; ?>
            </div>
        </div>

        <!-- Section 3: จุดเด่นของเรา -->
        <div class="bg-white rounded-xl shadow-md p-8">
            <div class="flex items-center justify-between mb-8">
                <h2 class="text-2xl font-bold text-gray-800">ทำไมต้องเลือกเรา?</h2>
                <div class="h-px flex-1 bg-gray-300 ml-4"></div>
            </div>

            <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-6">
                <?php foreach ($advantages as $index => $advantage): ?>
                    <div class="flex items-start">
                        <div class="bg-zinc-100 text-zinc-800 rounded-full p-2 mr-4">
                            <i class="fas fa-check-circle text-lg"></i>
                        </div>
                        <div>
                            <h3 class="font-semibold text-gray-800 mb-1">จุดเด่น #<?= $index + 1 ?></h3>
                            <p class="text-gray-600"><?= htmlspecialchars($advantage) ?></p>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <!-- Floating Action Button -->
    <div class="fixed bottom-8 right-8">
        <button data-tooltip-target="tooltip-left" data-tooltip-placement="left" class="floating-btn bg-zinc-900 text-white w-14 h-14 rounded-full flex items-center justify-center text-xl hover:bg-zinc-800 transition">
            <i class="fas fa-comment-dots"></i>
        </button>
        <!-- Show tooltip on left -->
        <div id="tooltip-left" role="tooltip" class="absolute z-10 invisible inline-block px-3 py-2 text-sm font-medium text-white bg-zinc-900 rounded-lg shadow-xs opacity-0 tooltip">
            ติดต่อสอบถาม
            <div class="tooltip-arrow" data-popper-arrow></div>
        </div>
    </div>








    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/js/all.min.js"></script>
</body>

</html>