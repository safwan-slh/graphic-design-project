-- ตารางผู้ใช้
CREATE TABLE customers (
    customer_id INT AUTO_INCREMENT PRIMARY KEY,
    fullname VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    phone VARCHAR(20),
    role ENUM('customer', 'admin') DEFAULT 'customer',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- ตารางบริการ
CREATE TABLE services (
    service_id INT AUTO_INCREMENT PRIMARY KEY,
    service_name TEXT NOT NULL,
    slug VARCHAR(255) UNIQUE NOT NULL,
    short_description TEXT,
    base_price DECIMAL(10,2),
    price_unit VARCHAR(10),
    is_featured BOOLEAN DEFAULT FALSE,
    is_active BOOLEAN DEFAULT TRUE,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- ตารางโปสเตอร์
CREATE TABLE poster_details (
    poster_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    service_id INT NOT NULL,
    
    -- ข้อมูลโครงการ
    project_name VARCHAR(255) NOT NULL,                        -- ชื่อโครงการ/กิจกรรม
    poster_type VARCHAR(50) NOT NULL,                          -- ประเภทโปสเตอร์
    objective TEXT NOT NULL,                                   -- วัตถุประสงค์ของโปสเตอร์
    target_audience VARCHAR(200) NOT NULL,                     -- กลุ่มเป้าหมาย
    main_message TEXT NOT NULL,                                -- ข้อความหลัก
    content TEXT NOT NULL,                                     -- เนื้อหาที่ต้องการ
    
    -- ข้อมูลเทคนิค
    size VARCHAR(20) NOT NULL,                                 -- ขนาดโปสเตอร์
    custom_width DECIMAL(5,2) NULL,                            -- ขนาดกำหนดเอง (กว้าง, ซม.)
    custom_height DECIMAL(5,2) NULL,                           -- ขนาดกำหนดเอง (สูง, ซม.)
    style VARCHAR(50) NULL,                                    -- สไตล์
    color_primary VARCHAR(7) NOT NULL DEFAULT '#4F46E5',
    color_secondary VARCHAR(7) NULL,
    color_accent VARCHAR(7) NULL,
    preferred_fonts VARCHAR(255) NULL,
    color_codes VARCHAR(255) NULL,
    orientation VARCHAR(20) NOT NULL,                          -- การวางแนว
    color_mode VARCHAR(10) DEFAULT 'both',                     -- โหมดสี
    
    -- ไฟล์และประกอบเพิ่มเติม
    logo_file VARCHAR(255) NULL,                               -- path ไฟล์โลโก้
    images_file VARCHAR(255) NULL,                             -- path ไฟล์รูปภาพ
    reference_file VARCHAR(255) NULL,                          -- ตัวอย่างงานที่ชอบ
    reference_link VARCHAR(255) NULL,                          -- ลิงก์ตัวอย่าง
    
    -- การดำเนินงาน
    budget_range VARCHAR(20) NOT NULL,                         -- ช่วงงบประมาณ
    due_date DATE NOT NULL,                                    -- วันที่ต้องการรับงาน
    avoid_elements TEXT,                                       -- สิ่งที่ไม่ต้องการ
    special_requirements TEXT NULL,
    
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    CONSTRAINT fk_poster_service FOREIGN KEY (service_id) REFERENCES services(service_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ตารางคำสั่งซื้อ
CREATE TABLE orders (
    order_id INT AUTO_INCREMENT PRIMARY KEY,
    order_code VARCHAR(32) UNIQUE,
    customer_id INT NOT NULL,
    service_id INT NOT NULL,
    ref_id INT NOT NULL, -- อ้างอิง id ของรายละเอียดงาน เช่น poster_id, logo_id ฯลฯ
    status VARCHAR(32) DEFAULT 'pending',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (customer_id) REFERENCES customers(customer_id),
    FOREIGN KEY (service_id) REFERENCES services(service_id)
    -- ref_id สามารถอ้างอิง id จากตารางรายละเอียดของแต่ละบริการ
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE payments (
    payment_id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT NOT NULL,
    customer_id INT, -- ใช้ customer_id แทน user_id
    amount DECIMAL(10,2) NOT NULL,
    payment_type VARCHAR(20) NOT NULL,
    deposit_remaining DECIMAL(10,2),
    payment_method VARCHAR(50) NOT NULL,
    payment_date DATETIME NOT NULL,
    payment_status VARCHAR(32) DEFAULT 'pending',
    reference_no VARCHAR(100),
    slip_file VARCHAR(255),
    remark TEXT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (order_id) REFERENCES orders(order_id) ON DELETE CASCADE,
    FOREIGN KEY (customer_id) REFERENCES customers(customer_id) -- เพิ่ม FK นี้ด้วย
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ตารางผลงานร้าน
CREATE TABLE portfolios (
    portfolio_id INT AUTO_INCREMENT PRIMARY KEY,
    service_id INT NOT NULL,
    title VARCHAR(255) NOT NULL,
    description TEXT,
    image_url VARCHAR(255) NOT NULL,
    thumbnail_url VARCHAR(255),
    client_name VARCHAR(100),
    project_date DATE,
    tags JSON,
    is_featured BOOLEAN DEFAULT FALSE,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (service_id) REFERENCES services(service_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ตารางไฟล์งานและความคิดเห็น
CREATE TABLE work_files (
    work_file_id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT NOT NULL,
    file_name VARCHAR(255),
    file_path VARCHAR(255),
    uploaded_at DATETIME,
    uploaded_by INT,
    note TEXT,
    version VARCHAR(20)
);

-- ตารางความคิดเห็น
CREATE TABLE work_comments (
    comment_id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT NOT NULL,
    version VARCHAR(20) NOT NULL,
    customer_id INT, -- เปลี่ยนจาก user_id เป็น customer_id
    comment TEXT NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- ตารางการแจ้งเตือน
CREATE TABLE notifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    customer_id INT,
    message TEXT,
    link VARCHAR(255),
    is_read TINYINT(1) DEFAULT 0,
    is_admin TINYINT(1) DEFAULT 0,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    type VARCHAR(20) DEFAULT 'general'
);

-- ตารางแชทข้อความ
CREATE TABLE chat_messages (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT NOT NULL,
    customer_id INT NOT NULL,
    sender_id INT NOT NULL,
    sender_role ENUM('customer', 'admin') NOT NULL,
    message TEXT NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    is_read TINYINT(1) DEFAULT 0,
    INDEX(order_id),
    INDEX(sender_id)
);