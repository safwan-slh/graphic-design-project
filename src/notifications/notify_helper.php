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

// แจ้งเตือนลูกค้าเมื่อสถานะการชำระเงินเปลี่ยนแปลง
function notifyPaymentStatusToCustomer($conn, $customer_id, $order_id, $order_code, $status, $remark = '') {
    if ($status === 'paid') {
        $message = "การชำระเงินสำหรับ Order #$order_code ของคุณได้รับการอนุมัติแล้ว";
    } elseif ($status === 'cancelled') {
        $message = "การชำระเงินสำหรับ Order #$order_code ของคุณถูกปฏิเสธ: $remark";
    } else {
        $message = "สถานะการชำระเงินสำหรับ Order #$order_code ของคุณถูกเปลี่ยนเป็น $status";
    }
    $link = "/graphic-design/src/client/order.php?order_id=$order_id";
    sendNotification($conn, $customer_id, $message, $link, 0, 'payment');
}

// เพิ่มฟังก์ชันอื่นๆ ตามประเภทแจ้งเตือนที่ต้องการ
