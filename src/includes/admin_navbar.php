<?php
// ตรวจสอบ error
error_reporting(E_ALL);
ini_set('display_errors', 1);
$fullname = $_SESSION['fullname'] ?? '';
$adminNotifications = [];
$adminUnreadCount = 0;
require_once __DIR__ . '/../includes/db_connect.php';
$notif_sql = "SELECT * FROM notifications WHERE is_admin = 1 ORDER BY created_at DESC LIMIT 10";
$notif_result = $conn->query($notif_sql);
while ($row = $notif_result->fetch_assoc()) {
    $adminNotifications[] = $row;
    if ($row['is_read'] == 0) $adminUnreadCount++;
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=IBM+Plex+Sans+Thai:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .font-thai {
            font-family: 'IBM Plex Sans Thai', sans-serif;
        }
    </style>
</head>

<body class="font-thai">
    <div class="flex items-center justify-between bg-white p-4 sticky top-0 z-10 border-b border-gray-200">
        <div class="flex items-center justify-between w-full">
            <div class="flex items-center space-x-4">
                <?php if (isset($breadcrumb) && is_array($breadcrumb) && count($breadcrumb) > 2): ?>
                    <button class="p-1.5 text-gray-800 hover:bg-zinc-100 rounded-lg cursor-pointer hover:text-gray-800 ring-1 ring-gray-200 transition-all duration-300 ease-in-out hover:scale-105" onclick="window.history.back()">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="w-6 h-6">
                            <path fill-rule="evenodd" d="M11.03 3.97a.75.75 0 0 1 0 1.06l-6.22 6.22H21a.75.75 0 0 1 0 1.5H4.81l6.22 6.22a.75.75 0 1 1-1.06 1.06l-7.5-7.5a.75.75 0 0 1 0-1.06l7.5-7.5a.75.75 0 0 1 1.06 0Z" clip-rule="evenodd" />
                        </svg>
                    </button>
                <?php endif; ?>
                <!-- Breadcrumb -->
                <nav class="text-sm text-gray-500 p-1 rounded-lg ring-1 ring-gray-200 transition-all duration-300 ease-in-out hover:scale-105">
                    <ol class="list-none p-0 inline-flex">
                        <?php if (isset($breadcrumb) && is_array($breadcrumb)): ?>
                            <?php foreach ($breadcrumb as $i => $item): ?>
                                <li class="flex items-center">
                                    <?php if ($i > 0): ?>
                                        <i class="fas fa-chevron-right mx-2 text-xs"></i>
                                    <?php endif; ?>
                                    <?php if ($i < count($breadcrumb) - 1 && isset($breadcrumb_links[$i])): ?>
                                        <!-- Breadcrumb ก่อนสุดท้ายเป็นลิงก์ -->
                                        <a href="<?= htmlspecialchars($breadcrumb_links[$i]) ?>"
                                            class="hover:text-zinc-900 hover:bg-zinc-100 p-1 rounded-lg transition-colors font-medium">
                                            <?= htmlspecialchars($item) ?>
                                        </a>
                                    <?php else: ?>
                                        <!-- Breadcrumb สุดท้ายเป็นหัวข้อ -->
                                        <span class="font-semibold text-zinc-900 hover:text-zinc-800 transition-colors hover:bg-zinc-100 p-1 rounded-lg">
                                            <?= htmlspecialchars($item) ?>
                                        </span>
                                    <?php endif; ?>
                                </li>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <li class="flex items-center">
                                <span class="font-semibold text-zinc-900 hover:text-zinc-800 transition-colors hover:bg-zinc-100 p-1 rounded-lg"><?= isset($pageTitle) ? htmlspecialchars($pageTitle) : 'หน้าแรก'; ?></span>
                            </li>
                        <?php endif; ?>
                    </ol>
                </nav>
            </div>
            <div class="flex items-center space-x-2">
                <!-- Bell Notification Dropdown for Admin -->
                <div class="relative">
                    <button id="adminNotifBell" class="p-1.5 text-zinc-900 hover:bg-zinc-100 rounded-lg cursor-pointer hover:text-zinc-900 ring-1 ring-gray-200 transition-all duration-300 ease-in-out hover:scale-105">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="w-6 h-6">
                            <path d="M5.85 3.5a.75.75 0 0 0-1.117-1 9.719 9.719 0 0 0-2.348 4.876.75.75 0 0 0 1.479.248A8.219 8.219 0 0 1 5.85 3.5ZM19.267 2.5a.75.75 0 1 0-1.118 1 8.22 8.22 0 0 1 1.987 4.124.75.75 0 0 0 1.48-.248A9.72 9.72 0 0 0 19.266 2.5Z" />
                            <path fill-rule="evenodd" d="M12 2.25A6.75 6.75 0 0 0 5.25 9v.75a8.217 8.217 0 0 1-2.119 5.52.75.75 0 0 0 .298 1.206c1.544.57 3.16.99 4.831 1.243a3.75 3.75 0 1 0 7.48 0 24.583 24.583 0 0 0 4.83-1.244.75.75 0 0 0 .298-1.205 8.217 8.217 0 0 1-2.118-5.52V9A6.75 6.75 0 0 0 12 2.25ZM9.75 18c0-.034 0-.067.002-.1a25.05 25.05 0 0 0 4.496 0l.002.1a2.25 2.25 0 1 1-4.5 0Z" clip-rule="evenodd" />
                        </svg>
                        <?php if ($adminUnreadCount > 0): ?>
                            <span class="absolute -top-2 -right-2 bg-red-500 text-white text-xs font-bold rounded-full px-1.5 py-0.5"><?= $adminUnreadCount ?></span>
                        <?php endif; ?>
                    </button>
                    <!-- Dropdown -->
                    <div id="adminNotifDropdown" class="hidden absolute right-0 mt-2 w-80 bg-white rounded-2xl mb-6 ring-1 ring-gray-200 z-50">
                        <div class="border-b bg-gray-50 rounded-t-2xl">
                            <h2 class="text-md font-semibold p-2 pl-2 ml-2">แจ้งเตือน</h2>
                        </div>
                        <ul class="max-h-80 overflow-y-auto p-2">
                            <?php if (count($adminNotifications) === 0): ?>
                                <li class="p-4 text-gray-500 text-sm text-center">ไม่มีแจ้งเตือน</li>
                            <?php else: ?>
                                <?php foreach ($adminNotifications as $notif): ?>
                                    <li class="mb-1">
                                        <?php
                                        $orderId = null;
                                        if (preg_match('/[?&](?:id|order_id)=([0-9]+)/', $notif['link'], $matches)) {
                                            $orderId = $matches[1];
                                        }
                                        ?>
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
                <button class="p-1.5 text-zinc-900 hover:bg-zinc-100 rounded-lg cursor-pointer hover:text-gray-800 ring-1 ring-gray-200 transition-all duration-300 ease-in-out hover:scale-105">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="w-6 h-6">
                        <path d="M3.478 2.404a.75.75 0 0 0-.926.941l2.432 7.905H13.5a.75.75 0 0 1 0 1.5H4.984l-2.432 7.905a.75.75 0 0 0 .926.94 60.519 60.519 0 0 0 18.445-8.986.75.75 0 0 0 0-1.218A60.517 60.517 0 0 0 3.478 2.404Z" />
                    </svg>
                </button>
                <button id="dropdownDefaultButton" data-dropdown-toggle="admin" class="p-1.5 flex items-center text-zinc-900 text-sm font-semibold hover:bg-zinc-100 rounded-lg cursor-pointer hover:text-gray-800 ring-1 ring-gray-200 transition-all duration-300 ease-in-out hover:scale-105">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="w-6 h-6 mr-2">
                        <path fill-rule="evenodd" d="M18.685 19.097A9.723 9.723 0 0 0 21.75 12c0-5.385-4.365-9.75-9.75-9.75S2.25 6.615 2.25 12a9.723 9.723 0 0 0 3.065 7.097A9.716 9.716 0 0 0 12 21.75a9.716 9.716 0 0 0 6.685-2.653Zm-12.54-1.285A7.486 7.486 0 0 1 12 15a7.486 7.486 0 0 1 5.855 2.812A8.224 8.224 0 0 1 12 20.25a8.224 8.224 0 0 1-5.855-2.438ZM15.75 9a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0Z" clip-rule="evenodd" />
                    </svg>
                    <?= htmlspecialchars($fullname) ?>
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="w-5 h-5 ml-3">
                        <path fill-rule="evenodd" d="M11.47 4.72a.75.75 0 0 1 1.06 0l3.75 3.75a.75.75 0 0 1-1.06 1.06L12 6.31 8.78 9.53a.75.75 0 0 1-1.06-1.06l3.75-3.75Zm-3.75 9.75a.75.75 0 0 1 1.06 0L12 17.69l3.22-3.22a.75.75 0 1 1 1.06 1.06l-3.75 3.75a.75.75 0 0 1-1.06 0l-3.75-3.75a.75.75 0 0 1 0-1.06Z" clip-rule="evenodd" />
                    </svg>
                </button>
                <div id="admin" class="z-10 hidden bg-white divide-y rounded-xl shadow-md w-44 border-1.5 border-gray-200 ring-1 ring-gray-200">
                    <ul class="space-y-2 p-2 py-2 text-sm text-gray-700" aria-labelledby="dropdownDefaultButton">
                        <li>
                            <a href="/graphic-design/src/client/index.php" class=" flex items-center justify-between px-3 py-2 text-sm rounded-lg bg-zinc-50 text-zinc-900 hover:bg-zinc-100 transition-colors duration-200 ring-1 ring-gray-200">
                                หน้าหลัก
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="w-5 h-5">
                                    <path d="M11.47 3.841a.75.75 0 0 1 1.06 0l8.69 8.69a.75.75 0 1 0 1.06-1.061l-8.689-8.69a2.25 2.25 0 0 0-3.182 0l-8.69 8.69a.75.75 0 1 0 1.061 1.06l8.69-8.689Z" />
                                    <path d="m12 5.432 8.159 8.159c.03.03.06.058.091.086v6.198c0 1.035-.84 1.875-1.875 1.875H15a.75.75 0 0 1-.75-.75v-4.5a.75.75 0 0 0-.75-.75h-3a.75.75 0 0 0-.75.75V21a.75.75 0 0 1-.75.75H5.625a1.875 1.875 0 0 1-1.875-1.875v-6.198a2.29 2.29 0 0 0 .091-.086L12 5.432Z" />
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
        </div>
    </div>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/flowbite/1.7.0/flowbite.min.js"></script>
    <script>
        document.getElementById('adminNotifBell').addEventListener('click', function(e) {
            e.stopPropagation();
            document.getElementById('adminNotifDropdown').classList.toggle('hidden');
        });
        document.addEventListener('click', function(e) {
            document.getElementById('adminNotifDropdown').classList.add('hidden');
        });
    </script>
</body>

</html>