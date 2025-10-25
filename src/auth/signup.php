<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
require '../includes/db_connect.php';

$message = '';
$messageType = ''; // success หรือ error

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $fullname = $_POST['fullname'];
    $email = $_POST['email'];
    $phone = $_POST['phone'];
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];

    if ($password !== $confirm_password) {
        $message = "รหัสผ่านและยืนยันรหัสผ่านไม่ตรงกัน";
        $messageType = "error";
    } else {
        // เข้ารหัสรหัสผ่าน
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

        // ตรวจสอบอีเมลซ้ำ
        $check = $conn->prepare("SELECT * FROM customers WHERE email = ?");
        $check->bind_param("s", $email);
        $check->execute();
        $result = $check->get_result();

        if ($result->num_rows > 0) {
            $message = "โอ๊ะ! อีเมลนี้มีคนใช้แล้ว!";
            $messageType = "error";
        } else {
            // ตรวจสอบเบอร์โทรศัพท์ซ้ำ
            $checkPhone = $conn->prepare("SELECT * FROM customers WHERE phone = ?");
            $checkPhone->bind_param("s", $phone);
            $checkPhone->execute();
            $resultPhone = $checkPhone->get_result();

            if ($resultPhone->num_rows > 0) {
                $message = "เบอร์โทรศัพท์นี้มีคนใช้แล้ว!";
                $messageType = "error";
            } else {
                // บันทึกข้อมูล
                $stmt = $conn->prepare("INSERT INTO customers (fullname, email, password, phone, role) VALUES (?, ?, ?, ?, 'customer')");
                $stmt->bind_param("ssss", $fullname, $email, $hashedPassword, $phone);

                if ($stmt->execute()) {
                    $message = "เย้! ลงทะเบียนสำเร็จแล้ว";
                    $messageType = "success";
                } else {
                    $message = "Error: " . $conn->error;
                    $messageType = "error";
                }
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Sign Up</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=IBM+Plex+Sans+Thai:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/flowbite/1.7.0/flowbite.min.css" rel="stylesheet" />
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/js/all.min.js"></script>
</head>

<body>
    <div class="flex items-center justify-center min-h-screen bg-gray-50">
        <div class="max-w-sm w-full">
            <div class=" bg-white border border-gray-200 rounded-3xl shadow-md">
                <div class="flex items-start p-4 border-b rounded-t border-gray-200">
                    <h3 class="text-xl font-semibold text-gray-900">
                        สมัครสมาชิกใหม่
                    </h3>
                </div>
                <div class="p-6">
                    <form class="space-y-4" method="POST">
                        <div>
                            <label for="fullname" class="block mb-2 text-sm font-medium text-gray-900">ชื่อ-นามสกุล</label>
                            <input type="text" name="fullname" id="fullname" class="block w-full rounded-xl border border-gray-300 bg-gray-50 p-2.5 text-sm text-gray-900 " placeholder="ชื่อ-นามสกุล" autofocus required />
                        </div>
                        <div>
                            <label for="email" class="block mb-2 text-sm font-medium text-gray-900">อีเมล</label>
                            <input type="email" name="email" id="email" class="block w-full rounded-xl border border-gray-300 bg-gray-50 p-2.5 text-sm text-gray-900 " placeholder="name@example.com" required />
                        </div>
                        <div>
                            <label for="phone" class="block mb-2 text-sm font-medium text-gray-900">เบอร์โทรศัพท์</label>
                            <input
                                type="text"
                                name="phone"
                                id="phone"
                                class="block w-full rounded-xl border border-gray-300 bg-gray-50 p-2.5 text-sm text-gray-900"
                                placeholder="08xxxxxxxx"
                                required
                                maxlength="10"
                                pattern="^[0-9]{10}$"
                                inputmode="numeric"
                                oninput="this.value = this.value.replace(/[^0-9]/g, '').slice(0,10);" />
                            <span class="text-xs text-gray-400">กรอกเบอร์โทร 10 หลัก (ตัวเลขเท่านั้น)</span>
                        </div>
                        <div>
                            <label for="password" class="block mb-2 text-sm font-medium text-gray-900">รหัสผ่าน</label>
                            <input type="password" name="password" id="password" minlength="6" placeholder="••••••••" class="block w-full rounded-xl border border-gray-300 bg-gray-50 p-2.5 text-sm text-gray-900 " required />
                        </div>
                        <div>
                            <label for="confirm_password" class="block mb-2 text-sm font-medium text-gray-900">ยืนยันรหัสผ่าน</label>
                            <input type="password" name="confirm_password" id="confirm_password" minlength="6" placeholder="••••••••" class="block w-full rounded-xl border border-gray-300 bg-gray-50 p-2.5 text-sm text-gray-900 " required />
                        </div>
                        <div>
                            <label class="inline-flex items-center">
                                <input type="checkbox" required class="form-checkbox rounded text-zinc-900" />
                                <span class="ml-2 text-sm text-gray-600">ฉันยอมรับ <a href="/terms" class="text-blue-700 hover:underline">ข้อตกลงการใช้บริการ</a></span>
                            </label>
                        </div>

                        <button type="submit" class="w-full border transition font-medium rounded-xl text-sm px-5 py-2 text-center flex items-center justify-center bg-zinc-900 hover:bg-zinc-700 text-white border-zinc-900">สมัครสมาชิก</button>
                        <div class="text-sm font-medium text-gray-500">
                            เป็นสมาชิกอยู่แล้ว? <a href="signin.php" class="text-blue-700 hover:underline">เข้าสู่ระบบ</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/flowbite/1.7.0/flowbite.min.js"></script>
    <!-- include toast component -->
    <?php include '../includes/toast.php'; ?>

    <?php if (!empty($message)): ?>
        <script>
            showToast(<?= json_encode($message) ?>, <?= json_encode($messageType) ?>);
        </script>
    <?php endif; ?>
    <script>
        document.getElementById('confirm_password').addEventListener('input', function() {
            const pw = document.getElementById('password').value;
            const cpw = this.value;
            if (pw !== cpw) {
                this.setCustomValidity('รหัสผ่านและยืนยันรหัสผ่านไม่ตรงกัน');
            } else {
                this.setCustomValidity('');
            }
        });
    </script>
    <script>
    // ป้องกันการ submit ซ้ำและแสดง animation หมุนรอ
    document.querySelector('form').addEventListener('submit', function(e) {
        const btn = this.querySelector('button[type="submit"]');
        btn.disabled = true;
        btn.innerHTML = `
            <svg class="animate-spin h-5 w-5 mr-2 text-white inline" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"></path>
            </svg>
            กำลังสมัคร...
        `;
    });
</script>
</body>

</html>