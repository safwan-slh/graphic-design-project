<?php
function sendNotification($conn, $customer_id, $message, $link, $is_admin = 0, $type = 'general')
{
    $stmt = $conn->prepare("INSERT INTO notifications (customer_id, message, link, is_admin, type) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("issis", $customer_id, $message, $link, $is_admin, $type);
    $stmt->execute();
}

// ฟังก์ชันเฉพาะแต่ละประเภท (ถ้าต้องการ)
//ลูกค้าแจ้งชำระเงินสำหรับ Order
function notifyPaymentToAdmin($conn, $order_code, $payment_id) {
    $message = "ลูกค้าแจ้งชำระเงินสำหรับ Order #$order_code";
    $link = "/graphic-design/src/admin/payment_detail.php?id=$payment_id";
    sendNotification($conn, 1, $message, $link, 1, 'payment');
}


// เพิ่มฟังก์ชันอื่นๆ ตามประเภทแจ้งเตือนที่ต้องการ
