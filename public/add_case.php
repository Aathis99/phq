<?php
// 1. เริ่ม Session ทันที
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

require_once dirname(__DIR__) . '/app/core/Database.php';
$db = Database::connect();

$pid = $_GET['pid'] ?? '';

// --- ส่วนดึงข้อมูลเคสเดิม (ถ้ามี) ---
$follow_case_id = $_GET['follow_case_id'] ?? '';
$case_to_follow = [];
if (!empty($follow_case_id) && !empty($pid)) {
    // ดึงข้อมูลเคสที่ต้องการติดตามผล
    $stmt_follow = $db->prepare("SELECT * FROM add_caselog WHERE id = :id AND pid = :pid");
    $stmt_follow->execute([':id' => $follow_case_id, ':pid' => $pid]);
    $case_to_follow = $stmt_follow->fetch(PDO::FETCH_ASSOC);
    if (!$case_to_follow) {
        $case_to_follow = []; // Reset ถ้าไม่พบข้อมูลหรือ pid ไม่ตรงกัน
    }
}

// --- ส่วนดึงข้อมูล Master Data ---
$schools = $db->query("SELECT school_id, school_name FROM school ORDER BY school_id")->fetchAll();
$prefixes = $db->query("SELECT prefix_id, prefix_name FROM prefix ORDER BY prefix_id")->fetchAll();
$sexes = $db->query("SELECT sex_id, sex_name FROM sex ORDER BY sex_id")->fetchAll();

// --- ส่วนดึงข้อมูลนักเรียน (จาก PID) ---
$student = [];
if (!empty($pid)) {
    $stmt_std = $db->prepare("SELECT * FROM student_data WHERE pid = :pid");
    $stmt_std->execute([':pid' => $pid]);
    $student = $stmt_std->fetch(PDO::FETCH_ASSOC);
}

// --- ส่วนคำนวณครั้งที่ (Case Number) ---
$case_number = 1; // ค่าเริ่มต้นคือ 1
if (!empty($pid)) {
    // ใช้ try-catch เผื่อกรณีที่ตาราง add_caselog ยังไม่มี
    try {
        $stmt_count = $db->prepare("SELECT COUNT(id) FROM add_caselog WHERE pid = :pid");
        $stmt_count->execute([':pid' => $pid]);
        $case_number = $stmt_count->fetchColumn() + 1;
    } catch (PDOException $e) {
        // ถ้าตารางไม่มี หรือมีข้อผิดพลาดอื่น ให้ใช้ค่าเริ่มต้น 1
        $case_number = 1;
    }
}

// --- ส่วนดึงชื่อผู้บันทึก (จาก Member) ---
$recorder_name = "";
// ต้องเช็ค $_SESSION['user']['username'] ตามที่ตั้งไว้ใน login_process.php
if (isset($_SESSION['user']['username'])) {
    $current_user = $_SESSION['user']['username'];

    $sql_recorder = "SELECT p.prefix_name, m.fname, m.lname 
                     FROM member m 
                     INNER JOIN prefix p ON m.prefix_id = p.prefix_id 
                     WHERE m.username = :username";

    $stmt_recorder = $db->prepare($sql_recorder);
    $stmt_recorder->execute([':username' => $current_user]);
    $res = $stmt_recorder->fetch(PDO::FETCH_ASSOC);

    if ($res) {
        $recorder_name = $res['prefix_name'] . $res['fname'] . ' ' . $res['lname'];
    }
}
?>




<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>แบบฟอร์มรายงานการช่วยเหลือรายกรณี</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="css/form2.css" rel="stylesheet">
    <style>
        .sticky-back-btn {
            /* css ของปุ่มย้อนกลับ */
            position: fixed;
            top: 20px;
            left: 20px;
            z-index: 1030;
            /* เพื่อให้ปุ่มแสดงอยู่เหนือองค์ประกอบอื่น */
        }
    </style>
</head>
<!-- form2.css -->

