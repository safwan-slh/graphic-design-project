module.exports = {
  content: [
    // ไฟล์ HTML
    "./public/**/*.html",
    "./src/**/*.html",
    
    // ไฟล์ PHP
    "./**/*.php",               // สแกนทุกไฟล์ PHP ในโปรเจ็กต์
    "./src/auth/**/*.php",      // ไฟล์ PHP ในโฟลเดอร์ auth
    "./src/admin/**/*.php",     // ไฟล์ PHP ในโฟลเดอร์ admin
    "./src/client/**/*.php",    // ไฟล์ PHP ในโฟลเดอร์ client
    "./src/test/**/*.php",    // ไฟล์ PHP ในโฟลเดอร์ test
    "./src/includes/**/*.php",    // ไฟล์ PHP ในโฟลเดอร์ includes


    // ไฟล์ JavaScript
    "./src/**/*.js"
  ], // ระบุไฟล์ที่ใช้ Tailwind
  theme: {
    extend: {},
  },
  plugins: [],
}