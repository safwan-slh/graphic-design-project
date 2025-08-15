<?php

echo "<h1>ทดสอบการเชื่อมต่อฐานข้อมูล</h1>";

// 1. ทดสอบการหาไฟล์ db_connect.php
$db_connect_path = __DIR__ . '/../includes/db_connect.php';
echo "<h2>1. ทดสอบ Path ไปยัง db_connect.php</h2>";
echo "Path ที่ใช้: <code>" . htmlspecialchars($db_connect_path) . "</code><br>";

if (file_exists($db_connect_path)) {
    echo "<p style='color:green;'>✓ พบไฟล์ db_connect.php</p>";
    
    // 2. ทดสอบการเชื่อมต่อฐานข้อมูล
    echo "<h2>2. ทดสอบการเชื่อมต่อ MySQL</h2>";
    
    require $db_connect_path;
    
    if ($conn->connect_error) {
        echo "<p style='color:red;'>✖ การเชื่อมต่อล้มเหลว: " . $conn->connect_error . "</p>";
        
        // แสดงข้อมูลการเชื่อมต่อ (ควรลบออกใน production)
        echo "<h3>ข้อมูลการเชื่อมต่อ:</h3>";
        echo "<pre>" . print_r([
            'host' => $conn->host_info,
            'error' => $conn->error
        ], true) . "</pre>";
    } else {
        echo "<p style='color:green;'>✓ เชื่อมต่อฐานข้อมูลสำเร็จ</p>";
        
        // ทดสอบ query ข้อมูล
        echo "<h2>3. ทดสอบ Query ข้อมูล</h2>";
        $test_query = "SELECT 1+1 AS result";
        if ($result = $conn->query($test_query)) {
            $row = $result->fetch_assoc();
            echo "<p>ผลลัพธ์ query ทดสอบ: <strong>" . $row['result'] . "</strong></p>";
            $result->close();
        } else {
            echo "<p style='color:red;'>✖ Query ล้มเหลว: " . $conn->error . "</p>";
        }
    }
    
    $conn->close();
} else {
    echo "<p style='color:red;'>✖ ไม่พบไฟล์ db_connect.php</p>";
    echo "<p>โปรดตรวจสอบว่าไฟล์อยู่ที่: <code>/Applications/XAMPP/xamppfiles/htdocs/graphic-design/includes/db_connect.php</code></p>";
}

// 3. ทดสอบ PHP Info
echo "<h2>4. ข้อมูลสภาพแวดล้อม PHP</h2>";
echo "<p><a href='#' onclick='document.getElementById(\"phpinfo\").style.display=\"block\";return false;'>
แสดงข้อมูล PHP Info
</a></p>";
echo "<div id='phpinfo' style='display:none;'>";
ob_start();
phpinfo();
$phpinfo = ob_get_clean();
echo $phpinfo;
echo "</div>";
?>
<!DOCTYPE html>
<html>
<head>
    <title>ทดสอบ Database</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        code { background: #f0f0f0; padding: 2px 5px; }
    </style>
</head>
<body>
    <?php include __DIR__ . '/../../includes/navbar.php'; ?>
    <hr>
    <p><a href="/">กลับหน้าแรก</a></p>
</body>
</html>