<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
$current_page = basename($_SERVER['PHP_SELF']);
// เชื่อมต่อฐานข้อมูล
require_once __DIR__ . '/../includes/db_connect.php';

// ดึงจำนวน payment ที่ pending
$sqlPending = "SELECT COUNT(*) AS pending_count FROM payments WHERE payment_status = 'pending'";
$resultPending = $conn->query($sqlPending);
$pendingCount = ($resultPending && $row = $resultPending->fetch_assoc()) ? (int)$row['pending_count'] : 0;

// ดึงจำนวนออเดอร์ที่ pending และ in_progress
$sqlOrderPending = "SELECT COUNT(*) AS order_pending FROM orders WHERE status = 'pending'";
$resultOrderPending = $conn->query($sqlOrderPending);
$orderPending = ($resultOrderPending && $row = $resultOrderPending->fetch_assoc()) ? (int)$row['order_pending'] : 0;

$sqlOrderInProgress = "SELECT COUNT(*) AS order_inprogress FROM orders WHERE status = 'in_progress'";
$resultOrderInProgress = $conn->query($sqlOrderInProgress);
$orderInProgress = ($resultOrderInProgress && $row = $resultOrderInProgress->fetch_assoc()) ? (int)$row['order_inprogress'] : 0;

// รวมจำนวนที่ต้อง action
$orderBadge = $orderPending + $orderInProgress;
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Admin Panel</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <link href="https://fonts.googleapis.com/css2?family=IBM+Plex+Sans+Thai:wght@300;400;500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <style>
    .font-thai {
      font-family: 'IBM Plex Sans Thai', sans-serif;
    }

    .active-menu {
      position: relative;
    }

    .active-menu:after {
      content: '';
      position: absolute;
      left: 0;
      top: 0;
      height: 100%;
      width: 3px;
      background-color: #1c1c1f;
      border-radius: 0 3px 3px 0;
    }
  </style>
</head>

