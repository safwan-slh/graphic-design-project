<?php
// ตรวจสอบ error
error_reporting(E_ALL);
ini_set('display_errors', 1);
require '../includes/db_connect.php';
require '../auth/auth.php';
requireRole('admin'); // ให้เข้าหน้านี้ได้เฉพาะ admin

$toastType = '';
$toastMessage = '';

function createSlug($string)
{
    $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $string)));
    return $slug;
}

$error = '';
$success = '';
$is_edit_mode = false;
$service = [
    'service_name' => '',
    'slug' => '',
    'short_description' => '',
    'base_price' => 0,
    'price_unit' => '',
    'is_featured' => 0,
    'is_active' => 1
];

// ตรวจสอบโหมดแก้ไข
if (isset($_GET['id'])) {
    $is_edit_mode = true;
    $id = intval($_GET['id']);

    // ลบเงื่อนไข deleted_at ออก
    $stmt = $conn->prepare("SELECT * FROM services WHERE service_id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        header("Location: services_list.php");
        exit();
    }

    $service = $result->fetch_assoc();
}

// ตรวจสอบการส่งฟอร์ม
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['service_name']);
    $slug = trim($_POST['slug']);
    $desc = trim($_POST['short_description']);
    $price = floatval($_POST['base_price']);
    $unit = trim($_POST['price_unit']);
    $is_featured = isset($_POST['is_featured']) ? 1 : 0;
    $is_active = isset($_POST['is_active']) ? 1 : 0;

    // ตรวจสอบ slug ซ้ำ
    if ($is_edit_mode) {
        $stmt = $conn->prepare("SELECT COUNT(*) FROM services WHERE slug = ? AND service_id != ?");
        $stmt->bind_param("si", $slug, $id);
    } else {
        $stmt = $conn->prepare("SELECT COUNT(*) FROM services WHERE slug = ?");
        $stmt->bind_param("s", $slug);
    }

    $stmt->execute();
    $stmt->bind_result($count);
    $stmt->fetch();
    $stmt->close();

    if ($count > 0) {
        $error = "Slug ของบริการนี้มีอยู่แล้ว กรุณาเปลี่ยนชื่อบริการ";
    } else {
        if ($is_edit_mode) {
            // โหมดแก้ไข
            $stmt = $conn->prepare("UPDATE services SET service_name=?, slug=?, short_description=?, base_price=?, price_unit=?, is_featured=?, is_active=?, updated_at=NOW() WHERE service_id=?");
            $stmt->bind_param("sssdsiii", $name, $slug, $desc, $price, $unit, $is_featured, $is_active, $id);
        } else {
            // โหมดเพิ่มใหม่
            $stmt = $conn->prepare("INSERT INTO services (service_name, slug, short_description, base_price, price_unit, is_featured, is_active) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("sssdsii", $name, $slug, $desc, $price, $unit, $is_featured, $is_active);
        }

        if ($stmt->execute()) {
            $success = $is_edit_mode ? "แก้ไขบริการเรียบร้อยแล้ว" : "เพิ่มบริการเรียบร้อยแล้ว";

            if ($is_edit_mode) {
                // อัปเดตข้อมูลในตัวแปร $service
                $service['service_name'] = $name;
                $service['slug'] = $slug;
                $service['short_description'] = $desc;
                $service['base_price'] = $price;
                $service['price_unit'] = $unit;
                $service['is_featured'] = $is_featured;
                $service['is_active'] = $is_active;
            } else {
                // รีเซ็ตฟอร์มหลังจากเพิ่มสำเร็จ
                $service = [
                    'service_name' => '',
                    'slug' => '',
                    'short_description' => '',
                    'base_price' => 0,
                    'price_unit' => '',
                    'is_featured' => 0,
                    'is_active' => 1
                ];
            }
        } else {
            $error = "เกิดข้อผิดพลาด: " . $conn->error;
        }
        $stmt->close();
    }
}
if (!empty($error)) {
    $toastType = 'error';
    $toastMessage = $error;
} elseif (!empty($success)) {
    $toastType = 'success';
    $toastMessage = $success;
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $is_edit_mode ? 'แก้ไขบริการ' : 'เพิ่มบริการใหม่' ?></title>
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
        <!-- breadcrumb -->
        <?php
        $breadcrumb = ['Dashboard', 'จัดการบริการ', $is_edit_mode ? 'แก้ไขบริการ' : 'เพิ่มบริการใหม่'];
        $breadcrumb_links = ['/graphic-design/src/admin/index.php', '/graphic-design/src/admin/service_list.php'];
        include '../includes/admin_navbar.php';
        ?>
        <!-- Main Content -->
        <div class="p-6">
            <div class=" text-zinc-900 bg-white rounded-2xl border border-slate-200 mb-2">
                <!-- Header -->
                <div class="flex items-center p-4">
                    <div class="mr-4 rounded-xl bg-zinc-900 p-3">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="h-6 w-6 text-white">
                            <path d="M5.566 4.657A4.505 4.505 0 0 1 6.75 4.5h10.5c.41 0 .806.055 1.183.157A3 3 0 0 0 15.75 3h-7.5a3 3 0 0 0-2.684 1.657ZM2.25 12a3 3 0 0 1 3-3h13.5a3 3 0 0 1 3 3v6a3 3 0 0 1-3 3H5.25a3 3 0 0 1-3-3v-6ZM5.25 7.5c-.41 0-.806.055-1.184.157A3 3 0 0 1 6.75 6h10.5a3 3 0 0 1 2.683 1.657A4.505 4.505 0 0 0 18.75 7.5H5.25Z" />
                        </svg>
                    </div>
                    <div class="">
                        <h1 class="flex items-center text-2xl font-bold text-zinc-900">
                            <?= $is_edit_mode ? 'แก้ไขบริการ' : 'เพิ่มบริการใหม่' ?>
                        </h1>
                        <p class="text-gray-600">
                            เพิ่มและแก้ไขบริการทั้งหมดของคุณ
                        </p>
                    </div>
                </div>
                <div class="form-container w-max-[800px] p-4">
                    <div class="bg-white items-center p-4 ring-1 ring-zinc-200 rounded-2xl">
                        <!-- Form -->
                        <form class="form-body" method="POST">
                            <div class="grid gap-6 mb-6 md:grid-cols-2">
                                <!-- ชื่อบริการ -->
                                <div class="col-span-2">
                                    <label for="name" class="block mb-2 text-sm font-medium text-gray-700">ชื่อบริการ</label>
                                    <input type="text" name="service_name" id="name" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5" placeholder="กรอกชื่อบริการ" required value="<?= htmlspecialchars($service['service_name']) ?>">
                                </div>

                                <!-- Slug -->
                                <div class="col-span-2">
                                    <label for="slug" class="block mb-2 text-sm font-medium text-gray-700">ชื่อภาษาอังกฤษ (Slug)</label>
                                    <div class="flex">
                                        <span class="inline-flex items-center px-3 text-sm text-gray-900 bg-gray-200 border border-r-0 border-gray-300 rounded-l-md">
                                            /
                                        </span>
                                        <input type="text" name="slug" id="slug" class="rounded-none rounded-r-lg bg-gray-50 border border-gray-300 text-gray-900 focus:ring-blue-500 focus:border-blue-500 block flex-1 min-w-0 w-full text-sm p-2.5" placeholder="service-name" required value="<?= htmlspecialchars($service['slug']) ?>">
                                    </div>
                                    <p class="mt-1 text-xs text-gray-500">ชื่อสำหรับใช้ใน URL (ภาษาอังกฤษเท่านั้น)</p>
                                </div>

                                <!-- คำอธิบายสั้น -->
                                <div class="col-span-2">
                                    <label for="short_description" class="block mb-2 text-sm font-medium text-gray-700">คำอธิบายสั้น</label>
                                    <textarea id="short_description" name="short_description" rows="3" class="block p-2.5 w-full text-sm text-gray-900 bg-gray-50 rounded-lg border border-gray-300 focus:ring-blue-500 focus:border-blue-500" placeholder="อธิบายเกี่ยวกับบริการนี้..."><?= htmlspecialchars($service['short_description']) ?></textarea>
                                </div>

                                <!-- ราคา -->
                                <div>
                                    <label for="price" class="block mb-2 text-sm font-medium text-gray-700">ราคาเริ่มต้น</label>
                                    <div class="relative">
                                        <div class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none">
                                            <span class="text-gray-500">฿</span>
                                        </div>
                                        <input type="number" name="base_price" id="price" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full pl-8 p-2.5" placeholder="0.00" step="0.01" min="0" required value="<?= htmlspecialchars($service['base_price']) ?>">
                                    </div>
                                </div>

                                <!-- หน่วยราคา -->
                                <div>
                                    <label for="price_unit" class="block mb-2 text-sm font-medium text-gray-700">หน่วยราคา</label>
                                    <select id="price_unit" name="price_unit" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2" required>
                                        <option value="ชิ้น" <?= $service['price_unit'] === 'ชิ้น' ? 'selected' : '' ?>>ต่อชิ้น</option>
                                        <option value="ชุด" <?= $service['price_unit'] === 'ชุด' ? 'selected' : '' ?>>ต่อชุด</option>
                                        <option value="หน้า" <?= $service['price_unit'] === 'หน้า' ? 'selected' : '' ?>>ต่อหน้า</option>
                                        <option value="แบบ" <?= $service['price_unit'] === 'แบบ' ? 'selected' : '' ?>>ต่อแบบ</option>
                                    </select>
                                </div>

                                <!-- Checkboxes -->
                                <div class="col-span-2 space-y-3">
                                    <div class="checkbox-label">
                                        <input id="is_featured" type="checkbox" name="is_featured" class="w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 rounded focus:ring-blue-500" <?= $service['is_featured'] ? 'checked' : '' ?>>
                                        <label for="is_featured" class="ml-2 text-sm font-medium text-gray-700">บริการแนะนำ</label>
                                    </div>
                                    <div class="checkbox-label">
                                        <input id="is_active" type="checkbox" name="is_active" class="w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 rounded focus:ring-blue-500" <?= $service['is_active'] ? 'checked' : '' ?>>
                                        <label for="is_active" class="ml-2 text-sm font-medium text-gray-700">เปิดใช้งานบริการ</label>
                                    </div>
                                </div>
                            </div>

                            <!-- ปุ่มส่งฟอร์ม -->
                            <div class="flex space-x-4 pt-2">
                                <button type="submit"
                                    class="border transition font-medium rounded-xl text-sm px-5 py-2 text-center flex items-center justify-center bg-zinc-900 text-white border-zinc-900">
                                    <?= $is_edit_mode ? 'อัปเดตบริการ' : 'บันทึกบริการ' ?>
                                </button>
                                <a href="service_list.php"
                                    class="border transition font-medium rounded-xl text-sm px-5 py-2 text-center flex items-center justify-center bg-white text-gray-600 border-gray-300 hover:bg-gray-100">
                                    ย้อนกลับ
                                </a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

        </div>

    </div>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/flowbite/1.7.0/flowbite.min.js"></script>
    <!-- include toast component -->
    <?php include '../includes/toast.php'; ?>

    <?php if (!empty($toastMessage)): ?>
        <script>
            showToast(<?= json_encode($toastMessage) ?>, <?= json_encode($toastType) ?>);
        </script>
    <?php endif; ?>

    <script>
        // Auto-generate slug from service name
        // document.getElementById('name').addEventListener('input', function() {
        //     const name = this.value.trim();
        //     const slug = name.toLowerCase()
        //         .replace(/[^a-z0-9ก-๙]+/g, '-')
        //         .replace(/(^-|-$)/g, '');
        //     document.getElementById('slug').value = slug;
        // });
    </script>
</body>

</html>