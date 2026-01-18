# PHQ-9 Student Support System

## Project Overview
ระบบดูแลช่วยเหลือนักเรียนและประเมินภาวะซึมเศร้า (PHQ-9)

## Tech Stack
- Language: PHP (Vanilla / No Framework)
- Database: MySQL (ใช้ PDO ในการเชื่อมต่อ)
- Frontend: Bootstrap 5, JavaScript (Vanilla)
- Font: Sarabun

## Directory Structure
- /app/core/Database.php : ไฟล์เชื่อมต่อฐานข้อมูลหลัก
- /public/ : ไฟล์หน้าเว็บทั้งหมด
- /public/api/ : ไฟล์ PHP สำหรับจัดการ Logic หลังบ้าน (AJAX requests)
- /public/uploads/ : โฟลเดอร์เก็บรูปภาพ

## Key Features
1. ระบบ Login (Admin/User)
2. แบบประเมิน PHQ-9
3. ระบบบันทึกเคส (Case History) และการติดตามผล
4. รายงานการยุติการช่วยเหลือ (Closure Report)
