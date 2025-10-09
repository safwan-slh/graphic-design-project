<?php
require_once __DIR__ . '/../includes/db_connect.php';
session_start();
$count = 0;
if (isset($_SESSION['customer_id'])) {
  $cid = $_SESSION['customer_id'];
  $sql = "SELECT COUNT(*) as cnt FROM chat_messages WHERE order_id IN (SELECT order_id FROM orders WHERE customer_id=?) AND sender_role='admin' AND is_read=0";
  $stmt = $conn->prepare($sql);
  $stmt->bind_param("i", $cid);
  $stmt->execute();
  $result = $stmt->get_result()->fetch_assoc();
  $count = $result['cnt'] ?? 0;
}
echo json_encode(['count' => $count]);