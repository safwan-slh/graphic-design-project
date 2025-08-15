<?php
// ตรวจสอบ error
error_reporting(E_ALL);
ini_set('display_errors', 1);
require '../includes/db_connect.php';
require '../auth/auth.php';
requireRole('admin'); // ให้เข้าหน้านี้ได้เฉพาะ admin

// ดึงข้อมูลทั้งหมด
$sql_all = "SELECT * FROM services ORDER BY created_at DESC";
$result_all = $conn->query($sql_all);

// ดึงบริการที่เปิดใช้งาน
$sql_active = "SELECT * FROM services WHERE is_active = 1 ORDER BY created_at DESC";
$result_active = $conn->query($sql_active);

// ดึงบริการที่ปิดใช้งาน
$sql_inactive = "SELECT * FROM services WHERE is_active = 0 ORDER BY created_at DESC";
$result_inactive = $conn->query($sql_inactive);

// ดึงบริการแนะนำ
$sql_featured = "SELECT * FROM services WHERE is_featured = 1 ORDER BY created_at DESC";
$result_featured = $conn->query($sql_featured);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>จัดการบริการ</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/flowbite/1.7.0/flowbite.min.css" rel="stylesheet" />
    <link href="../../dist/output.css" rel="stylesheet" />
    <style>
        /* เพิ่มสไตล์สำหรับแท็บที่ถูกเลือก */
        [role="tab"].active-tab {
            background-color: hsl(240, 10%, 3.9%);
            color: white;
        }

        /* สไตล์เมื่อ hover */
        [role="tab"]:not(.active-tab):hover {
            background-color: hsl(240, 4.8%, 95.9%);
        }
    </style>
</head>

