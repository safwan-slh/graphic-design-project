<?php
require __DIR__ . '/../includes/db_connect.php';

// Section 1: ดึงข้อมูลบริการที่แนะนำ (is_featured = 1)
$featured_sql = "SELECT * FROM services 
                 WHERE is_featured = 1 AND is_active = 1 
                 ORDER BY created_at DESC ";
$featured_result = $conn->query($featured_sql);

// Section 2: ดึงข้อมูลบริการทั้งหมดที่ active (query ใหม่)
$active_sql = "SELECT * FROM services 
               WHERE is_active = 1 AND is_featured = 0
               ORDER BY created_at DESC";
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

<body class="bg-gray-50 font-thai mt-10">
    <!-- Navigation -->
    <?php
    include __DIR__ . '/../includes/navbar.php';
    ?>

    <!-- Hero Section -->
    <div class="px-10 pt-10 mb-5">
		<div class="py-5 text-zinc-900 bg-white rounded-2xl p-2 border border-slate-200">
			<div class="container mx-auto px-4 pt-5 text-center">
				<div class="inline-block bg-yellow-100 text-yellow-800 px-4 py-2 rounded-full text-sm font-medium mb-4">
                        🎯 บริการทั้งหมด
                    </div>
				<h1 class="text-3xl md:text-5xl font-bold mb-4">บริการออกแบบกราฟิก</h1>
				<p class="text-lg text-slate-600 mb-8">
					เราพร้อมสร้างสรรค์งานออกแบบที่ช่วยให้ธุรกิจของคุณโดดเด่น
				</p>
			</div>
		</div>
	</div>

    <!-- Section 1: บริการแนะนำ -->
    <div class="container mx-auto px-4 py-10">
        <div class="mb-16">
            <div class="flex items-center justify-between mb-8">
                <h2 class="text-2xl font-bold text-gray-800">บริการแนะนำ</h2>
                <div class="h-px flex-1 bg-gray-300 ml-4"></div>
            </div>

            <div class="grid md:grid-cols-3 gap-8">
                <?php while ($service = $featured_result->fetch_assoc()): ?>
                    <div class="relative bg-white rounded-2xl p-2 border border-slate-200 hover:shadow-sm transition-all duration-300 ease-in-out hover:scale-105">
                        <!-- Badge บริการแนะนำ มุมบนซ้าย -->
                        <?php if ($service['is_featured']): ?>
                            <span class="absolute -top-2 left-1 z-10 bg-gradient-to-r from-yellow-400 to-yellow-600 text-white text-xs font-medium px-2.5 py-1 rounded-full shadow-sm">
                                <i class="fas fa-star mr-1"></i> บริการแนะนำ
                            </span>
                        <?php endif; ?>
                        <div class="bg-zinc-100 p-4 rounded-xl" style="height:164px;">
                            <h3 class="text-xl font-semibold text-acme-dark mb-3"><?= htmlspecialchars($service['service_name']) ?></h3>
                            <p class="text-acme-gray leading-relaxed mb-6">
                                <?= nl2br(htmlspecialchars($service['short_description'])) ?>
                            </p>
                        </div>
                        <div class="flex items-center justify-between p-4">
                            <div>
                                <span class="text-1xl font-bold text-acme-dark">฿<?= number_format($service['base_price'], 2) ?></span><span class="text-sm text-acme-gray"> /<?= htmlspecialchars($service['price_unit']) ?></span>
                            </div>
                            <a href="service_detail.php?slug=<?= urlencode($service['slug']) ?>" class="text-white bg-zinc-900 hover:bg-zinc-800 font-medium rounded-full text-sm px-4 py-2 text-center">
                                สั่งออกแบบ
                                <i class="fas fa-arrow-right text-xs"></i>
                            </a>
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
                    <div class="relative bg-white rounded-2xl p-2 border border-slate-200 hover:shadow-sm transition-all duration-300 ease-in-out hover:scale-105">
                        <div class="bg-zinc-100 p-4 rounded-xl" style="height:164px;">
                            <h3 class="text-xl font-semibold text-acme-dark mb-3"><?= htmlspecialchars($service['service_name']) ?></h3>
                            <p class="text-acme-gray leading-relaxed mb-6">
                                <?= nl2br(htmlspecialchars($service['short_description'])) ?>
                            </p>
                        </div>
                        <div class="flex items-center justify-between p-4">
                            <div>
                                <span class="text-1xl font-bold text-acme-dark">฿<?= number_format($service['base_price'], 2) ?></span><span class="text-sm text-acme-gray"> /<?= htmlspecialchars($service['price_unit']) ?></span>
                            </div>
                            <a href="service_detail.php?slug=<?= urlencode($service['slug']) ?>" class="text-white bg-zinc-900 hover:bg-zinc-800 font-medium rounded-full text-sm px-4 py-2 text-center">
                                สั่งออกแบบ
                                <i class="fas fa-arrow-right text-xs"></i>
                            </a>
                        </div>
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