<body>
    <a href="add_case_history.php?pid=<?= htmlspecialchars($pid) ?>" class="btn btn-danger sticky-back-btn">← ย้อนกลับ</a>
    <div class="container">
        <div class="d-flex justify-content-between align-items-center mt-3 mb-3">
            <h1>แบบฟอร์มรายงานการช่วยเหลือรายกรณี</h1>
        </div>


        <form action="save_case.php" method="POST">
            <!-- Case Info -->
            <section class="case-info">
                <div class="form-row">
                    <div class="form-col">
                        <label for="case_type">กรณี</label>
                        <select id="case_type" name="case_type" required>
                            <option value="">-- เลือกกรณี --</option>
                            <option value="ซึมเศร้า" <?= (isset($case_to_follow['case_type']) && $case_to_follow['case_type'] == 'ซึมเศร้า') ? 'selected' : '' ?>>ซึมเศร้า</option>
                            <option value="เครียด" <?= (isset($case_to_follow['case_type']) && $case_to_follow['case_type'] == 'เครียด') ? 'selected' : '' ?>>เครียด</option>
                            <option value="วิตกกังวล" <?= (isset($case_to_follow['case_type']) && $case_to_follow['case_type'] == 'วิตกกังวล') ? 'selected' : '' ?>>วิตกกังวล</option>
                            <option value="ปัญหาครอบครัว" <?= (isset($case_to_follow['case_type']) && $case_to_follow['case_type'] == 'ปัญหาครอบครัว') ? 'selected' : '' ?>>ปัญหาครอบครัว</option>
                            <option value="อื่นๆ" <?= (isset($case_to_follow['case_type']) && $case_to_follow['case_type'] == 'อื่นๆ') ? 'selected' : '' ?>>อื่นๆ</option>
                        </select>
                    </div>
                    <div class="form-col">
                        <label for="case_id">ครั้งที่ (รันอัตโนมัติ)</label>
                        <input type="text" id="case_id" name="case_id" value="<?= $case_number ?>" readonly />
                    </div>
                    <div class="form-col">
                        <label for="report_date">วัน/เดือน/ปี</label>
                        <input type="date" id="report_date" name="report_date" />
                    </div>
                </div>
            </section>

            <hr
                style="margin: 1.5rem 0; border: 0; border-top: 1px solid #e2e8f0" />

            <!-- Personal Info -->
            <section class="personal-info">
                <h2>ข้อมูลส่วนตัวนักเรียน</h2>
                <div class="form-row">
                    <div class="form-col">
                        <label for="id_card">เลขบัตรประชาชน</label>
                        <input
                            type="text"
                            inputmode="numeric"
                            id="id_card"
                            name="id_card"
                            value="<?= htmlspecialchars($student['pid'] ?? '') ?>"
                            maxlength="13"
                            oninput="this.value = this.value.replace(/[^0-9]/g, '')" />
                    </div>
                    <div class="form-col">
                        <label for="prefix_id">คำนำหน้า</label>
                        <select id="prefix_id" name="prefix_id">
                            <option value="">-- คำนำหน้า --</option>
                            <?php foreach ($prefixes as $prefix): ?>
                                <option value="<?= $prefix['prefix_id'] ?>" <?= (isset($student['prefix_id']) && $student['prefix_id'] == $prefix['prefix_id']) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($prefix['prefix_name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-col">
                        <label for="firstname">ชื่อ</label>
                        <input type="text" id="firstname" name="firstname" value="<?= htmlspecialchars($student['fname'] ?? '') ?>" />
                    </div>
                    <div class="form-col">
                        <label for="lastname">นามสกุล</label>
                        <input type="text" id="lastname" name="lastname" value="<?= htmlspecialchars($student['lname'] ?? '') ?>" />
                    </div>
                </div>

                <div class="form-row" style="margin-top: 1rem">
                    <div class="form-col">
                        <label for="gender">เพศ</label>
                        <select id="gender" name="gender">
                            <option value="">-- เลือกเพศ --</option>
                            <?php foreach ($sexes as $sex): ?>
                                <option value="<?= $sex['sex_id'] ?>" <?= (isset($student['sex']) && $student['sex'] == $sex['sex_id']) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($sex['sex_name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-col">
                        <label for="age">อายุ</label>
                        <select id="age" name="age">
                            <option value="">-- เลือกอายุ --</option>
                        </select>
                    </div>
                </div>

                <div class="form-row" style="margin-top: 1rem">
                    <div class="form-col">
                        <label for="edu_level">ระดับชั้น</label>
                        <select id="edu_level" name="edu_level">
                            <option value="">-- เลือกระดับชั้น --</option>
                        </select>
                    </div>
                    <div class="form-col">
                        <label for="edu_room">ห้อง</label>
                        <select id="edu_room" name="edu_room">
                            <option value="">-- เลือกห้อง --</option>
                        </select>
                    </div>
                </div>

                <div class="form-group" style="margin-top: 1rem">
                    <label for="school">โรงเรียน</label>
                    <select id="school" name="school">
                        <option value="">-- เลือกโรงเรียน --</option>
                        <?php foreach ($schools as $school): ?>
                            <option value="<?= $school['school_id'] ?>" <?= (isset($student['school_id']) && $student['school_id'] == $school['school_id']) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($school['school_name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </section>

            <!-- Details -->
            <section class="details">
                <h2>อาการและข้อมูลทั่วไป</h2>
                <div class="form-group">
                    <label for="presenting_symptoms">อาการนำ (Presenting Symptoms)</label>
                    <textarea
                        id="presenting_symptoms"
                        name="presenting_symptoms"
                        placeholder="ระบุอาการเบื้องต้น..."><?= htmlspecialchars($case_to_follow['presenting_symptoms'] ?? '') ?></textarea>
                </div>

                <h3>ลักษณะทั่วไป</h3>
                <div class="form-group">
                    <label for="history_personal">ประวัติส่วนตัว</label>
                    <textarea id="history_personal" name="history_personal"><?= htmlspecialchars($case_to_follow['history_personal'] ?? '') ?></textarea>
                </div>
                <div class="form-group">
                    <label for="history_family">ข้อมูลจากครอบครัว</label>
                    <textarea id="history_family" name="history_family"><?= htmlspecialchars($case_to_follow['history_family'] ?? '') ?></textarea>
                </div>
                <div class="form-group">
                    <label for="history_school">ข้อมูลจากโรงเรียน</label>
                    <textarea id="history_school" name="history_school"><?= htmlspecialchars($case_to_follow['history_school'] ?? '') ?></textarea>
                </div>
                <div class="form-group">
                    <label for="personal_habits">นิสัยส่วนตัว</label>
                    <textarea id="personal_habits" name="personal_habits"><?= htmlspecialchars($case_to_follow['personal_habits'] ?? '') ?></textarea>
                </div>
                <div class="form-group">
                    <label for="history_hospital">ข้อมูลจากโรงพยาบาล</label>
                    <textarea id="history_hospital" name="history_hospital"><?= htmlspecialchars($case_to_follow['history_hospital'] ?? '') ?></textarea>
                </div>
                <div class="form-group">
                    <label for="consultation_details">รายละเอียดการให้การปรึกษา</label>
                    <textarea
                        id="consultation_details"
                        name="consultation_details"><?= htmlspecialchars($case_to_follow['consultation_details'] ?? '') ?></textarea>
                </div>
                <div class="form-group">
                    <label for="event_details">รายละเอียดเหตุการณ์</label>
                    <textarea id="event_details" name="event_details"><?= htmlspecialchars($case_to_follow['event_details'] ?? '') ?></textarea>
                </div>
            </section>

            <!-- Assistance Guidelines -->
            <section class="assistance">
                <h2>แนวทางการดูแลช่วยเหลือ</h2>
                <div class="guidelines-grid">
                    <div class="form-group">
                        <label for="assist_school">1. โรงเรียน</label>
                        <textarea id="assist_school" name="assist_school"><?= htmlspecialchars($case_to_follow['assist_school'] ?? '') ?></textarea>
                    </div>
                    <div class="form-group">
                        <label for="assist_hospital">2. โรงพยาบาล</label>
                        <textarea id="assist_hospital" name="assist_hospital"><?= htmlspecialchars($case_to_follow['assist_hospital'] ?? '') ?></textarea>
                    </div>
                    <div class="form-group">
                        <label for="assist_parent">3. ผู้ปกครอง</label>
                        <textarea id="assist_parent" name="assist_parent"><?= htmlspecialchars($case_to_follow['assist_parent'] ?? '') ?></textarea>
                    </div>
                    <div class="form-group">
                        <label for="assist_other">4. หน่วยงานอื่น ๆ ที่เกี่ยวข้อง</label>
                        <textarea id="assist_other" name="assist_other"><?= htmlspecialchars($case_to_follow['assist_other'] ?? '') ?></textarea>
                    </div>
                </div>
                <div class="form-group">
                    <label for="suggestions">ข้อเสนอแนะ</label>
                    <textarea id="suggestions" name="suggestions"><?= htmlspecialchars($case_to_follow['suggestions'] ?? '') ?></textarea>
                </div>
            </section>

            <!-- Footer -->
            <section class="footer-info">
                <div class="form-row">
                    <div class="form-col">
                        <label for="recorder">ผู้บันทึกข้อมูล</label>
                        <input type="text"
                            id="recorder"
                            name="recorder"
                            value="<?= htmlspecialchars($recorder_name) ?>"
                            readonly />
                    </div>
                    <div class="form-col">
                        <label for="record_date">วันที่บันทึก (แก้ไขไม่ได้)</label>
                        <input type="text" id="record_date" name="record_date" value="<?= date('d/m/Y') ?>" readonly />
                    </div>
                </div>
            </section>

            <button type="submit" class="submit-btn">บันทึกข้อมูล</button>
        </form>
    </div>
    <script src="../public/script/javascript/form2.js"></script>
    <script>
        document.addEventListener("DOMContentLoaded", () => {
            // เติมข้อมูลลงใน Dropdown ที่สร้างโดย JS (Age, Class, Room)
            const student = <?= json_encode($student) ?>;
            if (student) {
                // Age
                if (student.age) document.getElementById('age').value = student.age;
                // Class (DB เก็บ 1, 2.. แต่ JS สร้าง value เป็น ม.1, ม.2..)
                if (student.class) document.getElementById('edu_level').value = 'ม.' + student.class;
                // Room
                if (student.room) document.getElementById('edu_room').value = student.room;
            }
        });
    </script>
</body>

</html>