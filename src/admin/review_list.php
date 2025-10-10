<?php
// ตรวจสอบ error
error_reporting(E_ALL);
ini_set('display_errors', 1);
require __DIR__ . '/../includes/db_connect.php';
require_once '../auth/auth.php';
requireRole('admin'); // ให้เข้าหน้านี้ได้เฉพาะ admin

// รับค่าค้นหาจากฟอร์ม
$search_customer = $_GET['customer'] ?? '';
$search_order = $_GET['order'] ?? '';
$search_status = $_GET['status'] ?? '';
$search_rating = $_GET['rating'] ?? '';

// สร้าง WHERE ตามเงื่อนไขที่กรอก
$where = [];
$params = [];
$types = '';

if ($search_customer !== '') {
    $where[] = 'c.fullname LIKE ?';
    $params[] = '%' . $search_customer . '%';
    $types .= 's';
}
if ($search_order !== '') {
    $where[] = 'o.order_code LIKE ?';
    $params[] = '%' . $search_order . '%';
    $types .= 's';
}
if ($search_status !== '') {
    $where[] = 'r.is_approved = ?';
    $params[] = $search_status === 'approved' ? 1 : 0;
    $types .= 'i';
}
if ($search_rating !== '') {
    $where[] = 'r.rating = ?';
    $params[] = (int)$search_rating;
    $types .= 'i';
}

$where_sql = $where ? 'WHERE ' . implode(' AND ', $where) : '';

$sql = "SELECT r.*, c.fullname, o.order_code 
    FROM reviews r
    JOIN customers c ON r.customer_id = c.customer_id
    JOIN orders o ON r.order_id = o.order_id
    $where_sql
    ORDER BY r.created_at DESC
    LIMIT 100";

$stmt = $conn->prepare($sql);
if ($params) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$reviews = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=IBM+Plex+Sans+Thai:wght@300;400;500;600;700&display=swap"
        rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .font-thai {
            font-family: 'IBM Plex Sans Thai', sans-serif;
        }
    </style>
</head>

