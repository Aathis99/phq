<?php

/**
 * หน้าแสดงประวัติรายงานการช่วยเหลือรายกรณี
 * File: public/add_case_history.php
 */
session_start();
require_once dirname(__DIR__) . '/app/core/Database.php';

// 1. ตรวจสอบสิทธิ์การเข้าใช้งาน
if (!isset($_SESSION['user'])) {
    header("Location: login.php");
    exit;
}

// 2. ตรวจสอบค่า PID
if (!isset($_GET['pid']) || empty($_GET['pid'])) {
    echo "<div class='alert alert-danger m-4'>ไม่พบรหัสบัตรประชาชน (PID)</div>";
    exit;
}

$pid = $_GET['pid'];
$db = Database::connect();

try {
    // 3. ดึงข้อมูลนักเรียน
    $sql_student = "SELECT s.*, p.prefix_name, sc.school_name, sx.sex_name 
                    FROM student_data s
                    LEFT JOIN prefix p ON s.prefix_id = p.prefix_id
                    LEFT JOIN school sc ON s.school_id = sc.school_id
                    LEFT JOIN sex sx ON s.sex = sx.sex_id
                    WHERE s.pid = :pid";
    $stmtStudent = $db->prepare($sql_student);
    $stmtStudent->execute([':pid' => $pid]);
    $student = $stmtStudent->fetch(PDO::FETCH_ASSOC);

    if (!$student) {
        echo "<div class='alert alert-warning m-4'>ไม่พบข้อมูลนักเรียนในระบบ</div>";
        exit;
    }

    // 4. ดึงประวัติการช่วยเหลือ (add_caselog)
    $stmtHistory = $db->prepare("SELECT * FROM add_caselog WHERE pid = :pid ORDER BY report_date DESC, created_at DESC");
    $stmtHistory->execute([':pid' => $pid]);
    $caseLogs = $stmtHistory->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    echo "<div class='alert alert-danger'>เกิดข้อผิดพลาด: " . htmlspecialchars($e->getMessage()) . "</div>";
    exit;
}
?>

<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ประวัติการช่วยเหลือรายกรณี - PHQ System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Sarabun:wght@300;400;500;600&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Sarabun', sans-serif;
        }
    </style>
</head>

