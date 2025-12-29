<?php
session_start();
require_once __DIR__ . '/../app/core/Database.php';

$username = $_POST['username'] ?? '';
$passwordInput = $_POST['password'] ?? ''; // เปลี่ยนชื่อตัวแปรกันสับสน

if ($username === '' || $passwordInput === '') {
    header("Location: login.php?error=1");
    exit;
}

$db = Database::connect();

/*
  ✅ แก้ไข: เลือกจากตาราง users (เดิมโค้ดเขียน user)
*/
$sql = "SELECT * FROM users WHERE username = :username LIMIT 1";
$stmt = $db->prepare($sql);
$stmt->execute(['username' => $username]);
$user = $stmt->fetch();

if (!$user) {
    // ไม่พบ Username
    header("Location: login.php?error=1");
    exit;
}

/* 🔴 แก้ไข: เปลี่ยนการเช็คจาก passname เป็น password 
  (ยังคงใช้การเช็คแบบ Plain Text ตามระบบเดิม)
*/
if ($user['password'] === $passwordInput) {

    $_SESSION['user'] = [
        'username' => $user['username'],
        // เช็คว่ามี user_id หรือไม่ ถ้าไม่มีให้ใช้ username หรือค่าอื่นที่เป็น unique แทน
        'user_id'  => $user['user_id'] ?? $user['username']
    ];

    header("Location: main.php");
    exit;
}

/* 💡 คำแนะนำเพิ่มเติมจาก Dev Buddy:
  หากในอนาคตต้องการความปลอดภัยมากขึ้น ควรเปลี่ยนไปใช้ password_hash() และ password_verify() ครับ
  
  if (password_verify($passwordInput, $user['password'])) { ... }
*/

// รหัสผ่านไม่ถูกต้อง
header("Location: login.php?error=1");
exit;
