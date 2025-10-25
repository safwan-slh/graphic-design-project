<?php
session_start();
require '../includes/db_connect.php';

$error = ''; // กำหนดตัวแปรเก็บข้อความ error

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = $_POST['email'];
    $password = $_POST['password'];

    $stmt = $conn->prepare("SELECT * FROM customers WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($user = $result->fetch_assoc()) {
        if (password_verify($password, $user['password'])) {
            $_SESSION['customer_id'] = $user['customer_id'];
            $_SESSION['fullname'] = $user['fullname'];
            $_SESSION['role'] = $user['role'];

            // ส่งไปหน้าตาม role
            if ($user['role'] === 'admin') {
                header("Location: /graphic-design/src/admin/index.php");
                exit();
            } else {
                header("Location: /graphic-design/src/client/index.php");
                exit();
            }
        } else {
            $error = "อ๊ะ! รหัสผ่านอาจไม่ถูกต้อง";
        }
    } else {
        $error = "โอ๊ะ! ไม่พบอีเมลนี้ในระบบ";
    }
}

$message = $error;
$messageType = !empty($error) ? 'error' : '';

?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Login</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=IBM+Plex+Sans+Thai:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/flowbite/1.7.0/flowbite.min.css" rel="stylesheet" />
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/js/all.min.js"></script>
</head>

<body>
    <div class="flex items-center justify-center min-h-screen bg-gray-50">
        <div class="max-w-sm w-full bg-white border border-gray-200 rounded-3xl shadow-md">
            <div class="flex items-start p-4 border-b rounded-t border-gray-200">
                <h3 class="text-xl font-semibold text-gray-900">
                    เข้าสู่ระบบ
                </h3>
            </div>
            <div class="p-6">
                <form class="space-y-4" method="POST">
                    <div>
                        <label for="email" class="block mb-2 text-sm font-medium text-gray-900">อีเมล</label>
                        <input type="email" name="email" id="email" class="block w-full rounded-xl border border-gray-300 bg-gray-50 p-2.5 text-sm text-gray-900 " placeholder="name@example.com" required autofocus />
                    </div>
                    <div>
                        <label for="password" class="block mb-2 text-sm font-medium text-gray-900">รหัสผ่าน</label>
                        <div class="relative">
                            <input type="password" name="password" id="password" placeholder="••••••••" class="block w-full rounded-xl border border-gray-300 bg-gray-50 p-2.5 text-sm text-gray-900 " required />
                            <button type="button" onclick="togglePassword()" class="absolute right-3 top-2 text-gray-400">
                                <i class="fa fa-eye" id="toggleIcon"></i>
                            </button>
                        </div>
                    </div>
                    <button type="submit" class="w-full border transition font-medium rounded-xl text-sm px-5 py-2 text-center flex items-center justify-center bg-zinc-900 hover:bg-zinc-700 text-white border-zinc-900">เข้าสู่ระบบ</button>
                    <div class="text-sm font-medium text-gray-500">
                        ยังไม่มีบัญชี? <a href="signup.php" class="text-blue-700 hover:underline">สมัครสมาชิก</a>
                    </div>
                </form>
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
        document.querySelector('form').addEventListener('submit', function(e) {
            const btn = this.querySelector('button[type="submit"]');
            btn.disabled = true;
            btn.innerHTML = `
        <svg class="animate-spin h-5 w-5 mr-2 text-white inline" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"></path>
        </svg>
        กำลังเข้าสู่ระบบ...
    `;
        });
    </script>
    <script>
        function togglePassword() {
            const pw = document.getElementById('password');
            const icon = document.getElementById('toggleIcon');
            if (pw.type === "password") {
                pw.type = "text";
                icon.classList.remove('fa-eye');
                icon.classList.add('fa-eye-slash');
            } else {
                pw.type = "password";
                icon.classList.remove('fa-eye-slash');
                icon.classList.add('fa-eye');
            }
        }
    </script>
</body>

</html>