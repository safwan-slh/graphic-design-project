-- ข้อมูลตัวอย่างสำหรับ customers
INSERT INTO customers (fullname, email, password, phone, role)
VALUES
('สมชาย ใจดี', 'somchai@example.com', '$2y$10$examplehashpassword', '0812345678', 'customer'),
('admin', 'admin@example.com', '$2y$10$adminhashpassword', '0898765432', 'admin');

-- ข้อมูลตัวอย่างสำหรับ services
INSERT INTO services (service_name, slug, short_description, base_price, price_unit, is_featured, is_active)
VALUES
('ออกแบบโปสเตอร์', 'poster-design', 'บริการออกแบบโปสเตอร์ทุกประเภท', 1000.00, 'บาท', TRUE, TRUE),
('ออกแบบโลโก้', 'logo-design', 'บริการออกแบบโลโก้สำหรับธุรกิจ', 2000.00, 'บาท', TRUE, TRUE);

-- ข้อมูลตัวอย่างสำหรับ poster_details
INSERT INTO poster_details (
    service_id, project_name, poster_type, objective, target_audience, main_message, content,
    size, custom_width, custom_height, style, color_primary, color_secondary, color_accent,
    preferred_fonts, color_codes, orientation, color_mode, budget_range, due_date, avoid_elements, special_requirements, reference_link
) VALUES
(1, 'โครงการรณรงค์ลดขยะ', 'ประชาสัมพันธ์', 'สร้างความตระหนักเรื่องขยะ', 'นักเรียนมัธยม', 'ลดขยะเพื่อโลก', 'ขอเชิญร่วมกิจกรรมลดขยะในโรงเรียน',
'A3', NULL, NULL, 'มินิมอล', '#4F46E5', '#F59E42', '#22D3EE', 'Prompt, Kanit', '#4F46E5,#F59E42,#22D3EE', 'แนวตั้ง', 'CMYK', '1000-2000', '2024-10-01', 'ภาพขยะ', 'เน้นสีฟ้า', 'https://example.com/reference1.pdf'
);

-- ข้อมูลตัวอย่างสำหรับ portfolios
INSERT INTO portfolios (
    service_id, title, description, image_url, thumbnail_url, client_name, project_date, tags, is_featured, is_active
) VALUES
(1, 'โปสเตอร์รณรงค์ลดขยะ', 'โปสเตอร์สำหรับโครงการรณรงค์ลดขยะในโรงเรียน', '/uploads/portfolio/poster1.jpg', '/uploads/portfolio/thumb1.jpg', 'โรงเรียนตัวอย่าง', '2024-09-01', '["สิ่งแวดล้อม","โรงเรียน","ลดขยะ"]', TRUE, TRUE),
(2, 'โลโก้ร้านกาแฟ', 'โลโก้สำหรับร้านกาแฟสไตล์มินิมอล', '/uploads/portfolio/logo1.png', '/uploads/portfolio/logo1-thumb.png', 'Coffee Minimal', '2024-08-15', '["โลโก้","กาแฟ","มินิมอล"]', TRUE, TRUE);