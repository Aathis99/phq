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

function toThaiDate($dateTimeStr)
{
    if (!$dateTimeStr) return '-';
    $timestamp = strtotime($dateTimeStr);
    $thai_months = [
        1 => 'มกราคม', 2 => 'กุมภาพันธ์', 3 => 'มีนาคม', 4 => 'เมษายน',
        5 => 'พฤษภาคม', 6 => 'มิถุนายน', 7 => 'กรกฎาคม', 8 => 'สิงหาคม',
        9 => 'กันยายน', 10 => 'ตุลาคม', 11 => 'พฤศจิกายน', 12 => 'ธันวาคม'
    ];
    $day = date('d', $timestamp);
    $month = $thai_months[(int)date('m', $timestamp)];
    $year = (int)date('Y', $timestamp) + 543;
    $time = date('H:i', $timestamp);
    return "$day $month พ.ศ. $year เวลา $time น.";
}
?>
<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>รายงานผลการประเมิน | PHQ System</title>
    <!-- SweetAlert2 CSS (optional, but good practice if customizing) -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap JS Bundle (includes Popper) -->
    <!-- Global Stylesheet (for background) -->
    <link href="css/style.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Sarabun:wght@400;700&display=swap" rel="stylesheet">
    <style>
        @page {
            size: A4;
            margin: 1.5cm;
        }

        body {
            font-family: 'Sarabun', sans-serif;
        }

        .report-container {
            max-width: 210mm;
            /* A4 width */
            min-height: 297mm;
            /* A4 height */
            margin: 2rem auto;
            background: #fff;
            padding: 2.5cm 2cm;
            /* Padding for content inside A4 */
            box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
            border: 1px solid #dee2e6;
        }

        .report-header {
            text-align: center;
            border-bottom: 2px solid #333;
            padding-bottom: 15px;
            margin-bottom: 30px;
        }

        .report-header h3 {
            margin: 0;
            font-weight: 700;
            color: #212529;
        }

        .section-title {
            font-size: 1.2rem;
            font-weight: 700;
            color: #0d6efd;
            border-bottom: 1px solid #0d6efd;
            padding-bottom: 8px;
            margin-top: 2rem;
            margin-bottom: 1.5rem;
        }

        .info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 0.5rem 1rem;
        }

        .info-item .label {
            font-weight: 600;
            color: #495057;
            min-width: 100px;
            display: inline-block;
        }

        .list-group-item {
            border: none;
            border-bottom: 1px solid #f0f0f0;
            padding: 0.75rem 0;
        }

        .list-group-item:last-child {
            border-bottom: none;
        }

        .score-summary {
            background-color: #e9f5ff;
            border: 1px solid #bde0fe;
            padding: 1.5rem;
            border-radius: 0.5rem;
            text-align: center;
            margin-top: 2rem;
        }

        .score-summary h4 {
            margin: 0;
            font-size: 1.5rem;
        }

        .score-summary .score-value {
            font-size: 2.5rem;
            font-weight: 700;
            color: #0d6efd;
        }

        .additional-info-item {
            background-color: #f8f9fa;
            border-left: 4px solid #ced4da;
            padding: 1rem;
            margin-bottom: 1rem;
        }

        .section-additional-questions {
            page-break-before: always; /* บังคับให้ส่วนนี้ขึ้นหน้าใหม่เสมอเมื่อพิมพ์ */
        }

        @media print {
            body {
                background-color: #fff;
            }

            .no-print {
                display: none !important;
            }

            .report-container {
                box-shadow: none;
                border: none;
                padding: 0;
                margin: 0;
                max-width: 100%;
                min-height: auto;
            }
        }
    </style>
</head>

