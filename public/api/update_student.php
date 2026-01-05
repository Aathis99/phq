<?php
session_start();
require_once dirname(__DIR__, 2) . '/app/core/Database.php';

header('Content-Type: application/json');

// ตรวจสอบสิทธิ์
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
    
    // รับค่าจากฟอร์ม
    $old_pid = $_POST['old_pid'] ?? ''; // PID เดิม (ใช้สำหรับ WHERE)
    $pid = $_POST['pid'] ?? '';         // PID ใหม่ (เผื่อมีการแก้ไข)
    $prefix_id = $_POST['prefix_id'] ?? null;
    $fname = $_POST['fname'] ?? '';
    $lname = $_POST['lname'] ?? '';
    $sex = $_POST['sex'] ?? null;
    $age = $_POST['age'] ?? null;
    $school_id = $_POST['school_id'] ?? null;
    $class = $_POST['class'] ?? '';
    $room = $_POST['room'] ?? '';
    $tel = $_POST['tel'] ?? '';

    if (empty($old_pid) || empty($pid) || empty($fname) || empty($lname) || empty($tel) || empty($school_id)) {
        throw new Exception("กรุณากรอกข้อมูลที่จำเป็นให้ครบถ้วน (เลขบัตรประชาชน, ชื่อ, นามสกุล, เบอร์โทร, โรงเรียน)");
    }

    $db->beginTransaction();

    // กรณีมีการเปลี่ยนเลขบัตรประชาชน ต้องเช็คว่าซ้ำไหม
    if ($old_pid !== $pid) {
        $stmt = $db->prepare("SELECT COUNT(*) FROM student_data WHERE pid = :pid");
        $stmt->execute([':pid' => $pid]);
        if ($stmt->fetchColumn() > 0) {
            throw new Exception("เลขบัตรประชาชนใหม่ ($pid) มีอยู่ในระบบแล้ว");
        }
    }

    // อัปเดตข้อมูล student_data
    $sql = "UPDATE student_data SET 
            pid = :pid,
            prefix_id = :prefix_id,
            fname = :fname,
            lname = :lname,
            sex = :sex,
            age = :age,
            school_id = :school_id,
            class = :class,
            room = :room,
            tel = :tel
            WHERE pid = :old_pid";

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
        ':tel' => $tel,
        ':old_pid' => $old_pid
    ]);

    // อัปเดตตาราง assessment ด้วย (เนื่องจากไม่มี FK Constraint ใน Schema ที่ให้มา)
    if ($old_pid !== $pid) {
        $sql_assess = "UPDATE assessment SET pid = :pid WHERE pid = :old_pid";
        $stmt_assess = $db->prepare($sql_assess);
        $stmt_assess->execute([':pid' => $pid, ':old_pid' => $old_pid]);
    }

    $db->commit();
    echo json_encode(['status' => 'success', 'message' => 'บันทึกข้อมูลเรียบร้อยแล้ว']);

} catch (Exception $e) {
    if (isset($db) && $db->inTransaction()) {
        $db->rollBack();
    }
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}