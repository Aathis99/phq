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

// --- 5. ดึงประวัติการช่วยเหลือ (add_caselog) เพื่อแสดงในหน้า report ---
$caseLogs = [];
if (!empty($pid)) {
    $stmtHistory = $db->prepare("SELECT * FROM add_caselog WHERE pid = :pid ORDER BY report_date DESC, created_at DESC");
    $stmtHistory->execute([':pid' => $pid]);
    $caseLogs = $stmtHistory->fetchAll(PDO::FETCH_ASSOC);

    // 6. ดึงข้อมูลรูปภาพประกอบ (ถ้ามี)
    $caseImages = [];
    if (!empty($caseLogs)) {
        $caseIds = array_column($caseLogs, 'id');
        $placeholders = implode(',', array_fill(0, count($caseIds), '?'));
        $stmtImages = $db->prepare("SELECT * FROM images WHERE case_id IN ($placeholders)");
        $stmtImages->execute($caseIds);
        while ($row = $stmtImages->fetch(PDO::FETCH_ASSOC)) {
            $caseImages[$row['case_id']][] = $row['file_name'];
        }
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
        <a href="add_case_history.php?pid=<?= htmlspecialchars($pid) ?>" class="btn btn-danger">← ย้อนกลับ</a>
    </div>
    <div class="container container-form">
        <h2>แบบรายงานการยุติให้การดูแลช่วยเหลือรายกรณี</h2>

        <form action="save_closure.php" method="post">
            <input type="hidden" name="pid" value="<?= htmlspecialchars($pid) ?>">
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
                        <?php 
                        // ดึงเฉพาะตัวเลขจากข้อมูลใน DB (เช่น "ม.1" -> 1, "1" -> 1) เพื่อให้เทียบกับ $i ได้ถูกต้อง
                        $db_class = isset($student['class']) ? (int)preg_replace('/[^0-9]/', '', $student['class']) : 0;
                        
                        for ($i = 1; $i <= 6; $i++): 
                        ?>
                            <option value="<?= $i ?>" <?= ($db_class == $i) ? 'selected' : '' ?>>ม.<?= $i ?></option>
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
                    <input type="date" name="report_date" class="form-control" value="<?= date('Y-m-d') ?>" required>
                </div>
            </div>

            <!-- ส่วนแสดงประวัติการช่วยเหลือ -->
            <div class="card mb-4 border-secondary">
                <div class="card-header text-white d-flex justify-content-between align-items-center" style="background: linear-gradient(90deg, #ff9a9e, #a18cd1);">
                    <h5 class="mb-0">ประวัติการช่วยเหลือ (Case History)</h5>
                    <div class="d-flex align-items-center">
                        <label class="me-2 text-white text-nowrap">เลือกดูครั้งที่:</label>
                        <select id="historyFilter" class="form-select form-select-sm">
                            <option value="all">-- แสดงทั้งหมด --</option>
                            <?php foreach ($caseLogs as $index => $log): ?>
                                <option value="<?= $log['id'] ?>">ครั้งที่ <?= count($caseLogs) - $index ?> (<?= date('d/m/Y', strtotime($log['report_date'])) ?>)</option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive" style="max-height: 250px; overflow-y: auto;">
                        <table class="table table-hover mb-0">
                            <thead class="table-light" style="position: sticky; top: 0; z-index: 5;">
                                <tr>
                                    <th>ครั้งที่</th>
                                    <th>วันที่</th>
                                    <th>ประเภท</th>
                                    <th>อาการนำ</th>
                                    <th>จัดการ</th>
                                </tr>
                            </thead>
                            <tbody id="historyTableBody">
                                <?php if (count($caseLogs) > 0): ?>
                                    <?php foreach ($caseLogs as $index => $log): ?>
                                        <tr class="case-row" data-id="<?= $log['id'] ?>">
                                            <td><?= count($caseLogs) - $index ?></td>
                                            <td><?= date('d/m/Y', strtotime($log['report_date'])) ?></td>
                                            <td><span class="badge bg-info text-dark"><?= htmlspecialchars($log['case_type']) ?></span></td>
                                            <td><small><?= htmlspecialchars(mb_strimwidth($log['presenting_symptoms'], 0, 50, '...')) ?></small></td>
                                            <td>
                                                <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#viewCaseModal<?= $log['id'] ?>">
                                                    ดูข้อมูล
                                                </button>
                                            </td>
                                        </tr>
                                        
                                        <!-- Modal แสดงรายละเอียด -->
                                        <div class="modal fade" id="viewCaseModal<?= $log['id'] ?>" tabindex="-1" aria-hidden="true">
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
                                                        <?php if (!empty($caseImages[$log['id']])): ?>
                                                            <div class="mt-3">
                                                                <strong>รูปภาพประกอบ:</strong>
                                                                <div class="d-flex flex-wrap gap-2 mt-2">
                                                                    <?php foreach ($caseImages[$log['id']] as $img): ?>
                                                                        <a href="uploads/cases/<?= htmlspecialchars($img) ?>" target="_blank">
                                                                            <img src="uploads/cases/<?= htmlspecialchars($img) ?>" class="rounded border shadow-sm" style="width: 100px; height: 100px; object-fit: cover;">
                                                                        </a>
                                                                    <?php endforeach; ?>
                                                                </div>
                                                            </div>
                                                        <?php endif; ?>
                                                    </div>
                                                    <div class="modal-footer">
                                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">ปิด</button>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr><td colspan="5" class="text-center text-muted py-3">ไม่พบประวัติการช่วยเหลือ</td></tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
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
                        class="form-control bg-light"
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

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
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

            // Script สำหรับ Filter ประวัติการช่วยเหลือ
            const historyFilter = document.getElementById('historyFilter');
            if(historyFilter){
                historyFilter.addEventListener('change', function() {
                    const selectedId = this.value;
                    const rows = document.querySelectorAll('.case-row');
                    rows.forEach(row => {
                        if (selectedId === 'all' || row.dataset.id === selectedId) {
                            row.style.display = '';
                        } else {
                            row.style.display = 'none';
                        }
                    });
                });
            }
        });
    </script>

</body>

</html>