<?php
session_start();

// [DEBUG MODE] เปิดแสดง Error ทั้งหมดเพื่อหาสาเหตุ (ถ้าแก้เสร็จแล้วควรลบออกหรือคอมเมนต์ไว้)
// ini_set('display_errors', 1);
// ini_set('display_startup_errors', 1);
// error_reporting(E_ALL);

// [DEBUG DATA] ถ้ากดส่งแล้วเงียบ ลองปลดคอมเมนต์ 3 บรรทัดล่างนี้ เพื่อเช็คว่าค่าถูกส่งมาถึงหน้านี้หรือไม่
// echo "<pre><h3>POST Data Check:</h3>"; print_r($_POST); echo "</pre>"; exit;

require_once dirname(__DIR__) . '/app/core/Database.php';

// ตรวจสอบว่าเป็น POST Request หรือไม่
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: index.php");
    exit;
}

try {
    $db = Database::connect();
    
    // --- 1. รับค่าจากฟอร์ม ---
    $pid = $_POST['pid'] ?? '';
    
    if (empty($pid)) {
        throw new Exception("ไม่พบรหัสบัตรประชาชน (PID)");
    }

    // ข้อมูลสำหรับ update student_data
    $prefix_id = !empty($_POST['prefix_id']) ? $_POST['prefix_id'] : null;
    $fname = $_POST['firstname'] ?? '';
    $lname = $_POST['lastname'] ?? '';
    $sex = !empty($_POST['sex_id']) ? $_POST['sex_id'] : null;
    $age = !empty($_POST['age']) ? $_POST['age'] : null;
    $class = $_POST['education_level'] ?? ''; // ในฟอร์มใช้ชื่อ education_level
    $school_id = !empty($_POST['school_id']) ? $_POST['school_id'] : null;
    
    // ข้อมูลสำหรับ closure_report
    $case_type = $_POST['case_type'] ?? '';
    $case_count = !empty($_POST['case_count']) ? $_POST['case_count'] : null;
    $report_date = !empty($_POST['report_date']) ? $_POST['report_date'] : date('Y-m-d');
    $detail_family = $_POST['detail_family'] ?? '';
    $detail_school = $_POST['detail_school'] ?? '';
    $detail_hospital = $_POST['detail_hospital'] ?? '';
    $suggestion = $_POST['suggestion'] ?? '';
    
    // ผู้บันทึก (ใช้ Username จาก Session)
    $recorder = $_SESSION['user']['username'] ?? null;

    // เริ่ม Transaction
    $db->beginTransaction();

    // --- 2. Update ข้อมูลนักเรียน (student_data) ---
    // อัปเดตเฉพาะข้อมูลที่มีในฟอร์ม (ไม่ยุ่งกับ room หรือ tel ถ้าฟอร์มไม่มี)
    $stmt_check = $db->prepare("SELECT pid FROM student_data WHERE pid = :pid");
    $stmt_check->execute([':pid' => $pid]);
    
    if ($stmt_check->rowCount() > 0) {
        $sql_student = "UPDATE student_data SET 
                        prefix_id = :prefix_id,
                        fname = :fname,
                        lname = :lname,
                        sex = :sex,
                        age = :age,
                        class = :class,
                        school_id = :school_id
                        WHERE pid = :pid";
        $stmt_std = $db->prepare($sql_student);
        $stmt_std->execute([
            ':prefix_id' => $prefix_id,
            ':fname' => $fname,
            ':lname' => $lname,
            ':sex' => $sex,
            ':age' => $age,
            ':class' => $class,
            ':school_id' => $school_id,
            ':pid' => $pid
        ]);
    }

    // --- 3. Insert รายงานการยุติ (closure_report) ---
    $sql_closure = "INSERT INTO closure_report (
                        pid, case_type, case_count, report_date,
                        detail_family, detail_school, detail_hospital,
                        suggestion, recorder, created_at, updated_at
                    ) VALUES (
                        :pid, :case_type, :case_count, :report_date,
                        :detail_family, :detail_school, :detail_hospital,
                        :suggestion, :recorder, NOW(), NOW()
                    )";
    
    $stmt_closure = $db->prepare($sql_closure);
    $stmt_closure->execute([
        ':pid' => $pid,
        ':case_type' => $case_type,
        ':case_count' => $case_count,
        ':report_date' => $report_date,
        ':detail_family' => $detail_family,
        ':detail_school' => $detail_school,
        ':detail_hospital' => $detail_hospital,
        ':suggestion' => $suggestion,
        ':recorder' => $recorder
    ]);

    $db->commit();

    // แจ้งเตือนและกลับไปหน้าประวัติ
    echo "<script>
            alert('บันทึกรายงานการยุติเรียบร้อยแล้ว');
            window.location.href = 'add_case_history.php?pid=" . htmlspecialchars($pid) . "';
          </script>";

} catch (Exception $e) {
    if (isset($db) && $db->inTransaction()) {
        $db->rollBack();
    }
    echo "<script>
            // ใช้ json_encode เพื่อป้องกัน JS Error กรณีข้อความมีอักขระพิเศษหรือการขึ้นบรรทัดใหม่
            console.error('PHP Error:', " . json_encode($e->getMessage()) . ");
            alert('เกิดข้อผิดพลาด: ' + " . json_encode($e->getMessage()) . ");
            window.history.back();
          </script>";
}