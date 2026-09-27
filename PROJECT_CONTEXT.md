# Project Context: PHQ Web System (SESA_DB)

## 1. System Overview
ระบบบริหารจัดการการประเมินสุขภาพจิตและติดตามการช่วยเหลือรายกรณี (PHQ-9 System) ออกแบบมาเพื่อให้นักเรียนสามารถทำแบบประเมินภาวะซึมเศร้า และให้เจ้าหน้าที่/ครูสามารถติดตาม ให้การช่วยเหลือ และส่งต่อเคสไปยังหน่วยงานที่เกี่ยวข้องได้อย่างเป็นระบบ

### Technology Stack
- **Backend:** PHP 8.2 (Vanilla PHP with OOP principles)
- **Database:** MariaDB (MySQL Compatible)
- **Database Access:** PHP Data Objects (PDO) with Singleton Pattern
- **Frontend:** Bootstrap 5.3, SweetAlert2, Vanilla JavaScript (Fetch API)
- **Architecture:** Procedural with separate Core/Config layers (App directory)
- **Server:** Nginx, Docker (Development Environment)

---

## 2. Database Schema & Relationships
ฐานข้อมูลประกอบด้วย 12 ตารางหลัก โดยมีความสัมพันธ์แบบ Student-Centric (ยึดเลขบัตรประชาชน `pid` เป็นหลัก)

### Core Tables
- **`student_data`**: เก็บข้อมูลพื้นฐานนักเรียน (PK: `pid`)
- **`users`**: ข้อมูลผู้ใช้งานระบบและสิทธิ์การเข้าถึง (PK: `username`)
- **`assessment`**: ผลการประเมิน PHQ-9 และคะแนนความเครียด
- **`add_caselog`**: บันทึกรายละเอียดการช่วยเหลือรายกรณี (Running Number ต่อคน)
- **`forward_case`**: บันทึกประวัติการส่งต่อหน่วยงานภายนอก
- **`closure_report`**: บันทึกการยุติการดูแลช่วยเหลือ

### Relationships (Foreign Keys)
- `assessment.pid`, `add_caselog.pid`, `forward_case.pid`, `closure_report.pid` -> `student_data.pid` (Cascade Update/Delete)
- `add_caselog.recorder`, `closure_report.recorder` -> `users.username` (Set Null/Cascade)
- `images.case_id` -> `add_caselog.id` (Cascade)
- ข้อมูลอ้างอิง: `prefix_id`, `sex_id`, `school_id` เชื่อมโยงกับตาราง Master ข้อมูลนั้นๆ

---

## 3. Architecture & Data Flow
ระบบแบ่งแยกส่วนการทำงาน (Separation of Concerns) เบื้องต้นดังนี้:

### Request Flow
1. **Frontend (Public/):** ไฟล์ PHP (View) รับ User Input และแสดงผล
2. **Assets (Script/CSS):** จัดการ UI และการเรียก AJAX ผ่าน `fetch()`
3. **Processing (Save Scripts/API):** รับค่าจาก Global Variables (`$_POST`, `$_GET`)
4. **Core Layer (App/Core/):** จัดการการเชื่อมต่อฐานข้อมูลผ่าน `Database.php` (Singleton)
5. **Database:** เก็บข้อมูลถาวรใน MariaDB

### Data Handling
- ใช้ **Prepared Statements** ทั้งหมดเพื่อป้องกัน SQL Injection
- ใช้ **Database Transactions** สำหรับ Operation ที่ซับซ้อน (เช่น การบันทึกเคสพร้อมรูปภาพ)
- จัดการไฟล์รูปภาพในโฟลเดอร์ `public/uploads/cases/` โดยเก็บชื่อไฟล์ลงในตาราง `images`

---

## 4. Core Modules & Endpoints

### Authentication
- `login_process.php`: ตรวจสอบ Username/Password จากตาราง `users` (Plain Text) และเก็บข้อมูลลงใน `$_SESSION['user']`
- **Auth Guard:** หน้าที่เป็นส่วนตัว (Admin/User) จะเช็ค `isset($_SESSION['user'])` ที่ส่วนหัวของไฟล์

### Case Management
- `save_case.php`: รับข้อมูลจาก `add_case.php` เพื่อ Insert ลง `add_caselog` และ Update ข้อมูลนักเรียนใน `student_data`
- `save_forward.php`: บันทึกข้อมูลการส่งต่อไปยังหน่วยงานภายนอก
- `save_closure.php`: บันทึกข้อมูลการปิดเคส (ยุติการช่วยเหลือ)

### Internal APIs (`public/api/`)
- `member_api.php`: จัดการข้อมูลผู้ใช้งาน (CRUD)
- `add_student.php`: เพิ่มข้อมูลนักเรียนใหม่ผ่าน AJAX
- `main.php?action=fetch_data`: ดึงข้อมูลนักเรียนพร้อม Pagination สำหรับหน้า Dashboard

---

## 5. Rules & Conventions
1. **Read-Only Core:** ห้ามแก้ไขไฟล์ใน `app/core/Database.php` หากไม่มีความจำเป็นเร่งด่วน
2. **Database Integrity:** ทุกตารางต้องมี Foreign Key กำกับ และใช้ Transaction เมื่อมีการบันทึกหลายขั้นตอน
3. **Consistency:** การตั้งชื่อตัวแปรใน PHP ให้ใช้ `snake_case` ตามโครงสร้างฟิลด์ในฐานข้อมูล
4. **Security:**
   - ห้ามใช้คำสั่ง SQL ตรงๆ โดยไม่ผ่าน Prepared Statements
   - ห้ามเก็บข้อมูลที่ Sensitive ลงในไฟล์ JavaScript
5. **UI/UX:** ใช้ `sweetalert_utils.js` สำหรับการแจ้งเตือน Error/Success ทุกครั้งที่มีการเปลี่ยนแปลงข้อมูลในฐานข้อมูล
