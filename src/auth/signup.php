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
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Sign Up</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/flowbite/1.7.0/flowbite.min.css" rel="stylesheet" />
    <link href="../../dist/output.css" rel="stylesheet" />
</head>

<body>
    <div class="flex items-center justify-center min-h-screen">
        <div class="max-w-sm w-full p-6 bg-white border border-gray-200 rounded-xl shadow-sm">
            <div class="flex items-start p-4 mb-4 md:p-5 border-b rounded-t border-gray-200">
                <h3 class="text-xl font-semibold text-gray-900">Create new account</h3>
            </div>
            <form class="space-y-4" method="POST">
                <div>
                    <label for="fullname" class="block mb-2 text-sm font-medium text-gray-900">Your fullname</label>
                    <input type="text" name="fullname" id="fullname" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-zinc-500 focus:border-zinc-500 block w-full p-2.5" placeholder="fullname" required />
                </div>
                <div>
                    <label for="email" class="block mb-2 text-sm font-medium text-gray-900">Your email</label>
                    <input type="email" name="email" id="email" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-zinc-500 focus:border-zinc-500 block w-full p-2.5" placeholder="name@example.com" required />
                </div>
                <div>
                    <label for="phone" class="block mb-2 text-sm font-medium text-gray-900">Your phone</label>
                    <input type="text" name="phone" id="phone" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-zinc-500 focus:border-zinc-500 block w-full p-2.5" placeholder="Your phone number" required />
                </div>
                <div>
                    <label for="password" class="block mb-2 text-sm font-medium text-gray-900">Your password</label>
                    <input type="password" name="password" id="password" placeholder="••••••••" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-zinc-500 focus:border-zinc-500 block w-full p-2.5" required />
                </div>

                <button type="submit" class="w-full text-white bg-zinc-900 hover:bg-zinc-800 font-medium rounded-lg text-sm px-5 py-2.5 text-center">Sign up</button>
                <div class="text-sm font-medium text-gray-500">
                    Already A Member? <a href="signin.php" class="text-blue-700 hover:underline">Log in</a>
                </div>
            </form>
        </div>
    </div>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/flowbite/1.7.0/flowbite.min.js"></script>
</body>

</html>