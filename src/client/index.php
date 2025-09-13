<?php
require '../includes/db_connect.php';

$active_sql = "SELECT * FROM services 
               WHERE is_active = 1 
               ORDER BY is_featured DESC, created_at DESC LIMIT 3";
$active_result = $conn->query($active_sql);

// ดึงข้อมูลผลงานทั้งหมด
$sql = "SELECT p.*, s.service_name 
        FROM portfolios p 
        LEFT JOIN services s ON p.service_id = s.service_id 
        ORDER BY p.created_at DESC LIMIT 3";

$result = $conn->query($sql); ?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Home</title>
    <link href="../../dist/output.css" rel="stylesheet" />
    <link href="https://fonts.googleapis.com/css2?family=IBM+Plex+Sans+Thai:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/flowbite/1.7.0/flowbite.min.css" rel="stylesheet" />
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/js/all.min.js"></script>
</head>
<style>
    .font-thai {
        font-family: 'IBM Plex Sans Thai', sans-serif;
    }

    .glassmorphism {
        background: rgba(255, 255, 255, 0.1);
        backdrop-filter: blur(20px);
        border: 1px solid rgba(255, 255, 255, 0.2);
    }

    .floating {
        animation: float 6s ease-in-out infinite;
    }

    @keyframes float {
        0% {
            transform: translateY(0px);
        }

        50% {
            transform: translateY(-20px);
        }

        100% {
            transform: translateY(0px);
        }
    }

    .floating-delayed {
        animation: float 6s ease-in-out infinite;
        animation-delay: -2s;
    }

    .animate {
        animation: float 1s ease-in-out infinite;
        /* animation-delay: -2s; */
    }
</style>

