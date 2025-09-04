<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
$current_page = basename($_SERVER['PHP_SELF']); ?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Admin Panel</title>
  <link href="https://cdnjs.cloudflare.com/ajax/libs/flowbite/1.7.0/flowbite.min.css" rel="stylesheet" />
  <link href="../../dist/output.css" rel="stylesheet" />
  <style>
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

<body>
  <div class="flex">
    <!-- Sidebar -->
    <div class="w-64 h-screen bg-zinc-50 border-r border-gray-200 fixed overflow-y-auto sidebar">
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
              <li>
                <a href="service_add.php" class="flex items-center px-3 py-2 text-sm rounded-lg transition-colors duration-200 <?= ($current_page == 'service_add.php') ? 'bg-zinc-200 text-zinc-900 font-medium active-menu' : 'text-gray-700 hover:bg-gray-100' ?>">
                  <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                  </svg>
                  เพิ่มบริการใหม่
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
              <li>
                <a href="customer_add.php" class="flex items-center px-3 py-2 text-sm rounded-lg transition-colors duration-200 <?= ($current_page == 'customer_add.php') ? 'bg-zinc-200 text-zinc-900 font-medium active-menu' : 'text-gray-700 hover:bg-gray-100' ?>">
                  <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                  </svg>
                  เพิ่มผู้ใช้ใหม่
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
              <li>
                <a href="portfolio_add.php" class="flex items-center px-3 py-2 text-sm rounded-lg transition-colors duration-200 <?= ($current_page == 'portfolio_add.php') ? 'bg-zinc-200 text-zinc-900 font-medium active-menu' : 'text-gray-700 hover:bg-gray-100' ?>">
                  <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                  </svg>
                  เพิ่มผลงานใหม่
                </a>
              </li>
            </ul>
          </div>
          <!-- Divider -->
          <div class="border-t border-gray-200 my-4"></div>
          <!-- Logout -->
          <div>
            <ul class="space-y-1">
              <li>
                <a href="/graphic-design/src/client/index.php" class="flex items-center px-3 py-2 text-sm rounded-lg text-gray-700 hover:bg-gray-100 transition-colors duration-200">
                  <svg class="w-5 h-5 mr-3" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="size-6">
                    <path stroke-linecap="round" stroke-linejoin="round" d="m2.25 12 8.954-8.955c.44-.439 1.152-.439 1.591 0L21.75 12M4.5 9.75v10.125c0 .621.504 1.125 1.125 1.125H9.75v-4.875c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21h4.125c.621 0 1.125-.504 1.125-1.125V9.75M8.25 21h8.25" />
                  </svg>
                  หน้าหลัก
                </a>
              </li>
              <li>
                <a href="/graphic-design/src/auth/logout.php" class="flex items-center px-3 py-2 text-sm rounded-lg text-red-600 hover:bg-red-50 transition-colors duration-200">
                  <svg class="w-5 h-5 mr-3" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                    <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 12H4m12 0-4 4m4-4-4-4m3-4h2a3 3 0 0 1 3 3v10a3 3 0 0 1-3 3h-2" />
                  </svg>
                  ออกจากระบบ
                </a>
              </li>
            </ul>
          </div>
        </nav>
      </div>
    </div>
  </div>
</body>

</html>