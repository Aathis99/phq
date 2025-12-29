<?php
require_once dirname(__DIR__) . '/app/core/Database.php';
// require_once 'navbar.php';

$db = Database::connect();

/* 1. ดึงรายชื่อโรงเรียน */
$sql_school = "SELECT school_id, school_name FROM school ORDER BY school_id";
$stmt_school = $db->query($sql_school);
$schools = $stmt_school->fetchAll();

/* 2. ดึงคำนำหน้า */
$sql_prefix = "SELECT prefix_id, prefix_name FROM prefix ORDER BY prefix_id";
$stmt_prefix = $db->query($sql_prefix);
$prefixes = $stmt_prefix->fetchAll();

/* 3. ดึงเพศ (Loop จากตาราง sex ตามโจทย์) */
$sexes = [];
try {
    $sql_sex = "SELECT sex_id, sex_name FROM sex ORDER BY sex_id";
    $stmt_sex = $db->query($sql_sex);
    $sexes = $stmt_sex->fetchAll();
} catch (Exception $e) {
    // Fallback กรณีไม่มีตาราง sex หรือเกิดข้อผิดพลาด
    $sexes = [
        ['sex_id' => 1, 'sex_name' => 'ชาย'],
        ['sex_id' => 2, 'sex_name' => 'หญิง'],
        ['sex_id' => 3, 'sex_name' => 'เพศทางเลือก']
    ];
}
?>
<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>แบบรายงานการยุติให้การดูแลช่วยเหลือรายกรณี</title>

    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Sarabun:wght@400;700&display=swap" rel="stylesheet">
    <!-- Custom CSS -->
    <link href="css/closure_report.css" rel="stylesheet">
</head>

<body>
    <div class="d-flex justify-content-between align-items-center mb-4">
        <a href="main.php" class="btn btn-danger">← กลับหน้าหลัก</a>
    </div>
    <div class="container container-form">
        <h2>แบบรายงานการยุติให้การดูแลช่วยเหลือรายกรณี</h2>

        <form action="" method="post">
            <!-- ส่วนที่ 1: ข้อมูลทั่วไป -->
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
                            <option value="<?= $prefix['prefix_id'] ?>">
                                <?= htmlspecialchars($prefix['prefix_name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-5">
                    <label class="form-label">ชื่อ</label>
                    <input type="text" name="firstname" class="form-control" required>
                </div>
                <div class="col-md-5">
                    <label class="form-label">นามสกุล</label>
                    <input type="text" name="lastname" class="form-control" required>
                </div>
            </div>

            <div class="row mb-3">
                <div class="col-md-4">
                    <label class="form-label">เพศ</label>
                    <select name="sex_id" class="form-select" required>
                        <option value="" disabled selected>-- เลือกเพศ --</option>
                        <?php foreach ($sexes as $sex): ?>
                            <option value="<?= $sex['sex_id'] ?>">
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
                            <option value="<?= $i ?>"><?= $i ?></option>
                        <?php endfor; ?>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">ระดับการศึกษา</label>
                    <select name="education_level" class="form-select" required>
                        <option value="" disabled selected>-- เลือกระดับชั้น --</option>
                        <?php for ($i = 1; $i <= 6; $i++): ?>
                            <option value="ม.<?= $i ?>">ม.<?= $i ?></option>
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
                            <option value="<?= $school['school_id'] ?>">
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

            <!-- ส่วนที่ 2: รายละเอียดการติดตาม -->
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

            <!-- ส่วนที่ 3: ผู้บันทึก -->
            <div class="form-section-header">การบันทึกข้อมูล</div>
            <div class="row mb-4">
                <div class="col-md-6">
                    <label class="form-label">ผู้บันทึก</label>
                    <input type="text" name="recorder_name" class="form-control" required>
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