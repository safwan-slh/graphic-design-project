<?php
// ตรวจสอบ error
error_reporting(E_ALL);
ini_set('display_errors', 1);

require '../includes/db_connect.php';
require '../auth/auth.php';
requireRole('admin'); // ให้เข้าหน้านี้ได้เฉพาะ admin

// ตรวจสอบว่ามี ID ที่จะแก้ไข
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header('Location: portfolio_list.php');
    exit;
}

$portfolioId = (int)$_GET['id'];

// ดึงข้อมูลบริการจากฐานข้อมูล
$services = [];
$serviceResult = $conn->query("SELECT service_id, service_name FROM services WHERE is_active = TRUE");
if ($serviceResult) {
    $services = $serviceResult->fetch_all(MYSQLI_ASSOC);
}

// ดึงข้อมูลผลงานที่จะแก้ไข
$portfolio = null;
$stmt = $conn->prepare("SELECT * FROM portfolios WHERE portfolio_id = ?");
$stmt->bind_param("i", $portfolioId);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    header('Location: portfolio_list.php');
    exit;
}

$portfolio = $result->fetch_assoc();
$tagsArray = json_decode($portfolio['tags'], true) ?: [];

$toastType = '';
$toastMessage = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // รับค่าจากฟอร์ม
        $serviceId = $_POST['service_id'];
        $title = $_POST['title'];
        $description = $_POST['description'];
        $clientName = $_POST['client_name'];
        $projectDate = !empty($_POST['project_date']) ? $_POST['project_date'] : null;
        $tags = json_encode(explode(',', $_POST['tags'])); // แปลง tags เป็น JSON
        $isFeatured = isset($_POST['is_featured']) ? 1 : 0;
        $isActive = isset($_POST['is_active']) ? 1 : 0;

        // จัดการอัพโหลดไฟล์หลัก (ถ้ามีการอัพโหลดใหม่)
        $imageUrl = $portfolio['image_url']; // ใช้รูปเดิมเป็น default

        if (isset($_FILES['image_url']) && $_FILES['image_url']['error'] === UPLOAD_ERR_OK) {
            $uploadDir = ROOT_PATH . '/uploads/portfolio/';

            // สร้างโฟลเดอร์ถ้ายังไม่มี
            if (!file_exists($uploadDir)) {
                mkdir($uploadDir, 0777, true);
            }

            $originalName = basename($_FILES['image_url']['name']);
            $fileExtension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));

            // ตรวจสอบประเภทไฟล์
            $allowedTypes = ['jpg', 'jpeg', 'png', 'webp', 'gif'];
            if (in_array($fileExtension, $allowedTypes)) {
                $newFilename = uniqid() . '_portfolio.' . $fileExtension;
                $targetPath = $uploadDir . $newFilename;

                if (move_uploaded_file($_FILES['image_url']['tmp_name'], $targetPath)) {
                    $imageUrl = '/uploads/portfolio/' . $newFilename;

                    // ลบไฟล์เก่าถ้ามี
                    if ($portfolio['image_url'] && file_exists(ROOT_PATH . $portfolio['image_url'])) {
                        unlink(ROOT_PATH . $portfolio['image_url']);
                    }
                } else {
                    throw new Exception("ไม่สามารถอัพโหลดไฟล์ได้");
                }
            } else {
                throw new Exception("อนุญาตเฉพาะไฟล์ JPG, JPEG, PNG, WebP และ GIF");
            }
        }

        // อัปเดทข้อมูลในฐานข้อมูล
        if ($projectDate) {
            // ถ้ามีวันที่
            $stmt = $conn->prepare("UPDATE portfolios SET 
                service_id = ?, 
                title = ?, 
                description = ?, 
                image_url = ?, 
                client_name = ?, 
                project_date = ?, 
                tags = ?, 
                is_featured = ?, 
                is_active = ?,
                updated_at = NOW()
                WHERE portfolio_id = ?");

            $stmt->bind_param(
                "issssssiii",
                $serviceId,
                $title,
                $description,
                $imageUrl,
                $clientName,
                $projectDate,
                $tags,
                $isFeatured,
                $isActive,
                $portfolioId
            );
        } else {
            // ถ้าไม่มีวันที่
            $stmt = $conn->prepare("UPDATE portfolios SET 
                service_id = ?, 
                title = ?, 
                description = ?, 
                image_url = ?, 
                client_name = ?, 
                project_date = NULL, 
                tags = ?, 
                is_featured = ?, 
                is_active = ?,
                updated_at = NOW()
                WHERE portfolio_id = ?");

            $stmt->bind_param(
                "isssssiii",
                $serviceId,
                $title,
                $description,
                $imageUrl,
                $clientName,
                $tags,
                $isFeatured,
                $isActive,
                $portfolioId
            );
        }

        if ($stmt->execute()) {
            // ใช้ JavaScript redirect เพื่อป้องกัน refresh ซ้ำ
            echo '<script>window.location.href = "portfolio_list.php?success=1";</script>';
            exit;
        } else {
            throw new Exception("เกิดข้อผิดพลาดในการอัปเดทข้อมูล: " . $conn->error);
        }
    } catch (Exception $e) {
        $toastType = 'error';
        $toastMessage = $e->getMessage();

        // Debug: แสดง error ที่เกิดขึ้น
        error_log("Portfolio Edit Error: " . $e->getMessage());
    }
}
?>

