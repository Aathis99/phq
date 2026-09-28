# Project Context: PHQ Web System (SESA_DB)

## 1. System Overview
ระบบบริหารจัดการการประเมินสุขภาพจิตและติดตามการช่วยเหลือรายกรณี (PHQ-9 System) ออกแบบมาเพื่อให้นักเรียนสามารถทำแบบประเมินภาวะซึมเศร้า และให้เจ้าหน้าที่/ครูสามารถติดตาม ให้การช่วยเหลือ และส่งต่อเคสไปยังหน่วยงานที่เกี่ยวข้องได้อย่างเป็นระบบ

### Technology Stack
- **Backend:** PHP 8.2 (Vanilla PHP with OOP principles)
- **Database:** MySQL / MariaDB (MySQL 8.0 Compatible)
- **Database Access:** PHP Data Objects (PDO) with Singleton Pattern (`app/core/Database.php`)
- **Frontend:** Bootstrap 5.3, SweetAlert2, Vanilla JavaScript (Fetch API)
- **Architecture:** Procedural with separate Core/Config layers (`app/` directory)
- **Server Environment:** Nginx, Docker / Linux Remote Server
- **AI Tooling & Live Inspection:** Gemini CLI with Model Context Protocol (MySQL MCP Server)

---

## 2. Database Environment & Schema Structure
ฐานข้อมูลจริงคือ `std660104db` ประกอบด้วย 12 ตารางหลัก โดยยึดหลัก Student-Centric (อ้างอิงเลขประจำตัวประชาชน `pid` เป็นแกนหลัก)

### Live Database & MCP Access Guidelines
- **Live Tooling:** เชื่อมต่อผ่าน MySQL MCP server (`mysql`) สำหรับการตรวจสอบ Schema, Index และโครงสร้างตารางสด
- **Token Saver & Quota Protection:**
  - หลีกเลี่ยงการสแกนหรืออ่านไฟล์ดัมป์ขนาดใหญ่ เช่น `backup/*.sql`
  - ตรวจสอบโครงสร้างตารางผ่านคำสั่ง Schema โดยตรง (`DESCRIBE`, `SHOW COLUMNS`, `SHOW CREATE TABLE`)
  - หากจำเป็นต้องดูข้อมูลจริง ให้กำหนดขอบเขตอย่างเข้มงวดเสมอ (`LIMIT 1` ถึง `LIMIT 3`) และห้ามดึงข้อมูลยกตาราง (`SELECT *`)

### Core Tables
- **`student_data`**: เก็บข้อมูลพื้นฐานนักเรียน (PK: `pid`)
- **`users`**: ข้อมูลผู้ใช้งานระบบและสิทธิ์การเข้าถึง (PK: `username`)
- **`assessment`**: ผลการประเมิน PHQ-9 และคะแนนความเครียด
- **`add_caselog`**: บันทึกรายละเอียดการช่วยเหลือรายกรณี (Running Number ต่อคน)
- **`forward_case`**: บันทึกประวัติการส่งต่อหน่วยงานภายนอก/ภายใน
- **`closure_report`**: บันทึกการยุติการดูแลช่วยเหลือ
- **`images`**: บันทึกพาธรูปภาพหลักฐานประกอบเคส
- **Master Tables:** `prefix`, `sex`, `school`, `type`

### Relationships (Foreign Keys)
- `assessment.pid`, `add_caselog.pid`, `forward_case.pid`, `closure_report.pid` -> `student_data.pid` (Cascade Update/Delete)
- `add_caselog.recorder`, `closure_report.recorder` -> `users.username` (Set Null/Cascade)
- `images.case_id` -> `add_caselog.id` (Cascade)
- ข้อมูลอ้างอิง: `prefix_id`, `sex_id`, `school_id` เชื่อมโยงกับตาราง Master ตามประเภทข้อมูล

---

## 3. Architecture & Data Flow
ระบบแบ่งแยกส่วนการทำงาน (Separation of Concerns) เบื้องต้นดังนี้:

