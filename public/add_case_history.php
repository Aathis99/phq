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

// 2. ตรวจสอบค่า PID (ปรับปรุงให้ซ่อน URL parameter)
if (isset($_GET['pid']) && !empty($_GET['pid'])) {
    $_SESSION['current_case_pid'] = $_GET['pid'];
    header("Location: add_case_history.php");
    exit;
}

if (isset($_SESSION['current_case_pid']) && !empty($_SESSION['current_case_pid'])) {
    $pid = $_SESSION['current_case_pid'];
} else {
    echo "<div class='alert alert-danger m-4'>ไม่พบรหัสบัตรประชาชน (PID)</div>";
    exit;
}

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

    // 4. ดึงประวัติการช่วยเหลือ (add_caselog) และ รายงานการยุติ (closure_report)
    // 4.1 add_caselog
    $sql_caselog = "SELECT ac.*, 'case' as record_type, u.fname AS u_fname, u.lname AS u_lname, p.prefix_name AS u_prefix 
                    FROM add_caselog ac
                    LEFT JOIN users u ON ac.recorder = u.username
                    LEFT JOIN prefix p ON u.prefix_id = p.prefix_id
                    WHERE ac.pid = :pid";
    $stmtCaselog = $db->prepare($sql_caselog);
    $stmtCaselog->execute([':pid' => $pid]);
    $logs = $stmtCaselog->fetchAll(PDO::FETCH_ASSOC);

    // 4.2 closure_report
    $sql_closure = "SELECT cr.*, 'closure' as record_type, u.fname AS u_fname, u.lname AS u_lname, p.prefix_name AS u_prefix 
                    FROM closure_report cr
                    LEFT JOIN users u ON cr.recorder = u.username
                    LEFT JOIN prefix p ON u.prefix_id = p.prefix_id
                    WHERE cr.pid = :pid";
    $stmtClosure = $db->prepare($sql_closure);
    $stmtClosure->execute([':pid' => $pid]);
    $closures = $stmtClosure->fetchAll(PDO::FETCH_ASSOC);

    // 4.3 forward_case (เพิ่มส่วนดึงข้อมูลการส่งต่อ)
    $sql_forward = "SELECT fc.*, 'forward' as record_type, u.fname AS u_fname, u.lname AS u_lname, p.prefix_name AS u_prefix 
                    FROM forward_case fc
                    LEFT JOIN users u ON fc.recorder = u.username
                    LEFT JOIN prefix p ON u.prefix_id = p.prefix_id
                    WHERE fc.pid = :pid";
    $stmtForwardList = $db->prepare($sql_forward);
    $stmtForwardList->execute([':pid' => $pid]);
    $forwards = $stmtForwardList->fetchAll(PDO::FETCH_ASSOC);

    // ตรวจสอบสถานะ (มีรายงานยุติ หรือ มีการส่งต่อ)
    $hasClosure = count($closures) > 0;
    $hasForward = count($forwards) > 0;
    $isClosed = $hasClosure || $hasForward;

    // รวมข้อมูลและเรียงลำดับตามวันที่ (ใหม่สุดขึ้นก่อน)
    $caseLogs = array_merge($logs, $closures, $forwards);
    usort($caseLogs, function ($a, $b) {
        // ให้ Closure Report อยู่บนสุดเสมอ
        if ($a['record_type'] === 'closure' && $b['record_type'] !== 'closure') {
            return -1;
        }
        if ($a['record_type'] !== 'closure' && $b['record_type'] === 'closure') {
            return 1;
        }
        // ให้ Forward Case อยู่บนสุดเช่นกัน (รองจาก Closure หรือเทียบเท่า)
        if ($a['record_type'] === 'forward' && $b['record_type'] !== 'forward' && $b['record_type'] !== 'closure') {
            return -1;
        }
        if ($a['record_type'] !== 'forward' && $a['record_type'] !== 'closure' && $b['record_type'] === 'forward') {
            return 1;
        }

        // เรียงตามวันที่บันทึก (created_at) ล่าสุดขึ้นก่อน (ครั้งล่าสุดที่กรอก)
        return strtotime($b['created_at']) - strtotime($a['created_at']);
    });

    // 5. ดึงข้อมูลรูปภาพประกอบ (เฉพาะ add_caselog)
    $caseImages = [];
    $caseIds = [];
    foreach ($caseLogs as $l) {
        if ($l['record_type'] === 'case') {
            $caseIds[] = $l['id'];
        }
    }

    if (!empty($caseIds)) {
        $placeholders = implode(',', array_fill(0, count($caseIds), '?'));
        $stmtImages = $db->prepare("SELECT * FROM images WHERE case_id IN ($placeholders)");
        $stmtImages->execute($caseIds);
        while ($row = $stmtImages->fetch(PDO::FETCH_ASSOC)) {
            $caseImages[$row['case_id']][] = $row;
        }
    }
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
    <!-- SweetAlert2 CSS (optional, but good practice if customizing) -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Sarabun:wght@300;400;500;600&display=swap" rel="stylesheet">
    <!-- Global Stylesheet (for background) -->
    <link href="css/style.css" rel="stylesheet">
