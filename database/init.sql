-- ตารางผู้ใช้
CREATE TABLE customers (
    customer_id INT AUTO_INCREMENT PRIMARY KEY,      -- รหัสลูกค้า
    fullname VARCHAR(100) NOT NULL,                  -- ชื่อ-นามสกุล
    email VARCHAR(100) NOT NULL UNIQUE,              -- อีเมล
    password VARCHAR(255) NOT NULL,                  -- รหัสผ่าน (hash)
    phone VARCHAR(20),                              -- เบอร์โทรศัพท์
    role ENUM('customer', 'admin') DEFAULT 'customer', -- บทบาท (ลูกค้า/แอดมิน)
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP   -- วันที่สมัคร
);

-- ตารางบริการ
CREATE TABLE services (
    service_id INT AUTO_INCREMENT PRIMARY KEY,       -- รหัสบริการ
    service_name TEXT NOT NULL,                      -- ชื่อบริการ
    slug VARCHAR(255) UNIQUE NOT NULL,               -- slug สำหรับ URL
    short_description TEXT,                          -- คำอธิบายสั้น
    base_price DECIMAL(10,2),                        -- ราคาตั้งต้น
    price_unit VARCHAR(10),                          -- หน่วยราคา
    is_featured BOOLEAN DEFAULT FALSE,               -- เป็นบริการแนะนำหรือไม่
    is_active BOOLEAN DEFAULT TRUE,                  -- เปิดใช้งานหรือไม่
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,   -- วันที่สร้าง
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP -- วันที่แก้ไขล่าสุด
);

