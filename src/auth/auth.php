<?php
session_start();

if (!function_exists('requireLogin')) {
    // ฟังก์ชันเช็กว่าล็อกอินหรือยัง
    function requireLogin()
    {
        if (!isset($_SESSION['customer_id'])) {
            header("Location: ../auth/signin.php");
            exit();
        }
    }
}

if (!function_exists('requireRole')) {
    // ฟังก์ชันเช็ก role
    function requireRole($role)
    {
        requireLogin();
        if ($_SESSION['role'] !== $role) {
            // ถ้า role ไม่ตรง ให้กลับไปหน้า login
            header("Location: ../auth/signin.php");
            exit();
        }
    }
}
