<?php
// /home/std660104/public_html/phq_web/public/save_case.php

// 1. เริ่ม Session
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// 2. เชื่อมต่อฐานข้อมูล
require_once dirname(__DIR__) . '/app/core/Database.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $db = Database::connect();

        // --- รับค่าจากฟอร์ม ---
        // ข้อมูลนักเรียน
        $pid = $_POST['id_card'] ?? '';
        $prefix_id = !empty($_POST['prefix_id']) ? $_POST['prefix_id'] : null;
        $fname = $_POST['firstname'] ?? '';
        $lname = $_POST['lastname'] ?? '';
        $sex = !empty($_POST['gender']) ? $_POST['gender'] : null;
        $age = !empty($_POST['age']) ? $_POST['age'] : null;
        $class = $_POST['edu_level'] ?? '';
        $room = $_POST['edu_room'] ?? '';
        $school_id = !empty($_POST['school']) ? $_POST['school'] : null;

        // ข้อมูล Case (add_caselog)
        $case_type = $_POST['case_type'] ?? '';
        // case_id (ครั้งที่) เป็นเพียงตัวเลขแสดงผล ไม่ได้บันทึกลง DB โดยตรง (DB ใช้ id เป็น PK)

        $report_date = !empty($_POST['report_date']) ? $_POST['report_date'] : date('Y-m-d');
        $presenting_symptoms = $_POST['presenting_symptoms'] ?? '';
        $history_personal = $_POST['history_personal'] ?? '';
        $history_family = $_POST['history_family'] ?? '';
        $history_school = $_POST['history_school'] ?? '';
        $personal_habits = $_POST['personal_habits'] ?? '';
        $history_hospital = $_POST['history_hospital'] ?? '';
        $consultation_details = $_POST['consultation_details'] ?? '';
        $event_details = $_POST['event_details'] ?? '';
        $assist_school = $_POST['assist_school'] ?? '';
        $assist_hospital = $_POST['assist_hospital'] ?? '';
        $assist_parent = $_POST['assist_parent'] ?? '';
        $assist_other = $_POST['assist_other'] ?? '';
        $suggestions = $_POST['suggestions'] ?? '';

        // ผู้บันทึก: ใช้ username จาก session ถ้ามี (เพื่อความถูกต้องของ FK) หรือถ้าไม่มีใช้ค่าที่ส่งมา
        $recorder = $_SESSION['user']['username'] ?? ($_POST['recorder'] ?? '');

        // ตรวจสอบข้อมูลจำเป็น
        if (empty($pid)) {
            throw new Exception("กรุณาระบุเลขบัตรประชาชน");
        }

        // เริ่ม Transaction
        $db->beginTransaction();

        // ---------------------------------------------------------
        // 3. จัดการข้อมูลนักเรียน (ตาราง student_data)
        // ---------------------------------------------------------
        // ตรวจสอบว่ามีนักเรียนคนนี้อยู่แล้วหรือไม่
        $stmt_check_std = $db->prepare("SELECT pid FROM student_data WHERE pid = :pid");
        $stmt_check_std->execute([':pid' => $pid]);

        if ($stmt_check_std->rowCount() > 0) {
            // มีอยู่แล้ว -> ทำการ Update ข้อมูลล่าสุด
            $sql_student = "UPDATE student_data SET 
                            school_id = :school_id, 
                            sex = :sex, 
                            prefix_id = :prefix_id, 
                            fname = :fname, 
                            lname = :lname, 
                            age = :age, 
                            class = :class, 
                            room = :room
                            WHERE pid = :pid";
        } else {
            // ยังไม่มี -> ทำการ Insert ใหม่
            $sql_student = "INSERT INTO student_data (school_id, pid, sex, prefix_id, fname, lname, age, class, room, date_time) 
                            VALUES (:school_id, :pid, :sex, :prefix_id, :fname, :lname, :age, :class, :room, NOW())";
        }

        $stmt_student = $db->prepare($sql_student);
        $stmt_student->execute([
            ':school_id' => $school_id,
            ':pid' => $pid,
            ':sex' => $sex,
            ':prefix_id' => $prefix_id,
            ':fname' => $fname,
            ':lname' => $lname,
            ':age' => $age,
            ':class' => $class,
            ':room' => $room
        ]);

        // ---------------------------------------------------------
        // 4. บันทึกข้อมูล Case (ตาราง add_caselog)
        // ---------------------------------------------------------

        // หา ID ถัดไป (เผื่อกรณีที่ตารางไม่ได้ตั้ง Auto Increment ไว้)
        $stmt_max_id = $db->query("SELECT MAX(id) as max_id FROM add_caselog");
        $res_max = $stmt_max_id->fetch(PDO::FETCH_ASSOC);
        $next_id = ($res_max['max_id'] ?? 0) + 1;

        $sql_case = "INSERT INTO add_caselog (
                        id, pid, case_type, report_date, 
                        presenting_symptoms, history_personal, history_family, history_school, 
                        personal_habits, history_hospital, consultation_details, event_details, 
                        assist_school, assist_hospital, assist_parent, assist_other, 
                        suggestions, recorder, created_at, updated_at
                    ) VALUES (
                        :id, :pid, :case_type, :report_date,
                        :presenting_symptoms, :history_personal, :history_family, :history_school,
                        :personal_habits, :history_hospital, :consultation_details, :event_details,
                        :assist_school, :assist_hospital, :assist_parent, :assist_other,
                        :suggestions, :recorder, NOW(), NOW()
                    )";

        $stmt_case = $db->prepare($sql_case);
        $stmt_case->execute([
            ':id' => $next_id,
            ':pid' => $pid,
            ':case_type' => $case_type,
            ':report_date' => $report_date,
            ':presenting_symptoms' => $presenting_symptoms,
            ':history_personal' => $history_personal,
            ':history_family' => $history_family,
            ':history_school' => $history_school,
            ':personal_habits' => $personal_habits,
            ':history_hospital' => $history_hospital,
            ':consultation_details' => $consultation_details,
            ':event_details' => $event_details,
            ':assist_school' => $assist_school,
            ':assist_hospital' => $assist_hospital,
            ':assist_parent' => $assist_parent,
            ':assist_other' => $assist_other,
            ':suggestions' => $suggestions,
            ':recorder' => $recorder
        ]);

        // ยืนยันการทำงาน (Commit)
        $db->commit();

        // แจ้งเตือนและกลับไปหน้าประวัติ
        echo "<script>
                alert('บันทึกข้อมูลเรียบร้อยแล้ว');
                window.location.href = 'phq_history.php?pid=" . htmlspecialchars($pid) . "';
              </script>";
    } catch (Exception $e) {
        // หากเกิดข้อผิดพลาด ให้ยกเลิกการทำงาน (Rollback)
        if (isset($db) && $db->inTransaction()) {
            $db->rollBack();
        }
        echo "<script>
                alert('เกิดข้อผิดพลาด: " . addslashes($e->getMessage()) . "');
                window.history.back();
              </script>";
    }
} else {
    // ถ้าเข้าหน้านี้โดยตรง ให้กลับไปหน้าฟอร์ม
    header("Location: add_case.php");
    exit;
}