### Request Flow
1. **Frontend (`public/`):** ไฟล์ PHP (View) รับ User Input และแสดงผลหน้าจอ
2. **Assets (`public/script/`, `public/css/`):** จัดการ UI และการเรียก AJAX ผ่าน `fetch()`
3. **Processing (`public/save_*.php` และ `public/api/`):** รับและกรองค่าจาก `$_POST`, `$_GET`
4. **Core Layer (`app/core/`):** จัดการ Connection Pool ผ่าน Singleton Instance ของ `Database.php`
5. **Database Layer:** บันทึกและดึงข้อมูลจาก MariaDB/MySQL

### Data Handling
- ใช้ **Prepared Statements (PDO)** ในทุกคำสั่งเพื่อป้องกัน SQL Injection
- ใช้ **Database Transactions** (`beginTransaction`, `commit`, `rollBack`) สำหรับกระบวนการหลายขั้นตอน (เช่น บันทึกเคสพร้อมอัปโหลดรูปภาพ)
- จัดการไฟล์รูปภาพในโฟลเดอร์ `public/uploads/cases/` โดยจัดเก็บเฉพาะชื่อไฟล์ลงในตาราง `images`

---

## 4. Core Modules & Endpoints

### Authentication & Session Guard
- `public/login.php`: หน้าฟอร์มลงชื่อเข้าใช้งาน
- `public/login_process.php`: ตรวจสอบรหัสผ่านกับตาราง `users` และบันทึก Session ใน `$_SESSION['user']`
- **Auth Guard:** หน้าควบคุมและบันทึกเคสจะตรวจสอบ `isset($_SESSION['user'])` ก่อนเริ่มประมวลผล หากไม่มีสิทธิ์จะถูก Redirect ไปหน้าล็อกอินทันที

### Case Management Workflows
- `public/add_case.php` & `public/save_case.php`: รับเรื่องและบันทึกเคสใหม่ลง `add_caselog` พร้อมอัปเดตสถานะใน `student_data`
- `public/forward_case.php` & `public/save_forward.php`: บันทึกข้อมูลการส่งต่อเคสไปยังหน่วยงานภายนอก/ภายใน
- `public/closure_report.php` & `public/save_closure.php`: สรุปผลและบันทึกการปิดเคส (ยุติการช่วยเหลือ)

### Internal APIs (`public/api/`)
- `member_api.php`: จัดการข้อมูลบัญชีผู้ใช้งาน (CRUD)
- `add_student.php`: เพิ่มข้อมูลนักเรียนใหม่ผ่าน AJAX
- `update_case.php` / `delete_case.php`: อัปเดตและลบรายการเคส
- `delete_assessment.php`: จัดการประวัติการประเมิน PHQ

---

## 5. Rules & Conventions for AI & Development
1. **Strict Read-Only by Default:** AI ทำหน้าที่เป็น Code Reviewer และ System Analyst เท่านั้น ห้ามแก้ไขโค้ดแอปพลิเคชันโดยไม่ได้รับคำสั่งอย่างชัดเจน
2. **MCP Live Database Policy:**
   - อนุญาตเฉพาะคำสั่งอ่านข้อมูล (`SELECT`, `SHOW`, `DESCRIBE`, `EXPLAIN`)
   - **ห้ามเด็ดขาด:** คำสั่ง DDL หรือ DML แก้ไขข้อมูล (`INSERT`, `UPDATE`, `DELETE`, `DROP`, `ALTER`, `TRUNCATE`) บนฐานข้อมูลจริงผ่าน MCP
3. **Database Integrity:** ทุกตารางต้องรักษา Foreign Key Constraint และใช้ Transaction กำกับเมื่อมีการเขียนข้อมูลข้ามตาราง
4. **Code Consistency:**
   - ตัวแปรและคอลัมน์ใช้รูปแบบ `snake_case` ตาม Schema ของฐานข้อมูล
   - ไฟล์แกนหลักใน `app/core/Database.php` จัดเป็นพื้นที่สงวน ห้ามแก้ไขหากไม่มีคำสั่งเฉพาะ
5. **Security & Validation:**
   - ห้ามรัน Dynamic Query แบบเชื่อมต่อ String เด็ดขาด
   - กรองและครอบผลลัพธ์หน้าบ้านด้วยฟังก์ชันป้องกัน XSS (`htmlspecialchars`)
6. **UI/UX Standard:** จัดการแจ้งเตือนผลลัพธ์ (Success/Error) ฝั่ง Frontend ด้วย `sweetalert_utils.js` สม่ำเสมอ