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
            throw new Exception("ไม่พบรหัสการประเมินที่ต้องการลบ");
        }

        // ลบข้อมูลในตาราง assessment
        $stmt = $db->prepare("DELETE FROM assessment WHERE id = :id");
        $stmt->execute([':id' => $id]);

        echo "<script>
                alert('ลบข้อมูลการประเมินเรียบร้อยแล้ว');
                window.location.href = '../phq_history.php?pid=" . htmlspecialchars($pid) . "';
              </script>";

    } catch (Exception $e) {
        echo "<script>
                alert('เกิดข้อผิดพลาด: " . addslashes($e->getMessage()) . "');
                window.history.back();
              </script>";
    }
} else {
    header("Location: ../main.php");
    exit;
}