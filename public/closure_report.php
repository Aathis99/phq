<?php
// 1. เริ่ม Session
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

require_once dirname(__DIR__) . '/app/core/Database.php';
$db = Database::connect();

// --- 2. รับค่า PID จาก URL (ส่วนที่เพิ่มเข้ามา) ---
$pid = $_GET['pid'] ?? '';

// --- Master Data ---
$schools = $db->query("SELECT school_id, school_name FROM school ORDER BY school_id")->fetchAll();
$prefixes = $db->query("SELECT prefix_id, prefix_name FROM prefix ORDER BY prefix_id")->fetchAll();
$sexes = $db->query("SELECT sex_id, sex_name FROM sex ORDER BY sex_id")->fetchAll();

// --- 3. ดึงข้อมูลนักเรียน (ส่วนที่เพิ่มเข้ามา) ---
$student = [];
if (!empty($pid)) {
    // ดึงข้อมูลนักเรียนตาม PID ที่ส่งมา
    $stmt_std = $db->prepare("SELECT * FROM student_data WHERE pid = :pid");
    $stmt_std->execute([':pid' => $pid]);
    $result_std = $stmt_std->fetch(PDO::FETCH_ASSOC);
    if ($result_std) {
        $student = $result_std;
    }
}

// --- 4. ดึงชื่อผู้บันทึก ---
$recorder_name = "";
if (isset($_SESSION['user']['username'])) {
    $current_user = $_SESSION['user']['username'];

    $sql_recorder = "SELECT p.prefix_name, m.fname, m.lname 
                     FROM member m 
                     INNER JOIN prefix p ON m.prefix_id = p.prefix_id 
                     WHERE m.username = :username";

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
    <title>แบบรายงานการยุติให้การดูแลช่วยเหลือรายกรณี</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

    <link href="https://fonts.googleapis.com/css2?family=Sarabun:wght@400;700&display=swap" rel="stylesheet">
    <link href="css/closure_report.css" rel="stylesheet">
</head>

<body>
    <div class="d-flex justify-content-between align-items-center mb-4">
        <a href="phq_history.php?pid=<?= htmlspecialchars($pid) ?>" class="btn btn-danger">← ย้อนกลับ</a>
    </div>
    <div class="container container-form">
        <h2>แบบรายงานการยุติให้การดูแลช่วยเหลือรายกรณี</h2>

        <form action="" method="post">
            <div class="form-section-header">ข้อมูลทั่วไป</div>

            <div class="row mb-3">
                <div class="col-md-6">
                    <label class="form-label">กรณี</label>
                    <select name="case_type" class="form-select" required>
                        <option value="" disabled selected>-- เลือกกรณี --</option>
                        <option value="ซึมเศร้า">ซึมเศร้า</option>
                        <option value="เครียด">เครียด</option>
                        <option value="วิตกกังวล">วิตกกังวล</option>
                        <option value="ปัญหาครอบครัว">ปัญหาครอบครัว</option>
                        <option value="อื่นๆ">อื่นๆ</option>
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label">ครั้งที่</label>
                    <select name="case_count" class="form-select" required>
                        <option value="" disabled selected>-- เลือกครั้งที่ --</option>
                        <?php for ($i = 1; $i <= 10; $i++): ?>
                            <option value="<?= $i ?>"><?= $i ?></option>
                        <?php endfor; ?>
                    </select>
                </div>
            </div>

            <div class="row mb-3">
                <div class="col-md-2">
                    <label class="form-label">คำนำหน้า</label>
                    <select name="prefix_id" class="form-select" required>
                        <option value="" disabled selected>เลือก</option>
                        <?php foreach ($prefixes as $prefix): ?>
                            <option value="<?= $prefix['prefix_id'] ?>" <?= (isset($student['prefix_id']) && $student['prefix_id'] == $prefix['prefix_id']) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($prefix['prefix_name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-5">
                    <label class="form-label">ชื่อ</label>
                    <input type="text" name="firstname" class="form-control" value="<?= isset($student['fname']) ? htmlspecialchars($student['fname']) : '' ?>" required>
                </div>
                <div class="col-md-5">
                    <label class="form-label">นามสกุล</label>
                    <input type="text" name="lastname" class="form-control" value="<?= isset($student['lname']) ? htmlspecialchars($student['lname']) : '' ?>" required>
                </div>
            </div>

            <div class="row mb-3">
                <div class="col-md-4">
                    <label class="form-label">เพศ</label>
                    <select name="sex_id" class="form-select" required>
                        <option value="" disabled selected>-- เลือกเพศ --</option>
                        <?php foreach ($sexes as $sex): ?>
                            <option value="<?= $sex['sex_id'] ?>" <?= (isset($student['sex']) && $student['sex'] == $sex['sex_id']) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($sex['sex_name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">อายุ (ปี)</label>
                    <select name="age" class="form-select" required>
                        <option value="" disabled selected>-- เลือกอายุ --</option>
                        <?php for ($i = 12; $i <= 20; $i++): ?>
                            <option value="<?= $i ?>" <?= (isset($student['age']) && $student['age'] == $i) ? 'selected' : '' ?>><?= $i ?></option>
                        <?php endfor; ?>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">ระดับการศึกษา</label>
                    <select name="education_level" class="form-select" required>
                        <option value="" disabled selected>-- เลือกระดับชั้น --</option>
                        <?php for ($i = 1; $i <= 6; $i++): ?>
                            <option value="ม.<?= $i ?>" <?= (isset($student['class']) && $student['class'] == $i) ? 'selected' : '' ?>>ม.<?= $i ?></option>
                        <?php endfor; ?>
                    </select>
                </div>
            </div>

            <div class="row mb-3">
                <div class="col-md-6">
                    <label class="form-label">โรงเรียน</label>
                    <select name="school_id" class="form-select" required>
                        <option value="" disabled selected>-- เลือกโรงเรียน --</option>
                        <?php foreach ($schools as $school): ?>
                            <option value="<?= $school['school_id'] ?>" <?= (isset($student['school_id']) && $student['school_id'] == $school['school_id']) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($school['school_name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label">วัน/เดือน/ปี</label>
                    <input type="date" name="report_date" class="form-control" required>
                </div>
            </div>

            <div class="form-section-header">รายละเอียดการติดตาม พฤติกรรม/อาการ</div>

            <div class="mb-3">
                <label class="form-label">ครอบครัว</label>
                <textarea name="detail_family" class="form-control" rows="3" placeholder="ระบุข้อมูล..."></textarea>
            </div>
            <div class="mb-3">
                <label class="form-label">โรงเรียน</label>
                <textarea name="detail_school" class="form-control" rows="3" placeholder="ระบุข้อมูล..."></textarea>
            </div>
            <div class="mb-3">
                <label class="form-label">โรงพยาบาล</label>
                <textarea name="detail_hospital" class="form-control" rows="3" placeholder="ระบุข้อมูล..."></textarea>
            </div>

            <div class="mb-4">
                <label class="form-label">ข้อเสนอแนะ</label>
                <textarea name="suggestion" class="form-control" rows="4" placeholder="ระบุข้อเสนอแนะ..."></textarea>
            </div>

            <div class="form-section-header">การบันทึกข้อมูล</div>
            <div class="row mb-4">
                <div class="col-md-6">
                    <label class="form-label">ผู้บันทึก</label>
                    <input type="text"
                        name="recorder_name"
                        class="form-control"
                        value="<?= htmlspecialchars($recorder_name) ?>"
                        readonly
                        required>
                </div>
                <div class="col-md-6">
                    <label class="form-label">วันที่บันทึก (อัตโนมัติ)</label>
                    <input type="text" id="record_date_display" class="form-control bg-light" readonly>
                </div>
            </div>

            <div class="text-center mt-5">
                <button type="submit" class="btn btn-success btn-lg px-5 rounded-pill">
                    บันทึกรายงานการยุติ
                </button>
            </div>

        </form>
    </div>

    <script>
        // Script สำหรับแสดงวันที่ปัจจุบันแบบอัตโนมัติ
        document.addEventListener("DOMContentLoaded", function() {
            const dateEl = document.getElementById('record_date_display');
            const now = new Date();

            // รูปแบบวันที่ไทย
            const options = {
                year: 'numeric',
                month: 'long',
                day: 'numeric'
            };
            dateEl.value = now.toLocaleDateString('th-TH', options);
        });
    </script>

</body>

</html>