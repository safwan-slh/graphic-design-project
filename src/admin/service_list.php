<?php
// ตรวจสอบ error
error_reporting(E_ALL);
ini_set('display_errors', 1);
require '../includes/db_connect.php';
require '../auth/auth.php';
requireRole('admin'); // ให้เข้าหน้านี้ได้เฉพาะ admin

$message = '';
$messageType = ''; // success หรือ error

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

if (!empty($success)) {
    $message = $success;
    $messageType = 'success';
} elseif (!empty($error)) {
    $message = $error;
    $messageType = 'error';
} else {
    $message = '';
    $messageType = '';
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>จัดการบริการ</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=IBM+Plex+Sans+Thai:wght@300;400;500;600;700&display=swap"
        rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .font-thai {
            font-family: 'IBM Plex Sans Thai', sans-serif;
        }

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

<body class="font-thai bg-zinc-100">
    <?php include '../includes/sidebar.php'; ?>
    <div class="ml-64">
        <?php
        $breadcrumb = ['Dashboard', 'จัดการบริการ'];
        $breadcrumb_links = ['/graphic-design/src/admin/index.php', '/graphic-design/src/admin/service_list.php'];
        include '../includes/admin_navbar.php';
        ?>
        <div class="p-6">
            <div class=" text-zinc-900 bg-white rounded-2xl border border-slate-200 mb-2">
                <!-- Header -->
                <div class="flex items-center border-b border-gray-200 p-4">
                    <div class="mr-4 rounded-xl bg-zinc-900 p-3">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="h-6 w-6 text-white">
                            <path d="M5.566 4.657A4.505 4.505 0 0 1 6.75 4.5h10.5c.41 0 .806.055 1.183.157A3 3 0 0 0 15.75 3h-7.5a3 3 0 0 0-2.684 1.657ZM2.25 12a3 3 0 0 1 3-3h13.5a3 3 0 0 1 3 3v6a3 3 0 0 1-3 3H5.25a3 3 0 0 1-3-3v-6ZM5.25 7.5c-.41 0-.806.055-1.184.157A3 3 0 0 1 6.75 6h10.5a3 3 0 0 1 2.683 1.657A4.505 4.505 0 0 0 18.75 7.5H5.25Z" />
                        </svg>
                    </div>
                    <div class="">
                        <h1 class="flex items-center text-2xl font-bold text-zinc-900">
                            จัดการบริการ
                        </h1>
                        <p class="text-gray-600">
                            ระบบบริหารจัดการบริการทั้งหมดของคุณ
                        </p>
                    </div>
                </div>
                <div class="mx-auto text-center p-4">
                    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                        <div class="bg-white flex items-center p-2 ring-1 ring-zinc-200 rounded-2xl">
                            <div class="mr-4 rounded-xl text-purple-600 bg-purple-100 ring-1 ring-purple-200 p-3">
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="h-6 w-6">
                                    <path fill-rule="evenodd" d="M2.625 6.75a1.125 1.125 0 1 1 2.25 0 1.125 1.125 0 0 1-2.25 0Zm4.875 0A.75.75 0 0 1 8.25 6h12a.75.75 0 0 1 0 1.5h-12a.75.75 0 0 1-.75-.75ZM2.625 12a1.125 1.125 0 1 1 2.25 0 1.125 1.125 0 0 1-2.25 0ZM7.5 12a.75.75 0 0 1 .75-.75h12a.75.75 0 0 1 0 1.5h-12A.75.75 0 0 1 7.5 12Zm-4.875 5.25a1.125 1.125 0 1 1 2.25 0 1.125 1.125 0 0 1-2.25 0Zm4.875 0a.75.75 0 0 1 .75-.75h12a.75.75 0 0 1 0 1.5h-12a.75.75 0 0 1-.75-.75Z" clip-rule="evenodd" />
                                </svg>
                            </div>
                            <div class="">
                                <h1 class="flex items-center text-2xl font-bold text-purple-700">
                                    <?= $result_all->num_rows ?>
                                </h1>
                                <p class="text-gray-500 text-sm font-bold">
                                    จำนวนบริการทั้งหมด
                                </p>
                            </div>
                        </div>
                        <div class="bg-white flex items-center p-2 ring-1 ring-zinc-200 rounded-2xl">
                            <div class="mr-4 rounded-xl text-yellow-600 bg-yellow-100 ring-1 ring-yellow-200 p-3">
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="h-6 w-6">
                                    <path fill-rule="evenodd" d="M16.403 12.652a3 3 0 0 0 0-5.304 3 3 0 0 0-3.75-3.751 3 3 0 0 0-5.305 0 3 3 0 0 0-3.751 3.75 3 3 0 0 0 0 5.305 3 3 0 0 0 3.75 3.751 3 3 0 0 0 5.305 0 3 3 0 0 0 3.751-3.75Zm-2.546-4.46a.75.75 0 0 0-1.214-.883l-3.483 4.79-1.88-1.88a.75.75 0 1 0-1.06 1.061l2.5 2.5a.75.75 0 0 0 1.137-.089l4-5.5Z" clip-rule="evenodd" />
                                </svg>
                            </div>
                            <div class="">
                                <h1 class="flex items-center text-2xl font-bold text-yellow-700">
                                    <?= $result_featured->num_rows ?>
                                </h1>
                                <p class="text-gray-500 text-sm font-bold">
                                    บริการแนะนำ
                                </p>
                            </div>
                        </div>
                        <div class="bg-white flex items-center p-2 ring-1 ring-zinc-200 rounded-2xl">
                            <div class="mr-4 rounded-xl text-green-600 bg-green-100 ring-1 ring-green-200 p-3">
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="h-6 w-6">
                                    <path d="M18 1.5c2.9 0 5.25 2.35 5.25 5.25v3.75a.75.75 0 0 1-1.5 0V6.75a3.75 3.75 0 1 0-7.5 0v3a3 3 0 0 1 3 3v6.75a3 3 0 0 1-3 3H3.75a3 3 0 0 1-3-3v-6.75a3 3 0 0 1 3-3h9v-3c0-2.9 2.35-5.25 5.25-5.25Z" />
                                </svg>
                            </div>
                            <div class="">
                                <h1 class="flex items-center text-2xl font-bold text-green-700">
                                    <?= $result_active->num_rows ?>
                                </h1>
                                <p class="text-gray-500 text-sm font-bold">
                                    เปิดใช้งาน
                                </p>
                            </div>
                        </div>
                        <div class="bg-white flex items-center p-2 ring-1 ring-zinc-200 rounded-2xl">
                            <div class="mr-4 rounded-xl text-red-600 bg-red-100 ring-1 ring-red-200 p-3">
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="h-6 w-6">
                                    <path fill-rule="evenodd" d="M12 1.5a5.25 5.25 0 0 0-5.25 5.25v3a3 3 0 0 0-3 3v6.75a3 3 0 0 0 3 3h10.5a3 3 0 0 0 3-3v-6.75a3 3 0 0 0-3-3v-3c0-2.9-2.35-5.25-5.25-5.25Zm3.75 8.25v-3a3.75 3.75 0 1 0-7.5 0v3h7.5Z" clip-rule="evenodd" />
                                </svg>
                            </div>
                            <div class="">
                                <h1 class="flex items-center text-2xl font-bold text-red-700">
                                    <?= $result_inactive->num_rows ?>
                                </h1>
                                <p class="text-gray-500 text-sm font-bold">
                                    ปิดใช้งาน
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <!-- แท็บ Filter -->
            <div class="bg-white items-center p-2 ring-1 ring-zinc-200 rounded-2xl">
                <div class="flex justify-between p-2 pb-4 mb-4 border-b border-gray-200">
                    <div class="flex flex-wrap gap-2 items-center">
                        <button class="border transition font-medium rounded-xl text-sm px-5 py-2 text-center flex items-center justify-center"
                            id="all-tab" data-tab-target="all" type="button" role="tab" aria-controls="all" aria-selected="true">
                            ทั้งหมด
                        </button>
                        <button class="border transition font-medium rounded-xl text-sm px-5 py-2 text-center flex items-center justify-center"
                            id="active-tab" data-tab-target="active" type="button" role="tab" aria-controls="active" aria-selected="false">
                            เปิดใช้งาน
                        </button>
                        <button class="border transition font-medium rounded-xl text-sm px-5 py-2 text-center flex items-center justify-center"
                            id="inactive-tab" data-tab-target="inactive" type="button" role="tab" aria-controls="inactive" aria-selected="false">
                            ปิดใช้งาน
                        </button>
                        <button class="border transition font-medium rounded-xl text-sm px-5 py-2 text-center flex items-center justify-center"
                            id="featured-tab" data-tab-target="featured" type="button" role="tab" aria-controls="featured" aria-selected="false">
                            แนะนำ
                        </button>
                    </div>
                    <div class="">
                        <a href="service_add.php" class="border transition font-medium rounded-xl text-sm px-5 py-2 text-center flex items-center justify-center bg-zinc-900 text-white border-zinc-900">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="h-5 w-5 mr-2">
                                <path fill-rule="evenodd" d="M12 3.75a.75.75 0 0 1 .75.75v6.75h6.75a.75.75 0 0 1 0 1.5h-6.75v6.75a.75.75 0 0 1-1.5 0v-6.75H4.5a.75.75 0 0 1 0-1.5h6.75V4.5a.75.75 0 0 1 .75-.75Z" clip-rule="evenodd" />
                            </svg>
                            เพิ่มบริการใหม่</a>
                    </div>
                </div>

                <!-- ส่วนแสดงผลแบบ Card -->
                <div class="tab-content">
                    <!-- แสดงทั้งหมด -->
                    <div class="tab-pane active" id="all" role="tabpanel" aria-labelledby="all-tab">
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                            <?php if ($result_all && $result_all->num_rows > 0): ?>
                                <?php while ($row = $result_all->fetch_assoc()): ?>
                                    <div class="bg-white rounded-2xl shadow-sm p-3 space-y-2 cursor-pointer ring-1 ring-gray-200">
                                        <!-- header -->
                                        <div class="">
                                            <div class="flex items-center justify-between mb-5">
                                                <div class="font-semibold text-lg text-zinc-900">
                                                    <?= htmlspecialchars($row['service_name']) ?>
                                                </div>
                                                <div class="flex space-x-1 item-center">
                                                    <div class="flex items-center space-x-1">
                                                        <?php if ($row['is_featured']): ?>
                                                            <span class="px-3 py-1 rounded-md text-xs font-medium inline-flex items-center bg-yellow-100 text-yellow-800">
                                                                แนะนำ
                                                            </span>
                                                        <?php endif; ?>
                                                        <?php if ($row['is_active']): ?>
                                                            <span class="px-3 py-1 rounded-md text-xs font-medium inline-flex items-center bg-green-100 text-green-800">
                                                                เปิด
                                                            </span>
                                                        <?php else: ?>
                                                            <span class="px-3 py-1 rounded-md text-xs font-medium inline-flex items-center bg-red-100 text-red-800">
                                                                ปิด
                                                            </span>
                                                        <?php endif; ?>
                                                    </div>
                                                    <div class="">
                                                        <button id="dropdownDefaultButton<?= $row['service_id'] ?>" data-dropdown-toggle="cancel<?= $row['service_id'] ?>" class="flex items-center p-1 text-sm font-medium text-center text-gray-400 rounded-lg bg-gray-100 hover:bg-gray-200 focus:ring-4 focus:outline-none focus:ring-gray-200" type="button">
                                                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="size-6">
                                                                <path fill-rule="evenodd" d="M10.5 6a1.5 1.5 0 1 1 3 0 1.5 1.5 0 0 1-3 0Zm0 6a1.5 1.5 0 1 1 3 0 1.5 1.5 0 0 1-3 0Zm0 6a1.5 1.5 0 1 1 3 0 1.5 1.5 0 0 1-3 0Z" clip-rule="evenodd" />
                                                            </svg>
                                                        </button>
                                                        <div id="cancel<?= $row['service_id'] ?>" class="z-10 hidden bg-white divide-y rounded-xl shadow-md w-44 border-1.5 border-gray-200 ring-1 ring-gray-200">
                                                            <ul class="space-y-2 p-2 py-2 text-sm text-gray-700" aria-labelledby="dropdownDefaultButton">
                                                                <li>
                                                                    <a href="service_add.php?id=<?php echo $row['service_id']; ?>" class=" flex items-center justify-between px-3 py-2 text-sm rounded-lg bg-zinc-50 text-blue-600 hover:bg-zinc-100 transition-colors duration-200 ring-1 ring-gray-200">
                                                                        แก้ไข
                                                                        <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor">
                                                                            <path d="M21.731 2.269a2.625 2.625 0 0 0-3.712 0l-1.157 1.157 3.712 3.712 1.157-1.157a2.625 2.625 0 0 0 0-3.712ZM19.513 8.199l-3.712-3.712-12.15 12.15a5.25 5.25 0 0 0-1.32 2.214l-.8 2.685a.75.75 0 0 0 .933.933l2.685-.8a5.25 5.25 0 0 0 2.214-1.32L19.513 8.2Z" />
                                                                        </svg>
                                                                    </a>
                                                                </li>
                                                                <li>
                                                                    <a href="service_delete.php?id=<?php echo $row['service_id']; ?>" class=" flex items-center justify-between px-3 py-2 text-sm rounded-lg bg-zinc-50 text-red-600 hover:bg-zinc-100 transition-colors duration-200 ring-1 ring-gray-200">
                                                                        ลบ
                                                                        <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor">
                                                                            <path fill-rule="evenodd" d="M16.5 4.478v.227a48.816 48.816 0 0 1 3.878.512.75.75 0 1 1-.256 1.478l-.209-.035-1.005 13.07a3 3 0 0 1-2.991 2.77H8.084a3 3 0 0 1-2.991-2.77L4.087 6.66l-.209.035a.75.75 0 0 1-.256-1.478A48.567 48.567 0 0 1 7.5 4.705v-.227c0-1.564 1.213-2.9 2.816-2.951a52.662 52.662 0 0 1 3.369 0c1.603.051 2.815 1.387 2.815 2.951Zm-6.136-1.452a51.196 51.196 0 0 1 3.273 0C14.39 3.05 15 3.684 15 4.478v.113a49.488 49.488 0 0 0-6 0v-.113c0-.794.609-1.428 1.364-1.452Zm-.355 5.945a.75.75 0 1 0-1.5.058l.347 9a.75.75 0 1 0 1.499-.058l-.346-9Zm5.48.058a.75.75 0 1 0-1.498-.058l-.347 9a.75.75 0 0 0 1.5.058l.345-9Z" clip-rule="evenodd" />
                                                                        </svg>
                                                                    </a>
                                                                </li>
                                                            </ul>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="bg-gray-50 p-3 rounded-xl ring-1 ring-gray-200">
                                            <p class="text-gray-600 mb-4 line-clamp-2"><?php echo htmlspecialchars($row['short_description']); ?></p>
                                            <div class="flex justify-between items-center ">
                                                <span>ราคา:</span>
                                                <span>
                                                    <span class="text-lg font-bold text-gray-900">฿<?php echo number_format($row['base_price'], 2); ?></span>
                                                    <span class="text-sm text-gray-500">/<?php echo htmlspecialchars($row['price_unit']); ?></span>
                                                </span>
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
                                    <div class="bg-white rounded-2xl shadow-sm p-3 space-y-2 cursor-pointer ring-1 ring-gray-200">
                                        <!-- header -->
                                        <div class="">
                                            <div class="flex items-center justify-between mb-5">
                                                <div class="font-semibold text-lg text-zinc-900">
                                                    <?= htmlspecialchars($row['service_name']) ?>
                                                </div>
                                                <div class="flex space-x-1 item-center">
                                                    <div class="flex items-center space-x-1">
                                                        <?php if ($row['is_featured']): ?>
                                                            <span class="px-3 py-1 rounded-md text-xs font-medium inline-flex items-center bg-yellow-100 text-yellow-800">
                                                                แนะนำ
                                                            </span>
                                                        <?php endif; ?>
                                                        <?php if ($row['is_active']): ?>
                                                            <span class="px-3 py-1 rounded-md text-xs font-medium inline-flex items-center bg-green-100 text-green-800">
                                                                เปิด
                                                            </span>
                                                        <?php else: ?>
                                                            <span class="px-3 py-1 rounded-md text-xs font-medium inline-flex items-center bg-red-100 text-red-800">
                                                                ปิด
                                                            </span>
                                                        <?php endif; ?>
                                                    </div>
                                                    <div class="">
                                                        <button id="dropdownDefaultButton" data-dropdown-toggle="cancel" class="flex items-center p-1 text-sm font-medium text-center text-gray-400 rounded-lg bg-gray-100 hover:bg-gray-200 focus:ring-4 focus:outline-none focus:ring-gray-200" type="button">
                                                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="size-6">
                                                                <path fill-rule="evenodd" d="M10.5 6a1.5 1.5 0 1 1 3 0 1.5 1.5 0 0 1-3 0Zm0 6a1.5 1.5 0 1 1 3 0 1.5 1.5 0 0 1-3 0Zm0 6a1.5 1.5 0 1 1 3 0 1.5 1.5 0 0 1-3 0Z" clip-rule="evenodd" />
                                                            </svg>
                                                        </button>
                                                        <div id="cancel" class="z-10 hidden bg-white divide-y rounded-xl shadow-md w-44 border-1.5 border-gray-200 ring-1 ring-gray-200">
                                                            <ul class="space-y-2 p-2 py-2 text-sm text-gray-700" aria-labelledby="dropdownDefaultButton">
                                                                <li>
                                                                    <a href="service_add.php?id=<?php echo $row['service_id']; ?>" class=" flex items-center justify-between px-3 py-2 text-sm rounded-lg bg-zinc-50 text-blue-600 hover:bg-zinc-100 transition-colors duration-200 ring-1 ring-gray-200">
                                                                        แก้ไข
                                                                        <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor">
                                                                            <path d="M21.731 2.269a2.625 2.625 0 0 0-3.712 0l-1.157 1.157 3.712 3.712 1.157-1.157a2.625 2.625 0 0 0 0-3.712ZM19.513 8.199l-3.712-3.712-12.15 12.15a5.25 5.25 0 0 0-1.32 2.214l-.8 2.685a.75.75 0 0 0 .933.933l2.685-.8a5.25 5.25 0 0 0 2.214-1.32L19.513 8.2Z" />
                                                                        </svg>
                                                                    </a>
                                                                </li>
                                                                <li>
                                                                    <a href="service_delete.php?id=<?php echo $row['service_id']; ?>" class=" flex items-center justify-between px-3 py-2 text-sm rounded-lg bg-zinc-50 text-red-600 hover:bg-zinc-100 transition-colors duration-200 ring-1 ring-gray-200">
                                                                        ลบ
                                                                        <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor">
                                                                            <path fill-rule="evenodd" d="M16.5 4.478v.227a48.816 48.816 0 0 1 3.878.512.75.75 0 1 1-.256 1.478l-.209-.035-1.005 13.07a3 3 0 0 1-2.991 2.77H8.084a3 3 0 0 1-2.991-2.77L4.087 6.66l-.209.035a.75.75 0 0 1-.256-1.478A48.567 48.567 0 0 1 7.5 4.705v-.227c0-1.564 1.213-2.9 2.816-2.951a52.662 52.662 0 0 1 3.369 0c1.603.051 2.815 1.387 2.815 2.951Zm-6.136-1.452a51.196 51.196 0 0 1 3.273 0C14.39 3.05 15 3.684 15 4.478v.113a49.488 49.488 0 0 0-6 0v-.113c0-.794.609-1.428 1.364-1.452Zm-.355 5.945a.75.75 0 1 0-1.5.058l.347 9a.75.75 0 1 0 1.499-.058l-.346-9Zm5.48.058a.75.75 0 1 0-1.498-.058l-.347 9a.75.75 0 0 0 1.5.058l.345-9Z" clip-rule="evenodd" />
                                                                        </svg>
                                                                    </a>
                                                                </li>
                                                            </ul>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="bg-gray-50 p-3 rounded-xl ring-1 ring-gray-200">
                                            <p class="text-gray-600 mb-4 line-clamp-2"><?php echo htmlspecialchars($row['short_description']); ?></p>
                                            <div class="flex justify-between items-center ">
                                                <span>ราคา:</span>
                                                <span>
                                                    <span class="text-lg font-bold text-gray-900">฿<?php echo number_format($row['base_price'], 2); ?></span>
                                                    <span class="text-sm text-gray-500">/<?php echo htmlspecialchars($row['price_unit']); ?></span>
                                                </span>
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
                                    <div class="bg-white rounded-2xl shadow-sm p-3 space-y-2 cursor-pointer ring-1 ring-gray-200">
                                        <!-- header -->
                                        <div class="">
                                            <div class="flex items-center justify-between mb-5">
                                                <div class="font-semibold text-lg text-zinc-900">
                                                    <?= htmlspecialchars($row['service_name']) ?>
                                                </div>
                                                <div class="flex space-x-1 item-center">
                                                    <div class="flex items-center space-x-1">
                                                        <?php if ($row['is_featured']): ?>
                                                            <span class="px-3 py-1 rounded-md text-xs font-medium inline-flex items-center bg-yellow-100 text-yellow-800">
                                                                แนะนำ
                                                            </span>
                                                        <?php endif; ?>
                                                        <?php if ($row['is_active']): ?>
                                                            <span class="px-3 py-1 rounded-md text-xs font-medium inline-flex items-center bg-green-100 text-green-800">
                                                                เปิด
                                                            </span>
                                                        <?php else: ?>
                                                            <span class="px-3 py-1 rounded-md text-xs font-medium inline-flex items-center bg-red-100 text-red-800">
                                                                ปิด
                                                            </span>
                                                        <?php endif; ?>
                                                    </div>
                                                    <div class="">
                                                        <button id="dropdownDefaultButton" data-dropdown-toggle="cancel" class="flex items-center p-1 text-sm font-medium text-center text-gray-400 rounded-lg bg-gray-100 hover:bg-gray-200 focus:ring-4 focus:outline-none focus:ring-gray-200" type="button">
                                                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="size-6">
                                                                <path fill-rule="evenodd" d="M10.5 6a1.5 1.5 0 1 1 3 0 1.5 1.5 0 0 1-3 0Zm0 6a1.5 1.5 0 1 1 3 0 1.5 1.5 0 0 1-3 0Zm0 6a1.5 1.5 0 1 1 3 0 1.5 1.5 0 0 1-3 0Z" clip-rule="evenodd" />
                                                            </svg>
                                                        </button>
                                                        <div id="cancel" class="z-10 hidden bg-white divide-y rounded-xl shadow-md w-44 border-1.5 border-gray-200 ring-1 ring-gray-200">
                                                            <ul class="space-y-2 p-2 py-2 text-sm text-gray-700" aria-labelledby="dropdownDefaultButton">
                                                                <li>
                                                                    <a href="service_add.php?id=<?php echo $row['service_id']; ?>" class=" flex items-center justify-between px-3 py-2 text-sm rounded-lg bg-zinc-50 text-blue-600 hover:bg-zinc-100 transition-colors duration-200 ring-1 ring-gray-200">
                                                                        แก้ไข
                                                                        <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor">
                                                                            <path d="M21.731 2.269a2.625 2.625 0 0 0-3.712 0l-1.157 1.157 3.712 3.712 1.157-1.157a2.625 2.625 0 0 0 0-3.712ZM19.513 8.199l-3.712-3.712-12.15 12.15a5.25 5.25 0 0 0-1.32 2.214l-.8 2.685a.75.75 0 0 0 .933.933l2.685-.8a5.25 5.25 0 0 0 2.214-1.32L19.513 8.2Z" />
                                                                        </svg>
                                                                    </a>
                                                                </li>
                                                                <li>
                                                                    <a href="service_delete.php?id=<?php echo $row['service_id']; ?>" class=" flex items-center justify-between px-3 py-2 text-sm rounded-lg bg-zinc-50 text-red-600 hover:bg-zinc-100 transition-colors duration-200 ring-1 ring-gray-200">
                                                                        ลบ
                                                                        <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor">
                                                                            <path fill-rule="evenodd" d="M16.5 4.478v.227a48.816 48.816 0 0 1 3.878.512.75.75 0 1 1-.256 1.478l-.209-.035-1.005 13.07a3 3 0 0 1-2.991 2.77H8.084a3 3 0 0 1-2.991-2.77L4.087 6.66l-.209.035a.75.75 0 0 1-.256-1.478A48.567 48.567 0 0 1 7.5 4.705v-.227c0-1.564 1.213-2.9 2.816-2.951a52.662 52.662 0 0 1 3.369 0c1.603.051 2.815 1.387 2.815 2.951Zm-6.136-1.452a51.196 51.196 0 0 1 3.273 0C14.39 3.05 15 3.684 15 4.478v.113a49.488 49.488 0 0 0-6 0v-.113c0-.794.609-1.428 1.364-1.452Zm-.355 5.945a.75.75 0 1 0-1.5.058l.347 9a.75.75 0 1 0 1.499-.058l-.346-9Zm5.48.058a.75.75 0 1 0-1.498-.058l-.347 9a.75.75 0 0 0 1.5.058l.345-9Z" clip-rule="evenodd" />
                                                                        </svg>
                                                                    </a>
                                                                </li>
                                                            </ul>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="bg-gray-50 p-3 rounded-xl ring-1 ring-gray-200">
                                            <p class="text-gray-600 mb-4 line-clamp-2"><?php echo htmlspecialchars($row['short_description']); ?></p>
                                            <div class="flex justify-between items-center ">
                                                <span>ราคา:</span>
                                                <span>
                                                    <span class="text-lg font-bold text-gray-900">฿<?php echo number_format($row['base_price'], 2); ?></span>
                                                    <span class="text-sm text-gray-500">/<?php echo htmlspecialchars($row['price_unit']); ?></span>
                                                </span>
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
                                    <div class="bg-white rounded-2xl shadow-sm p-3 space-y-2 cursor-pointer ring-1 ring-gray-200">
                                        <!-- header -->
                                        <div class="">
                                            <div class="flex items-center justify-between mb-5">
                                                <div class="font-semibold text-lg text-zinc-900">
                                                    <?= htmlspecialchars($row['service_name']) ?>
                                                </div>
                                                <div class="flex space-x-1 item-center">
                                                    <div class="flex items-center space-x-1">
                                                        <?php if ($row['is_featured']): ?>
                                                            <span class="px-3 py-1 rounded-md text-xs font-medium inline-flex items-center bg-yellow-100 text-yellow-800">
                                                                แนะนำ
                                                            </span>
                                                        <?php endif; ?>
                                                        <?php if ($row['is_active']): ?>
                                                            <span class="px-3 py-1 rounded-md text-xs font-medium inline-flex items-center bg-green-100 text-green-800">
                                                                เปิด
                                                            </span>
                                                        <?php else: ?>
                                                            <span class="px-3 py-1 rounded-md text-xs font-medium inline-flex items-center bg-red-100 text-red-800">
                                                                ปิด
                                                            </span>
                                                        <?php endif; ?>
                                                    </div>
                                                    <div class="">
                                                        <button id="dropdownDefaultButton" data-dropdown-toggle="cancel" class="flex items-center p-1 text-sm font-medium text-center text-gray-400 rounded-lg bg-gray-100 hover:bg-gray-200 focus:ring-4 focus:outline-none focus:ring-gray-200" type="button">
                                                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="size-6">
                                                                <path fill-rule="evenodd" d="M10.5 6a1.5 1.5 0 1 1 3 0 1.5 1.5 0 0 1-3 0Zm0 6a1.5 1.5 0 1 1 3 0 1.5 1.5 0 0 1-3 0Zm0 6a1.5 1.5 0 1 1 3 0 1.5 1.5 0 0 1-3 0Z" clip-rule="evenodd" />
                                                            </svg>
                                                        </button>
                                                        <div id="cancel" class="z-10 hidden bg-white divide-y rounded-xl shadow-md w-44 border-1.5 border-gray-200 ring-1 ring-gray-200">
                                                            <ul class="space-y-2 p-2 py-2 text-sm text-gray-700" aria-labelledby="dropdownDefaultButton">
                                                                <li>
                                                                    <a href="service_add.php?id=<?php echo $row['service_id']; ?>" class=" flex items-center justify-between px-3 py-2 text-sm rounded-lg bg-zinc-50 text-blue-600 hover:bg-zinc-100 transition-colors duration-200 ring-1 ring-gray-200">
                                                                        แก้ไข
                                                                        <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor">
                                                                            <path d="M21.731 2.269a2.625 2.625 0 0 0-3.712 0l-1.157 1.157 3.712 3.712 1.157-1.157a2.625 2.625 0 0 0 0-3.712ZM19.513 8.199l-3.712-3.712-12.15 12.15a5.25 5.25 0 0 0-1.32 2.214l-.8 2.685a.75.75 0 0 0 .933.933l2.685-.8a5.25 5.25 0 0 0 2.214-1.32L19.513 8.2Z" />
                                                                        </svg>
                                                                    </a>
                                                                </li>
                                                                <li>
                                                                    <a href="service_delete.php?id=<?php echo $row['service_id']; ?>" class=" flex items-center justify-between px-3 py-2 text-sm rounded-lg bg-zinc-50 text-red-600 hover:bg-zinc-100 transition-colors duration-200 ring-1 ring-gray-200">
                                                                        ลบ
                                                                        <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor">
                                                                            <path fill-rule="evenodd" d="M16.5 4.478v.227a48.816 48.816 0 0 1 3.878.512.75.75 0 1 1-.256 1.478l-.209-.035-1.005 13.07a3 3 0 0 1-2.991 2.77H8.084a3 3 0 0 1-2.991-2.77L4.087 6.66l-.209.035a.75.75 0 0 1-.256-1.478A48.567 48.567 0 0 1 7.5 4.705v-.227c0-1.564 1.213-2.9 2.816-2.951a52.662 52.662 0 0 1 3.369 0c1.603.051 2.815 1.387 2.815 2.951Zm-6.136-1.452a51.196 51.196 0 0 1 3.273 0C14.39 3.05 15 3.684 15 4.478v.113a49.488 49.488 0 0 0-6 0v-.113c0-.794.609-1.428 1.364-1.452Zm-.355 5.945a.75.75 0 1 0-1.5.058l.347 9a.75.75 0 1 0 1.499-.058l-.346-9Zm5.48.058a.75.75 0 1 0-1.498-.058l-.347 9a.75.75 0 0 0 1.5.058l.345-9Z" clip-rule="evenodd" />
                                                                        </svg>
                                                                    </a>
                                                                </li>
                                                            </ul>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="bg-gray-50 p-3 rounded-xl ring-1 ring-gray-200">
                                            <p class="text-gray-600 mb-4 line-clamp-2"><?php echo htmlspecialchars($row['short_description']); ?></p>
                                            <div class="flex justify-between items-center ">
                                                <span>ราคา:</span>
                                                <span>
                                                    <span class="text-lg font-bold text-gray-900">฿<?php echo number_format($row['base_price'], 2); ?></span>
                                                    <span class="text-sm text-gray-500">/<?php echo htmlspecialchars($row['price_unit']); ?></span>
                                                </span>
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
        </div>
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/flowbite/1.7.0/flowbite.min.js"></script>
    <!-- include toast component -->
    <?php include '../includes/toast.php'; ?>

    <?php if (!empty($message)): ?>
        <script>
            showToast(<?= json_encode($message) ?>, <?= json_encode($messageType) ?>);
        </script>
    <?php endif; ?>
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