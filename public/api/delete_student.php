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

    // 1. จัดการลบไฟล์รูปภาพออกจาก Server (Database ลบ Row ให้ แต่ไม่ลบไฟล์ เราต้องลบเอง)
    // ดึงรายการเคสทั้งหมดของนักเรียน
    $stmtCases = $db->prepare("SELECT id FROM add_caselog WHERE pid = :pid");
    $stmtCases->execute([':pid' => $pid]);
    $cases = $stmtCases->fetchAll(PDO::FETCH_COLUMN);

    if (!empty($cases)) {
        // ดึงชื่อไฟล์รูปภาพทั้งหมดที่เกี่ยวข้อง
        $placeholders = implode(',', array_fill(0, count($cases), '?'));
        $stmtImages = $db->prepare("SELECT file_name FROM images WHERE case_id IN ($placeholders)");
        $stmtImages->execute($cases);
        $images = $stmtImages->fetchAll(PDO::FETCH_COLUMN);

        // ลบไฟล์ออกจากโฟลเดอร์ uploads
        $upload_dir = dirname(__DIR__) . '/uploads/cases/';
        foreach ($images as $file_name) {
            $filePath = $upload_dir . $file_name;
            if (file_exists($filePath)) {
                @unlink($filePath);
            }
        }
    }

    // 2. สั่งลบแค่นักเรียน (Database จะ Cascade ลบ assessment, add_caselog, closure_report, images ให้เอง)
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