<body class="bg-light">
    <?php require_once 'navbar.php'; ?>

    <div class="container mt-5">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2>📜 ประวัติการช่วยเหลือรายกรณี (Case History)</h2>
            <a href="phq_history.php?pid=<?= htmlspecialchars($pid) ?>" class="btn btn-danger">← กลับหน้าประวัติการประเมิน</a>
        </div>

        <!-- ข้อมูลส่วนตัว -->
        <div class="card shadow-sm mb-4">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0">ข้อมูลนักเรียน</h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-4">
                        <strong>ชื่อ-นามสกุล:</strong>
                        <?= htmlspecialchars(($student['prefix_name'] ?? '') . ' ' . ($student['fname'] ?? '-') . ' ' . ($student['lname'] ?? '-')) ?>
                    </div>
                    <div class="col-md-4">
                        <strong>เลขบัตรประชาชน:</strong> <?= htmlspecialchars($student['pid']) ?>
                    </div>
                    <div class="col-md-4">
                        <strong>โรงเรียน:</strong> <?= htmlspecialchars($student['school_name'] ?? '-') ?>
                    </div>
                    <div class="col-md-4 mt-2">
                        <strong>ระดับชั้น:</strong> <?= htmlspecialchars($student['class'] ?? '-') ?>/<?= htmlspecialchars($student['room'] ?? '-') ?>
                    </div>
                    <div class="col-md-4 mt-2">
                        <strong>เพศ:</strong> <?= htmlspecialchars($student['sex_name'] ?? '-') ?>
                    </div>
                    <div class="col-md-4 mt-2">
                        <strong>อายุ:</strong> <?= htmlspecialchars($student['age'] ?? '-') ?> ปี
                    </div>
                </div>
            </div>
        </div>

        <!-- ตารางประวัติ -->
        <div class="card shadow-sm">
            <div class="card-header bg-white d-flex justify-content-between align-items-center">
                <h5 class="mb-0">รายการบันทึกย้อนหลัง (<?= count($caseLogs) ?> ครั้ง)</h5>
                <div class="d-flex gap-2">
                    <a href="add_case.php?pid=<?= htmlspecialchars($pid) ?>" class="btn btn-success btn-sm">
                        ➕ เพิ่มรายงานใหม่
                    </a>
                    <a href="closure_report.php?pid=<?= htmlspecialchars($pid) ?>" class="btn btn-danger btn-sm">
                        ➕ รายงานการยุติให้การดูแล
                    </a>
                </div>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover table-striped mb-0 align-middle">
                        <thead class="table-light">
                            <tr>
                                <th style="width: 5%">#</th>
                                <th style="width: 15%">วันที่รายงาน</th>
                                <th style="width: 15%">ประเภทกรณี</th>
                                <th style="width: 35%">อาการนำ (Symptoms)</th>
                                <th style="width: 20%">ผู้บันทึก</th>
                                <th style="width: 10%" class="text-center">จัดการ</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (count($caseLogs) > 0): ?>
                                <?php foreach ($caseLogs as $index => $log): ?>
                                    <tr>
                                        <td><?= count($caseLogs) - $index ?></td>
                                        <td><?= date('d/m/Y', strtotime($log['report_date'])) ?></td>
                                        <td>
                                            <span class="badge rounded-pill bg-info text-dark">
                                                <?= htmlspecialchars($log['case_type']) ?>
                                            </span>
                                        </td>
                                        <td><small><?= htmlspecialchars(mb_strimwidth($log['presenting_symptoms'], 0, 100, '...')) ?></small></td>
                                        <td><?= htmlspecialchars($log['recorder']) ?></td>
                                        <td class="text-center">
                                            <button type="button" class="btn btn-sm btn-primary text-nowrap" data-bs-toggle="modal" data-bs-target="#viewCaseModal<?= $log['id'] ?>">
                                                📄 ดูข้อมูล
                                            </button>

                                            <div class="modal fade text-start" id="viewCaseModal<?= $log['id'] ?>" tabindex="-1" aria-hidden="true">
                                                <div class="modal-dialog modal-lg">
                                                    <div class="modal-content">
                                                        <div class="modal-header bg-primary text-white">
                                                            <h5 class="modal-title">รายละเอียดเคส วันที่ <?= date('d/m/Y', strtotime($log['report_date'])) ?></h5>
                                                            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                                                        </div>
                                                        <div class="modal-body">
                                                            <p><strong>ประเภทกรณี:</strong> <?= htmlspecialchars($log['case_type']) ?></p>
                                                            <p><strong>อาการนำ:</strong><br> <?= nl2br(htmlspecialchars($log['presenting_symptoms'])) ?></p>
                                                            <hr>
                                                            <p><strong>ประวัติส่วนตัว:</strong> <?= htmlspecialchars($log['history_personal'] ?? '-') ?></p>
                                                            <p><strong>ข้อมูลจากครอบครัว:</strong> <?= htmlspecialchars($log['history_family'] ?? '-') ?></p>
                                                            <p><strong>ข้อมูลจากโรงเรียน:</strong> <?= htmlspecialchars($log['history_school'] ?? '-') ?></p>
                                                            <hr>
                                                            <p><strong>แนวทางช่วยเหลือ:</strong></p>
                                                            <ul>
                                                                <li>โรงเรียน: <?= htmlspecialchars($log['assist_school'] ?? '-') ?></li>
                                                                <li>ผู้ปกครอง: <?= htmlspecialchars($log['assist_parent'] ?? '-') ?></li>
                                                                <li>โรงพยาบาล: <?= htmlspecialchars($log['assist_hospital'] ?? '-') ?></li>
                                                            </ul>
                                                            <p class="text-muted small text-end">บันทึกเมื่อ: <?= $log['created_at'] ?></p>
                                                        </div>
                                                        <div class="modal-footer">
                                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">ปิด</button>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="6" class="text-center py-4 text-muted">
                                        ยังไม่มีประวัติการช่วยเหลือรายกรณี
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>