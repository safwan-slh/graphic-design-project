<?php
function sendNotification($conn, $customer_id, $message, $link, $is_admin = 0, $type = 'general')
{
    $stmt = $conn->prepare("INSERT INTO notifications (customer_id, message, link, is_admin, type) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("issis", $customer_id, $message, $link, $is_admin, $type);
    $stmt->execute();
}

// ฟังก์ชันเฉพาะแต่ละประเภท (ถ้าต้องการ)
function notifyPaymentApproved($conn, $customer_id, $order_id)
{
    $message = "การชำระเงินของคุณสำหรับ Order #$order_id ได้รับการอนุมัติแล้ว";
    $link = "/client/order.php?order_id=$order_id";
    sendNotification($conn, $customer_id, $message, $link, 0);
}

function notifyOrderCancelledToAdmin($conn, $order_id, $order_code)
{
    $message = "ลูกค้าได้ยกเลิก Order #$order_code";
    $link = "/admin/order_detail.php?id=$order_id";
    sendNotification($conn, 1, $message, $link, 1);
}

// เพิ่มฟังก์ชันอื่นๆ ตามประเภทแจ้งเตือนที่ต้องการ
