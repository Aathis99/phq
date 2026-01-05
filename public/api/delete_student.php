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

    if (empty($pid)) {
        throw new Exception("ไม่พบรหัสบัตรประชาชนที่ต้องการลบ");
    }

    $db->beginTransaction();

    // ลบข้อมูลจากตาราง assessment (เนื่องจากไม่มี FK Constraint ใน Schema ที่ให้มา)
    $stmt = $db->prepare("DELETE FROM assessment WHERE pid = :pid");
    $stmt->execute([':pid' => $pid]);

    // ลบข้อมูลจากตาราง student_data (จะลบ add_caselog อัตโนมัติถ้ามี FK Cascade)
    $stmt = $db->prepare("DELETE FROM student_data WHERE pid = :pid");
    $stmt->execute([':pid' => $pid]);

    $db->commit();
    echo json_encode(['status' => 'success', 'message' => 'ลบข้อมูลเรียบร้อยแล้ว']);

} catch (Exception $e) {
    if (isset($db) && $db->inTransaction()) {
        $db->rollBack();
    }
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}