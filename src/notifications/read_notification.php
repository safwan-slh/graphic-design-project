<?php
require_once __DIR__ . '/../includes/db_connect.php';
session_start();

$notificationId = $_GET['id'] ?? null;
$redirect = $_GET['redirect'] ?? '/graphic-design/src/client/order.php';

if ($notificationId) {
    $stmt = $conn->prepare("UPDATE notifications SET is_read = 1 WHERE id = ?");
    $stmt->bind_param("i", $notificationId);
    $stmt->execute();
}

header("Location: $redirect");
exit;