<body>
    <div class="container-fluid">
        <div class="d-flex justify-content-center gap-3 my-3 no-print">
            <a href="javascript:window.close()" class="btn btn-danger btn-lg">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-x-circle-fill me-2" viewBox="0 0 16 16">
                    <path d="M16 8A8 8 0 1 1 0 8a8 8 0 0 1 16 0M5.354 4.646a.5.5 0 1 0-.708.708L7.293 8l-2.647 2.646a.5.5 0 0 0 .708.708L8 8.707l2.646 2.647a.5.5 0 0 0 .708-.708L8.707 8l2.647-2.646a.5.5 0 0 0-.708-.708L8 7.293z" />
                </svg>
                ปิดหน้าต่าง
            </a>
            <button onclick="window.print()" class="btn btn-primary btn-lg">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-printer-fill me-2" viewBox="0 0 16 16">
                    <path d="M5 1a2 2 0 0 0-2 2v1h10V3a2 2 0 0 0-2-2zm6 8H5a1 1 0 0 0-1 1v3a1 1 0 0 0 1 1h6a1 1 0 0 0 1-1v-3a1 1 0 0 0-1-1" />
                    <path d="M0 7a2 2 0 0 1 2-2h12a2 2 0 0 1 2 2v3a2 2 0 0 1-2 2h-1v-2a2 2 0 0 0-2-2H5a2 2 0 0 0-2 2v2H2a2 2 0 0 1-2-2zm2.5 1a.5.5 0 1 0 0-1 .5.5 0 0 0 0 1" />
                </svg>
                พิมพ์รายงาน
            </button>
        </div>

        <div class="report-container">
            <header class="report-header">
                <h3>แบบรายงานผลการประเมินภาวะซึมเศร้า (PHQ-9)</h3>
                <p class="text-muted mb-0">วันที่ประเมิน: <?= toThaiDate($data['date_time']) ?></p>
            </header>

            <main>
                <!-- ส่วนที่ 1: ข้อมูลนักเรียน -->
                <section>
                    <h4 class="section-title">1. ข้อมูลทั่วไป</h4>
                    <div class="info-grid">
                        <div class="info-item">
                            <span class="label">ชื่อ-นามสกุล:</span>
                            <?= htmlspecialchars(($data['prefix_name'] ?? '') . ' ' . ($data['fname'] ?? '-') . ' ' . ($data['lname'] ?? '-')) ?>
                        </div>
                        <div class="info-item">
                            <span class="label">โรงเรียน:</span>
                            <?= htmlspecialchars($data['school_name'] ?? '-') ?>
                        </div>
                        <div class="info-item">
                            <span class="label">ระดับชั้น:</span>
                            <?= htmlspecialchars($data['class'] ?? '-') ?>/<?= htmlspecialchars($data['room'] ?? '-') ?>
                        </div>
                        <div class="info-item">
                            <span class="label">อายุ:</span>
                            <?= htmlspecialchars($data['age'] ?? '-') ?> ปี
                        </div>
                        <div class="info-item">
                            <span class="label">เพศ:</span>
                            <?= htmlspecialchars($data['sex_name'] ?? '-') ?>
                        </div>
                        <div class="info-item">
                            <span class="label">เบอร์โทร:</span>
                            <?= htmlspecialchars($data['tel'] ?? '-') ?>
                        </div>
                    </div>
                </section>

                <!-- ส่วนที่ 2: ผลการประเมิน -->
                <section>
                    <h4 class="section-title">2. ผลการประเมิน (PHQ-9)</h4>
                    <p class="text-muted">"ในช่วง 2 สัปดาห์ที่ผ่านมา รวมทั้งวันนี้ ท่านมีอาการเหล่านี้บ่อยแค่ไหน"</p>
                    <ul class="list-group list-group-flush">
                        <?php
                        foreach ($questions as $q) {
                            if ($q['id'] <= 9) {
                                $key = 'c' . $q['id'];
                                $answer = $data[$key];
                        ?>
                                <li class="list-group-item d-flex justify-content-between align-items-center">
                                    <span><?= $q['id'] ?>. <?= htmlspecialchars($q['question']) ?></span>
                                    <span class="badge bg-primary rounded-pill fs-6 fw-normal"><?= getAnswerText($answer) ?></span>
                                </li>
                        <?php
                            }
                        }
                        ?>
                    </ul>


                </section>

                <!-- ส่วนที่ 3: คำถามเพิ่มเติม -->
                <section class="section-additional-questions">
                    <h4 class="section-title">3. ข้อมูลเพิ่มเติม</h4>
                    <div class="additional-info-item">
                        <strong>10. ความคิดอยากตายหรือไม่อยากมีชีวิตอยู่ (ใน 1 เดือนที่ผ่านมา):</strong>
                        <span class="float-end fw-bold"><?= getYesNoText($data['c10']) ?></span>
                    </div>
                    <div class="additional-info-item">
                        <strong>11. เคยพยายามฆ่าตัวตาย (ตลอดชีวิตที่ผ่านมา):</strong>
                        <span class="float-end fw-bold"><?= getYesNoText($data['c11']) ?></span>
                    </div>
                    <div class="additional-info-item">
                        <strong>12. สาเหตุความเครียด:</strong>
                        <p class="mb-0 mt-1 fst-italic">"<?= htmlspecialchars($data['stress'] ?? '-') ?>"</p>
                    </div>
                    <div class="additional-info-item">
                        <strong>13. การจัดการความเครียด:</strong>
                        <p class="mb-0 mt-1 fst-italic">"<?= htmlspecialchars($data['manage_stress'] ?? '-') ?>"</p>
                    </div>

                    <div class="score-summary">
                        <h4 class="mb-2">คะแนนรวม</h4>
                        <div class="score-value"><?= $data['score'] ?? 0 ?></div>
                    </div>
                </section>
            </main>
        </div>
    </div>

    <!-- SweetAlert2 JS -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="script/javascript/sweetalert_utils.js"></script>
</body>

</html>