<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
require '../includes/db_connect.php';
require '../auth/auth.php';
requireRole('admin'); // ให้เข้าหน้านี้ได้เฉพาะ admin
$breadcrumb = ['รายการชำระเงิน', 'รายละเอียดการชำระเงิน'];
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment Detail</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=IBM+Plex+Sans+Thai:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .font-thai {
            font-family: 'IBM Plex Sans Thai', sans-serif;
        }
    </style>
</head>

<body class="bg-zinc-100 font-thai">
    <?php include __DIR__ . '/../includes/sidebar.php'; ?>
    <div class="ml-64">
        <!-- breadcrumb -->
         
        <div class="p-6">

        </div>
    </div>
</body>

</html>