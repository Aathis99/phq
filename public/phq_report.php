<?php
session_start();
require_once dirname(__DIR__) . '/app/core/Database.php';

if (!isset($_GET['id'])) {
    die("ไม่พบรหัสการประเมิน");
}

$id = $_GET['id'];
$db = Database::connect();

// ดึงข้อมูลการประเมิน เชื่อมกับ ข้อมูลนักเรียน
$sql = "SELECT a.*, s.pid, s.fname, s.lname, s.age, s.class, s.room, s.tel,
               p.prefix_name, sc.school_name, sx.sex_name
        FROM assessment a
        LEFT JOIN student_data s ON a.pid = s.pid
        LEFT JOIN prefix p ON s.prefix_id = p.prefix_id
        LEFT JOIN school sc ON s.school_id = sc.school_id
        LEFT JOIN sex sx ON s.sex = sx.sex_id
        WHERE a.id = :id";

$stmt = $db->prepare($sql);
$stmt->execute([':id' => $id]);
$data = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$data) {
    die("ไม่พบข้อมูลรายงาน");
}

// ดึงคำถาม PHQ-9 เพื่อมาแสดงคู่กับคำตอบ
$sql_q = "SELECT * FROM phq_question ORDER BY id";
$stmt_q = $db->query($sql_q);
$questions = $stmt_q->fetchAll(PDO::FETCH_ASSOC);

// ฟังก์ชันแปลงค่าคะแนนเป็นข้อความ
function getAnswerText($val)
{
    if ($val === null || $val === '') return '<span class="text-muted">-</span>';
    switch ($val) {
        case 0:
            return 'ไม่มีเลย (0)';
        case 1:
            return 'เป็นบางวัน (1)';
        case 2:
            return 'เป็นบ่อย (2)';
        case 3:
            return 'เป็นทุกวัน (3)';
        default:
            return $val;
    }
}

function getYesNoText($val)
{
    if ($val === null || $val === '') return '<span class="text-muted">-</span>';
    return $val == 1 ? '<span class="text-danger">มี/เคย</span>' : 'ไม่มี/ไม่เคย';
}
?>
<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>รายงานผลการประเมิน | PHQ System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Sarabun:wght@400;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Sarabun', sans-serif;
            background-color: #f8f9fa;
        }

        .report-container {
            max-width: 900px;
            margin: 30px auto;
            background: #fff;
            padding: 40px;
            border-radius: 8px;
            box-shadow: 0 0 15px rgba(0, 0, 0, 0.1);
        }

        .header-title {
            text-align: center;
            margin-bottom: 30px;
            color: #0d6efd;
        }

        .section-title {
            border-bottom: 2px solid #eee;
            padding-bottom: 10px;
            margin-bottom: 20px;
            margin-top: 30px;
            font-weight: bold;
            color: #555;
        }

        .info-label {
            font-weight: bold;
            color: #666;
        }

        .question-row {
            border-bottom: 1px solid #f0f0f0;
            padding: 10px 0;
        }

        .question-row:last-child {
            border-bottom: none;
        }

        .score-summary {
            background-color: #f1f8ff;
            padding: 15px;
            border-radius: 5px;
            text-align: center;
            margin-top: 20px;
        }

        @media print {
            .no-print {
                display: none;
            }

            .report-container {
                box-shadow: none;
                padding: 0;
            }
        }
    </style>
</head>

