<?php
// src/includes/db_connect.php

// ตั้งค่า path (XAMPP บน macOS)
define('ROOT_PATH', dirname(__DIR__, 2)); // ได้ /Applications/XAMPP/htdocs/your-project
define('UPLOADS_DIR', ROOT_PATH . '/uploads');
define('UPLOADS_URL', '/graphic-design/uploads');  // ต้องมีชื่อโปรเจคใน URL

// ตั้งค่า MySQL ของ XAMPP
$host = "localhost";
$user = "root";
$pass = "root";  # XAMPP macOS มักไม่มีรหัสผ่าน default
$dbname = "graphic_design_db";

try {
    $conn = new mysqli($host, $user, $pass, $dbname);
    if ($conn->connect_error) {
        throw new Exception("Connection failed: " . $conn->connect_error);
    }
    $conn->set_charset("utf8mb4");
} catch (Exception $e) {
    error_log($e->getMessage());
    die("Database error. Please try later.");
}
?>