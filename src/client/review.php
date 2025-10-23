<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
require __DIR__ . '/../includes/db_connect.php';

$my_id = $_SESSION['customer_id'] ?? null;
// JOIN ตาราง customers เพื่อดึงชื่อและอีเมล
$star = isset($_GET['star']) ? (int)$_GET['star'] : null;
$review_sql = "
    SELECT r.*, c.fullname, c.email
    FROM reviews r
    LEFT JOIN customers c ON r.customer_id = c.customer_id
    WHERE r.is_approved = 1
";
if ($star && $star >= 1 && $star <= 5) {
    $review_sql .= " AND r.rating = $star ";
}
$review_sql .= " ORDER BY r.created_at DESC";
$review_result = $conn->query($review_sql);

?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=IBM+Plex+Sans+Thai:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/flowbite/1.7.0/flowbite.min.css" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />
    <style>
        .font-thai {
            font-family: 'IBM Plex Sans Thai', sans-serif;
        }

        body.modal-open {
            overflow: hidden;
        }
    </style>
</head>

<body class="bg-white font-thai pt-10 absolute inset-0 -z-10 h-full w-full bg-[radial-gradient(#e5e7eb_1px,transparent_1px)] [background-size:16px_16px]">
    <!-- Navigation -->
    <?php include __DIR__ . '/../includes/navbar.php'; ?>
    <div class="p-10 text-center mt-10">
        <h1 class="text-5xl font-bold text-zinc-900 mb-4">รีวิวจากลูกค้าของเรา</h1>
        <p class="text-gray-600 text-xl">
            อ่านรีวิวจากลูกค้าที่เคยใช้บริการของเรา เพื่อประกอบการตัดสินใจ
        </p>
    </div>
    <div class="flex justify-center">
        <div class="p-2 bg-white rounded-full shadow ring-1 ring-gray-200">
            <div class="flex justify-center gap-2 flex-wrap">
                <a href="review.php" class="border transition font-medium rounded-full text-sm px-5 py-2 text-center flex items-center justify-center <?= !isset($_GET['star']) ? 'bg-zinc-900 hover:bg-zinc-700 text-white border-zinc-900' : 'bg-white text-gray-600 border-gray-300 hover:bg-gray-100' ?>">ทั้งหมด</a>
                <?php for ($i = 5; $i >= 1; $i--): ?>
                    <a href="review.php?star=<?= $i ?>" class="border transition font-medium rounded-full text-sm px-5 py-2 text-center flex items-center justify-center <?= (isset($_GET['star']) && $_GET['star'] == $i) ? 'bg-zinc-900 hover:bg-zinc-700 text-white border-zinc-900' : 'bg-white text-gray-600 border-gray-300 hover:bg-gray-100' ?>">
                        <?= $i ?> ดาว
                    </a>
                <?php endfor; ?>
            </div>
        </div>
    </div>
    <section class="px-10 pt-10 mb-10 pb-10">
        <div class="columns-1 sm:columns-2 md:columns-3 gap-4 [column-fill:_balance]">
            <?php while ($review = $review_result->fetch_assoc()): ?>
                <div class="mb-4 break-inside-avoid p-4 bg-white rounded-2xl shadow-md hover:shadow-lg ring-1 ring-gray-200 transition">
                    <div class="flex justify-between mb-4">
                        <div class="flex items-center">
                            <div class="flex-shrink-0 h-10 w-10 bg-zinc-900 rounded-full flex items-center justify-center text-white font-medium">
                                <?= htmlspecialchars(mb_substr($review['fullname'], 0, 1)) ?>
                            </div>
                            <div class="ml-3">
                                <h4 class="font-medium"><?= htmlspecialchars($review['fullname']) ?></h4>
                                <p class="text-xs text-gray-500"><?= htmlspecialchars($review['email']) ?></p>
                            </div>
                        </div>
                        <div>
                            <span class="ml-auto font-bold text-lg flex gap-0.5 justify-end">
                                <?php
                                $rating = (int)$review['rating'];
                                for ($i = 1; $i <= 5; $i++) {
                                    if ($i <= $rating) {
                                        echo '<span class="text-yellow-300">★</span>';
                                    } else {
                                        echo '<span class="text-gray-300">★</span>';
                                    }
                                }
                                ?>
                            </span>
                            <div class="text-xs text-gray-400 mt-2 text-right">
                                <span class="js-timeago" data-time="<?= htmlspecialchars($review['created_at']) ?>"></span>
                            </div>
                        </div>
                    </div>
                    <div class="mb-2"><?= nl2br(htmlspecialchars($review['comment'])) ?></div>
                    <?php if ($review['image']): ?>
                        <img src="/graphic-design/uploads/<?= htmlspecialchars($review['image']) ?>"
                            class="mt-2 w-full rounded-2xl object-cover cursor-zoom-in transition hover:brightness-90"
                            onclick="openImgModal(this.src)">
                    <?php endif; ?>
                </div>
            <?php endwhile; ?>
        </div>
    </section>
    <?php
    include __DIR__ . '/../includes/footer.php';
    ?>

    <!-- Image Modal -->
    <div id="imgModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-70 hidden backdrop-blur-md">
        <div class="relative">
            <button onclick="closeImgModal()" class="absolute -top-4 -right-4 text-zinc-900 bg-white rounded-full p-2 shadow-md transition-all duration-300 ease-in-out hover:scale-105">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
            <img id="imgModalPic" src="" class="max-w-[90vw] max-h-[80vh] rounded-2xl shadow-xl object-contain" alt="รีวิว">
        </div>
    </div>

    <script>
        function timeAgoJS(dateString) {
            const now = new Date();
            const then = new Date(dateString.replace(' ', 'T'));
            const diff = Math.floor((now - then) / 1000);
            if (diff < 60) return 'ไม่กี่วินาทีที่แล้ว';
            if (diff < 3600) return Math.floor(diff / 60) + ' นาทีที่แล้ว';
            if (diff < 86400) return Math.floor(diff / 3600) + ' ชั่วโมงที่แล้ว';
            if (diff < 2592000) return Math.floor(diff / 86400) + ' วันที่แล้ว';
            if (diff < 31536000) return Math.floor(diff / 2592000) + ' เดือนที่แล้ว';
            return Math.floor(diff / 31536000) + ' ปีที่แล้ว';
        }

        function updateAllTimeAgo() {
            document.querySelectorAll('.js-timeago').forEach(function(el) {
                const time = el.getAttribute('data-time');
                el.textContent = timeAgoJS(time);
            });
        }
        updateAllTimeAgo();
        setInterval(updateAllTimeAgo, 60000); // อัปเดตทุก 1 นาที
    </script>
    <script>
        function openImgModal(src) {
            document.getElementById('imgModalPic').src = src;
            document.getElementById('imgModal').classList.remove('hidden');
            document.body.classList.add('modal-open'); // ป้องกัน scroll
        }

        function closeImgModal() {
            document.getElementById('imgModal').classList.add('hidden');
            document.getElementById('imgModalPic').src = '';
            document.body.classList.remove('modal-open'); // กลับมา scroll ได้
        }
        // ปิด modal เมื่อคลิกพื้นหลัง
        document.getElementById('imgModal').addEventListener('click', function(e) {
            if (e.target === this) closeImgModal();
        });
    </script>
</body>

</html>