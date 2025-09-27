<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
require __DIR__ . '/../includes/db_connect.php';
require '../auth/auth.php';
requireLogin();

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // รับค่าจากฟอร์ม
    $service_id       = $_POST['service_id'];
    $project_name     = $_POST['project_name'];
    $poster_type      = $_POST['poster_type'];
    $objective        = $_POST['objective'];
    $target_audience  = $_POST['target_audience'];
    $main_message     = $_POST['main_message'];
    $content          = $_POST['content'];
    $size             = $_POST['size'];
    $custom_width     = !empty($_POST['custom_width']) ? $_POST['custom_width'] : null;
    $custom_height    = !empty($_POST['custom_height']) ? $_POST['custom_height'] : null;
    $style            = $_POST['style'];
    $color_primary    = $_POST['color_primary'];
    $color_secondary  = $_POST['color_secondary'];
    $color_accent     = $_POST['color_accent'];
    $preferred_fonts  = $_POST['preferred_fonts'];
    $color_codes      = $_POST['color_codes'];
    $orientation      = $_POST['orientation'];
    $color_mode       = $_POST['color_mode'];
    $budget_range     = $_POST['budget_range'];
    $due_date         = $_POST['due_date'];
    $avoid_elements   = $_POST['avoid_elements'];
    $special_requirements = $_POST['special_requirements'];
    $reference_link   = $_POST['reference_link'];

    // 1. Insert ข้อมูลโปสเตอร์ (ยังไม่อัปโหลดไฟล์)
    $stmt = $conn->prepare("INSERT INTO poster_details
        (service_id, project_name, poster_type, objective, target_audience, main_message, content,
        size, custom_width, custom_height, style, color_primary, color_secondary, color_accent, 
        preferred_fonts, color_codes, orientation, color_mode, logo_file, images_file, reference_file, reference_link,
        budget_range, due_date, avoid_elements, special_requirements)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");

    $stmt->bind_param(
        "isssssssssssssssssssssssss",
        $service_id,
        $project_name,
        $poster_type,
        $objective,
        $target_audience,
        $main_message,
        $content,
        $size,
        $custom_width,
        $custom_height,
        $style,
        $color_primary,
        $color_secondary,
        $color_accent,
        $preferred_fonts,
        $color_codes,
        $orientation,
        $color_mode,
        $logo_file_path,
        $images_file_path,
        $reference_file_path,
        $reference_link,
        $budget_range,
        $due_date,
        $avoid_elements,
        $special_requirements
    );
    if (!$stmt->execute()) {
        $error = "เกิดข้อผิดพลาด: " . $stmt->error;
    } else {
        $poster_id = $stmt->insert_id;

        // 2. สร้างโฟลเดอร์ตาม id
        $posterDir = ROOT_PATH . "/uploads/posters/$poster_id/";
        if (!file_exists($posterDir)) {
            mkdir($posterDir, 0777, true);
        }

        // 3. อัปโหลดไฟล์เข้าโฟลเดอร์นี้ (จำกัด 4 ไฟล์ต่อ field)
        $logo_file_path = null;
        if (!empty($_FILES['logo_file']['name'][0])) {
            $logo_paths = [];
            $fileNames = $_FILES['logo_file']['name'];
            $fileTmpNames = $_FILES['logo_file']['tmp_name'];
            $count = min(count($fileNames), 4);
            for ($i = 0; $i < $count; $i++) {
                if (!empty($fileNames[$i])) {
                    $shortName = substr(uniqid("logo_"), 0, 18) . "_" . basename($fileNames[$i]);
                    $path = $posterDir . $shortName;
                    move_uploaded_file($fileTmpNames[$i], $path);
                    $logo_paths[] = "/uploads/posters/$poster_id/$shortName";
                }
            }
            $logo_file_path = implode(',', $logo_paths);
        }

        $images_file_path = null;
        if (!empty($_FILES['images_file']['name'][0])) {
            $img_paths = [];
            $fileNames = $_FILES['images_file']['name'];
            $fileTmpNames = $_FILES['images_file']['tmp_name'];
            $count = min(count($fileNames), 4);
            for ($i = 0; $i < $count; $i++) {
                if (!empty($fileNames[$i])) {
                    $shortName = substr(uniqid("img_"), 0, 18) . "_" . basename($fileNames[$i]);
                    $path = $posterDir . $shortName;
                    move_uploaded_file($fileTmpNames[$i], $path);
                    $img_paths[] = "/uploads/posters/$poster_id/$shortName";
                }
            }
            $images_file_path = implode(',', $img_paths);
        }

        $reference_file_path = null;
        if (!empty($_FILES['reference_file']['name'][0])) {
            $ref_paths = [];
            $fileNames = $_FILES['reference_file']['name'];
            $fileTmpNames = $_FILES['reference_file']['tmp_name'];
            $count = min(count($fileNames), 4);
            for ($i = 0; $i < $count; $i++) {
                if (!empty($fileNames[$i])) {
                    $shortName = substr(uniqid("ref_"), 0, 18) . "_" . basename($fileNames[$i]);
                    $path = $posterDir . $shortName;
                    move_uploaded_file($fileTmpNames[$i], $path);
                    $ref_paths[] = "/uploads/posters/$poster_id/$shortName";
                }
            }
            $reference_file_path = implode(',', $ref_paths);
        }

        // 4. อัปเดต path ไฟล์ในฐานข้อมูล
        $updateStmt = $conn->prepare("UPDATE poster_details SET logo_file=?, images_file=?, reference_file=? WHERE poster_id=?");
        $updateStmt->bind_param("sssi", $logo_file_path, $images_file_path, $reference_file_path, $poster_id);
        if ($updateStmt->execute()) {
            $success = "บันทึกข้อมูลสำเร็จ!";
        } else {
            $error = "เกิดข้อผิดพลาด: " . $updateStmt->error;
        }

        // หลังจาก $poster_id = $stmt->insert_id; และอัปโหลดไฟล์เสร็จแล้ว
        $customer_id = $_SESSION['customer_id'];

        $orderStmt = $conn->prepare("INSERT INTO orders (customer_id, service_id, ref_id, status) VALUES (?, ?, ?, 'pending')");
        $orderStmt->bind_param("iii", $customer_id, $service_id, $poster_id);
        if ($orderStmt->execute()) {
            // สำเร็จ
            $order_id = $orderStmt->insert_id;

            // สร้างรหัส order_code เช่น #OD-2025-0001
            $year = date('Y');
            $order_code = sprintf("OD-%s-%04d", $year, $order_id);

            // อัปเดต order_code ลงในตาราง
            $updateOrderCode = $conn->prepare("UPDATE orders SET order_code=? WHERE order_id=?");
            $updateOrderCode->bind_param("si", $order_code, $order_id);
            $updateOrderCode->execute();

            header("Location: payment.php?order_id=" . $order_id);
            exit;
        } else {
            $error = "เกิดข้อผิดพลาดในการสร้างออเดอร์: " . $orderStmt->error;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Poster Details</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=IBM+Plex+Sans+Thai:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        .font-thai {
            font-family: 'IBM Plex Sans Thai', sans-serif;
        }
    </style>
</head>

<body class="min-h-screen bg-zinc-100 py-8 font-thai">
    <div class="mx-auto max-w-4xl px-4">
        <div class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
            <!-- Header -->
            <div class="mb-4 flex items-center border-b border-gray-200 px-8 py-6">
                <div class="mr-4 rounded-xl bg-zinc-900 p-3">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="h-6 w-6 text-white">
                        <path fill-rule="evenodd"
                            d="M5.625 1.5c-1.036 0-1.875.84-1.875 1.875v17.25c0 1.035.84 1.875 1.875 1.875h12.75c1.035 0 1.875-.84 1.875-1.875V12.75A3.75 3.75 0 0 0 16.5 9h-1.875a1.875 1.875 0 0 1-1.875-1.875V5.25A3.75 3.75 0 0 0 9 1.5H5.625ZM7.5 15a.75.75 0 0 1 .75-.75h7.5a.75.75 0 0 1 0 1.5h-7.5A.75.75 0 0 1 7.5 15Zm.75 2.25a.75.75 0 0 0 0 1.5H12a.75.75 0 0 0 0-1.5H8.25Z"
                            clip-rule="evenodd" />
                        <path
                            d="M12.971 1.816A5.23 5.23 0 0 1 14.25 5.25v1.875c0 .207.168.375.375.375H16.5a5.23 5.23 0 0 1 3.434 1.279 9.768 9.768 0 0 0-6.963-6.963Z" />
                    </svg>
                </div>

                <div class="">
                    <h1 class="flex items-center text-2xl font-bold text-zinc-900">
                        ออกแบบโปสเตอร์
                    </h1>
                    <p class="text-gray-600">
                        กรอกข้อมูลให้ครบถ้วนเพื่อให้นักออกแบบเข้าใจความต้องการของคุณ
                    </p>
                </div>
            </div>

            <!-- Progress Bar -->
            <div class="form-card mx-8 mt-8 rounded-xl bg-white p-4 shadow-sm ring-1 ring-gray-200">
                <div class="mb-2 flex items-center justify-between text-sm text-gray-600">
                    <span>ข้อมูลโครงการ</span>
                    <span>ข้อมูลเทคนิค</span>
                    <span>ข้อมูลไฟล์</span>
                    <span>ข้อมูลการดำเนินงาน</span>
                    <span>ตรวจสอบข้อมูล</span>
                </div>
                <div class="h-2 w-full rounded-full bg-gray-200">
                    <div class="h-2 rounded-full bg-zinc-900 transition-all duration-500" style="width: 0%" id="progressBar">
                    </div>
                </div>
            </div>

            <form method="POST" enctype="multipart/form-data" id="designForm">
                <input type="hidden" name="service_id" value="1">
                <!-- Section 1: Project Information -->
                <div class="form-section form-card rounded-xl bg-white m-8 p-6 shadow-sm ring-1 ring-gray-200" data-section="1">
                    <div class="mb-6 flex items-center">
                        <div class="mr-4 rounded-full bg-zinc-100 p-3">
                            <svg class="h-6 w-6 text-zinc-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z">
                                </path>
                            </svg>
                        </div>
                        <h2 class="text-xl font-semibold text-gray-900">
                            ข้อมูลเกี่ยวกับโครงการ
                        </h2>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 lg:gap-6">
                        <div class="mb-4">
                            <label class="mb-2 block text-sm font-medium text-gray-700">ชื่อโครงการ/กิจกรรม
                                <span class="text-red-500">*</span></label>
                            <input type="text" name="project_name" required
                                class="block w-full rounded-lg border border-gray-300 bg-gray-50 p-2.5 text-sm text-gray-900 focus:border-blue-500 focus:ring-blue-500"
                                placeholder="เช่น โครงการรณรงค์ลดโลกร้อน" />
                        </div>

                        <div class="mb-4">
                            <label for="poster_type" class="mb-2 block text-sm font-medium text-gray-700">ประเภทโปสเตอร์ <span
                                    class="text-red-500">*</span></label>
                            <select id="poster_type" name="poster_type" required
                                class="block w-full rounded-lg border border-gray-300 bg-gray-50 p-2.5 text-sm text-gray-900 focus:border-blue-500 focus:ring-blue-500">
                                <option value="">เลือกประเภท</option>
                                <option value="ประชาสัมพันธ์">โปสเตอร์ประชาสัมพันธ์</option>
                                <option value="กิจกรรม">โปสเตอร์กิจกรรม</option>
                                <option value="โฆษณา">โปสเตอร์โฆษณา</option>
                                <option value="การศึกษา">โปสเตอร์การศึกษา</option>
                                <option value="นิทรรศการ">โปสเตอร์นิทรรศการ</option>
                                <option value="ประกวด">โปสเตอร์ประกวด</option>
                                <option value="อื่นๆ">อื่นๆ</option>
                            </select>
                        </div>
                    </div>
                    <div class="mb-4">
                        <label class="mb-2 block text-sm font-medium text-gray-700">วัตถุประสงค์ของโปสเตอร์
                            <span class="text-red-500">*</span></label>
                        <textarea name="objective" required rows="3"
                            class="block w-full rounded-lg border border-gray-300 bg-gray-50 p-2.5 text-sm text-gray-900 focus:border-blue-500 focus:ring-blue-500"
                            placeholder="อธิบายว่าต้องการให้โปสเตอร์สื่อสารอะไรกับผู้ชม"></textarea>
                    </div>

                    <div class="mb-4">
                        <label class="mb-2 block text-sm font-medium text-gray-700">กลุ่มเป้าหมาย <span
                                class="text-red-500">*</span></label>
                        <input type="text" name="target_audience" required
                            class="block w-full rounded-lg border border-gray-300 bg-gray-50 p-2.5 text-sm text-gray-900 focus:border-blue-500 focus:ring-blue-500"
                            placeholder="เช่น นักเรียน, ผู้ปกครอง, บุคคลทั่วไป" />
                    </div>

                    <div class="mb-4">
                        <label class="mb-2 block text-sm font-medium text-gray-700">ข้อความหลัก <span
                                class="text-red-500">*</span></label>
                        <textarea name="main_message" required rows="2"
                            class="block w-full rounded-lg border border-gray-300 bg-gray-50 p-2.5 text-sm text-gray-900 focus:border-blue-500 focus:ring-blue-500"
                            placeholder="ข้อความสำคัญที่ต้องการสื่อ"></textarea>
                    </div>

                    <div>
                        <label class="mb-2 block text-sm font-medium text-gray-700">เนื้อหาที่ต้องการ <span
                                class="text-red-500">*</span></label>
                        <textarea name="content" required rows="4"
                            class="block w-full rounded-lg border border-gray-300 bg-gray-50 p-2.5 text-sm text-gray-900 focus:border-blue-500 focus:ring-blue-500"
                            placeholder="เช่น ชื่อโครงการ วันที่ สถานที่ เนื้อหาสำคัญ โลโก้องค์กร เบอร์ติดต่อ"></textarea>
                    </div>
                </div>

                <!-- Section 2: Technical Requirements -->
                <div class="form-section form-card rounded-xl bg-white m-8 p-6 shadow-sm ring-1 ring-gray-200" data-section="2">
                    <div class="mb-6 flex items-center">
                        <div class="mr-4 rounded-full bg-zinc-100 p-3">
                            <svg class="h-6 w-6 text-zinc-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z">
                                </path>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                            </svg>
                        </div>
                        <h2 class="text-xl font-semibold text-gray-900">ข้อมูลเทคนิค</h2>
                    </div>
                    <div class="mb-6">
                        <label class="mb-2 block text-sm font-medium text-gray-700">ขนาดโปสเตอร์ <span
                                class="text-red-500">*</span></label>
                        <div class="grid grid-cols-2 gap-3 sm:grid-cols-3">
                            <label class="block">
                                <input type="radio" name="size" value="A4" class="peer hidden" required />
                                <div
                                    class="size-option cursor-pointer rounded-lg border border-gray-200 p-4 text-center peer-checked:border-blue-500 peer-checked:bg-blue-50">
                                    <p class="font-medium">A4</p>
                                    <p class="text-sm text-gray-500">21 × 29.7 cm</p>
                                </div>
                            </label>
                            <label class="block">
                                <input type="radio" name="size" value="A3" class="peer hidden" />
                                <div
                                    class="size-option cursor-pointer rounded-lg border border-gray-200 p-4 text-center peer-checked:border-blue-500 peer-checked:bg-blue-50">
                                    <p class="font-medium">A3</p>
                                    <p class="text-sm text-gray-500">29.7 × 42 cm</p>
                                </div>
                            </label>
                            <label class="block">
                                <input type="radio" name="size" value="A2" class="peer hidden" />
                                <div
                                    class="size-option cursor-pointer rounded-lg border border-gray-200 p-4 text-center peer-checked:border-blue-500 peer-checked:bg-blue-50">
                                    <p class="font-medium">A2</p>
                                    <p class="text-sm text-gray-500">42 × 59.4 cm</p>
                                </div>
                            </label>
                            <label class="block">
                                <input type="radio" name="size" value="A1" class="peer hidden" />
                                <div
                                    class="size-option cursor-pointer rounded-lg border border-gray-200 p-4 text-center peer-checked:border-blue-500 peer-checked:bg-blue-50">
                                    <p class="font-medium">A1</p>
                                    <p class="text-sm text-gray-500">59.4 × 84.1 cm</p>
                                </div>
                            </label>
                            <label class="block">
                                <input type="radio" name="size" value="A0" class="peer hidden" />
                                <div
                                    class="size-option cursor-pointer rounded-lg border border-gray-200 p-4 text-center peer-checked:border-blue-500 peer-checked:bg-blue-50">
                                    <p class="font-medium">A0</p>
                                    <p class="text-sm text-gray-500">84.1 × 118.9 cm</p>
                                </div>
                            </label>
                            <label class="block">
                                <input type="radio" name="size" value="custom" class="peer hidden" />
                                <div
                                    class="size-option cursor-pointer rounded-lg border border-gray-200 p-4 text-center peer-checked:border-blue-500 peer-checked:bg-blue-50">
                                    <p class="font-medium">กำหนดเอง</p>
                                    <p class="text-sm text-gray-500">ระบุขนาดด้านล่าง</p>
                                </div>
                            </label>
                        </div>
                        <div id="custom-size-fields" class="my-4 hidden">
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label for="custom_width" class="mb-2 block text-sm font-medium text-gray-700">ความกว้าง (ซม.)</label>
                                    <input type="number" name="custom_width"
                                        class="w-full rounded-lg border border-gray-300 px-4 py-2 focus:border-blue-500 focus:ring-blue-500"
                                        placeholder="เช่น 50">
                                </div>
                                <div>
                                    <label for="custom_height" class="mb-2 block text-sm font-medium text-gray-700">ความสูง (ซม.)</label>
                                    <input type="number" name="custom_height"
                                        class="w-full rounded-lg border border-gray-300 px-4 py-2 focus:border-blue-500 focus:ring-blue-500"
                                        placeholder="เช่น 70">
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:gap-6">
                        <div class="mb-6">
                            <label for="orientation" class="mb-2 block text-sm font-medium text-gray-700">การวางแนว <span
                                    class="text-red-500">*</span></label>
                            <select id="orientation" name="orientation" required
                                class="block w-full rounded-lg border border-gray-300 bg-gray-50 p-2.5 text-sm text-gray-900 focus:border-blue-500 focus:ring-blue-500">
                                <option value="">เลือกการวางแนว</option>
                                <option value="แนวตั้ง">แนวตั้ง (Portrait)</option>
                                <option value="แนวนอน">แนวนอน (Landscape)</option>
                                <option value="สี่เหลี่ยมจัตุรัส">
                                    สี่เหลี่ยมจัตุรัส (Square)
                                </option>
                                <option value="ไม่แน่ใจ">ยังไม่แน่ใจ</option>
                            </select>
                        </div>
                        <div class="mb-6">
                            <label for="style" class="mb-2 block text-sm font-medium text-gray-700">สไตล์ที่ต้องการ</label>
                            <select id="style" name="style"
                                class="block w-full rounded-lg border border-gray-300 bg-gray-50 p-2.5 text-sm text-gray-900 focus:border-blue-500 focus:ring-blue-500">
                                <option value="">เลือกสไตล์</option>
                                <option value="มินิมอล">มินิมอล</option>
                                <option value="โมเดิร์น">โมเดิร์น</option>
                                <option value="วินเทจ">วินเทจ</option>
                                <option value="ภาพวาด">ภาพวาดวาด</option>
                                <option value="ภาพถ่าย">ภาพถ่าย</option>
                                <option value="ตัวอักษร">ตัวอักษร</option>
                            </select>
                        </div>
                    </div>
                    <div class="">
                        <h3 class="text-lg font-semibold text-gray-800 mb-4">เลือกสี</h3>
                        <div class="grid grid-cols-2 lg:gap-6">
                            <div class="space-y-4">
                                <!-- แก้ไขในส่วนของสีหลัก -->
                                <div class="flex items-center space-x-4">
                                    <label for="color_primary" class="mb-2 block text-sm font-medium text-gray-700 w-20">สีหลัก <span
                                            class="text-red-500">*</span></label>
                                    <div class="flex items-center space-x-3">
                                        <input type="color" id="color_picker_primary" value="#4F46E5"
                                            class="color-picker-preview w-12 h-12 rounded-xl"
                                            onchange="document.getElementById('color_primary').value = this.value">
                                        <input type="text" id="color_primary" name="color_primary" value="#4F46E5"
                                            class="w-24 px-3 py-2 border border-gray-300 rounded-lg text-sm"
                                            onchange="document.getElementById('color_picker_primary').value = this.value">
                                    </div>
                                </div>

                                <!-- ทำแบบเดียวกันสำหรับสีรองและสีเน้น -->
                                <div class="flex items-center space-x-4">
                                    <label for="color_secondary" class="mb-2 block text-sm font-medium text-gray-700 w-20">สีรอง</label>
                                    <div class="flex items-center space-x-3">
                                        <input type="color" id="color_picker_secondary" value="#ffffff"
                                            class="color-picker-preview w-12 h-12 rounded-xl"
                                            onchange="document.getElementById('color_secondary').value = this.value">
                                        <input type="text" id="color_secondary" name="color_secondary" value="#ffffff"
                                            class="w-24 px-3 py-2 border border-gray-300 rounded-lg text-sm"
                                            onchange="document.getElementById('color_picker_secondary').value = this.value">
                                    </div>
                                </div>

                                <div class="flex items-center space-x-4">
                                    <label for="color_accent" class="mb-2 block text-sm font-medium text-gray-700 w-20">สีเน้น</label>
                                    <div class="flex items-center space-x-3">
                                        <input type="color" id="color_picker_accent" value="#ffffff"
                                            class="color-picker-preview w-12 h-12 rounded-xl"
                                            onchange="document.getElementById('color_accent').value = this.value">
                                        <input type="text" id="color_accent" name="color_accent" value="#ffffff"
                                            class="w-24 px-3 py-2 border border-gray-300 rounded-lg text-sm"
                                            onchange="document.getElementById('color_picker_accent').value = this.value">
                                    </div>
                                </div>
                            </div>
                            <div class="">
                                <label for="color_mode" class="block text-sm font-medium text-gray-700 mb-1">โหมดสี</label>
                                <select id="color_mode" name="color_mode"
                                    class="block w-full rounded-lg border border-gray-300 bg-gray-50 p-2.5 text-sm text-gray-900 focus:border-blue-500 focus:ring-blue-500">
                                    <option value="both">ทั้ง CMYK และ RGB</option>
                                    <option value="CMYK">CMYK (สำหรับพิมพ์)</option>
                                    <option value="RGB">RGB (สำหรับออนไลน์)</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:gap-6">
                        <div class="mt-6">
                            <label class="block text-sm font-medium text-gray-700 mb-1">ฟอนต์ที่ต้องการใช้ (ถ้ามี)</label>
                            <input type="text" name="preferred_fonts"
                                class="block w-full rounded-lg border border-gray-300 bg-gray-50 p-2.5 text-sm text-gray-900 focus:border-blue-500 focus:ring-blue-500"
                                placeholder="เช่น TH Sarabun New, Kanit, Anuphan">
                        </div>
                        <div class="mt-6">
                            <label class="block text-sm font-medium text-gray-700 mb-1">โค้ดสีที่ต้องการ (ถ้ามี)</label>
                            <input type="text" name="color_codes"
                                class="block w-full rounded-lg border border-gray-300 bg-gray-50 p-2.5 text-sm text-gray-900 focus:border-blue-500 focus:ring-blue-500"
                                placeholder="เช่น #4F46E5, #10B981, #F59E0B">
                        </div>
                    </div>
                </div>
                <!-- Section 3: Content -->
                <div class="form-section form-card rounded-xl bg-white m-8 p-6 shadow-sm ring-1 ring-gray-200" data-section="3">
                    <div class="mb-6 flex items-center">
                        <div class="mr-4 rounded-full bg-zinc-100 p-3">
                            <svg class="h-6 w-6 text-zinc-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z">
                                </path>
                            </svg>
                        </div>
                        <h2 class="text-xl font-semibold text-gray-900">ไฟล์และประกอบเพิ่มเติม</h2>
                    </div>
                    <div class="mb-6">
                        <label for="logo_file" class="block text-sm font-medium text-gray-700 mb-2">ไฟล์โลโก้องค์กร/บริษัท</label>
                        <div
                            class="file-upload-area border-2 border-dashed border-gray-300 bg-gray-50 rounded-xl p-6 text-center hover:border-gray-400 cursor-pointer transition-colors duration-300">
                            <svg class="mx-auto h-12 w-12 text-gray-400 mb-4" fill="none" stroke="currentColor" viewBox="0 0 48 48">
                                <path
                                    d="M28 8H12a4 4 0 00-4 4v20m32-12v8m0 0v8a4 4 0 01-4 4H12a4 4 0 01-4-4v-4m32-4l-3.172-3.172a4 4 0 00-5.656 0L28 28M8 32l9.172-9.172a4 4 0 015.656 0L28 28m0 0l4 4m4-24h8m-4-4v8m-12 4h.02"
                                    stroke-width="2" stroke-linecap="round" stroke-linejoin="round"></path>
                            </svg>
                            <input id="logo_file" type="file" name="logo_file[]" class="hidden" accept="image/*,.ai,.eps,.svg" multiple onchange="handleFileUpload(this, 'logo-preview')">
                            <p class="text-sm text-gray-600">คลิกเพื่อเลือกไฟล์โลโก้</p>
                            <p class="text-xs text-gray-400 mt-1">PNG, JPG, AI, EPS, SVG</p>
                            <div id="logo-preview" class="mt-2 hidden"></div>
                        </div>
                    </div>

                    <div class="mb-6">
                        <label for="images_file" class="block text-sm font-semibold text-gray-700 mb-2">อัพโหลดรูปภาพ</label>
                        <div
                            class="file-upload-area border-2 border-dashed border-gray-300 bg-gray-50 rounded-xl p-6 text-center hover:border-gray-400 cursor-pointer transition-colors duration-300">
                            <svg class="mx-auto h-12 w-12 text-gray-400 mb-4" fill="none" stroke="currentColor" viewBox="0 0 48 48">
                                <path
                                    d="M28 8H12a4 4 0 00-4 4v20m32-12v8m0 0v8a4 4 0 01-4 4H12a4 4 0 01-4-4v-4m32-4l-3.172-3.172a4 4 0 00-5.656 0L28 28M8 32l9.172-9.172a4 4 0 015.656 0L28 28m0 0l4 4m4-24h8m-4-4v8m-12 4h.02"
                                    stroke-width="2" stroke-linecap="round" stroke-linejoin="round"></path>
                            </svg>
                            <input id="images_file" type="file" name="images_file[]" class="hidden" accept="image/*" multiple
                                onchange="handleFileUpload(this, 'images-preview')">
                            <p class="text-sm text-gray-600">คลิกเพื่อเลือกรูปภาพ</p>
                            <p class="text-xs text-gray-400 mt-1">PNG, JPG (หลายไฟล์ได้)</p>
                            <div id="images-preview" class="mt-2 hidden"></div>
                        </div>
                    </div>

                    <div class="mb-6">
                        <label for="reference_file" class="block text-sm font-semibold text-gray-700 mb-2">ตัวอย่างงานที่ชอบ</label>
                        <div
                            class="file-upload-area border-2 border-dashed border-gray-300 bg-gray-50 rounded-xl p-6 text-center hover:border-gray-400 cursor-pointer transition-colors duration-300">
                            <svg class="mx-auto h-12 w-12 text-gray-400 mb-4" fill="none" stroke="currentColor" viewBox="0 0 48 48">
                                <path
                                    d="M28 8H12a4 4 0 00-4 4v20m32-12v8m0 0v8a4 4 0 01-4 4H12a4 4 0 01-4-4v-4m32-4l-3.172-3.172a4 4 0 00-5.656 0L28 28M8 32l9.172-9.172a4 4 0 015.656 0L28 28m0 0l4 4m4-24h8m-4-4v8m-12 4h.02"
                                    stroke-width="2" stroke-linecap="round" stroke-linejoin="round"></path>
                            </svg>
                            <input id="reference_file" type="file" name="reference_file[]" class="hidden" accept="image/*,.pdf" multiple onchange="handleFileUpload(this, 'reference-preview')">
                            <p class="text-sm text-gray-600">อัพโหลดตัวอย่าง</p>
                            <p class="text-xs text-gray-400 mt-1">PNG, JPG, PDF</p>
                            <div id="reference-preview" class="mt-2 hidden"></div>
                        </div>
                    </div>

                    <div>
                        <label class="mb-2 block text-sm font-medium text-gray-700">ลิงก์ตัวอย่างอื่นๆ (ถ้ามี)</label>
                        <input type="url" name="reference_link"
                            class="block w-full rounded-lg border border-gray-300 bg-gray-50 p-2.5 text-sm text-gray-900 focus:border-blue-500 focus:ring-blue-500"
                            placeholder="เช่น Pinterest, Behance, หรือเว็บไซต์อื่นๆ">
                        <p class="text-xs text-gray-500 mt-1.5">ลิงก์ Pinterest, Behance หรือเว็บไซต์ที่มีตัวอย่างที่ชอบ</p>
                    </div>
                </div>

                <!-- Section 4: Additional Information -->
                <div class="form-section form-card rounded-xl bg-white m-8 p-6 shadow-sm ring-1 ring-gray-200" data-section="4">
                    <div class="mb-6 flex items-center">
                        <div class="mr-4 rounded-full bg-zinc-100 p-3">
                            <svg class="h-6 w-6 text-zinc-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                        </div>
                        <h2 class="text-xl font-semibold text-gray-900">
                            ข้อมูลการดำเนินงาน
                        </h2>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 lg:gap-6">
                        <div class="mb-6">
                            <label for="budget_range" class="mb-2 block text-sm font-medium text-gray-700">งบประมาณ <span
                                    class="text-red-500">*</span></label>
                            <select id="budget_range" name="budget_range" required
                                class="block w-full rounded-lg border border-gray-300 bg-gray-50 p-2.5 text-sm text-gray-900 focus:border-blue-500 focus:ring-blue-500">
                                <option value="">เลือกงบประมาณ</option>
                                <option value="1000">1,000 บาท</option>
                                <option value="2000">2,000 บาท</option>
                                <option value="3000">3,000 บาท</option>
                                <option value="4000">4,000 บาท</option>
                                <option value="5000">5,000 บาท</option>
                            </select>
                        </div>
                        <div class="mb-6">
                            <label for="due_date" class="mb-2 block text-sm font-medium text-gray-700">วันที่ต้องการรับงาน <span
                                    class="text-red-500">*</span></label>
                            <input id="due_date" type="date" name="due_date" required
                                class="block w-full rounded-lg border border-gray-300 bg-gray-50 p-2.5 text-sm text-gray-900 focus:border-blue-500 focus:ring-blue-500"
                                min="<?= date('Y-m-d', strtotime('+7 days')) ?>">
                            <p class="text-xs text-gray-500 mt-1.5">⚠️ การออกแบบต้องใช้เวลาอย่างน้อยขั้นต่ำ 7 วัน</p>
                        </div>
                    </div>
                    <div class="mb-6">
                        <label class="mb-2 block text-sm font-medium text-gray-700">สิ่งที่ไม่ต้องการ</label>
                        <textarea name="avoid_elements" rows="3"
                            class="w-full rounded-lg border border-gray-300 bg-gray-50 p-2.5 text-sm text-gray-900 focus:border-blue-500 focus:ring-blue-500"
                            placeholder="เช่น ไม่ต้องการใช้สีฟ้า, ไม่อยากเห็นรูปเด็ก, ฯลฯ"></textarea>
                    </div>

                    <div>
                        <label class="mb-2 block text-sm font-medium text-gray-700">ข้อกำหนดพิเศษ (ถ้ามี)</label>
                        <textarea name="special_requirements" rows="3"
                            class="w-full rounded-lg border border-gray-300 bg-gray-50 p-2.5 text-sm text-gray-900 focus:border-blue-500 focus:ring-blue-500"
                            placeholder="เช่น ต้องมี QR Code, ต้องเว้นพื้นที่สำหรับตราประทับ, ต้องส่งไฟล์ต้นฉบับ"></textarea>
                    </div>

                </div>

                <!-- Section 5: Review Section -->
                <div class="form-section form-card rounded-xl bg-white m-8 p-6 shadow-sm ring-1 ring-gray-200" data-section="5">
                    <div class="mb-6 flex items-center">
                        <div class="mr-4 rounded-full bg-zinc-100 p-3">
                            <svg class="h-6 w-6 text-zinc-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                        </div>
                        <h2 class="text-xl font-semibold text-gray-900">
                            ตรวจสอบข้อมูลก่อนส่ง
                        </h2>
                    </div>

                    <div class="">
                        <div id="reviewContent" class="space-y-6">
                            <!-- Review content will be generated here -->
                        </div>
                    </div>
                    <div class="p-4 mt-6 bg-blue-50 rounded-xl">
                        <div class="flex items-center">
                            <svg class="h-5 w-5 text-blue-600 mt-0.5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                            <p class="text-sm text-blue-700">
                                โปรดตรวจสอบข้อมูลทั้งหมดอีกครั้งก่อนกดส่ง หากข้อมูลไม่ถูกต้อง สามารถกดปุ่ม <span class="font-semibold">"ย้อนกลับ"</span> เพื่อแก้ไขข้อมูลได้
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Navigation Buttons -->
                <div class="flex items-center justify-between m-8">
                    <button type="button" id="prevBtn"
                        class="hidden bg-zinc-200 text-zinc-600 font-bold rounded-xl px-6 py-2 text-center transition-all duration-300 ease-in-out hover:scale-105 items-center justify-center">
                        ย้อนกลับ
                    </button>
                    <div class="ml-auto flex space-x-3">
                        <button type="button" id="nextBtn"
                            class="text-white bg-zinc-900 hover:bg-zinc-800 font-bold rounded-xl px-6 py-2 text-center transition-all duration-300 ease-in-out hover:scale-105 flex items-center justify-center">
                            ถัดไป
                        </button>
                        <button type="button" id="reviewBtn"
                            class="hidden text-white bg-blue-600 hover:bg-blue-700 font-bold rounded-xl px-6 py-2 text-center transition-all duration-300 ease-in-out hover:scale-105 items-center justify-center">
                            ตรวจสอบข้อมูล
                        </button>
                        <button type="button" id="editBtn"
                            class="hidden bg-zinc-200 text-zinc-600 font-bold rounded-xl px-6 py-2 text-center transition-all duration-300 ease-in-out hover:scale-105 items-center justify-center">
                            แก้ไขข้อมูล
                        </button>
                        <button type="submit" id="submitBtn"
                            class="hidden text-white bg-green-600 hover:bg-green-700 font-bold rounded-xl px-6 py-2 text-center transition-all duration-300 ease-in-out hover:scale-105 items-center justify-center">
                            ส่งข้อมูล
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <script>
        let currentSection = 1;
        const totalSections = 5; // เพิ่มส่วนตรวจสอบข้อมูล

        function updateProgress() {
            const progress = ((currentSection - 1) / (totalSections - 1)) * 100;
            document.getElementById("progressBar").style.width = progress + "%";
        }

        function showSection(section) {
            // Hide all sections
            document
                .querySelectorAll(".form-section")
                .forEach((s) => s.classList.add("hidden"));
            // Show current section
            document
                .querySelector(`[data-section="${section}"]`)
                .classList.remove("hidden");

            // Update buttons
            document
                .getElementById("prevBtn")
                .classList.toggle("hidden", section === 1);
            document
                .getElementById("nextBtn")
                .classList.toggle("hidden", section === totalSections - 1 || section === totalSections);
            document
                .getElementById("reviewBtn")
                .classList.toggle("hidden", section !== totalSections - 1);
            document
                .getElementById("editBtn")
                .classList.toggle("hidden", section !== totalSections);
            document
                .getElementById("submitBtn")
                .classList.toggle("hidden", section !== totalSections);

            updateProgress();

            // หากเป็นส่วนตรวจสอบข้อมูล ให้สร้างข้อมูลสำหรับแสดง
            if (section === totalSections) {
                generateReviewContent();
            }
        }

        // สร้างข้อมูลสำหรับแสดงในส่วนตรวจสอบ
        function generateReviewContent() {
            const form = document.getElementById('designForm');
            const reviewContent = document.getElementById('reviewContent');
            reviewContent.innerHTML = '';

            // ข้อมูลโครงการ
            let html = `
        <div class="bg-gray-50 p-6 rounded-xl mb-6 ring-1 ring-gray-200">
            <h3 class="text-lg font-semibold text-gray-800 mb-4">
                ข้อมูลโครงการ
            </h3>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <p class="mb-2 block text-sm font-medium text-gray-500">ชื่อโครงการ</p>
                    <p class="font-medium">${form.project_name.value || 'ไม่ได้กรอก'}</p>
                </div>
                <div>
                    <p class="mb-2 block text-sm font-medium text-gray-500">ประเภทโปสเตอร์</p>
                    <p class="font-medium">${form.poster_type.value || 'ไม่ได้กรอก'}</p>
                </div>
                <div class="md:col-span-2">
                    <p class="mb-2 block text-sm font-medium text-gray-500">วัตถุประสงค์</p>
                    <p class="font-medium">${form.objective.value || 'ไม่ได้กรอก'}</p>
                </div>
                <div>
                    <p class="mb-2 block text-sm font-medium text-gray-500">กลุ่มเป้าหมาย</p>
                    <p class="font-medium">${form.target_audience.value || 'ไม่ได้กรอก'}</p>
                </div>
                <div class="md:col-span-2">
                    <p class="mb-2 block text-sm font-medium text-gray-500">ข้อความหลัก</p>
                    <p class="font-medium">${form.main_message.value || 'ไม่ได้กรอก'}</p>
                </div>
                <div class="md:col-span-2">
                    <p class="mb-2 block text-sm font-medium text-gray-500">เนื้อหาที่ต้องการ</p>
                    <p class="font-medium">${form.content.value || 'ไม่ได้กรอก'}</p>
                </div>
            </div>
        </div>
    `;

            // ข้อมูลเทคนิค
            const sizeValue = form.size.value;
            let sizeText = sizeValue;
            if (sizeValue === 'custom') {
                sizeText = `กำหนดเอง (${form.custom_width.value} x ${form.custom_height.value} ซม.)`;
            }

            html += `
        <div class="bg-gray-50 p-6 rounded-xl mb-6 ring-1 ring-gray-200">
            <h3 class="text-lg font-semibold text-gray-800 mb-4">
                ข้อมูลเทคนิค
            </h3>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <p class="mb-2 block text-sm font-medium text-gray-500">ขนาดโปสเตอร์</p>
                    <p class="font-medium">${sizeText || 'ไม่ได้เลือก'}</p>
                </div>
                <div>
                    <p class="mb-2 block text-sm font-medium text-gray-500">การวางแนว</p>
                    <p class="font-medium">${form.orientation.value || 'ไม่ได้เลือก'}</p>
                </div>
                <div>
                    <p class="mb-2 block text-sm font-medium text-gray-500">สไตล์ที่ต้องการ</p>
                    <p class="font-medium">${form.style.value || 'ไม่ได้เลือก'}</p>
                </div>
                <div>
                    <p class="mb-2 block text-sm font-medium text-gray-500">โหมดสี</p>
                    <p class="font-medium">${form.color_mode.value || 'ไม่ได้เลือก'}</p>
                </div>
                <div>
                    <p class="mb-2 block text-sm font-medium text-gray-500">สีหลัก</p>
                    <div class="flex items-center">
                        <div class="w-6 h-6 rounded-lg mr-2 border border-gray-300" style="background-color: ${form.color_primary.value || '#ffffff'}"></div>
                        <p class="font-medium">${form.color_primary.value || 'ไม่ได้เลือก'}</p>
                    </div>
                </div>
                <div>
                    <p class="mb-2 block text-sm font-medium text-gray-500">สีรอง</p>
                    <div class="flex items-center">
                        <div class="w-6 h-6 rounded-lg mr-2 border border-gray-300" style="background-color: ${form.color_secondary.value || '#ffffff'}"></div>
                        <p class="font-medium">${form.color_secondary.value || 'ไม่ได้เลือก'}</p>
                    </div>
                </div>
                <div>
                    <p class="mb-2 block text-sm font-medium text-gray-500">สีเน้น</p>
                    <div class="flex items-center">
                        <div class="w-6 h-6 rounded-lg mr-2 border border-gray-300" style="background-color: ${form.color_accent.value || '#ffffff'}"></div>
                        <p class="font-medium">${form.color_accent.value || 'ไม่ได้เลือก'}</p>
                    </div>
                </div>
                <div>
                    <p class="mb-2 block text-sm font-medium text-gray-500">ฟอนต์ที่ต้องการ</p>
                    <p class="font-medium">${form.preferred_fonts.value || 'ไม่ได้กรอก'}</p>
                </div>
                <div class="md:col-span-2">
                    <p class="mb-2 block text-sm font-medium text-gray-500">โค้ดสีที่ต้องการ</p>
                    <p class="font-medium">${form.color_codes.value || 'ไม่ได้กรอก'}</p>
                </div>
            </div>
        </div>
    `;

            // ไฟล์และข้อมูลเพิ่มเติม
            const logoInput = document.querySelector('input[name="logo_file[]"]');
            const imageInput = document.querySelector('input[name="images_file[]"]');
            const referenceInput = document.querySelector('input[name="reference_file[]"]');

            let logoText = 'ไม่มีไฟล์';
            if (logoInput && logoInput.files.length > 0) {
                logoText = Array.from(logoInput.files).map(file => file.name).join(', ');
            }

            let imageText = 'ไม่มีไฟล์';
            if (imageInput && imageInput.files.length > 0) {
                imageText = Array.from(imageInput.files).map(file => file.name).join(', ');
            }

            let referenceText = 'ไม่มีไฟล์';
            if (referenceInput && referenceInput.files.length > 0) {
                referenceText = Array.from(referenceInput.files).map(file => file.name).join(', ');
            }

            html += `
        <div class="bg-gray-50 p-6 rounded-xl mb-6 ring-1 ring-gray-200">
            <h3 class="text-lg font-semibold text-gray-800 mb-4">
                ไฟล์และประกอบเพิ่มเติม
            </h3>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div class="md:col-span-2">
                    <p class="mb-2 block text-sm font-medium text-gray-500">ไฟล์โลโก้</p>
                    <p class="font-medium text-sm">${logoText}</p>
                </div>
                <div class="md:col-span-2">
                    <p class="mb-2 block text-sm font-medium text-gray-500">ไฟล์รูปภาพ</p>
                    <p class="font-medium text-sm">${imageText}</p>
                </div>
                <div class="md:col-span-2">
                    <p class="mb-2 block text-sm font-medium text-gray-500">ไฟล์ตัวอย่าง</p>
                    <p class="font-medium text-sm">${referenceText}</p>
                </div>
                <div class="md:col-span-2">
                    <p class="mb-2 block text-sm font-medium text-gray-500">ลิงก์ตัวอย่าง</p>
                    <p class="font-medium">${form.reference_link.value || 'ไม่ได้กรอก'}</p>
                </div>
            </div>
        </div>

        <div class="bg-gray-50 p-6 rounded-xl mb-6 ring-1 ring-gray-200">
            <h3 class="text-lg font-semibold text-gray-800 mb-4">
                ข้อมูลการดำเนินงาน
            </h3>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <p class="mb-2 block text-sm font-medium text-gray-500">งบประมาณ</p>
                    <p class="font-medium">${form.budget_range.value || 'ไม่ได้เลือก'}</p>
                </div>
                <div>
                    <p class="mb-2 block text-sm font-medium text-gray-500">วันที่ต้องการรับงาน</p>
                    <p class="font-medium">${form.due_date.value || 'ไม่ได้เลือก'}</p>
                </div>
                <div class="md:col-span-2">
                    <p class="mb-2 block text-sm font-medium text-gray-500">สิ่งที่ไม่ต้องการ</p>
                    <p class="font-medium">${form.avoid_elements.value || 'ไม่ได้กรอก'}</p>
                </div>
                <div class="md:col-span-2">
                    <p class="mb-2 block text-sm font-medium text-gray-500">ข้อกำหนดพิเศษ</p>
                    <p class="font-medium">${form.special_requirements.value || 'ไม่ได้กรอก'}</p>
                </div>
            </div>
        </div>
    `;

            reviewContent.innerHTML = html;
        }

        // Show custom size fields when custom size is selected
        document.querySelectorAll('input[name="size"]').forEach((radio) => {
            radio.addEventListener("change", function() {
                const customFields = document.getElementById("custom-size-fields");
                if (this.value === "custom") {
                    customFields.classList.remove("hidden");
                } else {
                    customFields.classList.add("hidden");
                }
            });
        });

        // Update color picker
        function updateColorPicker(textInput, colorInputName) {
            const colorInput = document.querySelector(`input[name="${colorInputName}"]`);
            colorInput.value = textInput.value;
        }
        // Color picker sync
        document.querySelectorAll('input[type="color"]').forEach(colorInput => {
            colorInput.addEventListener('change', function() {
                const textInput = this.nextElementSibling;
                if (textInput && textInput.type === 'text') {
                    textInput.value = this.value;
                }
            });
        });

        // Handle file upload
        function handleFileUpload(input, previewId) {
            const preview = document.getElementById(previewId);
            const files = input.files;
            const maxFiles = 4; // จำกัดไม่เกิน 4 ไฟล์
            if (input.files.length > maxFiles) {
                alert("อัปโหลดได้ไม่เกิน " + maxFiles + " ไฟล์");
                input.value = "";
                return;
            }

            if (files.length > 0) {
                preview.innerHTML = '';
                preview.classList.remove('hidden');

                Array.from(files).forEach(file => {
                    const fileDiv = document.createElement('div');
                    fileDiv.className = 'bg-blue-100 text-blue-800 px-3 py-1 rounded-lg text-xs inline-block mr-2 mb-2';
                    fileDiv.textContent = file.name;
                    preview.appendChild(fileDiv);
                });
            }
        }
        // File upload click handlers
        document.querySelectorAll('.file-upload-area').forEach(area => {
            area.addEventListener('click', function() {
                const fileInput = this.querySelector('input[type="file"]');
                if (fileInput) {
                    fileInput.click();
                }
            });
        });

        // Set minimum date for due_date input
        document.addEventListener('DOMContentLoaded', function() {
            const due_dateInput = document.getElementById('due_date');
            const today = new Date();
            const minDate = new Date();
            minDate.setDate(today.getDate() + 7); // +7 วันจากวันนี้

            // Set min date attribute
            const minDateString = minDate.toISOString().split('T')[0];
            due_dateInput.min = minDateString;

        });

        document.getElementById("nextBtn").addEventListener("click", function() {
            if (currentSection < totalSections - 1) { // หยุดก่อนถึงส่วนตรวจสอบ
                // Simple validation
                const currentSectionElement = document.querySelector(
                    `[data-section="${currentSection}"]`,
                );
                const requiredFields =
                    currentSectionElement.querySelectorAll("[required]");
                let allValid = true;

                requiredFields.forEach((field) => {
                    if (!field.value.trim()) {
                        field.classList.add("border-red-500");
                        allValid = false;
                    } else {
                        field.classList.remove("border-red-500");
                    }
                });

                if (allValid) {
                    currentSection++;
                    showSection(currentSection);
                } else {
                    alert("กรุณากรอกข้อมูลที่จำเป็นให้ครบถ้วน");
                }
            }
        });

        document.getElementById("prevBtn").addEventListener("click", function() {
            if (currentSection > 1) {
                currentSection--;
                showSection(currentSection);
            }
        });

        document.getElementById("reviewBtn").addEventListener("click", function() {
            // ตรวจสอบความถูกต้องของส่วนสุดท้ายก่อนไปยังหน้ารีวิว
            const currentSectionElement = document.querySelector(
                `[data-section="${currentSection}"]`,
            );
            const requiredFields =
                currentSectionElement.querySelectorAll("[required]");
            let allValid = true;

            requiredFields.forEach((field) => {
                if (!field.value.trim()) {
                    field.classList.add("border-red-500");
                    allValid = false;
                } else {
                    field.classList.remove("border-red-500");
                }
            });

            if (allValid) {
                currentSection = totalSections; // ไปที่ส่วนตรวจสอบ
                showSection(currentSection);
            } else {
                alert("กรุณากรอกข้อมูลที่จำเป็นให้ครบถ้วน");
            }
        });

        document.getElementById("editBtn").addEventListener("click", function() {
            currentSection = 1; // กลับไปที่ส่วนแรก
            showSection(currentSection);
        });

        document
            .getElementById("designForm")
            .addEventListener("submit", function(e) {
                e.preventDefault();

                // Show success message
                alert("ข้อมูลถูกส่งแล้ว! เราจะติดต่อกลับภายใน 24 ชั่วโมง");

                this.submit();
            });

        // Initialize
        showSection(1);

        // Add smooth animations
        document
            .querySelectorAll("input, select, textarea")
            .forEach((element) => {
                element.addEventListener("focus", function() {
                    this.classList.add("ring-2");
                });

                element.addEventListener("blur", function() {
                    this.classList.remove("ring-2");
                });
            });
    </script>
</body>

</html>