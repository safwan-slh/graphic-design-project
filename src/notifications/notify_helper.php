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

// แจ้งเตือนแอดมินเมื่อลูกค้ายกเลิกออเดอร์
function notifyOrderCancelledToAdmin($conn, $order_id, $order_code) {
    $message = "ลูกค้าได้ยกเลิก Order #$order_code";
    $link = "/graphic-design/src/admin/order_detail.php?id=$order_id";
    sendNotification($conn, 1, $message, $link, 1, 'order');
}

// แจ้งเตือนลูกค้าเมื่อแอดมินอัปโหลดไฟล์งาน
function notifyWorkFileUploaded($conn, $customer_id, $order_id, $orderCode, $version) {
    // กำหนด badge ตามเวอร์ชัน
    switch ($version) {
        case 'draft1':
            $badge = "<span class='bg-yellow-100 text-yellow-800 text-xs font-medium px-2.5 py-0.5 rounded-full ml-1'>แบบร่างที่ 1</span>";
            break;
        case 'draft2':
            $badge = "<span class='bg-blue-100 text-blue-800 text-xs font-medium px-2.5 py-0.5 rounded-full ml-1'>แบบร่างที่ 2</span>";
            break;
        case 'final':
            $badge = "<span class='bg-green-100 text-green-800 text-xs font-medium px-2.5 py-0.5 rounded-full ml-1'>ฉบับสมบูรณ์</span>";
            break;
        default:
            $badge = "";
    }
    $msg = "แอดมินอัปโหลดไฟล์งานสำหรับออเดอร์ #$orderCode $badge";
    $link = "/graphic-design/src/client/order_detail.php?order_id=" . $order_id;
    sendNotification($conn, $customer_id, $msg, $link, 0, 'workfile');
}