<body class="">
    <?php
    include __DIR__ . '/../includes/navbar.php';
    ?>
    <section class="flex items-center justify-center py-16 px-5 hero-gradient font-thai relative overflow-hidden">
        <div class="container mx-auto pt-10">
            <div class="flex flex-row sm:flex-col w-full items-center px-10 py-10 gap-12">
                <!-- Left Content -->
                <div class="w-full lg:w-1/2">
                    <div class="mb-8">
                        <div class="inline-flex items-center px-4 py-2 rounded-full text-sm text-gray-500 font-medium mb-6 bg-gray-100">
                            <span class="w-2 h-2 bg-green-400 rounded-full mr-2"></span>
                            พร้อมให้บริการแล้ววันนี้
                        </div>

                        <h1 class="text-4xl md:text-5xl font-bold text-slate-800 mb-6 leading-tight">
                            ยกระดับธุรกิจคุณด้วย <br><span class="text-yellow-600">การออกแบบกราฟิก</span> <br>ที่สมบูรณ์แบบ
                        </h1>

                        <p class="text-lg text-slate-600 mb-8">
                            เราช่วยให้แบรนด์ของคุณโดดเด่นด้วยผลงานออกแบบที่สวยงาม มีเอกลักษณ์ <br>และตรงกับกลุ่มเป้าหมาย
                            มอบประสบการณ์การทำงานที่ง่ายดายและรวดเร็วด้วยทีมงานมืออาชีพ
                        </p>
                    </div>
                    <!-- Features -->
                    <div class="grid grid-cols-2 gap-4 mb-10 animate-fade-in-delay">
                        <div class="flex items-center space-x-3">
                            <div class="w-5 h-5 bg-green-100 rounded-full flex items-center justify-center">
                                <i class="fas fa-check text-green-600 text-xs"></i>
                            </div>
                            <span class="text-sm text-acme-gray">เร็วและมีประสิทธิภาพ</span>
                        </div>
                        <div class="flex items-center space-x-3">
                            <div class="w-5 h-5 bg-green-100 rounded-full flex items-center justify-center">
                                <i class="fas fa-check text-green-600 text-xs"></i>
                            </div>
                            <span class="text-sm text-acme-gray">ส่งงานเร็วภายใน 3-7 วัน</span>
                        </div>
                        <div class="flex items-center space-x-3">
                            <div class="w-5 h-5 bg-green-100 rounded-full flex items-center justify-center">
                                <i class="fas fa-check text-green-600 text-xs"></i>
                            </div>
                            <span class="text-sm text-acme-gray">ไฟล์ครบทุกฟอร์แมทการใช้งาน</span>
                        </div>
                        <div class="flex items-center space-x-3">
                            <div class="w-5 h-5 bg-green-100 rounded-full flex items-center justify-center">
                                <i class="fas fa-check text-green-600 text-xs"></i>
                            </div>
                            <span class="text-sm text-acme-gray">ทีมงานมืออาชีพประสบการณ์ 3+ ปี</span>
                        </div>
                    </div>

                    <div class="flex gap-4">
                        <a href="services.php" class="text-white bg-zinc-900 hover:bg-zinc-800 font-medium rounded-full text-sm px-5 py-2 text-center transition-all duration-300 ease-in-out hover:scale-105 flex items-center justify-center">
                            🚀 สร้างงานออกแบบ
                        </a>
                        <a href="portfolios.php" class="border border-zinc-300 hover:border-zinc-500 hover:bg-zinc-100 text-slate-700 hover:text-zinc-600 px-5 py-2 rounded-full font-medium transition-all duration-300 ease-in-out hover:scale-105 flex items-center justify-center">
                            ดูผลงานตัวอย่าง
                        </a>
                    </div>
                </div>

                <!-- Right Content -->
                <div class="w-full lg:w-1/2">
                    <div class="relative">
                        <!-- Main Card -->
                        <div class="floating bg-zinc-900 rounded-2xl p-6 shadow-xl border border-slate-200">
                            <div class="flex items-center justify-between mb-6">
                                <div class="flex items-center">
                                    <div class="w-3 h-3 bg-red-400 rounded-full mr-2"></div>
                                    <div class="w-3 h-3 bg-amber-400 rounded-full mr-2"></div>
                                    <div class="w-3 h-3 bg-green-400 rounded-full"></div>
                                </div>
                                <div class="text-white font-medium">Brand Identity Design</div>
                            </div>
                            <div class="bg-gradient-to-r from-blue-500 to-purple-600 h-48 rounded-xl flex items-center justify-center mb-4">
                                <div class="text-white text-center">
                                    <div class="text-3xl font-bold mb-2 text-uppercase">GRAPHIC-DESIGN</div>
                                    <div class="text-sm opacity-80">BRANDING STUDIO</div>
                                </div>
                            </div>
                            <div class="flex justify-between items-center">
                                <div class="flex space-x-2">
                                    <div class="w-6 h-6 bg-blue-200 rounded-full"></div>
                                    <div class="w-6 h-6 bg-purple-200 rounded-full"></div>
                                    <div class="w-6 h-6 bg-pink-200 rounded-full"></div>
                                </div>
                                <div class="text-gray-200 text-sm">กำลังออกแบบ...</div>
                            </div>
                        </div>

                        <!-- Floating Card 1 -->
                        <div class="absolute -top-5 -right-5 floating-delayed glassmorphism text-white p-4 rounded-xl shadow-lg w-40 card-hover">
                            <div class="flex items-center justify-between mb-2">
                                <i class="fas fa-trophy text-yellow-300"></i>
                                <span class="text-xs bg-yellow-300 text-white px-2 py-1 rounded-full">รางวัล</span>
                            </div>
                            <p class="text-sm">การออกแบบยอดเยี่ยมปี 2025</p>
                        </div>

                        <!-- Floating Card 2 -->
                        <div class=" floating-delayed absolute -bottom-5 -left-5 floating-delayed glassmorphism p-4 rounded-xl card-hover">
                            <div class="flex items-center space-x-3">
                                <div class="w-12 h-12 bg-gradient-to-br from-green-400 to-blue-500 rounded-full flex items-center justify-center">
                                    <i class="fas fa-check text-white"></i>
                                </div>
                                <div>
                                    <p class="text-white font-medium text-sm w-full">งานเสร็จทันเวลา</p>
                                    <p class="text-gray-200 text-xs">95% ของลูกค้า</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="absolute animate bottom-0 left-0 w-full flex justify-center pb-4">
            <a href="#services" class="animate-bounce w-10 h-10 bg-gray-100 rounded-full flex items-center justify-center">
                <i class="fas fa-chevron-down text-gray-500"></i>
            </a>
        </div>
    </section>
    </section>
    <!-- services section -->
    <section class="py-5 bg-zinc-100 font-thai" id="services">
        <div class="container mx-auto px-6 my-10">
            <div class="max-w-7xl mx-auto">

                <!-- Header -->
                <div class="text-center mb-16">
                    <div class="inline-block bg-blue-100 text-blue-800 px-4 py-2 rounded-full text-sm font-medium mb-4">
                        🎯 บริการครบวงจร
                    </div>
                    <h2 class="text-4xl font-bold text-acme-dark mb-4">
                        เราทำได้มากกว่าที่คุณคิด
                    </h2>
                    <p class="text-xl text-gray-600 max-w-3xl mx-auto">
                        จากแค่ไอเดีย เราจะช่วยให้กลายเป็นแบรนด์ที่สมบูรณ์แบบ พร้อมสร้างความประทับใจให้ลูกค้าของคุณ
                    </p>
                </div>

                <!-- Services Grid -->
                <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-8">
                    <!-- Logo Design -->
                    <?php while ($service = $active_result->fetch_assoc()): ?>
                        <div class="bg-white rounded-2xl p-8 border border-slate-200 hover:shadow-sm transition-all duration-300 ease-in-out hover:scale-105 ">
                            <span class="bg-gradient-to-r from-yellow-400 to-yellow-600 text-white text-sm font-medium px-2.5 py-0.5 rounded-md mb-3 inline-block shadow-sm">
                                <i class="fas fa-star mr-1"></i> บริการแนะนำ
                            </span>
                            <h3 class="text-xl font-semibold text-acme-dark mb-3"><?= htmlspecialchars($service['service_name']) ?></h3>
                            <p class="text-acme-gray leading-relaxed mb-6">
                                <?= nl2br(htmlspecialchars($service['short_description'])) ?>
                            </p>
                            <div class="flex items-center justify-between">
                                <div>
                                    <span class="text-2xl font-bold text-acme-dark">฿<?= number_format($service['base_price'], 2) ?></span>
                                    <div class="text-sm text-acme-gray"><?= htmlspecialchars($service['price_unit']) ?></div>
                                </div>
                                <a href="service.php?id=<?= $service['slug'] ?>" class="w-10 h-10 bg-gray-100 rounded-full flex items-center justify-center transition-all duration-300 ease-in-out hover:scale-105 hover:bg-zinc-800 hover:text-white">
                                    <i class="fas fa-arrow-right text-xs"></i>
                                </a>
                            </div>
                        </div>
                    <?php endwhile; ?>
                </div>
                <div class="text-center mt-10">
                    <a href="services.php" class="text-white bg-zinc-900 hover:bg-zinc-800 font-medium rounded-full text-sm px-5 py-2 text-center transition-all duration-300 ease-in-out hover:scale-105">
                        ดูบริการทั้งหมด <i class="fas fa-arrow-right ml-2"></i>
                    </a>
                </div>
            </div>
        </div>
    </section>
    <section class="py-5 font-thai">
        <div class="container mx-auto px-6 my-10">
            <div class="max-w-7xl mx-auto">
                <div class="text-center mb-16">
                    <div class="inline-block bg-yellow-100 text-yellow-800 px-4 py-2 rounded-full text-sm font-medium mb-4">
                        🎯 ผลงานโดดเด่น
                    </div>
                    <h2 class="text-4xl font-bold text-gray-800 mb-4">ผลงานการออกแบบของเรา</h2>
                    <p class="text-xl text-gray-600 max-w-3xl mx-auto">สำรวจผลงานการออกแบบกราฟิกที่เราได้สร้างให้กับลูกค้าทั้งในและต่างประเทศ ด้วยความคิดสร้างสรรค์และความเชี่ยวชาญ</p>
                </div>
                <!-- Portfolio Grid -->
                <div class="grid grid-cols-3 gap-6">
                    <?php if (
                        $result->num_rows > 0
                    ): ?>
                        <?php while ($portfolio = $result->fetch_assoc()):
                            $tags = json_decode($portfolio['tags'], true); // ตรวจสอบว่าภาพมีอยู่จริง
                            $imagePath = __DIR__ . '/../../' . $portfolio['image_url'];
                            $imageExists =
                                file_exists($imagePath); ?>
                            <div
                                class="portfolio-item bg-white rounded-xl shadow-sm overflow-hidden border border-gray-100 relative">
                                <div class="relative">
                                    <div class="w-full h-64 <?= $imageExists ? '' : '' ?>">
                                        <?php if ($imageExists): ?>
                                            <img src="/graphic-design/<?= htmlspecialchars($portfolio['image_url']) ?>"
                                                alt="<?= htmlspecialchars($portfolio['title']) ?>"
                                                class="w-full h-full object-cover" />
                                        <?php else: ?>
                                            <div
                                                class="w-full h-full flex items-center justify-center text-white font-bold text-2xl">
                                                <?= htmlspecialchars(substr($portfolio['title'], 0, 1)) ?>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                    <!-- Badge สถานะ -->
                                    <div class="absolute top-2 flex space-x-2">
                                        <?php if ($portfolio['is_featured']): ?>
                                            <span
                                                class="px-2 py-1 glassmorphism text-white text-xs font-bold rounded-full ml-2">
                                                <i class="fas fa-star mr-1 text-yellow-300"></i>
                                                แนะนำ
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                    <!-- แท็ก -->
                                    <?php
                                    // ทำความสะอาดแท็ก: trim และตัดค่าว่างออก
                                    $cleanTags = [];
                                    if (is_array($tags)) {
                                        $cleanTags = array_values(array_filter(array_map('trim', $tags), function ($v) {
                                            return $v !== '';
                                        }));
                                    }
                                    ?>
                                    <?php if (!empty($cleanTags)): ?>
                                        <div class="absolute bottom-2 left-2 flex items-center justify-between">
                                            <div class="flex flex-wrap gap-1">
                                                <?php foreach (array_slice($cleanTags, 0, 4) as $tag): ?>
                                                    <span class=" glassmorphism text-white text-xs px-3 py-1 rounded-full">
                                                        <?= htmlspecialchars($tag) ?>
                                                    </span>
                                                <?php endforeach; ?>
                                                <?php if (count($cleanTags) > 4): ?>
                                                    <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium glassmorphism text-white">
                                                        +
                                                        <?= count($cleanTags) - 4 ?>
                                                    </span>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    <?php endif; ?>
                                </div>

                                <div class="flex items-end p-6">
                                    <div class="">
                                        <h3 class="font-semibold text-lg text-gray-800 mb-2"><?= htmlspecialchars($portfolio['title']) ?></h3>
                                        <p class="text-gray-500 mb-4 text-md"><?= htmlspecialchars($portfolio['description']) ?></p>
                                        <p class="text-sm inline-flex items-center">
                                            <span class="w-2 h-2 bg-green-400 rounded-full mr-2"></span>
                                            บริการ: <?= htmlspecialchars($portfolio['service_name']) ?>
                                        </p>
                                    </div>
                                </div>
                                <!-- วันที่สร้าง
                                    <div class="flex items-center text-sm text-gray-500 mb-4">
                                        <i class="far fa-clock mr-1"></i>
                                        <?= date('d/m/Y', strtotime($portfolio['created_at'])) ?>
                                    </div> -->
                            </div>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <div class="col-span-full text-center py-12">
                            <div class="text-gray-400 mb-4">
                                <i class="fas fa-image fa-3x"></i>
                            </div>
                            <h3 class="text-lg font-medium text-gray-600 mb-2">ยังไม่มีผลงาน</h3>
                        </div>
                    <?php endif; ?>
                </div>

                <div class="text-center mt-10">
                    <a href="services.php" class="text-white bg-zinc-900 hover:bg-zinc-800 font-medium rounded-full text-sm px-5 py-2 text-center transition-all duration-300 ease-in-out hover:scale-105">
                        ดูผลงานทั้งหมด <i class="fas fa-arrow-right ml-2"></i>
                    </a>
                </div>
            </div>
        </div>
    </section>
    <section class="py-5 bg-zinc-50 font-thai">
        <div class="container mx-auto px-6 my-10">
            <div class="max-w-4xl mx-auto">

                <!-- Header -->
                <div class="text-center mb-16">
                    <h2 class="text-4xl font-bold text-acme-dark mb-4">
                        คำถามที่พบบ่อย
                    </h2>
                    <p class="text-xl text-gray-600 max-w-3xl mx-auto">
                        เรารวบรวมคำถามที่ลูกค้ามักสอบถามเราเป็นประจำ
                    </p>
                </div>
                <div class="grid grid-row">
                    <div class="bg-white border border-gray-200 rounded-xl shadow-sm flex p-4 gap-6 mb-6">
                        <div class="flex items-top">
                            <div class="w-10 h-10 bg-zinc-900 rounded-lg flex items-center justify-center">
                                <span class="text-white font-bold text-md"><i class="fa-solid fa-hourglass-start"></i></span>
                            </div>
                        </div>
                        <div class="">
                            <p class="text-md font-bold">ใช้เวลาทำนานไหม?</p>
                            <p class="text-gray-600">ระยะเวลาในการทำงานขึ้นอยู่กับประเภทและความซับซ้อนของงาน:</p>
                            <ul class="mt-3 text-gray-600 space-y-2">
                                <li class="flex items-start">
                                    <i class="fas fa-clock text-zinc-500 mt-1 mr-2"></i>
                                    <span>ออกแบบโลโก้: 5-7 วันทำการ</span>
                                </li>
                                <li class="flex items-start">
                                    <i class="fas fa-clock text-zinc-500 mt-1 mr-2"></i>
                                    <span>แบรนด์ไอเดนติที: 10-14 วันทำการ</span>
                                </li>
                                <li class="flex items-start">
                                    <i class="fas fa-clock text-zinc-500 mt-1 mr-2"></i>
                                    <span>ออกแบบบรรจุภัณฑ์: 7-10 วันทำการ</span>
                                </li>
                                <li class="flex items-start">
                                    <i class="fas fa-clock text-zinc-500 mt-1 mr-2"></i>
                                    <span>ออกแบบเว็บไซต์: 14-21 วันทำการ</span>
                                </li>
                            </ul>
                            <p class="mt-3 text-gray-600">ระยะเวลาอาจเปลี่ยนแปลงขึ้นอยู่กับความซับซ้อนของโครงการและจำนวนรอบการแก้ไข</p>
                        </div>
                    </div>
                    <div class="bg-white border border-gray-200 rounded-xl shadow-sm flex p-4 gap-6 mb-6">
                        <div class="flex items-top">
                            <div class="w-10 h-10 bg-zinc-900 rounded-lg flex items-center justify-center">
                                <span class="text-white font-bold text-md"><i class="fa-solid fa-file"></i></span>
                            </div>
                        </div>
                        <div class="">
                            <p class="text-md font-bold">ไฟล์ที่ได้รับมีรูปแบบอะไรบ้าง?</p>
                            <p class="text-gray-600">คุณจะได้รับไฟล์ครบถ้วนตามความต้องการในการใช้งาน:</p>
                            <div class="mt-4">
                                <div class="flex items-start mb-4">
                                    <div class="w-10 h-10 bg-zinc-100 rounded-lg flex items-center justify-center text-zinc-600 mr-4">
                                        <i class="fas fa-file-image"></i>
                                    </div>
                                    <div>
                                        <h4 class="font-medium text-gray-800">ไฟล์ภาพมาตรฐาน</h4>
                                        <p class="text-gray-600">PNG, JPG (ความละเอียดสูง) สำหรับใช้งานบนเว็บไซต์และโซเชียลมีเดีย</p>
                                    </div>
                                </div>
                                <div class="flex items-start mb-4">
                                    <div class="w-10 h-10 bg-zinc-100 rounded-lg flex items-center justify-center text-zinc-600 mr-4">
                                        <i class="fas fa-file-pdf"></i>
                                    </div>
                                    <div>
                                        <h4 class="font-medium text-gray-800">ไฟล์ PDF</h4>
                                        <p class="text-gray-600">สำหรับการพิมพ์และนำเสนอ</p>
                                    </div>
                                </div>
                                <div class="flex items-start">
                                    <div class="w-10 h-10 bg-zinc-100 rounded-lg flex items-center justify-center text-zinc-600 mr-4">
                                        <i class="fas fa-file-code"></i>
                                    </div>
                                    <div>
                                        <h4 class="font-medium text-gray-800">ไฟล์ Vector</h4>
                                        <p class="text-gray-600">AI, EPS, SVG (สำหรับแพ็คเกจมาตรฐานและพรีเมียม) สำหรับการแก้ไขและพิมพ์ขนาดใหญ่</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="bg-white border border-gray-200 rounded-xl shadow-sm flex p-4 gap-6 mb-6">
                        <div class="flex items-top">
                            <div class="w-10 h-10 bg-zinc-900 rounded-lg flex items-center justify-center">
                                <span class="text-white font-bold text-md"><i class="fa-solid fa-comments-dollar"></i></span>
                            </div>
                        </div>
                        <div class="">
                            <p class="text-md font-bold">หากไม่พอใจในงานสามารถขอคืนเงินได้หรือไม่?</p>
                            <p class="text-gray-600">นโยบายการคืนเงินของเราเป็นไปตามเงื่อนไขดังต่อไปนี้:</p>
                            <div class="mt-4">
                                <div class="my-4 bg-red-50 p-4 rounded-lg">
                                    <h4 class="font-medium text-red-800 mb-2">นโยบายการคืนเงิน</h4>
                                    <ul class="text-sm text-red-700 space-y-2">
                                        <li class="flex items-start">
                                            <i class="fas fa-times-circle text-red-500 mt-0.5 mr-2"></i>
                                            <span>ยกเลิกก่อนเริ่มงาน: คืนเงิน 100%</span>
                                        </li>
                                        <li class="flex items-start">
                                            <i class="fas fa-times-circle text-red-500 mt-0.5 mr-2"></i>
                                            <span>ยกเลิกระหว่างการออกแบบ: คืนเงิน 50%</span>
                                        </li>
                                        <li class="flex items-start">
                                            <i class="fas fa-times-circle text-red-500 mt-0.5 mr-2"></i>
                                            <span>หลังส่งงานเสร็จแล้ว: ไม่สามารถคืนเงินได้</span>
                                        </li>
                                    </ul>
                                </div>
                                <p class="text-gray-600">เราให้ความสำคัญกับความพึงพอใจของลูกค้าเป็นอันดับแรก ดังนั้นเราจะทำงานจนกว่าคุณจะพอใจในงานออกแบบ โดยปกติแล้วเราสามารถแก้ไขงานได้จนกว่าจะตรงกับความต้องการของคุณ</p>
                            </div>
                        </div>
                    </div>

                </div>
            </div>
        </div>
    </section>
    <section class="py-5 font-thai">
        <div class="container mx-auto px-6 my-10">
            <div class="max-w-7xl mx-auto">

                <!-- Header -->
                <div class="text-center mb-16">
                    <h2 class="text-4xl font-bold text-acme-dark mb-4">
                        รีวิวจากลูกค้า
                    </h2>
                    <p class="text-xl text-gray-600 max-w-3xl mx-auto">
                        สิ่งที่ลูกค้าพูดเกี่ยวกับบริการของเรา
                    </p>
                </div>
                <div class="grid grid-cols-3 gap-6">
                    <!-- Testimonial 1 -->
                    <div class="bg-white border border-gray-200 rounded-xl shadow-sm p-6 gap-6 mb-6">
                        <div class="flex items-start mb-4">
                            <div class="avatar w-10 h-10 bg-blue-100 rounded-full flex items-center justify-center text-blue-600 text-sm font-medium mr-3">
                                ส
                            </div>
                            <div>
                                <h4 class="font-semibold text-gray-800 text-sm">สมชาย ใจดี</h4>
                                <p class="text-gray-500 text-xs">เจ้าของร้านอาหารไทย</p>
                            </div>
                        </div>
                        <p class="text-gray-600 text-sm mb-4">"พอได้โลโก้ใหม่จากทีมงาน ยอดขายเพิ่มขึ้นจริงๆ ลูกค้าบอกว่าเห็นแล้วน่ากิน จำง่าย"</p>
                        <div class="star-rating text-xs">
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star"></i>
                        </div>
                    </div>
                    <div class="bg-white border border-gray-200 rounded-xl shadow-sm p-6 gap-6 mb-6">
                        <div class="flex items-start mb-4">
                            <div class="avatar w-10 h-10 bg-blue-100 rounded-full flex items-center justify-center text-blue-600 text-sm font-medium mr-3">
                                ส
                            </div>
                            <div>
                                <h4 class="font-semibold text-gray-800 text-sm">สมชาย ใจดี</h4>
                                <p class="text-gray-500 text-xs">เจ้าของร้านอาหารไทย</p>
                            </div>
                        </div>
                        <p class="text-gray-600 text-sm mb-4">"พอได้โลโก้ใหม่จากทีมงาน ยอดขายเพิ่มขึ้นจริงๆ ลูกค้าบอกว่าเห็นแล้วน่ากิน จำง่าย"</p>
                        <div class="star-rating text-xs">
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star"></i>
                        </div>
                    </div>
                    <div class="bg-white border border-gray-200 rounded-xl shadow-sm p-6 gap-6 mb-6">
                        <div class="flex items-start mb-4">
                            <div class="avatar w-10 h-10 bg-blue-100 rounded-full flex items-center justify-center text-blue-600 text-sm font-medium mr-3">
                                ส
                            </div>
                            <div>
                                <h4 class="font-semibold text-gray-800 text-sm">สมชาย ใจดี</h4>
                                <p class="text-gray-500 text-xs">เจ้าของร้านอาหารไทย</p>
                            </div>
                        </div>
                        <p class="text-gray-600 text-sm mb-4">"พอได้โลโก้ใหม่จากทีมงาน ยอดขายเพิ่มขึ้นจริงๆ ลูกค้าบอกว่าเห็นแล้วน่ากิน จำง่าย"</p>
                        <div class="star-rating text-xs">
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
</body>

</html>