<?php
session_start();
require_once dirname(dirname(__DIR__)) . '/app/core/Database.php';

// ตรวจสอบสิทธิ์
if (!isset($_SESSION['user'])) {
    header("Location: ../login.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $db = Database::connect();
        $id = $_POST['id'] ?? null;
        $pid = $_POST['pid'] ?? '';

        if (!$id) {
            throw new Exception("ไม่พบรหัสรายงานที่ต้องการลบ");
        }

        // 1. ดึงข้อมูลรูปภาพเพื่อลบไฟล์ออกจาก Server ก่อน
        $stmtImg = $db->prepare("SELECT file_name FROM images WHERE case_id = :id");
        $stmtImg->execute([':id' => $id]);
        $images = $stmtImg->fetchAll(PDO::FETCH_ASSOC);

        $upload_dir = dirname(__DIR__) . '/uploads/cases/';
        foreach ($images as $img) {
            $filePath = $upload_dir . $img['file_name'];
            if (file_exists($filePath)) {
                unlink($filePath); // ลบไฟล์จริง
            }
        }

        // 2. เริ่ม Transaction เพื่อลบข้อมูลในฐานข้อมูล
        $db->beginTransaction();

        // ลบข้อมูลในตาราง images
        $stmtDelImg = $db->prepare("DELETE FROM images WHERE case_id = :id");
        $stmtDelImg->execute([':id' => $id]);

        // ลบข้อมูลในตาราง add_caselog
        $stmtDelCase = $db->prepare("DELETE FROM add_caselog WHERE id = :id");
        $stmtDelCase->execute([':id' => $id]);

        $db->commit();

        echo "<script>
                alert('ลบรายงานเรียบร้อยแล้ว');
                window.location.href = '../add_case_history.php?pid=" . htmlspecialchars($pid) . "';
              </script>";

    } catch (Exception $e) {
        if ($db->inTransaction()) {
            $db->rollBack();
        }
        echo "<script>
                alert('เกิดข้อผิดพลาด: " . addslashes($e->getMessage()) . "');
                window.history.back();
              </script>";
    }
} else {
    header("Location: ../main.php");
    exit;
}