<body class="">
    <?php include '../includes/sidebar.php'; ?>
    <div class="ml-64 p-8">
        <div class="mb-4">
            <h1 class="text-2xl font-bold text-gray-800">จัดการบริการ</h1>
            <p class="text-gray-600">ระบบบริหารจัดการบริการทั้งหมดของคุณ</p>
        </div>

        <!-- แท็บ Filter -->
        <div class="mb-6 gap-2 flex p-1">
            <button class="flex justify-center items-center font-medium rounded-full text-sm px-5 py-2.5 text-center active-tab ring-1 ring-zinc-200"
                id="all-tab" data-tab-target="all" type="button" role="tab" aria-controls="all" aria-selected="true">
                ทั้งหมด <span class="bg-gray-200 text-gray-800 px-2 py-0.5 rounded-full text-xs ml-1"><?= $result_all->num_rows ?></span>
            </button>
            <button class="flex justify-center items-center font-medium rounded-full text-sm px-5 py-2.5 text-center ring-1 ring-zinc-200"
                id="active-tab" data-tab-target="active" type="button" role="tab" aria-controls="active" aria-selected="false">
                เปิดใช้งาน <span class="bg-green-100 text-green-800 px-2 py-0.5 rounded-full text-xs ml-1"><?= $result_active->num_rows ?></span>
            </button>
            <button class="flex justify-center items-center font-medium rounded-full text-sm px-5 py-2.5 text-center ring-1 ring-zinc-200"
                id="inactive-tab" data-tab-target="inactive" type="button" role="tab" aria-controls="inactive" aria-selected="false">
                ปิดใช้งาน <span class="bg-red-100 text-red-800 px-2 py-0.5 rounded-full text-xs ml-1"><?= $result_inactive->num_rows ?></span>
            </button>
            <button class="flex justify-center items-center font-medium rounded-full text-sm px-5 py-2.5 text-center ring-1 ring-zinc-200"
                id="featured-tab" data-tab-target="featured" type="button" role="tab" aria-controls="featured" aria-selected="false">
                แนะนำ <span class="bg-purple-100 text-purple-800 px-2 py-0.5 rounded-full text-xs ml-1"><?= $result_featured->num_rows ?></span>
            </button>
        </div>

        <!-- ส่วนแสดงผลแบบ Card -->
        <div class="tab-content">
            <!-- แสดงทั้งหมด -->
            <div class="tab-pane active" id="all" role="tabpanel" aria-labelledby="all-tab">
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    <!-- Add New Service Card -->
                    <a href="#" class="">
                        <div class="border-2 border-dashed border-gray-300 rounded-xl hover:border-indigo-400 transition duration-300 flex flex-col items-center justify-center p-8 bg-gray-50">
                            <!-- กล่องไอคอนรูปวงกลมสีม่วงอ่อน -->
                            <div class="w-12 h-12 bg-indigo-100 rounded-full flex items-center justify-center text-indigo-600 mb-4">
                                <!-- ไอคอนเครื่องหมายบวก -->
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-6">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                                </svg>
                            </div>
                            <!-- หัวข้อ -->
                            <h3 class="font-medium text-gray-700 mb-1">เพิ่มบริการใหม่</h3>
                            <!-- คำอธิบายย่อย -->
                            <p class="text-gray-500 text-sm text-center">สร้างแพ็กเกจบริการใหม่สำหรับลูกค้าของคุณ</p>
                        </div>
                    </a>
                    <?php if ($result_all && $result_all->num_rows > 0): ?>
                        <?php while ($row = $result_all->fetch_assoc()): ?>
                            <div class="service-card bg-white rounded-xl shadow-sm ring-1 ring-gray-200">
                                <div class="p-5">
                                    <div class="flex justify-between items-start mb-3">
                                        <h3 class="text-lg font-bold text-gray-800 truncate"><?php echo htmlspecialchars($row['service_name']); ?></h3>
                                        <div class="flex space-x-2">
                                            <?php if ($row['is_featured']): ?>
                                                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-purple-100 text-purple-800">
                                                    แนะนำ
                                                </span>
                                            <?php endif; ?>
                                            <?php if ($row['is_active']): ?>
                                                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-green-100 text-green-800">
                                                    เปิด
                                                </span>
                                            <?php else: ?>
                                                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-red-100 text-red-800">
                                                    ปิด
                                                </span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    <p class="text-gray-600 mb-4 line-clamp-2"><?php echo htmlspecialchars($row['short_description']); ?></p>
                                    <div class="flex justify-between items-center mb-4">
                                        <span class="text-lg font-bold text-gray-900">฿<?php echo number_format($row['base_price'], 2); ?></span>
                                        <span class="text-sm text-gray-500"><?php echo htmlspecialchars($row['price_unit']); ?></span>
                                    </div>
                                    <div class="flex justify-between border-t pt-4">
                                        <a href="#"
                                            class="text-blue-600 hover:text-blue-800 font-medium flex items-center text-sm">
                                            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                                            </svg>
                                            แก้ไข
                                        </a>
                                        <a href="#"
                                            class="text-red-600 hover:text-red-800 font-medium flex items-center text-sm"
                                            onclick="return confirm('คุณแน่ใจที่จะลบบริการนี้?');">
                                            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                            </svg>
                                            ลบ
                                        </a>
                                    </div>
                                </div>
                            </div>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <div class="col-span-3 text-center py-10">
                            <p class="text-gray-500">ไม่มีข้อมูลบริการ</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- แสดงเฉพาะที่เปิดใช้งาน -->
            <div class="tab-pane hidden" id="active" role="tabpanel" aria-labelledby="active-tab">
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    <?php if ($result_active && $result_active->num_rows > 0): ?>
                        <?php while ($row = $result_active->fetch_assoc()): ?>
                            <div class="service-card bg-white rounded-xl shadow-sm ring-1 ring-gray-200">
                                <div class="p-5">
                                    <div class="flex justify-between items-start mb-3">
                                        <h3 class="text-lg font-bold text-gray-800 truncate"><?php echo htmlspecialchars($row['service_name']); ?></h3>
                                        <div class="flex space-x-2">
                                            <?php if ($row['is_featured']): ?>
                                                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-purple-100 text-purple-800">
                                                    แนะนำ
                                                </span>
                                            <?php endif; ?>
                                            <?php if ($row['is_active']): ?>
                                                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-green-100 text-green-800">
                                                    เปิด
                                                </span>
                                            <?php else: ?>
                                                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-red-100 text-red-800">
                                                    ปิด
                                                </span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    <p class="text-gray-600 mb-4 line-clamp-2"><?php echo htmlspecialchars($row['short_description']); ?></p>
                                    <div class="flex justify-between items-center mb-4">
                                        <span class="text-lg font-bold text-gray-900">฿<?php echo number_format($row['base_price'], 2); ?></span>
                                        <span class="text-sm text-gray-500"><?php echo htmlspecialchars($row['price_unit']); ?></span>
                                    </div>
                                    <div class="flex justify-between border-t pt-4">
                                        <a href="#"
                                            class="text-blue-600 hover:text-blue-800 font-medium flex items-center text-sm">
                                            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                                            </svg>
                                            แก้ไข
                                        </a>
                                        <a href="#"
                                            class="text-red-600 hover:text-red-800 font-medium flex items-center text-sm"
                                            onclick="return confirm('คุณแน่ใจที่จะลบบริการนี้?');">
                                            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                            </svg>
                                            ลบ
                                        </a>
                                    </div>
                                </div>
                            </div>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <div class="col-span-3 text-center py-10">
                            <p class="text-gray-500">ไม่มีบริการที่เปิดใช้งาน</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- แสดงเฉพาะที่ปิดใช้งาน -->
            <div class="tab-pane hidden" id="inactive" role="tabpanel" aria-labelledby="inactive-tab">
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    <?php if ($result_inactive && $result_inactive->num_rows > 0): ?>
                        <?php while ($row = $result_inactive->fetch_assoc()): ?>
                            <div class="service-card bg-white rounded-xl shadow-sm ring-1 ring-gray-200">
                                <div class="p-5">
                                    <div class="flex justify-between items-start mb-3">
                                        <h3 class="text-lg font-bold text-gray-800 truncate"><?php echo htmlspecialchars($row['service_name']); ?></h3>
                                        <div class="flex space-x-2">
                                            <?php if ($row['is_featured']): ?>
                                                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-purple-100 text-purple-800">
                                                    แนะนำ
                                                </span>
                                            <?php endif; ?>
                                            <?php if ($row['is_active']): ?>
                                                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-green-100 text-green-800">
                                                    เปิด
                                                </span>
                                            <?php else: ?>
                                                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-red-100 text-red-800">
                                                    ปิด
                                                </span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    <p class="text-gray-600 mb-4 line-clamp-2"><?php echo htmlspecialchars($row['short_description']); ?></p>
                                    <div class="flex justify-between items-center mb-4">
                                        <span class="text-lg font-bold text-gray-900">฿<?php echo number_format($row['base_price'], 2); ?></span>
                                        <span class="text-sm text-gray-500"><?php echo htmlspecialchars($row['price_unit']); ?></span>
                                    </div>
                                    <div class="flex justify-between border-t pt-4">
                                        <a href="#"
                                            class="text-blue-600 hover:text-blue-800 font-medium flex items-center text-sm">
                                            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                                            </svg>
                                            แก้ไข
                                        </a>
                                        <a href="#"
                                            class="text-red-600 hover:text-red-800 font-medium flex items-center text-sm"
                                            onclick="return confirm('คุณแน่ใจที่จะลบบริการนี้?');">
                                            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                            </svg>
                                            ลบ
                                        </a>
                                    </div>
                                </div>
                            </div>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <div class="col-span-3 text-center py-10">
                            <p class="text-gray-500">ไม่มีบริการที่ปิดใช้งาน</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- แสดงเฉพาะที่แนะนำ -->
            <div class="tab-pane hidden" id="featured" role="tabpanel" aria-labelledby="featured-tab">
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    <?php if ($result_featured && $result_featured->num_rows > 0): ?>
                        <?php while ($row = $result_featured->fetch_assoc()): ?>
                            <div class="service-card bg-white rounded-xl shadow-sm ring-1 ring-gray-200">
                                <div class="p-5">
                                    <div class="flex justify-between items-start mb-3">
                                        <h3 class="text-lg font-bold text-gray-800 truncate"><?php echo htmlspecialchars($row['service_name']); ?></h3>
                                        <div class="flex space-x-2">
                                            <?php if ($row['is_featured']): ?>
                                                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-purple-100 text-purple-800">
                                                    แนะนำ
                                                </span>
                                            <?php endif; ?>
                                            <?php if ($row['is_active']): ?>
                                                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-green-100 text-green-800">
                                                    เปิด
                                                </span>
                                            <?php else: ?>
                                                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-red-100 text-red-800">
                                                    ปิด
                                                </span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    <p class="text-gray-600 mb-4 line-clamp-2"><?php echo htmlspecialchars($row['short_description']); ?></p>
                                    <div class="flex justify-between items-center mb-4">
                                        <span class="text-lg font-bold text-gray-900">฿<?php echo number_format($row['base_price'], 2); ?></span>
                                        <span class="text-sm text-gray-500"><?php echo htmlspecialchars($row['price_unit']); ?></span>
                                    </div>
                                    <div class="flex justify-between border-t pt-4">
                                        <a href="#"
                                            class="text-blue-600 hover:text-blue-800 font-medium flex items-center text-sm">
                                            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                                            </svg>
                                            แก้ไข
                                        </a>
                                        <a href="#"
                                            class="text-red-600 hover:text-red-800 font-medium flex items-center text-sm"
                                            onclick="return confirm('คุณแน่ใจที่จะลบบริการนี้?');">
                                            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                            </svg>
                                            ลบ
                                        </a>
                                    </div>
                                </div>
                            </div>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <div class="col-span-3 text-center py-10">
                            <p class="text-gray-500">ไม่มีบริการแนะนำ</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/flowbite/1.7.0/flowbite.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // ฟังก์ชันเปลี่ยนแท็บ
            function switchTab(tab) {
                // ลบคลาส active จากทั้งหมด
                document.querySelectorAll('[role="tab"]').forEach(t => {
                    t.classList.remove('active-tab');
                    t.setAttribute('aria-selected', 'false');
                });

                // ซ่อนทั้งหมด
                document.querySelectorAll('.tab-pane').forEach(pane => {
                    pane.classList.add('hidden');
                });

                // เพิ่มคลาส active ให้แท็บที่เลือก
                tab.classList.add('active-tab');
                tab.setAttribute('aria-selected', 'true');

                // แสดง panel ที่เกี่ยวข้อง
                const target = document.getElementById(tab.dataset.tabTarget);
                target.classList.remove('hidden');
            }

            // ตั้งค่า event listener
            document.querySelectorAll('[data-tab-target]').forEach(tab => {
                tab.addEventListener('click', () => switchTab(tab));
            });

            // เปิดแท็บแรกโดย default
            switchTab(document.getElementById('all-tab'));
        });
    </script>
</body>

</html>