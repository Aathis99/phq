<?php
session_start();
// ปรับ Path ให้ถอยกลับ 2 ชั้น (api -> public -> root) เพื่อเข้าถึง app/core
require_once dirname(dirname(__DIR__)) . '/app/core/Database.php';

// ตรวจสอบสิทธิ์
if (!isset($_SESSION['user'])) {
    header("Location: ../login.php"); // ถอยกลับไปหน้า login
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $db = Database::connect();
        
        // รับค่า ID และ PID
        $id = $_POST['id'] ?? null;
        $pid = $_POST['pid'] ?? '';

        if (!$id || !$pid) {
            throw new Exception("ข้อมูลไม่ครบถ้วน");
        }

        // เตรียม SQL Update
        $sql = "UPDATE add_caselog SET 
                report_date = :report_date,
                case_type = :case_type,
                presenting_symptoms = :presenting_symptoms,
                history_personal = :history_personal,
                history_family = :history_family,
                history_school = :history_school,
                history_hospital = :history_hospital,
                assist_school = :assist_school,
                assist_parent = :assist_parent,
                assist_hospital = :assist_hospital,
                assist_other = :assist_other,
                suggestions = :suggestions,
                updated_at = NOW()
                WHERE id = :id";

        $stmt = $db->prepare($sql);
        $stmt->execute([
            ':report_date' => $_POST['report_date'],
            ':case_type' => $_POST['case_type'],
            ':presenting_symptoms' => $_POST['presenting_symptoms'],
            ':history_personal' => $_POST['history_personal'],
            ':history_family' => $_POST['history_family'],
            ':history_school' => $_POST['history_school'],
            ':history_hospital' => $_POST['history_hospital'],
            ':assist_school' => $_POST['assist_school'],
            ':assist_parent' => $_POST['assist_parent'],
            ':assist_hospital' => $_POST['assist_hospital'],
            ':assist_other' => $_POST['assist_other'],
            ':suggestions' => $_POST['suggestions'],
            ':id' => $id
        ]);

        // --- จัดการรูปภาพ ---
        $upload_dir = dirname(__DIR__) . '/uploads/cases/';

        // 1. ลบรูปภาพที่ถูกเลือก
        if (!empty($_POST['delete_images'])) {
            $deleteIds = $_POST['delete_images'];
            // ดึงชื่อไฟล์เพื่อลบออกจาก Server
            $placeholders = implode(',', array_fill(0, count($deleteIds), '?'));
            $stmtGetFiles = $db->prepare("SELECT id, file_name FROM images WHERE id IN ($placeholders)");
            $stmtGetFiles->execute($deleteIds);
            $filesToDelete = $stmtGetFiles->fetchAll(PDO::FETCH_ASSOC);

            foreach ($filesToDelete as $file) {
                $filePath = $upload_dir . $file['file_name'];
                if (file_exists($filePath)) {
                    unlink($filePath);
                }
            }

            // ลบข้อมูลจากฐานข้อมูล
            $stmtDelete = $db->prepare("DELETE FROM images WHERE id IN ($placeholders)");
            $stmtDelete->execute($deleteIds);
        }

        // 2. อัปโหลดรูปภาพใหม่
        if (!empty($_FILES['new_images']['name'][0])) {
            if (!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);

            $total_files = count($_FILES['new_images']['name']);
            for ($i = 0; $i < $total_files; $i++) {
                if ($_FILES['new_images']['error'][$i] === UPLOAD_ERR_OK) {
                    $tmp_name = $_FILES['new_images']['tmp_name'][$i];
                    $ext = pathinfo($_FILES['new_images']['name'][$i], PATHINFO_EXTENSION);
                    $new_name = uniqid('case_' . $id . '_') . '.' . $ext;
                    
                    if (move_uploaded_file($tmp_name, $upload_dir . $new_name)) {
                        $stmt_img = $db->prepare("INSERT INTO images (case_id, file_name) VALUES (:case_id, :file_name)");
                        $stmt_img->execute([':case_id' => $id, ':file_name' => $new_name]);
                    }
                }
            }
        }

        echo "<script>
                alert('แก้ไขข้อมูลเรียบร้อยแล้ว');
                window.location.href = '../add_case_history.php?pid=" . htmlspecialchars($pid) . "';
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