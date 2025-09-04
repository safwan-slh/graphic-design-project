<?php
require '../includes/db_connect.php';

// ดึงข้อมูลผลงานทั้งหมด
$sql = "SELECT p.*, s.service_name 
        FROM portfolios p 
        LEFT JOIN services s ON p.service_id = s.service_id 
        ORDER BY p.created_at DESC";

$result = $conn->query($sql); ?>

<!DOCTYPE html>
<html lang="th">

<head>
        <meta charset="UTF-8" />
        <meta name="viewport" content="width=device-width, initial-scale=1.0" />
        <title>จัดการผลงาน - Admin</title>
        <link href="https://cdnjs.cloudflare.com/ajax/libs/flowbite/1.7.0/flowbite.min.css" rel="stylesheet" />
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />
        <link href="../../dist/output.css" rel="stylesheet" />
        <style>
                .portfolio-overlay {
                        position: absolute;
                        top: 0;
                        left: 0;
                        right: 0;
                        bottom: 0;
                        background: linear-gradient(to top, rgba(0, 0, 0, 0.8) 0%, rgba(0, 0, 0, 0) 100%);
                        opacity: 0;
                        transition: opacity 0.4s ease;
                }

                .portfolio-item:hover .portfolio-overlay {
                        opacity: 5;
                }
        </style>
</head>

<body>
        <?php include '../includes/navbar.php'; ?>

        <div class="">
                <div class="max-w-7xl mx-auto">
                        <!-- Header -->
                        <div class="flex justify-between items-center mb-8">
                                <div>
                                        <h1 class="text-2xl font-bold text-gray-800">จัดการผลงาน</h1>
                                        <p class="text-gray-600 mt-1">จัดการผลงานทั้งหมดในระบบ</p>
                                </div>
                        </div>

                        <!-- Portfolio Grid -->
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
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
                                                                                <div class="portfolio-overlay flex items-end p-6">
                                                                                        <div class="text-white">
                                                                                                <h3 class="text-xl font-bold mb-2"><?= htmlspecialchars($portfolio['title']) ?></h3>
                                                                                                <p class="text-sm opacity-90"><?= htmlspecialchars($portfolio['description']) ?></p>
                                                                                                <?php if ($portfolio['client_name']): ?>
                                                                                                        <p class=" text-sm mt-1">
                                                                                                                ลูกค้า:
                                                                                                                <?= htmlspecialchars($portfolio['client_name']) ?>
                                                                                                        </p>
                                                                                                <?php endif; ?>

                                                                                                <p class=" text-sm mt-1">
                                                                                                        บริการ:
                                                                                                        <?= htmlspecialchars($portfolio['service_name']) ?>
                                                                                                </p>
                                                                                        </div>
                                                                                </div>

                                                                        <?php else: ?>
                                                                                <div
                                                                                        class="w-full h-full flex items-center justify-center text-white font-bold text-2xl">
                                                                                        <?= htmlspecialchars(substr($portfolio['title'], 0, 1)) ?>
                                                                                </div>
                                                                        <?php endif; ?>
                                                                </div>
                                                                <!-- Badge สถานะ -->
                                                                <div class="absolute top-2 flex space-x-2">
                                                                        <?php if (!$portfolio['is_active']): ?>
                                                                                <span
                                                                                        class="px-2 py-1 bg-red-100 text-red-800 text-xs rounded-full ml-2">
                                                                                        <i class="fas fa-eye-slash mr-1"></i>
                                                                                        ซ่อน
                                                                                </span>
                                                                        <?php endif; ?>
                                                                        <?php if ($portfolio['is_featured']): ?>
                                                                                <span
                                                                                        class="px-2 py-1 bg-yellow-100 text-yellow-800 text-xs font-bold rounded-full ml-2">
                                                                                        <i class="fas fa-star mr-1"></i>
                                                                                        แนะนำ
                                                                                </span>
                                                                        <?php endif; ?>
                                                                </div>
                                                        </div>

                                                        <div class="p-4">
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
                                                                        <div class="flex flex-wrap gap-1">
                                                                                <?php foreach (array_slice($cleanTags, 0, 4) as $tag): ?>
                                                                                        <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-indigo-100 text-indigo-800">
                                                                                                <?= htmlspecialchars($tag) ?>
                                                                                        </span>
                                                                                <?php endforeach; ?>
                                                                                <?php if (count($cleanTags) > 4): ?>
                                                                                        <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-gray-100 text-gray-800">
                                                                                                +
                                                                                                <?= count($cleanTags) - 4 ?>
                                                                                        </span>
                                                                                <?php endif; ?>
                                                                        </div>
                                                                <?php endif; ?>

                                                                <!-- วันที่สร้าง -->
                                                                <div class="text-xs text-gray-400 mt-2">
                                                                        <i class="far fa-clock mr-1"></i>
                                                                        <?= date('d/m/Y', strtotime($portfolio['created_at'])) ?>
                                                                </div>
                                                        </div>
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
                </div>
        </div>

        <script>
                // Fallback for images that fail to load
                document.addEventListener('DOMContentLoaded', function() {
                        document.querySelectorAll('img').forEach((img) => {
                                img.addEventListener('error', function() {
                                        this.style.display = 'none';
                                        const parent = this.parentElement;
                                        parent.classList.add('fallback-image');
                                        const title = this.alt || 'P';
                                        parent.innerHTML =
                                                '<div class="w-full h-full flex items-center justify-center text-white font-bold text-2xl">' +
                                                title.charAt(0).toUpperCase() +
                                                '</div>';
                                });
                        });
                });
        </script>
</body>

</html>