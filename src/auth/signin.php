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
    <!-- <link href="https://cdnjs.cloudflare.com/ajax/libs/flowbite/1.7.0/flowbite.min.css" rel="stylesheet" /> -->
    <link href="../../dist/output.css" rel="stylesheet" />
</head>

<body>
    <div class="flex items-center justify-center min-h-screen">
        <div class="max-w-sm w-full p-6 bg-white border border-gray-200 rounded-xl shadow-sm">
            <div class="flex items-start p-4 mb-4 md:p-5 border-b rounded-t border-gray-200">
                <h3 class="text-xl font-semibold text-gray-900">
                    Sign in to our platform
                </h3>
            </div>
            <form class="space-y-4" method="POST">
                <div>
                    <label for="email" class="block mb-2 text-sm font-medium text-gray-900">Your email</label>
                    <input type="email" name="email" id="email" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-zinc-500 focus:border-zinc-500 block w-full p-2.5" placeholder="name@example.com" required />
                </div>
                <div>
                    <label for="password" class="block mb-2 text-sm font-medium text-gray-900">Your password</label>
                    <input type="password" name="password" id="password" placeholder="••••••••" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-zinc-500 focus:border-zinc-500 block w-full p-2.5" required />
                </div>
                <button type="submit" class="w-full text-white bg-zinc-900 hover:bg-zinc-800 font-medium rounded-lg text-sm px-5 py-2.5 text-center">Login to your account</button>
                <div class="text-sm font-medium text-gray-500">
                    Not registered? <a href="signup.php" class="text-blue-700 hover:underline">Create account</a>
                </div>
            </form>
        </div>
    </div>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/flowbite/1.7.0/flowbite.min.js"></script>
</body>

</html>