<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>แก้ไขผลงาน - Admin</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=IBM+Plex+Sans+Thai:wght@300;400;500;600;700&display=swap"
        rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />
    <style>
        .font-thai {
            font-family: 'IBM Plex Sans Thai', sans-serif;
        }
    </style>
</head>

<body class="font-thai bg-zinc-100">
    <?php include '../includes/sidebar.php'; ?>

    <div class="ml-64">
        <!-- breadcrumb -->
        <?php
        $breadcrumb = ['Dashboard', 'จัดการผลงาน', 'อัปเดทผลงาน'];
        $breadcrumb_links = ['/graphic-design/src/admin/index.php', '/graphic-design/src/admin/portfolio_list.php'];
        include '../includes/admin_navbar.php';
        ?>
        <div class="p-6">
            <div class=" text-zinc-900 bg-white rounded-2xl border border-slate-200 mb-2">
                <!-- Header -->
                <div class="flex items-center p-4">
                    <div class="mr-4 rounded-xl bg-zinc-900 p-3">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="h-6 w-6 text-white">
                            <path d="M3.375 3C2.339 3 1.5 3.84 1.5 4.875v.75c0 1.036.84 1.875 1.875 1.875h17.25c1.035 0 1.875-.84 1.875-1.875v-.75C22.5 3.839 21.66 3 20.625 3H3.375Z" />
                            <path fill-rule="evenodd" d="m3.087 9 .54 9.176A3 3 0 0 0 6.62 21h10.757a3 3 0 0 0 2.995-2.824L20.913 9H3.087Zm6.163 3.75A.75.75 0 0 1 10 12h4a.75.75 0 0 1 0 1.5h-4a.75.75 0 0 1-.75-.75Z" clip-rule="evenodd" />
                        </svg>
                    </div>
                    <div class="">
                        <h1 class="flex items-center text-2xl font-bold text-zinc-900">
                            เพิ่มผลงานร้าน
                        </h1>
                        <p class="text-gray-600">
                            เพิ่มผลงานการออกแบบของคุณ
                        </p>
                    </div>
                </div>
                <div class="form-container w-max-[800px] p-4">
                    <div class="bg-white items-center p-4 ring-1 ring-zinc-200 rounded-2xl">
                        <!-- Toast Notification -->
                        <?php if ($toastMessage): ?>
                            <div class="toast <?php echo $toastType === 'success' ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700'; ?> p-3 rounded-md mb-4">
                                <?php echo $toastMessage; ?>
                            </div>
                        <?php endif; ?>
                        <form method="POST" enctype="multipart/form-data" class="space-y-6" id="portfolioForm">
                            <!-- ข้อมูลพื้นฐาน -->
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">บริการ
                                        <span class="text-red-500">*</span></label>
                                    <select name="service_id" required
                                        class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2">
                                        <option value="">-- เลือกบริการ --</option>
                                        <?php foreach ($services as $service): ?>
                                            <option value="<?= $service['service_id'] ?>"
                                                <?= ($service['service_id'] == $portfolio['service_id']) ? 'selected' : '' ?>>
                                                <?= htmlspecialchars($service['service_name']) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div>
                                    <label class="block mb-2 text-sm font-medium text-gray-700">ชื่อผลงาน
                                        <span class="text-red-500">*</span></label>
                                    <input type="text" name="title" required placeholder="กรุณากรอกชื่อผลงาน"
                                        value="<?= htmlspecialchars($portfolio['title']) ?>"
                                        class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5">
                                </div>
                            </div>

                            <!-- รูปภาพหลัก -->
                            <div>
                                <label class="block mb-2 text-sm font-medium text-gray-700">รูปภาพหลัก</label>

                                <!-- แสดงภาพปัจจุบัน -->
                                <div class="mb-4">
                                    <p class="text-sm text-gray-600 mb-2">ภาพปัจจุบัน:</p>
                                    <img src="<?= htmlspecialchars($portfolio['image_url']) ?>"
                                        alt="<?= htmlspecialchars($portfolio['title']) ?>"
                                        class="w-32 h-32 object-cover rounded-lg border border-gray-300"
                                        onerror="this.style.display='none';">
                                </div>

                                <div class="upload-area relative border-2 border-dashed border-gray-300 bg-gray-50 rounded-xl p-6 text-center hover:border-gray-400 cursor-pointer transition-colors duration-300"
                                    id="uploadArea">
                                    <div class="flex flex-col items-center justify-center space-y-3" id="uploadContent">
                                        <div class="w-16 h-16 bg-blue-100 rounded-full text-blue-500 flex items-center justify-center">
                                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="size-6">
                                                <path fill-rule="evenodd" d="M10.5 3.75a6 6 0 0 0-5.98 6.496A5.25 5.25 0 0 0 6.75 20.25H18a4.5 4.5 0 0 0 2.206-8.423 3.75 3.75 0 0 0-4.133-4.303A6.001 6.001 0 0 0 10.5 3.75Zm2.03 5.47a.75.75 0 0 0-1.06 0l-3 3a.75.75 0 1 0 1.06 1.06l1.72-1.72v4.94a.75.75 0 0 0 1.5 0v-4.94l1.72 1.72a.75.75 0 1 0 1.06-1.06l-3-3Z" clip-rule="evenodd" />
                                            </svg>
                                        </div>

                                        <div>
                                            <p class="font-medium text-gray-900">ลากและวางหรือเลือกไฟล์</p>
                                            <p class="text-xs text-gray-500 mt-1">ขนาดไฟล์ไม่เกิน 10MB</p>
                                        </div>
                                        <label class="px-4 py-2 bg-blue-100 text-blue-700 rounded-lg text-sm font-medium hover:bg-blue-200 transition-colors duration-300 cursor-pointer">
                                            เลือกไฟล์จากคอมพิวเตอร์
                                        </label>
                                    </div>
                                    <!-- ไฟล์ input หลักอยู่ที่นี่ -->
                                    <input type="file" name="image_url" class="absolute inset-0 w-full h-full opacity-0 cursor-pointer" id="fileInput" accept="image/*">
                                </div>
                                <div class="text-center mt-4">
                                    <p class="text-xs text-gray-500 mb-2">รองรับไฟล์:</p>
                                    <div class="flex justify-center space-x-2">
                                        <span class="format-tag px-2 py-1 bg-gray-100 text-gray-700 rounded text-xs">JPG</span>
                                        <span class="format-tag px-2 py-1 bg-gray-100 text-gray-700 rounded text-xs">PNG</span>
                                        <span class="format-tag px-2 py-1 bg-gray-100 text-gray-700 rounded text-xs">GIF</span>
                                        <span class="format-tag px-2 py-1 bg-gray-100 text-gray-700 rounded text-xs">WebP</span>
                                    </div>
                                    <p class="text-xs text-gray-500 mt-2">ปล่อยว่างไว้หากไม่ต้องการเปลี่ยนรูปภาพ</p>
                                </div>
                            </div>

                            <!-- คำอธิบาย -->
                            <div>
                                <label class="block mb-2 text-sm font-medium text-gray-700">คำอธิบาย</label>
                                <textarea name="description" rows="4"
                                    placeholder="กรุณากรอกคำอธิบายเกี่ยวกับผลงาน"
                                    class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5"><?= htmlspecialchars($portfolio['description']) ?></textarea>
                            </div>

                            <!-- ข้อมูลลูกค้า -->
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div>
                                    <label class="block mb-2 text-sm font-medium text-gray-700">ชื่อลูกค้า</label>
                                    <input type="text" name="client_name"
                                        placeholder="กรุณากรอกชื่อของลูกค้า"
                                        value="<?= htmlspecialchars($portfolio['client_name']) ?>"
                                        class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5">
                                </div>

                                <div>
                                    <label class="block mb-2 text-sm font-medium text-gray-700">วันที่โครงการ</label>
                                    <input type="date" name="project_date"
                                        value="<?= htmlspecialchars($portfolio['project_date']) ?>"
                                        class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5">
                                </div>
                            </div>

                            <!-- Tags -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">แท็ก
                                    (คั่นด้วย comma)</label>
                                <input type="text" name="tags"
                                    placeholder="โลโก้, Minimalist, อาหาร"
                                    value="<?= htmlspecialchars(implode(', ', $tagsArray)) ?>"
                                    class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5">
                                <p class="text-xs text-gray-500 mt-2">ตัวอย่าง: โลโก้, Minimalist, อาหาร, สีน้ำเงิน</p>
                            </div>

                            <!-- การตั้งค่า -->
                            <div class="col-span-2 space-y-3">
                                <div class="checkbox-label">
                                    <input type="checkbox" name="is_featured" class="w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 rounded focus:ring-blue-500" value="1"
                                        <?= $portfolio['is_featured'] ? 'checked' : '' ?>>
                                    <label for="is_featured" class="ml-2 text-sm font-medium text-gray-700">เป็นผลงานแนะนำ</label>
                                </div>
                                <div class="checkbox-label">
                                    <input type="checkbox" name="is_active" value="1" class="w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 rounded focus:ring-blue-500"
                                        <?= $portfolio['is_active'] ? 'checked' : '' ?>>
                                    <label for="is_active" class="ml-2 text-sm font-medium text-gray-700">แสดงผลในเว็บไซต์</label>
                                </div>
                            </div>

                            <!-- ปุ่มส่งฟอร์ม -->
                            <div class="flex space-x-4 pt-2">
                                <button type="submit" id="submitBtn"
                                    class="border transition font-medium rounded-xl text-sm px-5 py-2 text-center flex items-center justify-center bg-zinc-900 hover:bg-zinc-700 text-white border-zinc-900">
                                    อัปเดทผลงาน
                                </button>
                                <a href="portfolio_list.php"
                                    class="border transition font-medium rounded-xl text-sm px-5 py-2 text-center flex items-center justify-center bg-white text-gray-600 border-gray-300 hover:bg-gray-100">
                                    ยกเลิก
                                </a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        const fileInput = document.getElementById('fileInput');
        const uploadArea = document.getElementById('uploadArea');
        const uploadContent = document.getElementById('uploadContent');
        const form = document.getElementById('portfolioForm');
        const submitBtn = document.getElementById('submitBtn');

        // ฟังก์ชันแสดงสถานะเริ่มต้น
        function showInitialState() {
            // ดึง url รูปเดิมจาก PHP
            const oldImageUrl = "<?= htmlspecialchars($portfolio['image_url']) ?>";
            const oldImageTitle = "<?= htmlspecialchars($portfolio['title']) ?>";
            uploadContent.innerHTML = `
        <div class="w-16 h-16 bg-blue-100 rounded-full text-blue-500 flex items-center justify-center">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="size-6">
                <path fill-rule="evenodd" d="M10.5 3.75a6 6 0 0 0-5.98 6.496A5.25 5.25 0 0 0 6.75 20.25H18a4.5 4.5 0 0 0 2.206-8.423 3.75 3.75 0 0 0-4.133-4.303A6.001 6.001 0 0 0 10.5 3.75Zm2.03 5.47a.75.75 0 0 0-1.06 0l-3 3a.75.75 0 1 0 1.06 1.06l1.72-1.72v4.94a.75.75 0 0 0 1.5 0v-4.94l1.72 1.72a.75.75 0 1 0 1.06-1.06l-3-3Z" clip-rule="evenodd" />
            </svg>
        </div>
        <div>
            <p class="font-medium text-gray-900">ลากและวางหรือเลือกไฟล์</p>
            <p class="text-xs text-gray-500 mt-1">ขนาดไฟล์ไม่เกิน 10MB</p>
        </div>
        <label class="px-4 py-2 bg-blue-100 text-blue-700 rounded-lg text-sm font-medium hover:bg-blue-200 transition-colors duration-300 cursor-pointer">
            เลือกไฟล์จากคอมพิวเตอร์
        </label>
        ${oldImageUrl ? `<div class="mt-4"><img src="${oldImageUrl}" alt="${oldImageTitle}" class="w-32 h-32 object-cover rounded-lg border border-gray-300 mx-auto"></div>` : ''}
    `;
        }

        // ฟังก์ชันแสดงชื่อไฟล์ที่เลือก
        function showFileName(file) {
            let fileIcon = 'fa-file-image';
            const extension = file.name.split('.').pop().toLowerCase();

            uploadContent.innerHTML = `
                <div class="w-16 h-16 bg-blue-100 rounded-full text-blue-500 flex items-center justify-center">
                    <i class="fas ${fileIcon} text-2xl"></i>
                </div>
                <div class="text-center">
                    <p class="font-medium text-gray-900 mb-1">ไฟล์ที่เลือก:</p>
                    <p class="file-name text-sm text-gray-700 bg-gray-100 px-3 py-2 rounded-lg">
                        <i class="fas fa-file mr-2 text-blue-500"></i>
                        ${file.name}
                    </p>
                    <p class="text-xs text-gray-500 mt-2">ขนาด: ${formatFileSize(file.size)}</p>
                </div>
                <button type="button" class="px-4 py-2 bg-gray-200 text-gray-700 rounded-lg text-sm font-medium hover:bg-gray-300 transition-colors duration-300" id="changeFileButton">
                    <i class="fas fa-exchange-alt mr-2"></i>เปลี่ยนไฟล์
                </button>
            `;

            // เพิ่ม event listener สำหรับปุ่มเปลี่ยนไฟล์
            document.getElementById('changeFileButton').addEventListener('click', function(e) {
                e.stopPropagation();
                fileInput.value = '';
                showInitialState();
            });
        }

        // ฟังก์ชันจัดรูปแบบขนาดไฟล์
        function formatFileSize(bytes) {
            if (bytes === 0) return '0 Bytes';
            const k = 1024;
            const sizes = ['Bytes', 'KB', 'MB', 'GB'];
            const i = Math.floor(Math.log(bytes) / Math.log(k));
            return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
        }

        // ฟังก์ชันจัดการเมื่อเลือกไฟล์
        function handleFileSelect() {
            if (this.files && this.files[0]) {
                const file = this.files[0];

                // ตรวจสอบประเภทไฟล์
                const validTypes = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];
                if (!validTypes.includes(file.type)) {
                    alert('กรุณาเลือกไฟล์ภาพที่รองรับ (JPG, PNG, WebP, GIF)');
                    this.value = '';
                    showInitialState();
                    return;
                }

                // ตรวจสอบขนาดไฟล์ (ไม่เกิน 10MB)
                if (file.size > 10 * 1024 * 1024) {
                    alert('ขนาดไฟล์ต้องไม่เกิน 10MB');
                    this.value = '';
                    showInitialState();
                    return;
                }

                showFileName(file);
            }
        }

        // ฟังก์ชัน绑定 events
        function bindFileInputEvents() {
            // เมื่อเลือกไฟล์
            fileInput.addEventListener('change', handleFileSelect);

            // ลากและวางไฟล์
            uploadArea.addEventListener('dragover', function(e) {
                e.preventDefault();
                this.classList.add('dragover');
            });

            uploadArea.addEventListener('dragleave', function() {
                this.classList.remove('dragover');
            });

            uploadArea.addEventListener('drop', function(e) {
                e.preventDefault();
                this.classList.remove('dragover');

                if (e.dataTransfer.files.length) {
                    fileInput.files = e.dataTransfer.files;
                    const event = new Event('change');
                    fileInput.dispatchEvent(event);
                }
            });
        }

        // ป้องกันการ submit ซ้ำ
        form.addEventListener('submit', function(e) {
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i> กำลังอัปเดท...';
        });

        // เริ่มต้น binding events
        bindFileInputEvents();
    </script>
</body>

</html>