-- ตารางโปสเตอร์
CREATE TABLE poster_details (
    poster_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY, -- รหัสโปสเตอร์
    service_id INT NOT NULL,                           -- อ้างอิงบริการ

    project_name VARCHAR(255) NOT NULL,                -- ชื่อโครงการ/กิจกรรม
    poster_type VARCHAR(50) NOT NULL,                  -- ประเภทโปสเตอร์
    objective TEXT NOT NULL,                           -- วัตถุประสงค์
    target_audience VARCHAR(200) NOT NULL,             -- กลุ่มเป้าหมาย
    main_message TEXT NOT NULL,                        -- ข้อความหลัก
    content TEXT NOT NULL,                             -- เนื้อหาที่ต้องการ

    size VARCHAR(20) NOT NULL,                         -- ขนาดโปสเตอร์
    custom_width DECIMAL(5,2) NULL,                    -- กว้าง (ถ้ากำหนดเอง)
    custom_height DECIMAL(5,2) NULL,                   -- สูง (ถ้ากำหนดเอง)
    style VARCHAR(50) NULL,                            -- สไตล์
    color_primary VARCHAR(7) NOT NULL DEFAULT '#4F46E5', -- สีหลัก
    color_secondary VARCHAR(7) NULL,                   -- สีรอง
    color_accent VARCHAR(7) NULL,                      -- สีเน้น
    preferred_fonts VARCHAR(255) NULL,                 -- ฟอนต์ที่ต้องการ
    color_codes VARCHAR(255) NULL,                     -- รหัสสีเพิ่มเติม
    orientation VARCHAR(20) NOT NULL,                  -- แนวตั้ง/แนวนอน
    color_mode VARCHAR(10) DEFAULT 'both',             -- โหมดสี (CMYK/RGB/both)

    logo_file VARCHAR(255) NULL,                       -- ไฟล์โลโก้
    images_file VARCHAR(255) NULL,                     -- ไฟล์รูปภาพ
    reference_file VARCHAR(255) NULL,                  -- ตัวอย่างงานที่ชอบ
    reference_link VARCHAR(255) NULL,                  -- ลิงก์ตัวอย่าง

    budget_range VARCHAR(20) NOT NULL,                 -- ช่วงงบประมาณ
    due_date DATE NOT NULL,                            -- วันที่ต้องการรับงาน
    avoid_elements TEXT,                               -- สิ่งที่ไม่ต้องการ
    special_requirements TEXT NULL,                    -- ข้อกำหนดพิเศษ

    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,     -- วันที่สร้าง
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP -- วันที่แก้ไขล่าสุด
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ตารางคำสั่งซื้อ
CREATE TABLE orders (
    order_id INT AUTO_INCREMENT PRIMARY KEY,          -- รหัสคำสั่งซื้อ
    order_code VARCHAR(32) UNIQUE,                    -- รหัสออเดอร์ (สำหรับลูกค้า)
    customer_id INT NOT NULL,                         -- อ้างอิงลูกค้า
    service_id INT NOT NULL,                          -- อ้างอิงบริการ
    ref_id INT NOT NULL,                              -- อ้างอิงรายละเอียดงาน (เช่น poster_id)
    status VARCHAR(32) DEFAULT 'pending',             -- สถานะออเดอร์
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,    -- วันที่สร้าง
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP -- วันที่แก้ไขล่าสุด
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE payments (
    payment_id INT AUTO_INCREMENT PRIMARY KEY,        -- รหัสการชำระเงิน
    order_id INT NOT NULL,                            -- อ้างอิงออเดอร์
    customer_id INT,                                  -- อ้างอิงลูกค้า
    amount DECIMAL(10,2) NOT NULL,                    -- จำนวนเงิน
    payment_type VARCHAR(20) NOT NULL,                -- ประเภทการชำระ (มัดจำ/เต็มจำนวน)
    deposit_remaining DECIMAL(10,2),                  -- ยอดคงเหลือมัดจำ
    payment_method VARCHAR(50) NOT NULL,              -- วิธีชำระเงิน
    payment_date DATETIME NOT NULL,                   -- วันที่ชำระเงิน
    payment_status VARCHAR(32) DEFAULT 'pending',     -- สถานะการชำระเงิน
    reference_no VARCHAR(100),                        -- เลขอ้างอิง
    slip_file VARCHAR(255),                           -- ไฟล์สลิป
    remark TEXT,                                      -- หมายเหตุ
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,    -- วันที่สร้าง
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP -- วันที่แก้ไขล่าสุด
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ตารางผลงานร้าน
CREATE TABLE portfolios (
    portfolio_id INT AUTO_INCREMENT PRIMARY KEY,      -- รหัสผลงาน
    service_id INT NOT NULL,                          -- อ้างอิงบริการ
    title VARCHAR(255) NOT NULL,                      -- ชื่อผลงาน
    description TEXT,                                 -- รายละเอียดผลงาน
    image_url VARCHAR(255) NOT NULL,                  -- รูปผลงาน
    thumbnail_url VARCHAR(255),                       -- รูป thumbnail
    client_name VARCHAR(100),                         -- ชื่อลูกค้า
    project_date DATE,                                -- วันที่ทำงาน
    tags JSON,                                        -- แท็กผลงาน
    is_featured BOOLEAN DEFAULT FALSE,                -- เป็นผลงานแนะนำหรือไม่
    is_active BOOLEAN DEFAULT TRUE,                   -- เปิดใช้งานหรือไม่
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,   -- วันที่สร้าง
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP -- วันที่แก้ไขล่าสุด
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ตารางไฟล์งานและความคิดเห็น
CREATE TABLE work_files (
    work_file_id INT AUTO_INCREMENT PRIMARY KEY,      -- รหัสไฟล์งาน
    order_id INT NOT NULL,                            -- อ้างอิงออเดอร์
    file_name VARCHAR(255),                           -- ชื่อไฟล์
    file_path VARCHAR(255),                           -- path ไฟล์
    uploaded_at DATETIME,                             -- วันที่อัปโหลด
    uploaded_by INT,                                  -- อัปโหลดโดย (user id)
    note TEXT,                                        -- หมายเหตุ
    version VARCHAR(20)                               -- เวอร์ชันไฟล์
);

-- ตารางความคิดเห็น
CREATE TABLE work_comments (
    comment_id INT AUTO_INCREMENT PRIMARY KEY,        -- รหัสคอมเมนต์
    order_id INT NOT NULL,                            -- อ้างอิงออเดอร์
    version VARCHAR(20) NOT NULL,                     -- เวอร์ชันงาน
    customer_id INT,                                  -- อ้างอิงลูกค้า
    comment TEXT NOT NULL,                            -- ข้อความคอมเมนต์
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP     -- วันที่คอมเมนต์
);

-- ตารางการแจ้งเตือน
CREATE TABLE notifications (
    id INT AUTO_INCREMENT PRIMARY KEY,                -- รหัสแจ้งเตือน
    customer_id INT,                                  -- อ้างอิงลูกค้า
    message TEXT,                                     -- ข้อความแจ้งเตือน
    link VARCHAR(255),                                -- ลิงก์ที่เกี่ยวข้อง
    is_read TINYINT(1) DEFAULT 0,                     -- อ่านแล้วหรือยัง
    is_admin TINYINT(1) DEFAULT 0,                    -- แจ้งเตือนสำหรับแอดมินหรือไม่
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,    -- วันที่แจ้งเตือน
    type VARCHAR(20) DEFAULT 'general'                -- ประเภทแจ้งเตือน
);

-- ตารางแชทข้อความ
CREATE TABLE chat_messages (
    id INT AUTO_INCREMENT PRIMARY KEY,                -- รหัสข้อความแชท
    order_id INT NOT NULL,                            -- อ้างอิงออเดอร์ (0 = แชททั่วไป)
    customer_id INT NOT NULL,                         -- อ้างอิงลูกค้า
    sender_id INT NOT NULL,                           -- รหัสผู้ส่ง
    sender_role ENUM('customer', 'admin') NOT NULL,   -- บทบาทผู้ส่ง
    message TEXT NOT NULL,                            -- ข้อความ
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,    -- วันที่ส่ง
    is_read TINYINT(1) DEFAULT 0,                     -- อ่านแล้วหรือยัง
    INDEX(order_id),                                  -- index สำหรับ order_id
    INDEX(sender_id)                                  -- index สำหรับ sender_id
);

-- ตารางรีวิว
CREATE TABLE reviews (
    id INT AUTO_INCREMENT PRIMARY KEY,                -- รหัสรีวิว
    order_id INT NOT NULL,                            -- อ้างอิงออเดอร์
    customer_id INT NOT NULL,                         -- อ้างอิงลูกค้า
    rating INT NOT NULL,                              -- คะแนนรีวิว (1-5)
    comment TEXT,                                     -- ข้อความรีวิว
    image VARCHAR(255),                               -- รูปภาพประกอบรีวิว
    is_approved TINYINT(1) DEFAULT 1,                 -- สถานะอนุมัติแสดงผล
    reason TEXT,                                      -- เหตุผลถ้าไม่อนุมัติ
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,    -- วันที่รีวิว
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP -- วันที่แก้ไขล่าสุด
);