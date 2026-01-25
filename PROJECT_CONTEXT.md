Project Context: PHQ-9 Student Support System
1. ข้อมูลภาพรวม (Project Overview)
ชื่อโปรเจค: ระบบสนับสนุนดูแลช่วยเหลือนักเรียน (PHQ-9 Student Support System)

วัตถุประสงค์: จัดการข้อมูลพื้นฐานนักเรียน ประเมินสุขภาพจิต (PHQ-9), บันทึกการติดตามเคส (Case Log), และจัดทำรายงานผลการดูแลช่วยเหลือนักเรียน

กลุ่มเป้าหมาย: ครูแนะแนว และบุคลากรทางการศึกษาในเขตพื้นที่การศึกษา

2. สภาพแวดล้อมทางเทคนิค (Target System Information)
ข้อมูลนี้อ้างอิงจากเซิร์ฟเวอร์หลักที่ต้องนำงานไปขึ้น (Production) เพื่อให้ AI เขียนโค้ดที่รองรับเวอร์ชันปัจจุบัน:

OS: Ubuntu 24.04

Web Server: Nginx 1.28.0

Language: PHP 8.4.14 (พร้อม Extension: mysqli, curl, mbstring, pdo_mysql)

Database: MariaDB 10.11.14

Database Management: phpMyAdmin 5.2.3

3. โครงสร้างโครงสร้างพื้นฐาน (Infrastructure & Docker)
โปรเจคนี้พัฒนาผ่าน Docker โดยมีการตั้งค่าดังนี้:

Services:

web: Nginx (Forward PHP requests to app)

app: PHP 8.4-FPM (Workdir: /var/www/html)

db: MariaDB 10.11

phpmyadmin: พอร์ต 8080 สำหรับจัดการฐานข้อมูล

Network Alias: PHP เชื่อมต่อฐานข้อมูลผ่าน Hostname db

4. โครงสร้างฐานข้อมูล (Database Schema)
อ้างอิงจากไฟล์ schema.sql:

Database Name: sesa_db

Table สำคัญ:

student_data: ข้อมูลพื้นฐานนักเรียน (PID, ชื่อ, ชั้นเรียน)

add_caselog: บันทึกรายละเอียดการติดตามเคสรายบุคคล

users: ข้อมูลผู้ใช้งานระบบและระดับสิทธิ์

closure_report: รายงานการปิดเคส

Character Set: utf8mb4_unicode_ci (รองรับภาษาไทย)

5. โครงสร้างไฟล์และโฟลเดอร์ (Project Structure)
/app: โค้ดหลักของระบบ (Logic, Config, Core)

/public: ส่วนหน้าเว็บที่เข้าถึงได้จากภายนอก (Web Root)

/nginx: ไฟล์ตั้งค่า Nginx Server Block

/db: ไฟล์ SQL สำหรับ Initial ฐานข้อมูล

6. ข้อกำหนดการพัฒนา (Development Standards)
Database Access: ใช้ PDO ในการเชื่อมต่อ (ผ่านไฟล์ app/core/Database.php)

Error Handling: ในช่วงพัฒนาต้องแสดง Error ทั้งหมดเพื่อตรวจสอบ Compatibility กับ PHP 8.4

Naming Convention: ชื่อไฟล์และ Class ควรเป็น Case-sensitive ตามมาตรฐาน Linux (Docker)