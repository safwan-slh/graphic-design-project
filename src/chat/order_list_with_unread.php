<?php
require_once __DIR__ . '/../includes/db_connect.php';
session_start();

$ordersWithChat = [];
if (isset($_SESSION['customer_id'])) {
  $cid = $_SESSION['customer_id'];
  $sql = "SELECT o.order_id, o.order_code
            FROM orders o
            WHERE o.customer_id = ?
            AND EXISTS (SELECT 1 FROM chat_messages c WHERE c.order_id = o.order_id)
            ORDER BY o.created_at DESC";
  $stmt = $conn->prepare($sql);
  $stmt->bind_param("i", $cid);
  $stmt->execute();
  $ordersWithChat = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}

foreach ($ordersWithChat as $order) {
  // Query หาจำนวนแชทยังไม่อ่านของแต่ละ order
  $sql = "SELECT COUNT(*) as unread FROM chat_messages WHERE order_id=? AND sender_role='admin' AND is_read=0";
  $stmt = $conn->prepare($sql);
  $stmt->bind_param("i", $order['order_id']);
  $stmt->execute();
  $unread = $stmt->get_result()->fetch_assoc()['unread'] ?? 0;
?>
  <li class="p-2 text-sm pb-0">
    <button type="button"
      class="w-full text-left px-4 py-3 bg-gray-100 hover:bg-gray-200 transition rounded-xl flex justify-between items-center hover:ring-1 hover:ring-gray-200 <?= $unread > 0 ? 'font-bold' : 'font-medium text-gray-500 bg-gray-50' ?>"
      onclick="selectOrderChat(<?= $order['order_id'] ?>)">
      ออเดอร์ #<?= htmlspecialchars($order['order_code']) ?>
      <?php if ($unread > 0): ?>
        <span class="ml-2 bg-red-500 text-white text-xs rounded-full px-1.5 py-0.5"><?= $unread ?></span>
      <?php endif; ?>
    </button>
  </li>
<?php
}
?>