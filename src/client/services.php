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

// Mapping slug หรือ service_name กับ SVG หรือ class ไอคอน
$serviceIcons = [
    'poster-design' => '<i class="fas fa-image"></i>',
    'logo-design' => '<i class="fas fa-pen-nib"></i>',
    'banner-design' => '<i class="fas fa-flag"></i>',
    // หรือจะใช้ SVG code ตรงนี้ก็ได้
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

<body class="bg-gray-50 font-thai mt-10 absolute inset-0 -z-10 h-full w-full bg-[radial-gradient(#d4d4d8_1px,transparent_1px)] [background-size:16px_16px]">
    <!-- Navigation -->
    <?php
    include __DIR__ . '/../includes/navbar.php';
    ?>

    <!-- Hero Section -->
    <div class="px-10 pt-10 mb-5">
        <div class="py-2 text-zinc-900 bg-white ring-1 ring-gray-200 rounded-3xl p-2 my-10">
            <div class="mx-auto px-10 py-10 text-start space-x-2 flex flex-col">
                <h1 class="text-3xl md:text-5xl font-bold mb-4">บริการของเรา</h1>
                <p class="text-gray-600 max-w-md">
                    ครบทุกบริการออกแบบในที่เดียว
                    งานคุณภาพสูง ดีไซน์สวย ตรงกลุ่มเป้าหมาย
                    พร้อมดูแลคุณทุกขั้นตอนโดยทีมงานมืออาชีพ
                </p>
            </div>
        </div>
    </div>

    <!-- Section 1: บริการแนะนำ -->
    <div class="container mx-auto px-4 mb-10 pb-10">
        <div class="mb-10">
            <div class="flex items-center justify-between mb-8">
                <h2 class="text-xl font-bold text-gray-800 bg-white py-1 ring-1 ring-gray-200 rounded-full px-4">
                    <i class="fas fa-star text-yellow-300"></i>
                    บริการแนะนำ
                </h2>
                <div class="h-px flex-1 bg-gray-300"></div>
                <button class="w-9 h-9 bg-white ring-1 ring-gray-300 rounded-full flex items-center justify-center">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="size-6">
                        <path fill-rule="evenodd" d="M11.47 4.72a.75.75 0 0 1 1.06 0l3.75 3.75a.75.75 0 0 1-1.06 1.06L12 6.31 8.78 9.53a.75.75 0 0 1-1.06-1.06l3.75-3.75Zm-3.75 9.75a.75.75 0 0 1 1.06 0L12 17.69l3.22-3.22a.75.75 0 1 1 1.06 1.06l-3.75 3.75a.75.75 0 0 1-1.06 0l-3.75-3.75a.75.75 0 0 1 0-1.06Z" clip-rule="evenodd" />
                    </svg>
                </button>
            </div>

            <div class="grid md:grid-cols-3 gap-8">
                <?php while ($service = $featured_result->fetch_assoc()): ?>
                    <?php $icon = $serviceIcons[$service['slug']] ?? '<i class="fas fa-paint-brush"></i>'; ?>
                    <div class="relative bg-white rounded-3xl border border-slate-200 hover:shadow-sm transition-all duration-300 ease-in-out hover:scale-105">
                        <div class="flex items-center p-4 pb-0">
                            <div class="mr-4 rounded-xl bg-zinc-900 p-3 text-white text-2xl flex items-center justify-center">
                                <?= $icon ?>
                            </div>
                            <div class="">
                                <h3 class="flex items-center text-xl font-bold text-zinc-900">
                                    <?= htmlspecialchars($service['service_name']) ?>
                                </h3>
                                <!-- Badge บริการแนะนำ มุมบนซ้าย -->
                                <?php if ($service['is_featured']): ?>
                                    <div class=" text-sm font-medium">
                                        บริการแนะนำ
                                    </div>
                                <?php elseif (! $service['is_featured']): ?>
                                    <span class="text-sm font-medium">
                                        บริการทั่วไป
                                    </span>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="p-4">
                            <div class="bg-zinc-50 p-4 rounded-xl ring-1 ring-gray-200" style="height:164px;">
                                <p class="text-acme-gray leading-relaxed mb-6">
                                    <?= nl2br(htmlspecialchars($service['short_description'])) ?>
                                </p>
                            </div>
                            <div class="flex items-center justify-between pt-4">
                                <div>
                                    <span class="text-2xl font-bold text-acme-dark">฿<?= number_format($service['base_price'], 2) ?></span><span class="text-sm text-acme-gray"> /<?= htmlspecialchars($service['price_unit']) ?></span>
                                </div>
                                <a href="service_detail.php?slug=<?= urlencode($service['slug']) ?>" class="border transition font-medium rounded-xl text-sm px-5 py-2 text-center flex items-center justify-center bg-zinc-900 hover:bg-zinc-700 text-white border-zinc-900">
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
                <h2 class="text-xl font-bold text-gray-800 bg-white py-1 ring-1 ring-gray-200 rounded-full px-4">บริการทั้งหมด</h2>
                <div class="h-px flex-1 bg-gray-300"></div>
                <button class="w-9 h-9 bg-white ring-1 ring-gray-300 rounded-full flex items-center justify-center">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="size-6">
                        <path fill-rule="evenodd" d="M11.47 4.72a.75.75 0 0 1 1.06 0l3.75 3.75a.75.75 0 0 1-1.06 1.06L12 6.31 8.78 9.53a.75.75 0 0 1-1.06-1.06l3.75-3.75Zm-3.75 9.75a.75.75 0 0 1 1.06 0L12 17.69l3.22-3.22a.75.75 0 1 1 1.06 1.06l-3.75 3.75a.75.75 0 0 1-1.06 0l-3.75-3.75a.75.75 0 0 1 0-1.06Z" clip-rule="evenodd" />
                    </svg>
                </button>
            </div>
            <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-8">
                <?php while ($service = $active_result->fetch_assoc()): ?>
                    <?php $icon = $serviceIcons[$service['slug']] ?? '<i class="fas fa-paint-brush"></i>'; ?>
                    <div class="relative bg-white rounded-3xl border border-slate-200 hover:shadow-sm transition-all duration-300 ease-in-out hover:scale-105">
                        <div class="flex items-center p-4 pb-0">
                            <div class="mr-4 rounded-xl bg-zinc-900 p-3 text-white text-2xl flex items-center justify-center">
                                <?= $icon ?>
                            </div>
                            <div class="">
                                <h3 class="flex items-center text-xl font-bold text-zinc-900">
                                    <?= htmlspecialchars($service['service_name']) ?>
                                </h3>
                                <!-- Badge บริการแนะนำ มุมบนซ้าย -->
                                <?php if ($service['is_featured']): ?>
                                    <div class=" text-sm font-medium">
                                        บริการแนะนำ
                                    </div>
                                <?php elseif (! $service['is_featured']): ?>
                                    <span class="text-sm font-medium">
                                        บริการทั่วไป
                                    </span>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="p-4">
                            <div class="bg-zinc-50 p-4 rounded-xl ring-1 ring-gray-200" style="height:164px;">
                                <p class="text-acme-gray leading-relaxed mb-6">
                                    <?= nl2br(htmlspecialchars($service['short_description'])) ?>
                                </p>
                            </div>
                            <div class="flex items-center justify-between pt-4">
                                <div>
                                    <span class="text-2xl font-bold text-acme-dark">฿<?= number_format($service['base_price'], 2) ?></span><span class="text-sm text-acme-gray"> /<?= htmlspecialchars($service['price_unit']) ?></span>
                                </div>
                                <a href="service_detail.php?slug=<?= urlencode($service['slug']) ?>" class="border transition font-medium rounded-xl text-sm px-5 py-2 text-center flex items-center justify-center bg-zinc-900 hover:bg-zinc-700 text-white border-zinc-900">
                                    สั่งออกแบบ
                                </a>
                            </div>
                        </div>
                    </div>
                <?php endwhile; ?>
            </div>
        </div>

        <!-- Section 3: จุดเด่นของเรา -->
        <div class="bg-white rounded-3xl p-8 ring-1 ring-gray-200">
            <div class="flex items-center justify-between mb-8">
                <h2 class="text-2xl font-bold text-gray-800">ทำไมต้องเลือกเรา?</h2>
            </div>

            <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-6">
                <?php foreach ($advantages as $index => $advantage): ?>
                    <div class="flex items-start">
                        <div class=" text-zinc-800 rounded-full p-2 mr-4">
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
    <?php
    include __DIR__ . '/../includes/footer.php';
    ?>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/js/all.min.js"></script>
</body>

</html>