</head>

<body>
    <?php require_once 'navbar.php'; ?>

    <div class="container mt-5">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2>📜 ประวัติการช่วยเหลือรายกรณี (Case History)</h2>
            <a href="phq_history.php?pid=<?= htmlspecialchars($pid) ?>" class="btn btn-danger">↩ กลับหน้าประวัติการประเมิน</a>
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
                    <div class="dropdown">
                        <button class="btn btn-success btn-sm dropdown-toggle d-inline-flex align-items-center gap-1" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="bi bi-file-earmark-plus-fill fs-5"></i> เพิ่มรายงานใหม่
                        </button>
                        <ul class="dropdown-menu">
                            <!-- เพิ่มรายงาน: ทำได้เมื่อไม่มีการยุติ และ ไม่มีการส่งต่อ -->
                            <?php if (!$hasClosure && !$hasForward): ?>
                                <li><a class="dropdown-item text-success" href="add_case.php?pid=<?= htmlspecialchars($pid) ?>"><i class="bi bi-file-earmark-plus-fill me-2"></i> เพิ่มรายงาน</a></li>
                            <?php else: ?>
                                <li><a class="dropdown-item text-secondary" href="#" onclick="showClosureAlert(); return false;"><i class="bi bi-file-earmark-plus-fill me-2"></i> เพิ่มรายงาน</a></li>
                            <?php endif; ?>

                            <!-- ส่งต่อกรณี: ทำได้เมื่อไม่มีการยุติ และ ไม่มีการส่งต่อ -->
                            <?php if (!$hasClosure && !$hasForward): ?>
                                <li><a class="dropdown-item text-info" href="forward_case.php?pid=<?= htmlspecialchars($pid) ?>"><i class="bi bi-send-fill me-2"></i> ส่งต่อกรณี</a></li>
                            <?php else: ?>
                                <li><a class="dropdown-item text-secondary" href="#" onclick="showClosureAlert(); return false;"><i class="bi bi-send-fill me-2"></i> ส่งต่อกรณี</a></li>
                            <?php endif; ?>

                            <!-- ยุติการช่วยเหลือ: ทำได้เมื่อไม่มีการยุติ (แม้จะส่งต่อแล้วก็ยังมายุติได้) -->
                            <?php if (!$hasClosure): ?>
                                <li><a class="dropdown-item text-danger" href="closure_report.php?pid=<?= htmlspecialchars($pid) ?>"><i class="bi bi-file-earmark-x-fill me-2"></i> ยุติการช่วยเหลือ</a></li>
                            <?php else: ?>
                                <li><a class="dropdown-item text-secondary" href="#" onclick="showClosureAlert(); return false;"><i class="bi bi-file-earmark-x-fill me-2"></i> ยุติการช่วยเหลือ</a></li>
                            <?php endif; ?>
                        </ul>
                    </div>
                </div>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover table-striped mb-0 align-middle text-nowrap">
                        <thead class="table-light">
                            <tr>
                                <th style="width: 5%">#</th>
                                <th style="width: 15%">ประเภทกรณี</th>
                                <th style="width: 35%">อาการนำ (Symptoms)</th>
                                <th style="width: 20%">ผู้บันทึก</th>
                                <th style="width: 10%" class="text-center">จัดการ</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (count($caseLogs) > 0): ?>
                                <?php foreach ($caseLogs as $index => $log): ?>
                                    <?php
                                    $isClosure = ($log['record_type'] === 'closure');
                                    $isForward = ($log['record_type'] === 'forward');
                                    // ใช้ --bs-table-bg เพื่อ override สีพื้นหลังของ Bootstrap table-striped
                                    $rowStyle = '';
                                    if ($isClosure) {
                                        $rowStyle = 'style="background-color: #5DD3B6; --bs-table-bg: #5DD3B6;"';
                                    } elseif ($isForward) {
                                        $rowStyle = 'style="background-color: #B7BDF7; --bs-table-bg: #B7BDF7;"';
                                    }
                                    $modalId = 'viewModal_' . $log['record_type'] . '_' . $log['id'];
                                    ?>
                                    <tr <?= $rowStyle ?>>
                                        <td><?= count($caseLogs) - $index ?>.</td>
                                        <td>
                                            <span class="badge rounded-pill <?= $isClosure ? 'bg-success' : ($isForward ? 'bg-primary' : 'bg-info') ?> text-dark">
                                                <?= $isForward ? 'ส่งต่อกรณี' : htmlspecialchars($log['case_type']) ?>
                                            </span>
                                        </td>
                                        <td>
                                            <small>
                                                <?php if ($isClosure): ?>
                                                    <strong>[รายงานการยุติ]</strong> <?= htmlspecialchars(mb_strimwidth($log['suggestion'] ?? '', 0, 100, '...')) ?>
                                                <?php elseif ($isForward): ?>
                                                    <strong>[การส่งต่อ]</strong> <?= htmlspecialchars($log['referral_agency'] ?? '') ?>
                                                    <?php if (!empty($log['referral_other'])) echo '(' . htmlspecialchars($log['referral_other']) . ')'; ?>
                                                <?php else: ?>
                                                    <?= htmlspecialchars(mb_strimwidth($log['presenting_symptoms'] ?? '', 0, 100, '...')) ?>
                                                <?php endif; ?>
                                            </small>
                                        </td>
                                        <td><?= htmlspecialchars($log['recorder']) ?></td>
                                        <td class="text-center text-nowrap">
                                            <button type="button" class="btn btn-sm btn-primary text-nowrap" data-bs-toggle="modal" data-bs-target="#<?= $modalId ?>">
                                                📄 ดูข้อมูล
                                            </button>
                                            <?php if (!$isClosure && !$isForward): ?>
                                                <button type="button" class="btn btn-sm btn-warning text-nowrap ms-1" data-bs-toggle="modal" data-bs-target="#editCaseModal<?= $log['id'] ?>">
                                                    ✏️ แก้ไข
                                                </button>
                                                <form action="api/delete_case.php" method="POST" class="d-inline" onsubmit="return confirm('คุณต้องการลบรายงานนี้ใช่หรือไม่?\n⚠️ การกระทำนี้ไม่สามารถย้อนกลับได้ และรูปภาพประกอบจะถูกลบด้วย');">
                                                    <input type="hidden" name="id" value="<?= $log['id'] ?>">
                                                    <input type="hidden" name="pid" value="<?= htmlspecialchars($pid) ?>">
                                                    <button type="submit" class="btn btn-sm btn-danger text-nowrap ms-1">⛔ ลบ</button>
                                                </form>
                                            <?php endif; ?>
                                            <div class="modal fade text-start" id="<?= $modalId ?>" tabindex="-1" aria-hidden="true">
                                                <div class="modal-dialog modal-lg">
                                                    <div class="modal-content">
                                                        <div class="modal-header bg-primary text-white">
                                                            <h5 class="modal-title">
                                                                <?php 
                                                                    if ($isClosure) echo 'รายละเอียดรายงานการยุติ';
                                                                    elseif ($isForward) echo 'รายละเอียดการส่งต่อ';
                                                                    else echo 'รายละเอียดเคส';
                                                                ?>
                                                                วันที่ <?= date('d/m/Y', strtotime($log['created_at'])) // ใช้ created_at เพราะ forward ไม่มี report_date ?>
                                                            </h5>
                                                            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                                                        </div>
                                                        <div class="modal-body">
                                                            <?php if ($isClosure): ?>
                                                                <!-- ส่วนแสดงผลสำหรับ Closure Report -->
                                                                <p><strong>ประเภทกรณี:</strong> <?= htmlspecialchars($log['case_type']) ?></p>
                                                                <p><strong>ครั้งที่:</strong> <?= htmlspecialchars($log['case_count'] ?? '-') ?></p>
                                                                <hr>
                                                                <p><strong>รายละเอียดการติดตาม:</strong></p>
                                                                <ul>
                                                                    <li>ครอบครัว: <?= htmlspecialchars($log['detail_family'] ?? '-') ?></li>
                                                                    <li>โรงเรียน: <?= htmlspecialchars($log['detail_school'] ?? '-') ?></li>
                                                                    <li>โรงพยาบาล: <?= htmlspecialchars($log['detail_hospital'] ?? '-') ?></li>
                                                                </ul>
                                                                <hr>
                                                                <p><strong>ข้อเสนอแนะ:</strong><br> <?= nl2br(htmlspecialchars($log['suggestion'] ?? '-')) ?></p>
                                                                
                                                                <!-- เชคหน้ารายงาน ยุติ -->
                                                                <!-- <p><strong>การส่งต่อ:</strong>
                                                                    <?= htmlspecialchars($log['referral_agency'] ?? '-') ?>
                                                                    <?php if (!empty($log['referral_other'])) echo ' (' . htmlspecialchars($log['referral_other']) . ')'; ?>
                                                                </p> -->
                                                            <?php elseif ($isForward): ?>
                                                                <!-- ส่วนแสดงผลสำหรับ Forward Case -->
                                                                <p><strong>หน่วยงานที่ส่งต่อ:</strong> <?= htmlspecialchars($log['referral_agency']) ?></p>
                                                                <?php if (!empty($log['referral_other'])): ?>
                                                                    <p><strong>ระบุเพิ่มเติม:</strong> <?= htmlspecialchars($log['referral_other']) ?></p>
                                                                <?php endif; ?>
                                                            <?php else: ?>
                                                                <!-- ส่วนแสดงผลสำหรับ Case Log ปกติ -->
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
                                                                <?php if (!empty($caseImages[$log['id']])): ?>
                                                                    <div class="mt-3">
                                                                        <strong>รูปภาพประกอบ:</strong>
                                                                        <div class="d-flex flex-wrap gap-2 mt-2">
                                                                            <?php foreach ($caseImages[$log['id']] as $img): ?>
                                                                                <a href="uploads/cases/<?= htmlspecialchars($img['file_name']) ?>" target="_blank">
                                                                                    <img src="uploads/cases/<?= htmlspecialchars($img['file_name']) ?>" class="rounded border shadow-sm" style="width: 100px; height: 100px; object-fit: cover;">
                                                                                </a>
                                                                            <?php endforeach; ?>
                                                                        </div>
                                                                    </div>
                                                                <?php endif; ?>
                                                            <?php endif; ?>

                                                            <p class="text-muted small text-end mb-0">บันทึกเมื่อ: <?= $log['created_at'] ?></p>
                                                            <?php
                                                            $recorder_show = trim(($log['u_prefix'] ?? '') . ($log['u_fname'] ?? '') . ' ' . ($log['u_lname'] ?? ''));
                                                            if ($recorder_show === '') $recorder_show = $log['recorder'];
                                                            ?>
                                                            <p class="text-muted small text-end mt-0">ผู้บันทึก : <?= htmlspecialchars($recorder_show) ?></p>
                                                        </div>
                                                        <div class="modal-footer">
                                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">ปิด</button>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>

                                            <?php if (!$isClosure && !$isForward): ?>
                                                <!-- Modal แก้ไขข้อมูล -->
                                                <div class="modal fade text-start" id="editCaseModal<?= $log['id'] ?>" tabindex="-1" aria-hidden="true">
                                                    <div class="modal-dialog modal-lg">
                                                        <div class="modal-content">
                                                            <div class="modal-header bg-warning">
                                                                <h5 class="modal-title">✏️ แก้ไขข้อมูลเคส วันที่ <?= date('d/m/Y', strtotime($log['report_date'])) ?></h5>
                                                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                            </div>
                                                            <form action="api/update_case.php" method="POST" enctype="multipart/form-data">
                                                                <div class="modal-body">
                                                                    <input type="hidden" name="id" value="<?= $log['id'] ?>">
                                                                    <input type="hidden" name="pid" value="<?= htmlspecialchars($pid) ?>">

                                                                    <div class="row mb-3">
                                                                        <div class="col-md-6">
                                                                            <label class="form-label">วันที่รายงาน</label>
                                                                            <input type="date" name="report_date" class="form-control" value="<?= $log['report_date'] ?>" required>
                                                                        </div>
                                                                        <div class="col-md-6">
                                                                            <label class="form-label">ประเภทกรณี</label>
                                                                            <select name="case_type" class="form-select" required>
                                                                                <option value="ซึมเศร้า" <?= $log['case_type'] == 'ซึมเศร้า' ? 'selected' : '' ?>>ซึมเศร้า</option>
                                                                                <option value="เครียด" <?= $log['case_type'] == 'เครียด' ? 'selected' : '' ?>>เครียด</option>
                                                                                <option value="วิตกกังวล" <?= $log['case_type'] == 'วิตกกังวล' ? 'selected' : '' ?>>วิตกกังวล</option>
                                                                                <option value="ปัญหาครอบครัว" <?= $log['case_type'] == 'ปัญหาครอบครัว' ? 'selected' : '' ?>>ปัญหาครอบครัว</option>
                                                                                <option value="อื่นๆ" <?= $log['case_type'] == 'อื่นๆ' ? 'selected' : '' ?>>อื่นๆ</option>
                                                                            </select>
                                                                        </div>
                                                                    </div>

                                                                    <div class="mb-3">
                                                                        <label class="form-label">อาการนำ (Presenting Symptoms)</label>
                                                                        <textarea name="presenting_symptoms" class="form-control" rows="3"><?= htmlspecialchars($log['presenting_symptoms']) ?></textarea>
                                                                    </div>

                                                                    <h6 class="text-primary border-bottom pb-2 mt-4">ข้อมูลเพิ่มเติม</h6>
                                                                    <div class="row">
                                                                        <div class="col-md-6 mb-2"><label class="form-label small">ประวัติส่วนตัว</label><textarea name="history_personal" class="form-control" rows="2"><?= htmlspecialchars($log['history_personal']) ?></textarea></div>
                                                                        <div class="col-md-6 mb-2"><label class="form-label small">ข้อมูลจากครอบครัว</label><textarea name="history_family" class="form-control" rows="2"><?= htmlspecialchars($log['history_family']) ?></textarea></div>
                                                                        <div class="col-md-6 mb-2"><label class="form-label small">ข้อมูลจากโรงเรียน</label><textarea name="history_school" class="form-control" rows="2"><?= htmlspecialchars($log['history_school']) ?></textarea></div>
                                                                        <div class="col-md-6 mb-2"><label class="form-label small">ข้อมูลจากโรงพยาบาล</label><textarea name="history_hospital" class="form-control" rows="2"><?= htmlspecialchars($log['history_hospital']) ?></textarea></div>
                                                                    </div>

                                                                    <h6 class="text-primary border-bottom pb-2 mt-4">แนวทางการช่วยเหลือ</h6>
                                                                    <div class="row">
                                                                        <div class="col-md-6 mb-2"><label class="form-label small">โรงเรียน</label><textarea name="assist_school" class="form-control" rows="2"><?= htmlspecialchars($log['assist_school']) ?></textarea></div>
                                                                        <div class="col-md-6 mb-2"><label class="form-label small">ผู้ปกครอง</label><textarea name="assist_parent" class="form-control" rows="2"><?= htmlspecialchars($log['assist_parent']) ?></textarea></div>
                                                                        <div class="col-md-6 mb-2"><label class="form-label small">โรงพยาบาล</label><textarea name="assist_hospital" class="form-control" rows="2"><?= htmlspecialchars($log['assist_hospital']) ?></textarea></div>
                                                                        <div class="col-md-6 mb-2"><label class="form-label small">หน่วยงานอื่น</label><textarea name="assist_other" class="form-control" rows="2"><?= htmlspecialchars($log['assist_other']) ?></textarea></div>
                                                                    </div>

                                                                    <div class="mb-3 mt-3">
                                                                        <label class="form-label">ข้อเสนอแนะ</label>
                                                                        <textarea name="suggestions" class="form-control" rows="2"><?= htmlspecialchars($log['suggestions']) ?></textarea>
                                                                    </div>

                                                                    <div class="mb-3 border-top pt-3">
                                                                        <label class="form-label fw-bold">จัดการรูปภาพ</label>
                                                                        <?php if (!empty($caseImages[$log['id']])): ?>
                                                                            <div class="d-flex flex-wrap gap-3 mb-3">
                                                                                <?php foreach ($caseImages[$log['id']] as $img): ?>
                                                                                    <div class="text-center">
                                                                                        <img src="uploads/cases/<?= htmlspecialchars($img['file_name']) ?>" class="rounded border shadow-sm mb-1" style="width: 80px; height: 80px; object-fit: cover;">
                                                                                        <div class="form-check d-flex justify-content-center">
                                                                                            <input class="form-check-input me-1" type="checkbox" name="delete_images[]" value="<?= $img['id'] ?>" id="del_img_<?= $img['id'] ?>">
                                                                                            <label class="form-check-label small text-danger" for="del_img_<?= $img['id'] ?>">ลบ</label>
                                                                                        </div>
                                                                                    </div>
                                                                                <?php endforeach; ?>
                                                                            </div>
                                                                        <?php endif; ?>
                                                                        <label class="form-label small">อัปโหลดรูปภาพเพิ่ม (เลือกได้หลายรูป)</label>
                                                                        <input type="file" name="new_images[]" id="new_images_<?= $log['id'] ?>" class="form-control new-images-input" multiple accept="image/*">
                                                                        <div id="preview_container_<?= $log['id'] ?>" class="d-flex flex-wrap gap-2 mt-2"></div>
                                                                    </div>

                                                                </div>
                                                                <div class="modal-footer">
                                                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">ยกเลิก</button>
                                                                    <button type="button" class="btn btn-primary" onclick="validateAndSubmitEditForm(this.form, <?= count($caseImages[$log['id']] ?? []) ?>)">บันทึกการแก้ไข</button>
                                                                </div>
                                                            </form>
                                                        </div>
                                                    </div>
                                                </div>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="5" class="text-center py-4 text-muted">
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

    <!-- 16:12 ติดตั้ง sweet alert และต้องสร้าง function และเรียกใช้  จะนำไปใช้กับทุกหน้าในเพจ -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="script/javascript/add_case_history.js"></script>
    <!-- เชค sweetalert มี sweetalert2 แล้วลองดูว่าศ้ำซ้อนไหม -->
    <script src="script/javascript/sweetalert_utils.js"></script>
    <script>
        // ฟังก์ชันสำหรับแจ้งเตือนเมื่อมีรายงานการยุติแล้ว
        function showClosureAlert() {
            showInfoAlert(
                'ไม่สามารถเพิ่ม/ยุติรายงานได้',
                'นักเรียนคนนี้มีรายงานการยุติให้การดูแลแล้ว หากต้องการแก้ไขโปรดติดต่อผู้ดูแลระบบ'
            );
        }
    </script>
</body>

</html>