<body class="font-thai bg-zinc-100">
    <?php include '../includes/sidebar.php'; ?>

    <div class="ml-64">
        <?php
        $breadcrumb = ['Dashboard', 'จัดการรีวิวลูกค้า'];
        $breadcrumb_links = ['/graphic-design/src/admin/index.php', '/graphic-design/src/admin/review_list.php'];
        include '../includes/admin_navbar.php';
        ?>
        <div class="p-6">
            <div class=" text-zinc-900 bg-white rounded-2xl border border-slate-200 mb-2">
                <!-- Header -->
                <div class="flex items-center p-4">
                    <div class="mr-4 rounded-xl bg-zinc-900 p-3">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="h-6 w-6 text-white">
                            <path fill-rule="evenodd" d="M10.788 3.21c.448-1.077 1.976-1.077 2.424 0l2.082 5.006 5.404.434c1.164.093 1.636 1.545.749 2.305l-4.117 3.527 1.257 5.273c.271 1.136-.964 2.033-1.96 1.425L12 18.354 7.373 21.18c-.996.608-2.231-.29-1.96-1.425l1.257-5.273-4.117-3.527c-.887-.76-.415-2.212.749-2.305l5.404-.434 2.082-5.005Z" clip-rule="evenodd" />
                        </svg>
                    </div>
                    <div class="">
                        <h1 class="flex items-center text-2xl font-bold text-zinc-900">
                            รายการรีวิวลูกค้า
                        </h1>
                        <p class="text-gray-600">
                            จัดการและติดตามการรีวิวของลูกค้า
                        </p>
                    </div>
                </div>
            </div>
            <div class="flex items-center justify-between bg-white rounded-2xl mb-2 p-4 ring-1 ring-gray-200">
                <!-- Filter -->
                <form method="get" class="flex gap-2 items-end">
                    <div class="flex gap-2">
                        <div>
                            <input type="text" name="customer" placeholder="พิมพ์ชื่อลูกค้า" value="<?= htmlspecialchars($search_customer) ?>" class="border transition rounded-xl text-sm px-3 py-2 flex items-center justify-center bg-white text-gray-600 border-gray-300 hover:bg-gray-100">
                        </div>
                        <div>
                            <input type="text" name="order" placeholder="พิมพ์หมายเลขคำสั่งซื้อ" value="<?= htmlspecialchars($search_order) ?>" class="border transition rounded-xl text-sm px-3 py-2 flex items-center justify-center bg-white text-gray-600 border-gray-300 hover:bg-gray-100">
                        </div>
                        <div>
                            <select name="status" class="border transition font-medium rounded-xl text-sm px-10 py-1.5 text-center flex items-center justify-center bg-white text-gray-600 border-gray-300 hover:bg-gray-100">
                                <option value="">ทั้งหมด</option>
                                <option value="approved" <?= $search_status === 'approved' ? 'selected' : '' ?>>อนุมัติ</option>
                                <option value="pending" <?= $search_status === 'pending' ? 'selected' : '' ?>>รออนุมัติ</option>
                            </select>
                        </div>
                        <div>
                            <select name="rating" class="border transition font-medium rounded-xl text-sm px-10 py-1.5 text-center flex items-center justify-center bg-white text-gray-600 border-gray-300 hover:bg-gray-100">
                                <option value="">ทั้งหมด</option>
                                <?php for ($i = 5; $i >= 1; $i--): ?>
                                    <option value="<?= $i ?>" <?= $search_rating == $i ? 'selected' : '' ?>><?= $i ?> ดาว</option>
                                <?php endfor; ?>
                            </select>
                        </div>
                        <?php if ($_GET): ?>
                            <div class="">
                                <a href="review_list.php" class="border transition font-medium rounded-xl text-sm px-3 py-2 text-center flex items-center justify-center bg-white text-gray-600 border-gray-300 hover:bg-gray-100">ล้างค่า</a>
                            </div>
                        <?php endif; ?>
                        <div class="">
                            <button type="submit" class="border transition font-medium rounded-xl text-sm px-5 py-2 text-center flex items-center justify-center bg-zinc-900 hover:bg-zinc-700 text-white border-zinc-900">ค้นหา</button>
                        </div>
                </form>
            </div>
        </div>
        <!-- Modal สำหรับดูรูป -->
        <div id="imageModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-60 hidden">
            <div class="relative p-4 flex flex-col items-center">
                <button onclick="closeImageModal()" class="absolute top-2 right-2 text-zinc-900 bg-white rounded-full p-2  shadow-md transition-all duration-300 ease-in-out hover:scale-105">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
                <img id="modalImage"
                    src=""
                    class="max-w-[90vw] max-h-[70vh] w-auto h-auto rounded-xl shadow"
                    style="object-fit:contain; display:block; margin:auto;"
                    alt="รีวิว">
            </div>
        </div>
        <div class=" text-zinc-900 bg-white rounded-2xl border border-slate-200 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-gray-50 border-b border-gray-200">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">ลูกค้า</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Order</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">คะแนน</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">ข้อความ</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">รูป</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">วันที่</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">สถานะ</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">จัดการ</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($reviews as $review): ?>
                            <tr class="hover:bg-gray-50 transition-colors">
                                <td class="px-6 py-4 whitespace-nowrap text-sm"><?= htmlspecialchars($review['fullname']) ?></td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm">#<?= htmlspecialchars($review['order_code']) ?></td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm"><?= str_repeat('⭐', $review['rating']) ?></td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm"><?= nl2br(htmlspecialchars($review['comment'])) ?></td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm">
                                    <?php if ($review['image']): ?>
                                        <img src="/graphic-design/uploads/<?= htmlspecialchars($review['image']) ?>"
                                            style="max-width:60px;cursor:pointer"
                                            onclick="openImageModal('/graphic-design/uploads/<?= htmlspecialchars($review['image']) ?>')"
                                            alt="รีวิว">
                                    <?php endif; ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm"><?= $review['created_at'] ?></td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm">
                                    <form method="post" action="review_approve.php" style="display:inline;">
                                        <input type="hidden" name="review_id" value="<?= $review['id'] ?>">
                                        <select name="is_approved" class="border transition font-medium rounded-xl text-sm px-5 py-1 text-center flex items-center justify-center bg-white text-gray-600 border-gray-300 hover:bg-gray-100"
                                            onchange="this.form.submit()">
                                            <option value="0" <?= !$review['is_approved'] ? 'selected' : '' ?>>รออนุมัติ</option>
                                            <option value="1" <?= $review['is_approved'] ? 'selected' : '' ?>>อนุมัติ</option>
                                        </select>
                                    </form>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm">
                                    <a href="delete_review.php?review_id=<?= $review['id'] ?>" onclick="return confirm('ยืนยันลบรีวิวนี้?')" class="text-red-600">ลบ</a>
                                    <button type="button"
                                        onclick="showEditForm(
                                            <?= $review['id'] ?>,
                                            <?= $review['rating'] ?>,
                                            this.getAttribute('data-comment'),
                                            '<?= htmlspecialchars($review['image'] ?? '') ?>'
                                        )"
                                        data-comment="<?= htmlspecialchars(json_encode($review['comment']), ENT_QUOTES, 'UTF-8') ?>"
                                        class="ml-2 text-blue-600 underline">แก้ไข</button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    </div>

    <!-- ฟอร์มแก้ไขรีวิว (modal แบบง่าย) -->
    <div id="editReviewModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50 hidden">
        <div class="bg-white rounded-2xl w-full max-w-md relative ring-1 ring-gray-200">
            <button onclick="closeEditForm()" class="absolute top-2 right-2 bg-zinc-900 text-white rounded-full p-2 ring-1 ring-gray-200 shadow-md hover:bg-zinc-700 transition-all duration-300 ease-in-out hover:scale-105">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
            <div class="border-b bg-gray-50 rounded-t-2xl">
                <h2 class="text-md font-semibold p-2 pl-4">แก้ไขรีวิว</h2>
            </div>
            <div class="p-4">
                <form id="editReviewForm" method="post" action="review_edit.php" enctype="multipart/form-data">
                    <input type="hidden" name="review_id" id="editReviewId">
                    <div class="mb-3">
                        <label class="block text-sm font-medium text-gray-700">คะแนน</label>
                        <select name="rating" id="editReviewRating" class="border transition font-medium rounded-xl text-sm px-5 py-1.5 flex items-center justify-center w-full bg-white text-gray-600 border-gray-300 hover:bg-gray-100">
                            <?php for ($i = 5; $i >= 1; $i--): ?>
                                <option value="<?= $i ?>"><?= $i ?> ดาว</option>
                            <?php endfor; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="block text-sm font-medium text-gray-700">ข้อความ</label>
                        <textarea name="comment" id="editReviewComment" rows="2" class="border transition font-medium rounded-xl text-sm px-5 py-2 flex items-center justify-center bg-white text-gray-600 border-gray-300 hover:bg-gray-100 w-full"></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="block text-sm font-medium text-gray-700">เปลี่ยนรูป (ถ้าต้องการ)</label>
                        <input type="file" name="image" class="border transition font-medium rounded-xl text-sm px-5 py-2 flex items-center justify-center w-full bg-white text-gray-600 border-gray-300 hover:bg-gray-100">
                        <div id="editReviewImagePreview" class="mt-2"></div>
                    </div>
                    <div class="flex justify-end">
                        <button type="submit" class="border transition font-medium rounded-xl text-sm px-5 py-2 flex items-center justify-center bg-zinc-900 hover:bg-zinc-700 text-white border-zinc-900">บันทึก</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <script>
        function openImageModal(src) {
            document.getElementById('modalImage').src = src;
            document.getElementById('imageModal').classList.remove('hidden');
        }

        function closeImageModal() {
            document.getElementById('imageModal').classList.add('hidden');
            document.getElementById('modalImage').src = '';
        }
        // ปิด modal เมื่อคลิกพื้นหลัง
        document.getElementById('imageModal').addEventListener('click', function(e) {
            if (e.target === this) closeImageModal();
        });
    </script>
    <script>
        function showEditForm(id, rating, comment, image) {
            document.getElementById('editReviewId').value = id;
            document.getElementById('editReviewRating').value = rating;
            // comment เป็น string อยู่แล้ว ไม่ต้อง JSON.parse
            document.getElementById('editReviewComment').value = JSON.parse(comment);
            let imgHtml = '';
            if (image) {
                imgHtml = `<img src="/graphic-design/uploads/${image}" style="max-width:100px;" class="rounded border">`;
            }
            document.getElementById('editReviewImagePreview').innerHTML = imgHtml;
            document.getElementById('editReviewModal').classList.remove('hidden');
        }

        function closeEditForm() {
            document.getElementById('editReviewModal').classList.add('hidden');
        }
    </script>
</body>

</html>