<?php
session_start();
require_once dirname(__DIR__) . '/app/core/Database.php';
$db = Database::connect();

if (!isset($_SESSION['user'])) {
    header("Location: login.php");
    exit;
}

$pid = $_GET['pid'] ?? '';

// ดึงข้อมูลนักเรียนเพื่อนำมาแสดงหัวข้อ
$student = [];
if (!empty($pid)) {
    $stmt_std = $db->prepare("SELECT s.*, p.prefix_name, sc.school_name, sx.sex_name 
                              FROM student_data s
                              LEFT JOIN prefix p ON s.prefix_id = p.prefix_id
                              LEFT JOIN school sc ON s.school_id = sc.school_id
                              LEFT JOIN sex sx ON s.sex = sx.sex_id
                              WHERE s.pid = :pid");
    $stmt_std->execute([':pid' => $pid]);
    $student = $stmt_std->fetch(PDO::FETCH_ASSOC);
}

// ดึงชื่อผู้บันทึก
$recorder_name = "";
if (isset($_SESSION['user']['username'])) {
    $current_user = $_SESSION['user']['username'];
    $sql_recorder = "SELECT p.prefix_name, u.fname, u.lname 
                     FROM users u 
                     INNER JOIN prefix p ON u.prefix_id = p.prefix_id 
                     WHERE u.username = :username";
    $stmt = $db->prepare($sql_recorder);
    $stmt->execute([':username' => $current_user]);
    $res = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($res) {
        $recorder_name = $res['prefix_name'] . $res['fname'] . ' ' . $res['lname'];
    }
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>แบบฟอร์มการส่งต่อกรณี</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="css/style.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Sarabun:wght@400;700&display=swap" rel="stylesheet">
    <link href="css/closure_report.css" rel="stylesheet">
</head>
<body>
    <div class="d-flex justify-content-between align-items-center mb-4">
        <a href="add_case_history.php?pid=<?= htmlspecialchars($pid) ?>" class="btn btn-danger">← ย้อนกลับ</a>
    </div>
    <div class="container container-form">
        <h2>แบบฟอร์มการส่งต่อกรณี</h2>
        
        <!-- ข้อมูลนักเรียนโดยย่อ -->
        <div class="card mb-4">
            <div class="card-body">
                <h5 class="card-title">ข้อมูลนักเรียน</h5>
                <p class="mb-1"><strong>ชื่อ-นามสกุล:</strong> <?= htmlspecialchars(($student['prefix_name']??'') . ($student['fname']??'') . ' ' . ($student['lname']??'')) ?></p>
                <p class="mb-0"><strong>เลขบัตรประชาชน:</strong> <?= htmlspecialchars($pid) ?></p>
            </div>
        </div>

        <form action="save_forward.php" method="post">
            <input type="hidden" name="pid" value="<?= htmlspecialchars($pid) ?>">

            <!-- *** ส่วนที่ย้ายมาจาก closure_report.php *** -->
            <div class="form-section-header" style="background-color: #ffe0b2; color: #e65100;">การส่งต่อกรณี</div>
            <div class="row mb-3">
                <div class="col-md-6">
                    <label class="form-label">หน่วยงานที่จะส่งต่อ</label>
                    <select name="referral_agency" id="referral_agency" class="form-select">
                        <option value="" selected>-- ไม่มีการส่งต่อ --</option>
                        <option value="โรงพยาบาลส่งเสริมสุขภาพตำบล">โรงพยาบาลส่งเสริมสุขภาพตำบล</option>
                        <option value="โรงพยาบาลชุมชน">โรงพยาบาลชุมชน</option>
                        <option value="โรงพยาบาลทั่วไป/ศูนย์">โรงพยาบาลทั่วไป/ศูนย์</option>
                        <option value="อื่นๆ">อื่นๆ</option>
                    </select>
                </div>
            </div>
            <div class="row mb-3" id="referral_other_container" style="display: none;">
                <div class="col-md-12">
                    <label class="form-label">ระบุหน่วยงานอื่น</label>
                    <input type="text" name="referral_other" id="referral_other" class="form-control" placeholder="โปรดระบุ...">
                </div>
            </div>
             
            <div class="form-section-header">การบันทึกข้อมูล</div>
            <div class="row mb-4">
                <div class="col-md-6">
                    <label class="form-label">ผู้บันทึก</label>
                    <input type="text" name="recorder_name" class="form-control bg-light" value="<?= htmlspecialchars($recorder_name) ?>" readonly required>
                </div>
                <div class="col-md-6">
                    <label class="form-label">วันที่บันทึก (อัตโนมัติ)</label>
                    <input type="text" id="record_date_display" class="form-control bg-light" readonly>
                </div>
            </div>

            <div class="text-center mt-5">
                <button type="submit" class="btn btn-warning btn-lg px-5 rounded-pill">
                    บันทึกการส่งต่อ
                </button>
            </div>
        </form>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener("DOMContentLoaded", function() {
            const dateEl = document.getElementById('record_date_display');
            const now = new Date();
            const options = { year: 'numeric', month: 'long', day: 'numeric' };
            dateEl.value = now.toLocaleDateString('th-TH', options);

            const referralSelect = document.getElementById('referral_agency');
            const referralOtherContainer = document.getElementById('referral_other_container');
            const referralOtherInput = document.getElementById('referral_other');

            if (referralSelect) {
                referralSelect.addEventListener('change', function() {
                    if (this.value === 'อื่นๆ') {
                        referralOtherContainer.style.display = 'block';
                        referralOtherInput.required = true;
                    } else {
                        referralOtherContainer.style.display = 'none';
                        referralOtherInput.required = false;
                        referralOtherInput.value = '';
                    }
                });
            }
        });
    </script>
</body>
</html>