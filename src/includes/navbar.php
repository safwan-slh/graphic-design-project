<?php
// ตรวจสอบหน้าปัจจุบัน
// session_start();
if (session_status() === PHP_SESSION_NONE) {
  session_start();
}
$current_page = basename($_SERVER['PHP_SELF']);

$fullname = $_SESSION['fullname'] ?? '';
$initial = mb_strtoupper(mb_substr(trim($fullname), 0, 1, 'UTF-8'), 'UTF-8');

$order_count = 0;
if (isset($_SESSION['customer_id'])) {
  require_once __DIR__ . '/../includes/db_connect.php';
  $cid = $_SESSION['customer_id'];
  $sql = "SELECT COUNT(*) as cnt FROM orders WHERE customer_id=? AND status IN ('pending','in_progress')";
  $stmt = $conn->prepare($sql);
  $stmt->bind_param("i", $cid);
  $stmt->execute();
  $result = $stmt->get_result()->fetch_assoc();
  $order_count = $result['cnt'] ?? 0;
}
// ดึงการแจ้งเตือนล่าสุด 10 รายการ
$notifications = [];
$unreadCount = 0;
if (isset($_SESSION['customer_id'])) {
  $cid = $_SESSION['customer_id'];
  $sql = "SELECT * FROM notifications WHERE customer_id=? AND is_admin=0 ORDER BY created_at DESC LIMIT 10";
  $stmt = $conn->prepare($sql);
  $stmt->bind_param("i", $cid);
  $stmt->execute();
  $result = $stmt->get_result();
  while ($row = $result->fetch_assoc()) {
    $notifications[] = $row;
    if ($row['is_read'] == 0) $unreadCount++;
  }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Navigation</title>
  <link href="https://cdnjs.cloudflare.com/ajax/libs/flowbite/1.7.0/flowbite.min.css" rel="stylesheet" />
  <script src="https://cdn.tailwindcss.com"></script>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Geist+Mono:wght@100..900&display=swap" rel="stylesheet">

  <style>
    .geist-mono {
      font-family: "Geist Mono", monospace;
      font-optical-sizing: auto;
      font-weight: 500;
      font-style: normal;
    }

    .glassmorphism {
      background: rgba(255, 255, 255, 0.1);
      backdrop-filter: blur(20px);
      border-bottom: 1px solid rgba(255, 255, 255, 0.2);
    }
  </style>
</head>

<body>
  <nav class=" fixed top-0 left-0 w-full z-50 transition-all duration-500 ease-in-out will-change-transform will-change-opacity glassmorphism ">
    <div class="flex justify-between items-center px-5 py-3">
      <!-- Logo/Brand -->
      <div class="">
        <a href="/graphic-design/src/client/index.php" class="flex items-center space-x-2">
          <div class="w-10 h-10 bg-zinc-900 rounded-xl flex items-center justify-center">
            <span class="text-white font-bold text-lg">G</span>
          </div>
          <span class="text-xl font-bold text-zinc-900">Graphic-Design</span>
        </a>
      </div>

      <!-- Menu Links -->
      <div class="flex gap-1 rounded-full border-2 p-1 bg-white/50">
        <a href="/graphic-design/src/client/index.php" class="flex items-center geist-mono justify-center px-4 py-2 text-sm font-medium rounded-full transition-all duration-300 ease-in-out hover:scale-105 <?= ($current_page == 'index.php') ? 'bg-zinc-950 text-white' : 'text-zinc-800 hover:bg-zinc-100' ?>">
          Home
        </a>
        <a href="/graphic-design/src/client/portfolios.php" class="flex items-center geist-mono justify-center px-4 py-2 text-sm font-medium rounded-full transition-all duration-300 ease-in-out hover:scale-105 <?= ($current_page == 'portfolios.php') ? 'bg-zinc-950 text-white' : 'text-zinc-800 hover:bg-zinc-100' ?>">
          Portfolio
        </a>
        <a href="/graphic-design/src/client/services.php" class="flex items-center geist-mono justify-center px-4 py-2 text-sm font-medium rounded-full transition-all duration-300 ease-in-out hover:scale-105 <?= ($current_page == 'services.php') ? 'bg-zinc-950 text-white' : 'text-zinc-800 hover:bg-zinc-100' ?>">
          Services
        </a>
        <?php if (isset($_SESSION['customer_id'])): ?>
          <a href="/graphic-design/src/client/order.php" class="flex items-center geist-mono justify-center px-4 py-2 text-sm font-medium rounded-full transition-all duration-300 ease-in-out hover:scale-105 <?= ($current_page == 'order.php') ? 'bg-zinc-950 text-white' : 'text-zinc-800 hover:bg-zinc-100' ?>">
            Order
            <?php if ($order_count > 0): ?>
              <span class="bg-red-500 text-white text-xs font-medium ml-2 px-2.5 py-0.5 rounded-full"><?= $order_count ?></span>
            <?php endif; ?>
          </a>
        <?php endif; ?>
        <a href="#" class="flex items-center geist-mono justify-center px-4 py-2 text-sm font-medium rounded-full transition-all duration-300 ease-in-out hover:scale-105 <?= ($current_page == 'review.php') ? 'bg-zinc-950 text-white' : 'text-zinc-800 hover:bg-zinc-100' ?>">
          Review
        </a>
      </div>

      <!-- Navigation Links -->
      <?php if (isset($_SESSION['customer_id'])): ?>
        <div class="flex gap-1 rounded-full border-2 p-1 bg-white/50">
          <!-- Bell Notification Dropdown -->
          <div class="relative">
            <button id="notifBell" class="w-9 h-9 rounded-full text-zinc-900 hover:bg-zinc-200 flex items-center justify-center text-xs font-bold cursor-pointer hover:scale-105 transition-all duration-300" type="button">
              <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="w-6 h-6">
                <path d="M5.85 3.5a.75.75 0 0 0-1.117-1 9.719 9.719 0 0 0-2.348 4.876.75.75 0 0 0 1.479.248A8.219 8.219 0 0 1 5.85 3.5ZM19.267 2.5a.75.75 0 1 0-1.118 1 8.22 8.22 0 0 1 1.987 4.124.75.75 0 0 0 1.48-.248A9.72 9.72 0 0 0 19.266 2.5Z" />
                <path fill-rule="evenodd" d="M12 2.25A6.75 6.75 0 0 0 5.25 9v.75a8.217 8.217 0 0 1-2.119 5.52.75.75 0 0 0 .298 1.206c1.544.57 3.16.99 4.831 1.243a3.75 3.75 0 1 0 7.48 0 24.583 24.583 0 0 0 4.83-1.244.75.75 0 0 0 .298-1.205 8.217 8.217 0 0 1-2.118-5.52V9A6.75 6.75 0 0 0 12 2.25ZM9.75 18c0-.034 0-.067.002-.1a25.05 25.05 0 0 0 4.496 0l.002.1a2.25 2.25 0 1 1-4.5 0Z" clip-rule="evenodd" />
              </svg>
              <?php if ($unreadCount > 0): ?>
                <span class="absolute top-0 right-0 bg-red-500 text-white text-xs font-bold rounded-full px-1.5 py-0.5"><?= $unreadCount ?></span>
              <?php endif; ?>
            </button>
            <!-- Dropdown -->
            <div id="notifDropdown" class="hidden absolute right-0 mt-2 w-80 bg-white shadow-lg rounded-2xl mb-6 ring-1 ring-gray-200 z-50">
              <div class="border-b bg-gray-50 rounded-t-2xl">
                <h2 class="text-md font-semibold p-2 pl-2 ml-2">แจ้งเตือน</h2>
              </div>
              <ul class="max-h-80 overflow-y-auto p-2">
                <?php if (count($notifications) === 0): ?>
                  <li class="p-4 text-gray-500 text-sm text-center">ไม่มีแจ้งเตือน</li>
                <?php else: ?>
                  <?php foreach ($notifications as $notif): ?>
                    <li class="mb-1">
                      <a href="/graphic-design/src/notifications/read_notification.php?id=<?= $notif['id'] ?>&redirect=<?= urlencode($notif['link']) ?>"
                        class="block px-4 py-3 text-sm rounded-xl <?= $notif['is_read'] ? 'text-gray-400' : 'text-zinc-900 font-medium' ?> bg-zinc-50 hover:bg-zinc-100 transition">
                        <?= $notif['message'] ?>
                        <div class="text-xs text-gray-400 mt-1"><?= date('d/m/Y H:i', strtotime($notif['created_at'])) ?></div>
                      </a>
                    </li>
                  <?php endforeach; ?>
                <?php endif; ?>
              </ul>
            </div>
          </div>
          <button class="w-9 h-9 rounded-full text-zinc-900 hover:bg-zinc-200 flex items-center justify-center text-xs font-bold cursor-pointer hover:scale-105 transition-all duration-300" type="button">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="w-6 h-6">
              <path fill-rule="evenodd" d="M4.804 21.644A6.707 6.707 0 0 0 6 21.75a6.721 6.721 0 0 0 3.583-1.029c.774.182 1.584.279 2.417.279 5.322 0 9.75-3.97 9.75-9 0-5.03-4.428-9-9.75-9s-9.75 3.97-9.75 9c0 2.409 1.025 4.587 2.674 6.192.232.226.277.428.254.543a3.73 3.73 0 0 1-.814 1.686.75.75 0 0 0 .44 1.223ZM8.25 10.875a1.125 1.125 0 1 0 0 2.25 1.125 1.125 0 0 0 0-2.25ZM10.875 12a1.125 1.125 0 1 1 2.25 0 1.125 1.125 0 0 1-2.25 0Zm4.875-1.125a1.125 1.125 0 1 0 0 2.25 1.125 1.125 0 0 0 0-2.25Z" clip-rule="evenodd" />
            </svg>
          </button>
          <button id="dropdownDefaultButton" data-dropdown-toggle="dropdown" class="w-9 h-9 rounded-full bg-gradient-to-r from-zinc-900 to-zinc-900 hover:bg-zinc-600 flex items-center justify-center text-xs font-bold text-zinc-50 cursor-pointer hover:scale-105 transition-all duration-300" type="button">
            <?= $initial ?>
          </button>
          <div id="dropdown" class="z-10 hidden bg-white divide-y rounded-xl shadow-md w-44 border-1.5 border-gray-200 ring-1 ring-gray-200">
            <div class="px-4 py-3 text-sm font-bold text-gray-900">
              <div><?php echo $_SESSION['fullname']; ?></div>
            </div>
            <ul class="space-y-2 p-2">
              <?php if (isset($_SESSION['customer_id']) && $_SESSION['role'] == 'admin'): ?>
                <li>
                  <a href="/graphic-design/src/admin/index.php" class="flex items-center justify-between px-3 py-2 text-sm rounded-lg bg-zinc-50 text-zinc-900 hover:bg-zinc-100 transition-colors duration-200 ring-1 ring-gray-200">
                    ภาพรวมระบบ
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"></path>
                    </svg>
                  </a>
                </li>
              <?php endif; ?>
              <!-- <li>
                <a href="#" class="flex items-center justify-between px-3 py-2 text-sm rounded-lg bg-zinc-50 text-zinc-900 hover:bg-zinc-100 transition-colors duration-200 ring-1 ring-gray-200">
                  ตั้งค่า
                  <svg class="w-5 h-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" strokeWidth={2} stroke="currentColor">
                    <path strokeLinecap="round" stroke-width="2" strokeLinejoin="round" d="M9.594 3.94c.09-.542.56-.94 1.11-.94h2.593c.55 0 1.02.398 1.11.94l.213 1.281c.063.374.313.686.645.87.074.04.147.083.22.127.325.196.72.257 1.075.124l1.217-.456a1.125 1.125 0 0 1 1.37.49l1.296 2.247a1.125 1.125 0 0 1-.26 1.431l-1.003.827c-.293.241-.438.613-.43.992a7.723 7.723 0 0 1 0 .255c-.008.378.137.75.43.991l1.004.827c.424.35.534.955.26 1.43l-1.298 2.247a1.125 1.125 0 0 1-1.369.491l-1.217-.456c-.355-.133-.75-.072-1.076.124a6.47 6.47 0 0 1-.22.128c-.331.183-.581.495-.644.869l-.213 1.281c-.09.543-.56.94-1.11.94h-2.594c-.55 0-1.019-.398-1.11-.94l-.213-1.281c-.062-.374-.312-.686-.644-.87a6.52 6.52 0 0 1-.22-.127c-.325-.196-.72-.257-1.076-.124l-1.217.456a1.125 1.125 0 0 1-1.369-.49l-1.297-2.247a1.125 1.125 0 0 1 .26-1.431l1.004-.827c.292-.24.437-.613.43-.991a6.932 6.932 0 0 1 0-.255c.007-.38-.138-.751-.43-.992l-1.004-.827a1.125 1.125 0 0 1-.26-1.43l1.297-2.247a1.125 1.125 0 0 1 1.37-.491l1.216.456c.356.133.751.072 1.076-.124.072-.044.146-.086.22-.128.332-.183.582-.495.644-.869l.214-1.28Z" />
                    <path strokeLinecap="round" stroke-width="2" strokeLinejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
                  </svg>
                </a>
              </li> -->
              <li>
                <a href="/graphic-design/src/auth/logout.php" class=" flex items-center justify-between px-3 py-2 text-sm rounded-lg bg-zinc-50 text-red-600 hover:bg-zinc-100 transition-colors duration-200 ring-1 ring-gray-200">
                  ออกจากระบบ
                  <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="w-5 h-5">
                    <path fill-rule="evenodd" d="M7.5 3.75A1.5 1.5 0 0 0 6 5.25v13.5a1.5 1.5 0 0 0 1.5 1.5h6a1.5 1.5 0 0 0 1.5-1.5V15a.75.75 0 0 1 1.5 0v3.75a3 3 0 0 1-3 3h-6a3 3 0 0 1-3-3V5.25a3 3 0 0 1 3-3h6a3 3 0 0 1 3 3V9A.75.75 0 0 1 15 9V5.25a1.5 1.5 0 0 0-1.5-1.5h-6Zm10.72 4.72a.75.75 0 0 1 1.06 0l3 3a.75.75 0 0 1 0 1.06l-3 3a.75.75 0 1 1-1.06-1.06l1.72-1.72H9a.75.75 0 0 1 0-1.5h10.94l-1.72-1.72a.75.75 0 0 1 0-1.06Z" clip-rule="evenodd" />
                  </svg>
                </a>
              </li>
            </ul>
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
  <script>
    document.getElementById('notifBell').addEventListener('click', function(e) {
      e.stopPropagation();
      document.getElementById('notifDropdown').classList.toggle('hidden');
    });
    document.addEventListener('click', function(e) {
      document.getElementById('notifDropdown').classList.add('hidden');
    });
  </script>
</body>

</html>