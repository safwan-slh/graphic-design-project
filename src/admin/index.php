<?php
require '../auth/auth.php';
requireRole('admin'); // ให้เข้าหน้านี้ได้เฉพาะ admin
?>
<!DOCTYPE html>
<html lang="en">
<head>
          <meta charset="UTF-8">
          <meta name="viewport" content="width=device-width, initial-scale=1.0">
          <title>Dashboard</title>
          <link href="https://cdnjs.cloudflare.com/ajax/libs/flowbite/1.7.0/flowbite.min.css" rel="stylesheet" />
          <link href="../../dist/output.css" rel="stylesheet" />
</head>
<body>
          <?php include '../includes/sidebar.php'; ?>
          <div class=" ml-64 p-8 flex-1 flex items-center justify-center min-h-screen px-4">
                    <h2>Welcome Admin, <?php echo $_SESSION['fullname']; ?></h2>
          </div>
</body>
</html>