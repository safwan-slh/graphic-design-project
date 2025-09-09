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
?>
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
                        <a href="services.php" class=" text-white bg-zinc-900 hover:bg-zinc-800 font-medium rounded-full text-sm px-5 py-2 text-center transition-all duration-300 ease-in-out hover:scale-105 flex items-center justify-center">
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
    
</body>

</html>