<?php
session_start();
require_once dirname(__DIR__, 2) . '/app/core/Database.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user'])) {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request method']);
    exit;
}

try {
    $db = Database::connect();
    
    $pid = $_POST['pid'] ?? '';
    $prefix_id = $_POST['prefix_id'] ?? null;
    $fname = $_POST['fname'] ?? '';
    $lname = $_POST['lname'] ?? '';
    $sex = $_POST['sex'] ?? null;
    $age = $_POST['age'] ?? null;
    $school_id = $_POST['school_id'] ?? null;
    $class = $_POST['class'] ?? '';
    $room = $_POST['room'] ?? '';
    $tel = $_POST['tel'] ?? '';

    if (empty($pid) || empty($fname) || empty($lname) || empty($tel) || empty($school_id)) {
        throw new Exception("กรุณากรอกข้อมูลที่จำเป็นให้ครบถ้วน (เลขบัตรประชาชน, ชื่อ, นามสกุล, เบอร์โทร, โรงเรียน)");
    }

    // ตรวจสอบว่ามีเลขบัตรประชาชนนี้อยู่แล้วหรือไม่
    $stmt = $db->prepare("SELECT COUNT(*) FROM student_data WHERE pid = :pid");
    $stmt->execute([':pid' => $pid]);
    if ($stmt->fetchColumn() > 0) {
        throw new Exception("เลขบัตรประชาชน ($pid) มีอยู่ในระบบแล้ว");
    }

    $sql = "INSERT INTO student_data (pid, prefix_id, fname, lname, sex, age, school_id, class, room, tel, date_time) 
            VALUES (:pid, :prefix_id, :fname, :lname, :sex, :age, :school_id, :class, :room, :tel, NOW())";

    $stmt = $db->prepare($sql);
    $stmt->execute([
        ':pid' => $pid,
        ':prefix_id' => $prefix_id ?: null,
        ':fname' => $fname,
        ':lname' => $lname,
        ':sex' => $sex ?: null,
        ':age' => $age ?: null,
        ':school_id' => $school_id ?: null,
        ':class' => $class,
        ':room' => $room,
        ':tel' => $tel
    ]);

    echo json_encode(['status' => 'success', 'message' => 'เพิ่มข้อมูลนักเรียนเรียบร้อยแล้ว']);

} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}