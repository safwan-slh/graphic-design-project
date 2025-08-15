<?php
// ตรวจสอบหน้าปัจจุบัน
session_start();
$current_page = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Navigation</title>
  <link href="https://cdnjs.cloudflare.com/ajax/libs/flowbite/1.7.0/flowbite.min.css" rel="stylesheet" />
  <link href="../../dist/output.css" rel="stylesheet" />

  </style>
</head>

<body>
  <nav class=" fixed top-0 left-0 w-full z-50 transition-all duration-500 ease-in-out will-change-transform will-change-opacity bg-white/50 backdrop-blur-md">
    <div class="flex justify-between items-center px-5 py-1">
      <!-- Logo/Brand -->
      <div class="">
        <a href="/graphic-design/src/client/index.php" class="flex items-center space-x-2">
          <span class="text-xl font-bold text-zinc-900">Graphic-Design</span>
        </a>
      </div>

      <!-- Menu Links -->
      <div class="flex gap-1 rounded-full border-2 p-1 bg-white/50">
        <a href="/graphic-design/src/client/index.php" class="flex items-center justify-center px-4 py-2 text-sm font-medium rounded-full transition-all duration-300 ease-in-out hover:scale-105 <?= ($current_page == 'index.php') ? 'bg-zinc-950 text-white' : 'text-zinc-800 hover:bg-zinc-100' ?>">
          Home
        </a>
        <a href="#" class="flex items-center justify-center px-4 py-2 text-sm font-medium  rounded-full  transition-all duration-300 ease-in-out hover:scale-105 <?= ($current_page == 'portfolio.php') ? 'bg-zinc-950 text-white' : 'text-zinc-800 hover:bg-zinc-100' ?>">
          Portfolio
        </a>
        <a href="#" class="flex items-center justify-center px-4 py-2 text-sm font-medium rounded-full transition-all duration-300 ease-in-out hover:scale-105 <?= ($current_page == 'services.php') ? 'bg-zinc-950 text-white' : 'text-zinc-800 hover:bg-zinc-100' ?>">
          Services
        </a>
        <?php if (isset($_SESSION['customer_id'])): ?>
          <a href="#" class="flex items-center justify-center px-4 py-2 text-sm font-medium rounded-full transition-all duration-300 ease-in-out hover:scale-105 <?= ($current_page == 'order.php') ? 'bg-zinc-950 text-white' : 'text-zinc-800 hover:bg-zinc-100' ?>">
            Order
          </a>
        <?php endif; ?>
        <a href="#" class="flex items-center justify-center px-4 py-2 text-sm font-medium rounded-full transition-all duration-300 ease-in-out hover:scale-105 <?= ($current_page == 'review.php') ? 'bg-zinc-950 text-white' : 'text-zinc-800 hover:bg-zinc-100' ?>">
          Review
        </a>
      </div>

      <!-- Navigation Links -->
      <?php if (isset($_SESSION['customer_id'])): ?>
        <div class="flex gap-1 rounded-full border-2 p-1 bg-white/50">
          <button class="w-9 h-9 rounded-full text-zinc-600 hover:bg-zinc-200 flex items-center justify-center text-xs font-bold cursor-pointer hover:scale-105 transition-all duration-300" type="button">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5">
              <path stroke-linecap="round" stroke-linejoin="round" d="M14.857 17.082a23.848 23.848 0 0 0 5.454-1.31A8.967 8.967 0 0 1 18 9.75V9A6 6 0 0 0 6 9v.75a8.967 8.967 0 0 1-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 0 1-5.714 0m5.714 0a3 3 0 1 1-5.714 0" />
            </svg>
          </button>
          <button class="w-9 h-9 rounded-full text-zinc-600 hover:bg-zinc-200 flex items-center justify-center text-xs font-bold cursor-pointer hover:scale-105 transition-all duration-300" type="button">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5">
              <path stroke-linecap="round" stroke-linejoin="round" d="M8.625 12a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm0 0H8.25m4.125 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm0 0H12m4.125 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm0 0h-.375M21 12c0 4.556-4.03 8.25-9 8.25a9.764 9.764 0 0 1-2.555-.337A5.972 5.972 0 0 1 5.41 20.97a5.969 5.969 0 0 1-.474-.065 4.48 4.48 0 0 0 .978-2.025c.09-.457-.133-.901-.467-1.226C3.93 16.178 3 14.189 3 12c0-4.556 4.03-8.25 9-8.25s9 3.694 9 8.25Z" />
            </svg>
          </button>
          <button id="dropdownDefaultButton" data-dropdown-toggle="dropdown" class="w-9 h-9 rounded-full bg-zinc-950 hover:bg-zinc-600 flex items-center justify-center text-xs font-bold text-zinc-50 cursor-pointer hover:scale-105 transition-all duration-300" type="button">
            H
          </button>
          <div id="dropdown" class="z-10 hidden bg-white divide-y rounded-xl shadow-md w-44 border-1.5 border-gray-200">
            <div class="px-4 py-3 text-sm text-gray-900">
              <div><?php echo $_SESSION['fullname']; ?></div>
            </div>
            <ul class="space-y-1 p-2">
              <?php if (isset($_SESSION['customer_id']) && $_SESSION['role'] == 'admin'): ?>
                <li>
                  <a href="#" class="flex items-center px-3 py-2 text-sm rounded-lg transition-colors duration-200 <?= ($current_page == 'dashboard.php') ? 'bg-zinc-200 text-zinc-900 font-medium active-menu' : 'text-gray-700 hover:bg-gray-100' ?>">
                    <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"></path>
                    </svg>
                    ภาพรวมระบบ
                  </a>
                </li>
              <?php endif; ?>
              <li>
                <a href="#" class="flex items-center px-3 py-2 text-sm rounded-lg transition-colors duration-200 <?= ($current_page == 'dashboard.php') ? 'bg-zinc-200 text-zinc-900 font-medium active-menu' : 'text-gray-700 hover:bg-gray-100' ?>">
                  <svg class="w-5 h-5 mr-3" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" strokeWidth={2} stroke="currentColor">
                    <path strokeLinecap="round" stroke-width="2" strokeLinejoin="round" d="M9.594 3.94c.09-.542.56-.94 1.11-.94h2.593c.55 0 1.02.398 1.11.94l.213 1.281c.063.374.313.686.645.87.074.04.147.083.22.127.325.196.72.257 1.075.124l1.217-.456a1.125 1.125 0 0 1 1.37.49l1.296 2.247a1.125 1.125 0 0 1-.26 1.431l-1.003.827c-.293.241-.438.613-.43.992a7.723 7.723 0 0 1 0 .255c-.008.378.137.75.43.991l1.004.827c.424.35.534.955.26 1.43l-1.298 2.247a1.125 1.125 0 0 1-1.369.491l-1.217-.456c-.355-.133-.75-.072-1.076.124a6.47 6.47 0 0 1-.22.128c-.331.183-.581.495-.644.869l-.213 1.281c-.09.543-.56.94-1.11.94h-2.594c-.55 0-1.019-.398-1.11-.94l-.213-1.281c-.062-.374-.312-.686-.644-.87a6.52 6.52 0 0 1-.22-.127c-.325-.196-.72-.257-1.076-.124l-1.217.456a1.125 1.125 0 0 1-1.369-.49l-1.297-2.247a1.125 1.125 0 0 1 .26-1.431l1.004-.827c.292-.24.437-.613.43-.991a6.932 6.932 0 0 1 0-.255c.007-.38-.138-.751-.43-.992l-1.004-.827a1.125 1.125 0 0 1-.26-1.43l1.297-2.247a1.125 1.125 0 0 1 1.37-.491l1.216.456c.356.133.751.072 1.076-.124.072-.044.146-.086.22-.128.332-.183.582-.495.644-.869l.214-1.28Z" />
                    <path strokeLinecap="round" stroke-width="2" strokeLinejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
                  </svg>
                  ตั้งค่า
                </a>
              </li>
            </ul>
            <div class="py-2 px-2">
              <ul class="space-y-1">
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
          </div>
        </div>
      <?php else: ?>
        <div class="flex gap-1 rounded-full border-2 p-1 bg-white/50">
          <a href="/graphic-design/src/auth/signin.php" class="flex items-center justify-center px-4 py-2 text-sm font-medium bg-zinc-950 text-zinc-50 rounded-full hover:bg-zinc-800 transition-all duration-300 ease-in-out hover:scale-105">
            Sign In
          </a>
          <a href="/graphic-design/src/auth/signup.php" class="flex items-center justify-center px-4 py-2 text-sm font-medium rounded-full text-zinc-800 hover:bg-zinc-100 transition-all duration-300 ease-in-out hover:scale-105">
            Sign Up
          </a>
        </div>
      <?php endif; ?>
    </div>
    </div>
  </nav>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/flowbite/1.7.0/flowbite.min.js"></script>
</body>

</html>