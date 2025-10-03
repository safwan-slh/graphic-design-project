<?php
// ตรวจสอบ error
error_reporting(E_ALL);
ini_set('display_errors', 1);
require __DIR__ . '/../includes/db_connect.php';
require_once '../auth/auth.php';
requireRole('admin'); // ให้เข้าหน้านี้ได้เฉพาะ admin

$isEditMode = false;
$customer = [
    'customer_id' => '',
    'fullname' => '',
    'email' => '',
    'phone' => '',
    'role' => 'customer'
];

// ตรวจสอบโหมดแก้ไข
if (isset($_GET['id'])) {
    $isEditMode = true;
    $customer_id = $_GET['id'];

    // ดึงข้อมูลลูกค้าที่จะแก้ไข
    $stmt = $conn->prepare("SELECT * FROM customers WHERE customer_id = ?");
    $stmt->bind_param("i", $customer_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $customer = $result->fetch_assoc();

    if (!$customer) {
        header("Location: customer_list.php?toastType=error&toastMessage=customer_not_found");
        exit;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $customer_id = $_POST['customer_id'] ?? null;
    $fullname = trim($_POST['fullname']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $phone = trim($_POST['phone']);
    $role = $_POST['role'];

    // ตรวจสอบข้อมูล
    if (empty($fullname) || empty($email)) {
        $toastType = 'error';
        $toastMessage = 'กรุณากรอกข้อมูลให้ครบถ้วน';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $toastType = 'error';
        $toastMessage = 'รูปแบบอีเมลไม่ถูกต้อง';
    } else {
        try {
            // ตรวจสอบว่าอีเมลซ้ำหรือไม่ (ไม่นับของตัวเองในโหมดแก้ไข)
            $sql = "SELECT customer_id FROM customers WHERE email = ?";
            $params = [$email];
            $types = "s";

            if ($isEditMode) {
                $sql .= " AND customer_id != ?";
                $params[] = $customer_id;
                $types .= "i";
            }

            $stmt = $conn->prepare($sql);
            $stmt->bind_param($types, ...$params);
            $stmt->execute();
            $stmt->store_result();

            if ($stmt->num_rows > 0) {
                $toastType = 'error';
                $toastMessage = 'อีเมลนี้ถูกใช้งานแล้ว';
            } else {
                if ($isEditMode) {
                    // โหมดแก้ไข
                    $sql = "UPDATE customers SET fullname = ?, email = ?, phone = ?, role = ?";
                    $params = [$fullname, $email, $phone, $role];
                    $types = "ssss";

                    // ถ้ามีการเปลี่ยนรหัสผ่าน
                    if (!empty($password)) {
                        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
                        $sql .= ", password = ?";
                        $params[] = $hashedPassword;
                        $types .= "s";
                    }

                    $sql .= " WHERE customer_id = ?";
                    $params[] = $customer_id;
                    $types .= "i";

                    $stmt = $conn->prepare($sql);
                    $stmt->bind_param($types, ...$params);
                    $stmt->execute();

                    if ($stmt->affected_rows >= 0) {
                        $toastType = 'success';
                        $toastMessage = 'อัปเดตข้อมูลลูกค้าเรียบร้อยแล้ว';
                        // อัปเดตข้อมูลที่แสดงในฟอร์ม
                        $customer['fullname'] = $fullname;
                        $customer['email'] = $email;
                        $customer['phone'] = $phone;
                        $customer['role'] = $role;
                    } else {
                        $toastType = 'info';
                        $toastMessage = 'ไม่มีการเปลี่ยนแปลงข้อมูล';
                    }
                } else {
                    // โหมดเพิ่ม
                    if (empty($password)) {
                        $toastType = 'error';
                        $toastMessage = 'กรุณากรอกรหัสผ่าน';
                    } else {
                        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

                        $stmt = $conn->prepare("INSERT INTO customers (fullname, email, password, phone, role) VALUES (?, ?, ?, ?, ?)");
                        $stmt->bind_param("sssss", $fullname, $email, $hashedPassword, $phone, $role);
                        $stmt->execute();

                        if ($stmt->affected_rows > 0) {
                            header("Location: customer_add.php?toastType=success&toastMessage=" . urlencode('เพิ่มลูกค้าเรียบร้อยแล้ว'));
                            exit;
                        } else {
                            $toastType = 'error';
                            $toastMessage = 'เกิดข้อผิดพลาดในการบันทึกข้อมูล';
                        }
                    }
                }
            }
        } catch (Exception $e) {
            $toastType = 'error';
            $toastMessage = 'เกิดข้อผิดพลาด: ' . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8">
    <title><?php echo $isEditMode ? 'แก้ไขลูกค้า' : 'เพิ่มลูกค้าใหม่'; ?> - Admin Panel</title>
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

<body class="bg-zinc-100 font-thai">
    <?php include '../includes/sidebar.php'; ?>
    <div class="ml-64">
        <!-- breadcrumb -->
        <?php
        $breadcrumb = ['Dashboard', 'จัดการลูกค้า', $isEditMode ? 'แก้ไขลูกค้า' : 'เพิ่มลูกค้าใหม่'];
        $breadcrumb_links = ['/graphic-design/src/admin/index.php', '/graphic-design/src/admin/customer_list.php'];
        include '../includes/admin_navbar.php';
        ?>
        <!-- Main Content -->
        <div class="p-6">
            <div class=" text-zinc-900 bg-white rounded-2xl border border-slate-200 mb-2">
                <!-- Header -->
                <div class="flex items-center p-4">
                    <div class="mr-4 rounded-xl bg-zinc-900 p-3">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="h-6 w-6 text-white">
                            <path fill-rule="evenodd" d="M8.25 6.75a3.75 3.75 0 1 1 7.5 0 3.75 3.75 0 0 1-7.5 0ZM15.75 9.75a3 3 0 1 1 6 0 3 3 0 0 1-6 0ZM2.25 9.75a3 3 0 1 1 6 0 3 3 0 0 1-6 0ZM6.31 15.117A6.745 6.745 0 0 1 12 12a6.745 6.745 0 0 1 6.709 7.498.75.75 0 0 1-.372.568A12.696 12.696 0 0 1 12 21.75c-2.305 0-4.47-.612-6.337-1.684a.75.75 0 0 1-.372-.568 6.787 6.787 0 0 1 1.019-4.38Z" clip-rule="evenodd" />
                            <path d="M5.082 14.254a8.287 8.287 0 0 0-1.308 5.135 9.687 9.687 0 0 1-1.764-.44l-.115-.04a.563.563 0 0 1-.373-.487l-.01-.121a3.75 3.75 0 0 1 3.57-4.047ZM20.226 19.389a8.287 8.287 0 0 0-1.308-5.135 3.75 3.75 0 0 1 3.57 4.047l-.01.121a.563.563 0 0 1-.373.486l-.115.04c-.567.2-1.156.349-1.764.441Z" />
                        </svg>
                    </div>
                    <div class="">
                        <h1 class="flex items-center text-2xl font-bold text-zinc-900">
                            <?= $isEditMode ? 'แก้ไขข้อมูลลูกค้า' : 'เพิ่มข้อมูลลูกค้าใหม่' ?>
                        </h1>
                        <p class="text-gray-600">
                            เพิ่มและแก้ไขข้อมูลลูกค้าทั้งหมดของคุณ
                        </p>
                    </div>
                </div>
                <div class="form-container w-max-[800px] p-4">
                    <div class="bg-white items-center p-4 ring-1 ring-zinc-200 rounded-2xl">
                        <!-- Form Container -->
                        <form action="customer_add.php<?php echo $isEditMode ? '?id=' . $customer['customer_id'] : ''; ?>" method="POST" class="space-y-4">
                            <?php if ($isEditMode): ?>
                                <input type="hidden" name="customer_id" value="<?php echo $customer['customer_id']; ?>">
                            <?php endif; ?>

                            <div>
                                <label for="fullname" class="block text-sm font-medium text-gray-700 mb-1">ชื่อ-นามสกุล</label>
                                <input type="text" id="fullname" name="fullname" required
                                    placeholder="กรอกชื่อ-นามสกุล"
                                    class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5"
                                    value="<?php echo htmlspecialchars($customer['fullname']); ?>">
                            </div>

                            <div>
                                <label for="email" class="block text-sm font-medium text-gray-700 mb-1">อีเมล</label>
                                <input type="email" id="email" name="email" required
                                    placeholder="กรอกอีเมล"
                                    class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5"
                                    value="<?php echo htmlspecialchars($customer['email']); ?>">

                            </div>

                            <div>
                                <label for="password" class="block text-sm font-medium text-gray-700 mb-1">
                                    <?php echo $isEditMode ? 'รหัสผ่านใหม่' : 'รหัสผ่าน'; ?>
                                </label>
                                <input type="password" id="password" name="password" <?php echo $isEditMode ? '' : 'required'; ?>
                                    placeholder="กรอกรหัสผ่าน"
                                    class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5"
                                    placeholder="<?php echo $isEditMode ? 'เว้นว่างไว้หากไม่ต้องการเปลี่ยนรหัสผ่าน' : ''; ?>">
                                <?php if ($isEditMode): ?>
                                    <p class="mt-1 text-xs text-gray-500">หากไม่ต้องการเปลี่ยนรหัสผ่าน ให้เว้นช่องนี้ว่างไว้</p>
                                <?php endif; ?>
                            </div>

                            <div>
                                <label for="phone" class="block text-sm font-medium text-gray-700 mb-1">เบอร์โทรศัพท์</label>
                                <input type="text" id="phone" name="phone"
                                    placeholder="กรอกเบอร์โทรศัพท์"
                                    class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5"
                                    value="<?php echo htmlspecialchars($customer['phone']); ?>">
                            </div>

                            <div>
                                <label for="role" class="block text-sm font-medium text-gray-700 mb-1">ตำแหน่ง</label>
                                <select id="role" name="role" required
                                    class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2">
                                    <option value="customer" <?php echo $customer['role'] === 'customer' ? 'selected' : ''; ?>>Customer</option>
                                    <option value="admin" <?php echo $customer['role'] === 'admin' ? 'selected' : ''; ?>>Admin</option>
                                </select>
                            </div>
                            <!-- ปุ่มส่งฟอร์ม -->
                            <div class="flex space-x-4 pt-2">
                                <button type="submit"
                                    class="border transition font-medium rounded-xl text-sm px-5 py-2 text-center flex items-center justify-center bg-zinc-900 text-white hover:bg-zinc-800">
                                    <?php echo $isEditMode ? 'อัปเดตข้อมูล' : 'บันทึกข้อมูล'; ?>
                                </button>
                                <a href="customer_list.php"
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

    <?php
    // รองรับกรณี redirect ด้วย
    if (isset($_GET['toastType']) && isset($_GET['toastMessage'])) {
        $toastType = $_GET['toastType'];
        $toastMessage = $_GET['toastMessage'];
    }

    // แสดง toast ถ้ามีข้อความและประเภท
    if (!empty($toastMessage) && !empty($toastType)) :
    ?>
        <script>
            showToast(<?= json_encode($toastMessage) ?>, <?= json_encode($toastType) ?>);
        </script>
    <?php endif; ?>

</body>

</html>