<body class="font-thai">
  <div class="flex">
    <!-- Sidebar -->
    <div class="w-64 h-screen bg-white border-r border-gray-200 fixed overflow-y-auto sidebar">
      <div class="p-4">
        <!-- Logo/Branding -->
        <!-- ปรับโลโก้ให้มีความทันสมัยมากขึ้น -->
        <div class="flex items-center justify-center mb-8 p-4 bg-gradient-to-r from-zinc-700 to-zinc-800 rounded-lg shadow">
          <h1 class="text-xl font-bold text-white uppercase tracking-wider flex items-center">
            <svg class="w-6 h-6 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
            </svg>
            Admin Panel
          </h1>
        </div>

        <nav class="space-y-2">
          <!-- Dashboard Section -->
          <div>
            <h2 class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1 px-3">แดชบอร์ด</h2>
            <ul class="space-y-1">
              <li>
                <a href="/graphic-design/src/admin/index.php" class="flex items-center px-3 py-2 text-sm rounded-lg transition-colors duration-200 <?= ($current_page == 'index.php') ? 'bg-zinc-200 text-zinc-900 font-medium active-menu' : 'text-gray-700 hover:bg-gray-100' ?>">
                  <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"></path>
                  </svg>
                  ภาพรวมระบบ
                </a>
              </li>
            </ul>
          </div>
          <!-- Payment Section -->
          <div>
            <h2 class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1 px-3">จัดการชำระเงิน</h2>
            <ul class="space-y-1">
              <li>
                <a href="payment_list.php" class="flex items-center px-3 py-2 text-sm rounded-lg transition-colors duration-200 <?= ($current_page == 'payment_list.php') ? 'bg-zinc-200 text-zinc-900 font-medium active-menu' : 'text-gray-700 hover:bg-gray-100' ?>">
                  <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-5 h-5 mr-3">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 8.25h19.5M2.25 9h19.5m-16.5 5.25h6m-6 2.25h3m-3.75 3h15a2.25 2.25 0 0 0 2.25-2.25V6.75A2.25 2.25 0 0 0 19.5 4.5h-15a2.25 2.25 0 0 0-2.25 2.25v10.5A2.25 2.25 0 0 0 4.5 19.5Z" />
                  </svg>
                  รายการชำระเงิน
                  <?php if ($pendingCount > 0): ?>
                    <span class="bg-red-500 text-white text-xs font-medium ml-2 px-2.5 py-0.5 rounded-full"><?= $pendingCount ?></span>
                  <?php endif; ?>
                </a>
              </li>
            </ul>
          </div>

          <!-- Order Section -->
          <div>
            <h2 class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1 px-3">จัดการออเดอร์</h2>
            <ul class="space-y-1">
              <li>
                <a href="order_list.php" class="flex items-center px-3 py-2 text-sm rounded-lg transition-colors duration-200 <?= ($current_page == 'order_list.php') ? 'bg-zinc-200 text-zinc-900 font-medium active-menu' : 'text-gray-700 hover:bg-gray-100' ?>">
                  <svg class="w-5 h-5 mr-3" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="m21 7.5-9-5.25L3 7.5m18 0-9 5.25m9-5.25v9l-9 5.25M3 7.5l9 5.25M3 7.5v9l9 5.25m0-9v9" />
                  </svg>
                  รายการออเดอร์
                  <?php if ($orderBadge > 0): ?>
                    <span class="bg-red-500 text-white text-xs font-medium ml-2 px-2.5 py-0.5 rounded-full"><?= $orderBadge ?></span>
                  <?php endif; ?>
                </a>
              </li>
            </ul>
          </div>

          <!-- Services Section -->
          <div>
            <h2 class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1 px-3">จัดการบริการ</h2>
            <ul class="space-y-1">
              <li>
                <a href="service_list.php" class="flex items-center px-3 py-2 text-sm rounded-lg transition-colors duration-200 <?= ($current_page == 'service_list.php') ? 'bg-zinc-200 text-zinc-900 font-medium active-menu' : 'text-gray-700 hover:bg-gray-100' ?>">
                  <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path>
                  </svg>
                  รายการบริการ
                </a>
              </li>
            </ul>
          </div>

          <!-- Review Section -->
          <div>
            <h2 class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1 px-3">จัดการรีวิว</h2>
            <ul class="space-y-1">
              <li>
                <a href="review_list.php" class="flex items-center px-3 py-2 text-sm rounded-lg transition-colors duration-200 <?= ($current_page == 'review_list.php') ? 'bg-zinc-200 text-zinc-900 font-medium active-menu' : 'text-gray-700 hover:bg-gray-100' ?>">
                  <svg class="w-5 h-5 mr-3" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-width="2" stroke-linejoin="round" d="M11.48 3.499a.562.562 0 0 1 1.04 0l2.125 5.111a.563.563 0 0 0 .475.345l5.518.442c.499.04.701.663.321.988l-4.204 3.602a.563.563 0 0 0-.182.557l1.285 5.385a.562.562 0 0 1-.84.61l-4.725-2.885a.562.562 0 0 0-.586 0L6.982 20.54a.562.562 0 0 1-.84-.61l1.285-5.386a.562.562 0 0 0-.182-.557l-4.204-3.602a.562.562 0 0 1 .321-.988l5.518-.442a.563.563 0 0 0 .475-.345L11.48 3.5Z" />
                  </svg>
                  รายการรีวิว
                </a>
              </li>
            </ul>
          </div>

          <!-- Users Section -->
          <div>
            <h2 class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1 px-3">จัดการผู้ใช้</h2>
            <ul class="space-y-1">
              <li>
                <a href="customer_list.php" class="flex items-center px-3 py-2 text-sm rounded-lg transition-colors duration-200 <?= ($current_page == 'customer_list.php') ? 'bg-zinc-200 text-zinc-900 font-medium active-menu' : 'text-gray-700 hover:bg-gray-100' ?>">
                  <svg class="w-5 h-5 mr-3" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M18 18.72a9.094 9.094 0 0 0 3.741-.479 3 3 0 0 0-4.682-2.72m.94 3.198.001.031c0 .225-.012.447-.037.666A11.944 11.944 0 0 1 12 21c-2.17 0-4.207-.576-5.963-1.584A6.062 6.062 0 0 1 6 18.719m12 0a5.971 5.971 0 0 0-.941-3.197m0 0A5.995 5.995 0 0 0 12 12.75a5.995 5.995 0 0 0-5.058 2.772m0 0a3 3 0 0 0-4.681 2.72 8.986 8.986 0 0 0 3.74.477m.94-3.197a5.971 5.971 0 0 0-.94 3.197M15 6.75a3 3 0 1 1-6 0 3 3 0 0 1 6 0Zm6 3a2.25 2.25 0 1 1-4.5 0 2.25 2.25 0 0 1 4.5 0Zm-13.5 0a2.25 2.25 0 1 1-4.5 0 2.25 2.25 0 0 1 4.5 0Z" />
                  </svg>
                  รายการผู้ใช้
                </a>
              </li>
            </ul>
          </div>
          <!-- Portfolio Section -->
          <div>
            <h2 class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1 px-3">จัดการผลงานร้าน</h2>
            <ul class="space-y-1">
              <li>
                <a href="portfolio_list.php" class="flex items-center px-3 py-2 text-sm rounded-lg transition-colors duration-200 <?= ($current_page == 'portfolio_list.php') ? 'bg-zinc-200 text-zinc-900 font-medium active-menu' : 'text-gray-700 hover:bg-gray-100' ?>">
                  <svg class="w-5 h-5 mr-3" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="m20.25 7.5-.625 10.632a2.25 2.25 0 0 1-2.247 2.118H6.622a2.25 2.25 0 0 1-2.247-2.118L3.75 7.5M10 11.25h4M3.375 7.5h17.25c.621 0 1.125-.504 1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125H3.375c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125Z" />
                  </svg>

                  รายการผลงานร้าน
                </a>
              </li>
            </ul>
          </div>
          <!-- Divider -->
          <div class="border-t border-gray-200 my-4"></div>
        </nav>
      </div>
    </div>
  </div>
</body>

</html>