<body>

    <div class="container">
        <div class="report-container">
            <h3 class="header-title">แบบรายงานผลการประเมินภาวะซึมเศร้า (PHQ-9)</h3>
            <div class="text-center text-muted mb-4">
                วันที่ประเมิน: <?= date('d/m/Y เวลา H:i น.', strtotime($data['date_time'])) ?>
            </div>

            <!-- ส่วนที่ 1: ข้อมูลนักเรียน -->
            <div class="section-title">1. ข้อมูลทั่วไป</div>
            <div class="row mb-3">
                <div class="col-md-6 mb-2">
                    <span class="info-label">ชื่อ-นามสกุล:</span>
                    <?= htmlspecialchars(($data['prefix_name'] ?? '') . ' ' . ($data['fname'] ?? '-') . ' ' . ($data['lname'] ?? '-')) ?>
                </div>
                <!-- <div class="col-md-6 mb-2">
                <span class="info-label">เลขบัตรประชาชน:</span> <?= htmlspecialchars($data['pid']) ?>
            </div> -->
                <div class="col-md-6 mb-2">
                    <span class="info-label">โรงเรียน:</span> <?= htmlspecialchars($data['school_name'] ?? '-') ?>
                </div>
                <div class="col-md-3 mb-2">
                    <span class="info-label">ระดับชั้น: </span> <?= htmlspecialchars($data['class'] ?? '-') ?>/<?= htmlspecialchars($data['room'] ?? '-') ?>
                </div>
                <div class="col-md-3 mb-2">
                    <span class="info-label">อายุ:</span> <?= htmlspecialchars($data['age'] ?? '-') ?> ปี
                </div>
                <div class="col-md-6 mb-2">
                    <span class="info-label">เพศ:</span> <?= htmlspecialchars($data['sex_name'] ?? '-') ?>
                </div>
                <div class="col-md-6 mb-2">
                    <span class="info-label">เบอร์โทร:</span> <?= htmlspecialchars($data['tel'] ?? '-') ?>
                </div>
            </div>

            <!-- ส่วนที่ 2: ผลการประเมิน -->
            <div class="section-title">2. ผลการประเมิน (PHQ-9)</div>
            <div class="mb-4">
                <?php
                // แสดงคำถามข้อ 1-9
                foreach ($questions as $q) {
                    if ($q['id'] <= 9) {
                        $key = 'c' . $q['id'];
                        $answer = $data[$key];
                ?>
                        <div class="row question-row">
                            <div class="col-md-8">
                                <?= $q['id'] ?>. <?= htmlspecialchars($q['question']) ?>
                            </div>
                            <div class="col-md-4 text-end fw-bold text-primary">
                                <?= getAnswerText($answer) ?>
                            </div>
                        </div>
                <?php
                    }
                }
                ?>
            </div>

            <div class="score-summary">
                <h4>คะแนนรวม: <span class="text-primary"><?= $data['score'] ?? 0 ?></span> คะแนน</h4>
            </div>

            <!-- ส่วนที่ 3: คำถามเพิ่มเติม -->
            <div class="section-title">3. ข้อมูลเพิ่มเติม</div>
            <div class="mb-3">
                <strong>10. ความคิดอยากตายหรือไม่อยากมีชีวิตอยู่ (ใน 1 เดือนที่ผ่านมา):</strong><br>
                <?= getYesNoText($data['c10']) ?>
            </div>
            <div class="mb-3">
                <strong>11. เคยพยายามฆ่าตัวตาย (ตลอดชีวิตที่ผ่านมา):</strong><br>
                <?= getYesNoText($data['c11']) ?>
            </div>
            <div class="mb-3">
                <strong>12. สาเหตุความเครียด:</strong><br>
                <div class="p-2 bg-light border rounded"><?= htmlspecialchars($data['stress'] ?? '-') ?></div>
            </div>
            <div class="mb-3">
                <strong>13. การจัดการความเครียด:</strong><br>
                <div class="p-2 bg-light border rounded"><?= htmlspecialchars($data['manage_stress'] ?? '-') ?></div>
            </div>

            <div class="d-flex justify-content-center gap-3 no-print mt-4">
                <a href="javascript:window.close()" class="btn btn-danger">ปิดหน้าต่าง</a>
                <button onclick="window.print()" class="btn btn-primary">🖨️ พิมพ์รายงาน</button>
            </div>

        </div>
    </div>

</body>

</html>