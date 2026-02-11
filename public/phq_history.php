<?php
session_start();
require_once dirname(__DIR__) . '/app/core/Database.php';

// ตรวจสอบสิทธิ์
if (!isset($_SESSION['user'])) {
    header("Location: login.php");
    exit;
}

if (isset($_GET['pid']) && !empty($_GET['pid'])) {
    $_SESSION['phq_history_pid'] = $_GET['pid'];
    header("Location: phq_history.php");
    exit;
}

if (isset($_SESSION['phq_history_pid'])) {
    $pid = $_SESSION['phq_history_pid'];
} else {
    echo '<div class="container mt-5"><div class="alert alert-danger">ไม่พบรหัสบัตรประชาชน</div></div>';
    exit;
}

$db = Database::connect();

// ดึงข้อมูลนักเรียน
$sql_student = "SELECT s.*, p.prefix_name, sc.school_name, sx.sex_name 
                FROM student_data s
                LEFT JOIN prefix p ON s.prefix_id = p.prefix_id
                LEFT JOIN school sc ON s.school_id = sc.school_id
                LEFT JOIN sex sx ON s.sex = sx.sex_id
                WHERE s.pid = :pid";
$stmt_student = $db->prepare($sql_student);
$stmt_student->execute([':pid' => $pid]);
$student = $stmt_student->fetch(PDO::FETCH_ASSOC);

if (!$student) {
    echo '<div class="container mt-5"><div class="alert alert-danger">ไม่พบข้อมูลนักเรียน</div></div>';
    exit;
}

// ดึงประวัติการประเมิน (Assessment History)
$sql_history = "SELECT * FROM assessment WHERE pid = :pid ORDER BY date_time DESC";
$stmt_history = $db->prepare($sql_history);
$stmt_history->execute([':pid' => $pid]);
$history = $stmt_history->fetchAll(PDO::FETCH_ASSOC);

function getScoreColor($score)
{
    if ($score < 7) return 'success';
    if ($score < 13) return 'warning';
    return 'danger';
}

function getScoreMeaning($score)
{
    if ($score < 7) return 'ปกติ';
    if ($score < 13) return 'ปานกลาง';
    return 'รุนแรง';
}
?>
<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ประวัติการประเมิน | PHQ System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap JS Bundle (includes Popper) -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <!-- Global Stylesheet (for background) -->
    <link href="css/style.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Sarabun:wght@400;700&display=swap" rel="stylesheet">
</head>

<body>

    <?php require_once 'navbar.php'; ?>
    <div class="container mt-5">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2>📜 ประวัติการประเมิน (PHQ-9)</h2>
            <a href="main.php" class="btn btn-danger">↩ กลับหน้าหลัก</a>
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
                <h5 class="mb-0">รายการประเมินย้อนหลัง (<?= count($history) ?> ครั้ง)</h5>
                <div class="d-flex gap-2">
                    <a href="add_case_history.php?pid=<?= htmlspecialchars($pid) ?>" class="btn btn-warning btn-sm mb-2">
                        📝 ดูรายงานการช่วยเหลือรายกรณี
                    </a>
                </div>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover table-striped mb-0 align-middle">
                        <thead class="table-light">
                            <tr>
                                <th style="width: 5%">#</th>
                                <th style="width: 15%">วันที่ประเมิน</th>
                                <th style="width: 10%" class="text-center">คะแนนรวม</th>
                                <th style="width: 15%">ผลการประเมิน</th>
                                <th style="width: 20%">สาเหตุความเครียด</th>
                                <th style="width: 20%">การจัดการความเครียด</th>
                                <th style="width: 15%" class="text-center">จัดการ</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (count($history) > 0): ?>
                                <?php foreach ($history as $index => $row): ?>
                                    <tr>
                                        <td><?= count($history) - $index ?></td>
                                        <td><?= date('d/m/Y H:i', strtotime($row['date_time'])) ?></td>
                                        <td class="text-center">
                                            <span class="badge rounded-pill bg-<?= getScoreColor($row['score']) ?> fs-6">
                                                <?= $row['score'] ?>
                                            </span>
                                        </td>
                                        <td><?= getScoreMeaning($row['score']) ?></td>
                                        <td><small><?= htmlspecialchars($row['stress'] ?? '-') ?></small></td>
                                        <td><small><?= htmlspecialchars($row['manage_stress'] ?? '-') ?></small></td>
                                        <td class="text-center text-nowrap">
                                            <a href="phq_report.php?id=<?= $row['id'] ?>" class="btn btn-sm btn-info" target="_blank">
                                                📄 ดูรายงาน
                                            </a>
                                            <form action="api/delete_assessment.php" method="POST" class="d-inline" onsubmit="return confirm('คุณต้องการลบรายงานการประเมินนี้ใช่หรือไม่?');">
                                                <input type="hidden" name="id" value="<?= $row['id'] ?>">
                                                <input type="hidden" name="pid" value="<?= htmlspecialchars($pid) ?>">
                                                <button type="submit" class="btn btn-sm btn-danger ms-1">⛔ ลบ</button>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="7" class="text-center py-4 text-muted">ยังไม่มีประวัติการประเมิน</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>