<?php
return [
    'host' => getenv('DB_HOST') ?: 'db', // ใน Docker ใช้ชื่อ service 'db'
    'dbname' => getenv('DB_NAME') ?: 'sesa_db',
    'user' => getenv('DB_USER') ?: 'sesalpglpn_supervision',
    'pass' => getenv('DB_PASS') ?: 'your_password',
    'charset' => 'utf8mb4'
];

// ใช้กับการพัฒนาในเครื่อง localhost หรือเซิร์ฟเวอร์จริง และมหาลัยที่ไม่ได้ใช้ Docker
// return [
//     'host' => 'localhost',
//     'dbname' => 'std660104db',
//     'user' => 'std660104',
//     'pass' => 'pro660104',
//     'charset' => 'utf8mb4'
// ];
