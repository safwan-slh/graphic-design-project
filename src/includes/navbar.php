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
  // นับเฉพาะแจ้งเตือนที่ไม่ใช่ chat
  $sql = "SELECT COUNT(*) as cnt FROM notifications WHERE customer_id=? AND is_admin=0 AND is_read=0 AND type != 'chat'";
  $stmt = $conn->prepare($sql);
  $stmt->bind_param("i", $cid);
  $stmt->execute();
  $result = $stmt->get_result()->fetch_assoc();
  $unreadCount = $result['cnt'] ?? 0;

  $sql = "SELECT * FROM notifications WHERE customer_id=? AND is_admin=0 AND type != 'chat' ORDER BY created_at DESC LIMIT 10";
  $stmt = $conn->prepare($sql);
  $stmt->bind_param("i", $cid);
  $stmt->execute();
  $result = $stmt->get_result();
  while ($row = $result->fetch_assoc()) {
    $notifications[] = $row;
  }
}
// นับข้อความแชทที่ยังไม่ได้อ่าน
$unreadChatCount = 0;
if (isset($_SESSION['customer_id'])) {
  $cid = $_SESSION['customer_id'];
  // นับแชทออเดอร์ที่ยังไม่ได้อ่าน
  $sql = "SELECT COUNT(*) as cnt FROM chat_messages WHERE order_id IN (SELECT order_id FROM orders WHERE customer_id=?) AND sender_role='admin' AND is_read=0";
  $stmt = $conn->prepare($sql);
  $stmt->bind_param("i", $cid);
  $stmt->execute();
  $result = $stmt->get_result()->fetch_assoc();
  $unreadChatCount = $result['cnt'] ?? 0;
  $stmt->close();

  // นับแชททั่วไปที่ยังไม่ได้อ่าน
  $sql = "SELECT COUNT(*) as cnt FROM chat_messages WHERE (order_id IS NULL OR order_id=0) AND customer_id=? AND sender_role='admin' AND is_read=0";
  $stmt = $conn->prepare($sql);
  $stmt->bind_param("i", $cid);
  $stmt->execute();
  $result = $stmt->get_result()->fetch_assoc();
  $unreadChatCount += $result['cnt'] ?? 0;
  $stmt->close();
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
        <a href="review.php" class="flex items-center geist-mono justify-center px-4 py-2 text-sm font-medium rounded-full transition-all duration-300 ease-in-out hover:scale-105 <?= ($current_page == 'review.php') ? 'bg-zinc-950 text-white' : 'text-zinc-800 hover:bg-zinc-100' ?>">
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
                <span class="absolute top-0 -right-2 bg-red-500 text-white text-xs font-bold rounded-full px-1.5 py-0.5"><?= $unreadCount ?></span>
              <?php endif; ?>
            </button>
            <!-- Dropdown -->
            <div id="notifDropdown" class="hidden absolute right-0 mt-2 w-96 bg-white shadow-lg rounded-2xl mb-6 ring-1 ring-gray-200 z-50">
              <div class="border-b bg-gray-50 rounded-t-2xl">
                <h2 class="text-md font-semibold p-2 pl-2 ml-2">แจ้งเตือน</h2>
              </div>
              <?php
              // Mapping type กับ SVG หรือ class ไอคอน
              $notifIcons = [
                'payment' => '
                  <div class="w-10 h-10 text-green-600 bg-green-100 ring-1 ring-green-200 mr-4 rounded-lg flex items-center justify-center">       
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="w-5 h-5">
                      <path d="M12 7.5a2.25 2.25 0 1 0 0 4.5 2.25 2.25 0 0 0 0-4.5Z" />
                      <path fill-rule="evenodd" d="M1.5 4.875C1.5 3.839 2.34 3 3.375 3h17.25c1.035 0 1.875.84 1.875 1.875v9.75c0 1.036-.84 1.875-1.875 1.875H3.375A1.875 1.875 0 0 1 1.5 14.625v-9.75ZM8.25 9.75a3.75 3.75 0 1 1 7.5 0 3.75 3.75 0 0 1-7.5 0ZM18.75 9a.75.75 0 0 0-.75.75v.008c0 .414.336.75.75.75h.008a.75.75 0 0 0 .75-.75V9.75a.75.75 0 0 0-.75-.75h-.008ZM4.5 9.75A.75.75 0 0 1 5.25 9h.008a.75.75 0 0 1 .75.75v.008a.75.75 0 0 1-.75.75H5.25a.75.75 0 0 1-.75-.75V9.75Z" clip-rule="evenodd" />
                      <path d="M2.25 18a.75.75 0 0 0 0 1.5c5.4 0 10.63.722 15.6 2.075 1.19.324 2.4-.558 2.4-1.82V18.75a.75.75 0 0 0-.75-.75H2.25Z" />
                    </svg>
                  </div>
                ',
                'payment_update' => '
                  <div class="w-10 h-10 text-green-600 bg-green-100 ring-1 ring-green-200 mr-4 rounded-lg flex items-center justify-center">       
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="w-5 h-5">
                      <path d="M12 7.5a2.25 2.25 0 1 0 0 4.5 2.25 2.25 0 0 0 0-4.5Z" />
                      <path fill-rule="evenodd" d="M1.5 4.875C1.5 3.839 2.34 3 3.375 3h17.25c1.035 0 1.875.84 1.875 1.875v9.75c0 1.036-.84 1.875-1.875 1.875H3.375A1.875 1.875 0 0 1 1.5 14.625v-9.75ZM8.25 9.75a3.75 3.75 0 1 1 7.5 0 3.75 3.75 0 0 1-7.5 0ZM18.75 9a.75.75 0 0 0-.75.75v.008c0 .414.336.75.75.75h.008a.75.75 0 0 0 .75-.75V9.75a.75.75 0 0 0-.75-.75h-.008ZM4.5 9.75A.75.75 0 0 1 5.25 9h.008a.75.75 0 0 1 .75.75v.008a.75.75 0 0 1-.75.75H5.25a.75.75 0 0 1-.75-.75V9.75Z" clip-rule="evenodd" />
                      <path d="M2.25 18a.75.75 0 0 0 0 1.5c5.4 0 10.63.722 15.6 2.075 1.19.324 2.4-.558 2.4-1.82V18.75a.75.75 0 0 0-.75-.75H2.25Z" />
                    </svg>
                  </div>
                ',
                'payment_rejected' => '
                  <div class="w-10 h-10 text-red-600 bg-red-100 ring-1 ring-red-200 mr-4 rounded-lg flex items-center justify-center">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="w-5 h-5">
                      <path fill-rule="evenodd" d="M12 2.25c-5.385 0-9.75 4.365-9.75 9.75s4.365 9.75 9.75 9.75 9.75-4.365 9.75-9.75S17.385 2.25 12 2.25Zm-1.72 6.97a.75.75 0 1 0-1.06 1.06L10.94 12l-1.72 1.72a.75.75 0 1 0 1.06 1.06L12 13.06l1.72 1.72a.75.75 0 1 0 1.06-1.06L13.06 12l1.72-1.72a.75.75 0 1 0-1.06-1.06L12 10.94l-1.72-1.72Z" clip-rule="evenodd" />
                    </svg>
                  </div>
                ',
                'order'   => '
                  <div class="w-10 h-10 text-blue-600 bg-blue-100 ring-1 ring-blue-200 mr-4 rounded-lg flex items-center justify-center">       
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="w-5 h-5">
                      <path d="M12.378 1.602a.75.75 0 0 0-.756 0L3 6.632l9 5.25 9-5.25-8.622-5.03ZM21.75 7.93l-9 5.25v9l8.628-5.032a.75.75 0 0 0 .372-.648V7.93ZM11.25 22.18v-9l-9-5.25v8.57a.75.75 0 0 0 .372.648l8.628 5.033Z" />
                    </svg>
                  </div>
                ',
                'workfile' => '
                  <div class="w-10 h-10 text-orange-600 bg-orange-100 ring-1 ring-orange-200 mr-4 rounded-lg flex items-center justify-center">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="w-5 h-5">
                      <path fill-rule="evenodd" d="M19.5 21a3 3 0 0 0 3-3V9a3 3 0 0 0-3-3h-5.379a.75.75 0 0 1-.53-.22L11.47 3.66A2.25 2.25 0 0 0 9.879 3H4.5a3 3 0 0 0-3 3v12a3 3 0 0 0 3 3h15Zm-6.75-10.5a.75.75 0 0 0-1.5 0v4.19l-1.72-1.72a.75.75 0 0 0-1.06 1.06l3 3a.75.75 0 0 0 1.06 0l3-3a.75.75 0 1 0-1.06-1.06l-1.72 1.72V10.5Z" clip-rule="evenodd" />
                    </svg>
                  </div>
                ',
                'comment' => '
                  <div class="w-10 h-10 text-purple-600 bg-purple-100 ring-1 ring-purple-200 mr-4 rounded-lg flex items-center justify-center">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="w-5 h-5">
                      <path d="M4.913 2.658c2.075-.27 4.19-.408 6.337-.408 2.147 0 4.262.139 6.337.408 1.922.25 3.291 1.861 3.405 3.727a4.403 4.403 0 0 0-1.032-.211 50.89 50.89 0 0 0-8.42 0c-2.358.196-4.04 2.19-4.04 4.434v4.286a4.47 4.47 0 0 0 2.433 3.984L7.28 21.53A.75.75 0 0 1 6 21v-4.03a48.527 48.527 0 0 1-1.087-.128C2.905 16.58 1.5 14.833 1.5 12.862V6.638c0-1.97 1.405-3.718 3.413-3.979Z" />
                      <path d="M15.75 7.5c-1.376 0-2.739.057-4.086.169C10.124 7.797 9 9.103 9 10.609v4.285c0 1.507 1.128 2.814 2.67 2.94 1.243.102 2.5.157 3.768.165l2.782 2.781a.75.75 0 0 0 1.28-.53v-2.39l.33-.026c1.542-.125 2.67-1.433 2.67-2.94v-4.286c0-1.505-1.125-2.811-2.664-2.94A49.392 49.392 0 0 0 15.75 7.5Z" />
                    </svg>
                  </div>
                ',
                'review'  => '
                  <div class="w-10 h-10 text-yellow-600 bg-yellow-100 ring-1 ring-yellow-200 mr-4 rounded-lg flex items-center justify-center">       
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="w-5 h-5 text-yellow-400">
                      <path fill-rule="evenodd" d="M10.788 3.21c.448-1.077 1.976-1.077 2.424 0l2.082 5.006 5.404.434c1.164.093 1.636 1.545.749 2.305l-4.117 3.527 1.257 5.273c.271 1.136-.964 2.033-1.96 1.425L12 18.354 7.373 21.18c-.996.608-2.231-.29-1.96-1.425l1.257-5.273-4.117-3.527c-.887-.76-.415-2.212.749-2.305l5.404-.434 2.082-5.005Z" clip-rule="evenodd" />
                    </svg>
                  </div>
                ',
                'chat'    => '
                  <div class="w-10 h-10 text-purple-600 bg-purple-100 ring-1 ring-purple-200 mr-4 rounded-lg flex items-center justify-center">       
                    <svg xmlns="http://www.w3.org/2000/svg" fill="currentColor" class="w-5 h-5" viewBox="0 0 24 24">
                      <path d="M4 19.5V17A2.5 2.5 0 0 1 6.5 14.5h11A2.5 2.5 0 0 1 20 17v2.5"/>
                      <circle cx="12" cy="10" r="6"/>
                    </svg>
                  </div>
                ',
                'general' => '
                  <div class="w-10 h-10 text-gray-600 bg-gray-100 ring-1 ring-gray-200 mr-4 rounded-lg flex items-center justify-center">       
                    <svg xmlns="http://www.w3.org/2000/svg" fill="currentColor" class="w-5 h-5" viewBox="0 0 24 24">
                      <circle cx="12" cy="12" r="10"/>
                      <text x="12" y="16" text-anchor="middle" font-size="10" fill="#fff">i</text>
                    </svg>
                  </div>
                ',
              ];
              ?>
              <ul class="max-h-80 overflow-y-auto p-2">
                <?php if (count($notifications) === 0): ?>
                  <li class="p-4 text-gray-500 text-sm text-center">ไม่มีแจ้งเตือน</li>
                <?php else: ?>
                  <?php foreach ($notifications as $notif): ?>
                    <?php
                    $icon = $notifIcons[$notif['type']] ?? $notifIcons['general'];
                    ?>
                    <li class="mb-1">
                      <a href="/graphic-design/src/notifications/read_notification.php?id=<?= $notif['id'] ?>&redirect=<?= urlencode($notif['link']) ?>"
                        class="block px-4 py-3 text-sm rounded-xl <?= $notif['is_read'] ? 'text-gray-400' : 'text-zinc-900 font-medium' ?> bg-zinc-50 hover:bg-zinc-100 transition flex items-center">
                        <div class="flex items-center space-x-3">
                          <!-- <div class="w-10 h-10 bg-zinc-900 ring-1 ring-gray-200 mr-4 rounded-lg flex items-center justify-center">
                            </div> -->
                            <?= $icon ?>
                        </div>
                        <div class="flex flex-col w-full">
                          <span><?= $notif['message'] ?></span>
                          <div class="text-xs text-gray-400 mt-1 w-full"><?= date('d/m/Y H:i', strtotime($notif['created_at'])) ?></div>
                        </div>
                      </a>
                    </li>
                  <?php endforeach; ?>
                <?php endif; ?>
              </ul>
            </div>
          </div>
          <div class="relative" id="openChatModalBtn">
            <button class="w-9 h-9 rounded-full text-zinc-900 hover:bg-zinc-200 flex items-center justify-center text-xs font-bold cursor-pointer hover:scale-105 transition-all duration-300" type="button">
              <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="w-6 h-6">
                <path fill-rule="evenodd" d="M4.804 21.644A6.707 6.707 0 0 0 6 21.75a6.721 6.721 0 0 0 3.583-1.029c.774.182 1.584.279 2.417.279 5.322 0 9.75-3.97 9.75-9 0-5.03-4.428-9-9.75-9s-9.75 3.97-9.75 9c0 2.409 1.025 4.587 2.674 6.192.232.226.277.428.254.543a3.73 3.73 0 0 1-.814 1.686.75.75 0 0 0 .44 1.223ZM8.25 10.875a1.125 1.125 0 1 0 0 2.25 1.125 1.125 0 0 0 0-2.25ZM10.875 12a1.125 1.125 0 1 1 2.25 0 1.125 1.125 0 0 1-2.25 0Zm4.875-1.125a1.125 1.125 0 1 0 0 2.25 1.125 1.125 0 0 0 0-2.25Z" clip-rule="evenodd" />
              </svg>
            </button>
            <?php if ($unreadChatCount > 0): ?>
              <span class="absolute top-0 right-0 bg-red-500 text-white text-xs font-bold rounded-full px-1.5 py-0.5"><?= $unreadChatCount ?></span>
            <?php endif; ?>
          </div>
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
              <li>
                <a href="#" onclick="openProfileModal(); return false;" class="flex items-center justify-between px-3 py-2 text-sm rounded-lg bg-zinc-50 text-zinc-900 hover:bg-zinc-100 transition-colors duration-200 ring-1 ring-gray-200">
                  โปรไฟล์ของฉัน
                  <svg class="w-5 h-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor">
                    <path fill-rule="evenodd" d="M4.5 3.75a3 3 0 0 0-3 3v10.5a3 3 0 0 0 3 3h15a3 3 0 0 0 3-3V6.75a3 3 0 0 0-3-3h-15Zm4.125 3a2.25 2.25 0 1 0 0 4.5 2.25 2.25 0 0 0 0-4.5Zm-3.873 8.703a4.126 4.126 0 0 1 7.746 0 .75.75 0 0 1-.351.92 7.47 7.47 0 0 1-3.522.877 7.47 7.47 0 0 1-3.522-.877.75.75 0 0 1-.351-.92ZM15 8.25a.75.75 0 0 0 0 1.5h3.75a.75.75 0 0 0 0-1.5H15ZM14.25 12a.75.75 0 0 1 .75-.75h3.75a.75.75 0 0 1 0 1.5H15a.75.75 0 0 1-.75-.75Zm.75 2.25a.75.75 0 0 0 0 1.5h3.75a.75.75 0 0 0 0-1.5H15Z" clip-rule="evenodd" />
                  </svg>
                </a>
              </li>
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

  <?php include $_SERVER['DOCUMENT_ROOT'] . '/graphic-design/src/includes/profile_modal.php'; ?>
  <!-- Chat Modal -->
  <div id="chatModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-40 backdrop-blur-sm hidden">
    <div class="bg-gray-100 p-3 rounded-3xl shadow-xl w-full max-w-3xl mx-auto relative flex space-x-3 max-h-[80vh]" style="height: 500px;">
      <!-- Sidebar: รายการออเดอร์ -->
      <div class="w-64 bg-white rounded-2xl overflow-y-auto ring-1 ring-gray-200 shadow-sm">
        <div class="p-2 pl-4 font-semibold text-zinc-900 border-b bg-gray-50">ประวัติแชทของคุณ</div>
        <?php
        if (isset($_SESSION['customer_id'])) {
          $cid = $_SESSION['customer_id'];

          // ดึง order ทั้งหมดที่ลูกค้าคนนี้เคยมีแชท
          $ordersWithChat = [];
          $sql = "SELECT o.order_id, o.order_code
            FROM orders o
            WHERE o.customer_id = ?
            AND EXISTS (SELECT 1 FROM chat_messages c WHERE c.order_id = o.order_id)
            ORDER BY o.created_at DESC";
          $stmt = $conn->prepare($sql);
          $stmt->bind_param("i", $cid);
          $stmt->execute();
          $ordersWithChat = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
          $stmt->close();

          // ดึง unread ของแต่ละ order ทั้งหมดในครั้งเดียว
          $orderUnreadMap = [];
          $sql = "SELECT order_id, COUNT(*) as unread 
          FROM chat_messages 
          WHERE order_id IN (SELECT order_id FROM orders WHERE customer_id=?) 
            AND sender_role='admin' AND is_read=0
          GROUP BY order_id";
          $stmt = $conn->prepare($sql);
          $stmt->bind_param("i", $cid);
          $stmt->execute();
          $result = $stmt->get_result();
          while ($row = $result->fetch_assoc()) {
            $orderUnreadMap[$row['order_id']] = $row['unread'];
          }
          $stmt->close();

          // ดึง unread ของ "สอบถามทั่วไป"
          $generalUnread = 0;
          $sql = "SELECT COUNT(*) as unread FROM chat_messages WHERE (order_id IS NULL OR order_id=0) AND sender_role='admin' AND is_read=0 AND customer_id=?";
          $stmt = $conn->prepare($sql);
          $stmt->bind_param("i", $cid);
          $stmt->execute();
          $generalUnread = $stmt->get_result()->fetch_assoc()['unread'] ?? 0;
          $stmt->close();
        }
        ?>

        <ul id="orderList">
          <!-- ปุ่มสอบถามทั่วไป -->
          <li class="p-2 text-sm pb-0">
            <button type="button"
              class="w-full text-left px-4 py-3 bg-gray-100 hover:bg-gray-200 transition rounded-xl flex justify-between items-center font-medium text-gray-500 ring-1 ring-gray-300"
              onclick="selectOrderChat(0)">
              #สอบถามทั่วไป
              <?php if (!empty($generalUnread)): ?>
                <span class="bg-red-500 text-white text-xs font-bold rounded-full px-1.5 py-0.5"><?= $generalUnread ?></span>
              <?php endif; ?>
            </button>
          </li>
          <!-- ลูปแสดง order -->
          <?php if (!empty($ordersWithChat)): ?>
            <?php foreach ($ordersWithChat as $order): ?>
              <?php $unread = $orderUnreadMap[$order['order_id']] ?? 0; ?>
              <li class="p-2 text-sm pb-0">
                <button type="button"
                  class="w-full text-left px-4 py-3 bg-gray-100 hover:bg-gray-200 transition rounded-xl flex justify-between items-center hover:ring-1 hover:ring-gray-200 <?= $unread > 0 ? 'font-bold' : 'font-medium text-gray-500 bg-gray-50' ?>"
                  onclick="selectOrderChat(<?= $order['order_id'] ?>)">
                  ออเดอร์ #<?= htmlspecialchars($order['order_code']) ?>
                  <?php if ($unread > 0): ?>
                    <span class="bg-red-500 text-white text-xs font-bold rounded-full px-1.5 py-0.5"><?= $unread ?></span>
                  <?php endif; ?>
                </button>
              </li>
            <?php endforeach; ?>
          <?php endif; ?>
        </ul>
      </div>
      <!-- Main Chat Area -->
      <div class="flex-1 flex flex-col bg-white rounded-2xl overflow-y-auto ring-1 ring-gray-200 shadow-sm">
        <button onclick="closeChatModal()" class="absolute top-2 right-2 bg-zinc-900 text-white rounded-full p-2 ring-1 ring-gray-200 shadow-md hover:bg-zinc-700 transition-all duration-300 ease-in-out hover:scale-105">
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
          </svg>
        </button>
        <div class="p-2 pl-4 font-semibold text-zinc-900 border-b bg-gray-50">
          <h2 class="text-md font-semibold" id="chatOrderTitle">แชทกับทีมงาน</h2>
        </div>
        <div class="flex-1 overflow-y-auto p-4" id="chatBoxModal" style="min-height:200px;">
          <div class="text-gray-400 text-center" id="chatLoadingModal"></div>
        </div>
        <form id="chatFormModal" class="p-4 border-t flex gap-2 hidden">
          <textarea id="chatInputModal" rows="1" required class="block w-full rounded-xl border border-gray-300 bg-gray-50 p-2.5 text-sm text-gray-900" placeholder="พิมพ์ข้อความ..."></textarea>
          <button type="submit" class="bg-gray-900 hover:bg-zinc-800 text-white font-medium rounded-xl text-sm px-4 py-2 flex items-center justify-center hover:scale-105 transition-all duration-300 ease-in-out">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="w-5 h-5">
              <path d="M3.478 2.404a.75.75 0 0 0-.926.941l2.432 7.905H13.5a.75.75 0 0 1 0 1.5H4.984l-2.432 7.905a.75.75 0 0 0 .926.94 60.519 60.519 0 0 0 18.445-8.986.75.75 0 0 0 0-1.218A60.517 60.517 0 0 0 3.478 2.404Z" />
            </svg>
          </button>
        </form>
      </div>
    </div>
  </div>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/flowbite/1.7.0/flowbite.min.js"></script>
  <script src="/graphic-design/src/chat/chat-modal.js"></script>
  <script>
    document.getElementById('notifBell').addEventListener('click', function(e) {
      e.stopPropagation();
      document.getElementById('notifDropdown').classList.toggle('hidden');
    });
    document.addEventListener('click', function(e) {
      document.getElementById('notifDropdown').classList.add('hidden');
    });
  </script>
  <script>
    window.customerId = <?= (int)$_SESSION['customer_id'] ?>;
  </script>
</body>

</html>