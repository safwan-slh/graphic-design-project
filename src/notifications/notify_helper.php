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

// แจ้งเตือนแอดมินเมื่อลูกค้าอัปเดทการชำระเงิน
function notifyPaymentUpdateToAdmin($conn, $order_code, $payment_id) {
    $message = "ลูกค้าอัปเดทข้อมูลการชำระเงินสำหรับ Order #$order_code";
    $link = "/graphic-design/src/admin/payment_detail.php?id=$payment_id";
    sendNotification($conn, 1, $message, $link, 1, 'payment_update');
}

// แจ้งเตือนลูกค้าเมื่อสถานะการชำระเงินเปลี่ยนแปลง
function notifyPaymentStatusToCustomer($conn, $customer_id, $order_id, $order_code, $status, $remark = '') {
    if ($status === 'paid') {
        $message = "การชำระเงินสำหรับ Order #$order_code ของคุณได้รับการอนุมัติแล้ว";
        $type = 'payment';
    } elseif ($status === 'cancelled') {
        $message = "การชำระเงินสำหรับ Order #$order_code ของคุณถูกปฏิเสธ: $remark";
        $type = 'payment_rejected'; // ใช้ type ใหม่
    } else {
        $message = "สถานะการชำระเงินสำหรับ Order #$order_code ของคุณถูกเปลี่ยนเป็น $status";
        $type = 'payment';
    }
    $link = "/graphic-design/src/client/order.php?order_id=$order_id";
    sendNotification($conn, $customer_id, $message, $link, 0, $type);
}

// แจ้งเตือนลูกค้าเมื่อสถานะออเดอร์เปลี่ยนแปลง
function notifyOrderStatusChanged($conn, $customer_id, $order_id, $order_code, $newStatus) {
    // กำหนดข้อความและ badge ตามสถานะ
    switch ($newStatus) {
        case 'pending':
            $badge = "<span class='bg-yellow-100 text-yellow-800 text-xs font-medium px-2.5 py-0.5 rounded-full ml-1'>รอตรวจสอบ</span>";
            $msg = "ออเดอร์ #$order_code ของคุณอยู่ระหว่างรอตรวจสอบ $badge";
            break;
        case 'in_progress':
            $badge = "<span class='bg-blue-100 text-blue-800 text-xs font-medium px-2.5 py-0.5 rounded-full ml-1'>กำลังดำเนินการ</span>";
            $msg = "ออเดอร์ #$order_code ของคุณกำลังดำเนินการ $badge";
            break;
        case 'completed':
            $badge = "<span class='bg-green-100 text-green-800 text-xs font-medium px-2.5 py-0.5 rounded-full ml-1'>เสร็จสมบูรณ์</span>";
            $msg = "ออเดอร์ #$order_code ของคุณเสร็จสมบูรณ์แล้ว $badge";
            break;
        case 'cancelled':
            $badge = "<span class='bg-red-100 text-red-800 text-xs font-medium px-2.5 py-0.5 rounded-full ml-1'>ยกเลิก</span>";
            $msg = "ออเดอร์ #$order_code ของคุณถูกยกเลิก $badge";
            break;
        default:
            $badge = "";
            $msg = "สถานะออเดอร์ #$order_code ของคุณถูกอัปเดต";
    }
    $link = "/graphic-design/src/client/order_detail.php?order_id=" . $order_id;
    sendNotification($conn, $customer_id, $msg, $link, 0, 'order');
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

// แจ้งเตือนแอดมินเมื่อ "ลูกค้า" คอมเมนต์
function notifyAdminCustomerComment($conn, $order_id, $orderCode, $version) {
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
    $msg = "ลูกค้าคอมเมนต์ในออเดอร์ #$orderCode $badge";
    $link = "/graphic-design/src/admin/order_detail.php?id=" . $order_id;
    sendNotification($conn, 1, $msg, $link, 1, 'comment');
}

// แจ้งเตือนลูกค้าเมื่อ "แอดมิน" คอมเมนต์
function notifyComment($conn, $isAdmin, $order_id, $orderCode, $customer_id, $version) {
    // กำหนด badge เวอร์ชัน
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
    $msg = "แอดมินคอมเมนต์ในออเดอร์ #$orderCode $badge";
    $link = "/graphic-design/src/client/order_detail.php?order_id=" . $order_id;
    sendNotification($conn, $customer_id, $msg, $link, 0, 'comment');
}

// แจ้งเตือนแชท (ทั่วไป/ออเดอร์)
function notifyChat($conn, $to_admin, $customer_id, $order_id = null, $order_code = null, $type = 'order') {
    if ($to_admin) {
        $msg = $type === 'order'
            ? "ลูกค้าส่งข้อความใหม่ในออเดอร์ #$order_code"
            : "ลูกค้าส่งข้อความใหม่ในแชทสอบถามทั่วไป";
        $link = $type === 'order'
            ? "/graphic-design/src/admin/order_detail.php?id=$order_id"
            : "/graphic-design/src/admin/general_chat.php?customer_id=$customer_id";
        sendNotification($conn, 1, $msg, $link, 1, 'chat');
    } else {
        $msg = $type === 'order'
            ? "ทีมงานส่งข้อความใหม่ในออเดอร์ #$order_code"
            : "ทีมงานตอบกลับข้อความสอบถามทั่วไปของคุณ";
        $link = $type === 'order'
            ? "/graphic-design/src/client/poster_detail.php?order_id=$order_id"
            : "/graphic-design/src/client/chat_general.php";
        sendNotification($conn, $customer_id, $msg, $link, 0, 'chat');
    }
}

// แจ้งเตือนแอดมินเมื่อ "ลูกค้า" รีวิวออเดอร์
function notifyReviewToAdmin($conn, $order_id, $customer_id) {
    $msg = "ลูกค้าได้รีวิวออเดอร์ #$order_id";
    $link = "/graphic-design/src/admin/order_detail.php?id=$order_id";
    sendNotification($conn, 1, $msg, $link, 1, 'review');
}

// แจ้งเตือนลูกค้าเมื่อรีวิวถูกอนุมัติหรือไม่อนุมัติ
function notifyReviewApproveStatusToCustomer($conn, $customer_id, $order_id, $is_approved, $reason = '') {
    if ($is_approved) {
        $msg = "รีวิวของคุณสำหรับออเดอร์ #$order_id ได้รับการอนุมัติแล้ว";
    } else {
        $msg = "รีวิวของคุณสำหรับออเดอร์ #$order_id ไม่ได้รับการอนุมัติ";
        if ($reason) {
            $msg .= " เหตุผล: $reason";
        }
    }
    $link = "/graphic-design/src/client/poster_detail.php?order_id=$order_id";
    sendNotification($conn, $customer_id, $msg, $link, 0, 'review');
}