<?php
// ตรวจสอบ error
error_reporting(E_ALL);
ini_set('display_errors', 1);
require '../includes/db_connect.php';
require '../auth/auth.php';
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
    <link href="https://cdnjs.cloudflare.com/ajax/libs/flowbite/1.7.0/flowbite.min.css" rel="stylesheet" />
    <link href="../../dist/output.css" rel="stylesheet" />
</head>

<body>
    <?php include '../includes/sidebar.php'; ?>

    <div class="ml-64 p-8">
        <div class="form-container w-max-[800px]">
            <div class="form-card bg-white p-4 rounded-xl shadow-sm ring-1 ring-gray-200">
                <!-- Header -->
                <div class="form-header mb-5">
                    <h3 class="text-xl font-semibold text-gray-900 flex items-center">
                        <svg class="w-5 h-5 mr-2 text-gray-700" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                        </svg>
                        <?= $isEditMode ? 'แก้ไขข้อมูลลูกค้า' : 'เพิ่มข้อมูลลูกค้าใหม่' ?>
                    </h3>
                </div>

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
                            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-zinc-900 focus:border-transparent">
                            <option value="customer" <?php echo $customer['role'] === 'customer' ? 'selected' : ''; ?>>Customer</option>
                            <option value="admin" <?php echo $customer['role'] === 'admin' ? 'selected' : ''; ?>>Admin</option>
                        </select>
                    </div>

                    <div class="form-footer flex justify-between">
                        <a href="customer_list.php" class="mb-4 text-zinc-600 flex justify-center items-center bg-zinc-200 hover:bg-zinc-200 font-medium rounded-lg text-sm px-5 py-2.5 text-center">
                            ย้อนกลับ
                        </a>
                        <button type="submit" class="mb-4 text-white flex justify-center items-center bg-zinc-900 hover:bg-zinc-800 font-medium rounded-lg text-sm px-5 py-2.5 text-center">
                            <?php echo $isEditMode ? 'อัปเดตข้อมูล' : 'บันทึกข้อมูล'; ?>
                        </button>
                    </div>
                </form>
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