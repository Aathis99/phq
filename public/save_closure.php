<?php
session_start();
require_once dirname(__DIR__) . '/app/core/Database.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    try {
        $db = Database::connect();

        // รับค่าจากฟอร์ม
        $pid = $_POST['pid'];
        $case_type = $_POST['case_type'];
        $case_count = $_POST['case_count'];
        $report_date = $_POST['report_date'];
        $detail_family = $_POST['detail_family'];
        $detail_school = $_POST['detail_school'];
        $detail_hospital = $_POST['detail_hospital'];
        $suggestion = $_POST['suggestion'];
        
        // ** ส่วนที่เพิ่ม: รับค่าปีการศึกษาและเทอม **
        $academic_year = !empty($_POST['academic_year']) ? $_POST['academic_year'] : null;
        $semester = !empty($_POST['semester']) ? $_POST['semester'] : null;

        // ใช้ username จาก Session เพื่อความถูกต้อง (FK)
        $recorder = $_SESSION['user']['username'] ?? null;

        $sql = "INSERT INTO closure_report (
                    pid, case_type, case_count, report_date, 
                    detail_family, detail_school, detail_hospital, 
                    suggestion, recorder, academic_year, semester
                ) VALUES (
                    :pid, :case_type, :case_count, :report_date, 
                    :detail_family, :detail_school, :detail_hospital, 
                    :suggestion, :recorder, :academic_year, :semester
                )";

        $stmt = $db->prepare($sql);
        $stmt->execute([
            ':pid' => $pid,
            ':case_type' => $case_type,
            ':case_count' => $case_count,
            ':report_date' => $report_date,
            ':detail_family' => $detail_family,
            ':detail_school' => $detail_school,
            ':detail_hospital' => $detail_hospital,
            ':suggestion' => $suggestion,
            ':recorder' => $recorder,
            ':academic_year' => $academic_year,
            ':semester' => $semester
        ]);

        // Redirect กลับไปหน้าประวัติ
        header("Location: add_case_history.php?pid=" . $pid);
        exit;

    } catch (PDOException $e) {
        echo "Error: " . $e->getMessage();
    }
}
?>