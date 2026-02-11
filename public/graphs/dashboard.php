<?php
session_start();

/* ----โค้ดสำหรับตรวจสอบข้อผิดพลาด (ถ้าใช้งานได้แล้วควรลบออก)---- */
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
/* ----------------------------------------------------------- */
require_once dirname(__DIR__, 2) . '/app/core/Database.php';

// เนื่องจากคลาส Database ถูกออกแบบให้เรียกใช้ผ่าน static method ที่ชื่อว่า connect()
$conn = Database::connect();

// ดึงข้อมูลจำนวนนักเรียนแยกตามเพศที่ทำแบบประเมิน
$sql = "SELECT s.sex_name, COUNT(a.id) as count 
        FROM assessment a 
        JOIN student_data sd ON a.pid = sd.pid 
        JOIN sex s ON sd.sex = s.sex_id 
        GROUP BY s.sex_name";

$stmt = $conn->prepare($sql);
$stmt->execute();
$data_points = $stmt->fetchAll(PDO::FETCH_ASSOC);

$labels = [];
$data = [];
$colors = [];

foreach ($data_points as $point) {
    $labels[] = $point['sex_name'];
    $data[] = $point['count'];
    if ($point['sex_name'] == 'ชาย') {
        $colors[] = 'rgba(54, 162, 235, 0.8)'; // สีฟ้า
    } elseif ($point['sex_name'] == 'หญิง') {
        $colors[] = 'rgba(255, 99, 132, 0.8)'; // สีชมพู
    } else {
        $colors[] = 'RAINBOW'; // สีรุ้ง (จะถูกแทนที่ด้วย Gradient ใน JavaScript)
    }
}

$total_gender_count = array_sum($data);

// ดึงข้อมูลจำนวนนักเรียนตามช่วงคะแนน (จาก report_chart.php)
$sql_dep = "SELECT 
            SUM(CASE WHEN score <= 7 THEN 1 ELSE 0 END) as normal,
            SUM(CASE WHEN score > 7 AND score <= 13 THEN 1 ELSE 0 END) as moderate,
            SUM(CASE WHEN score > 13 THEN 1 ELSE 0 END) as severe
        FROM assessment";
$stmt_dep = $conn->query($sql_dep);
$result_dep = $stmt_dep->fetch(PDO::FETCH_ASSOC);

$dep_normal = $result_dep['normal'] ?? 0;
$dep_moderate = $result_dep['moderate'] ?? 0;
$dep_severe = $result_dep['severe'] ?? 0;

// --- ส่วนที่เพิ่มใหม่: เตรียมข้อมูลสำหรับกราฟเพิ่มเติม ---

// 1. ข้อมูลกราฟแท่งแสดงจำนวนอาการ (Case Type) แยกตามเพศ
$sql_case_type = "SELECT ac.case_type, s.sex_name, COUNT(ac.id) as count 
                  FROM add_caselog ac 
                  JOIN student_data sd ON ac.pid = sd.pid 
                  JOIN sex s ON sd.sex = s.sex_id 
                  GROUP BY ac.case_type, s.sex_name";
$stmt_ct = $conn->query($sql_case_type);
$raw_ct = $stmt_ct->fetchAll(PDO::FETCH_ASSOC);

$ct_labels = []; // ชื่ออาการ
$ct_genders = []; // เพศที่มี
$ct_map = []; // เก็บข้อมูลเพื่อ map

foreach ($raw_ct as $r) {
    if (!in_array($r['case_type'], $ct_labels)) $ct_labels[] = $r['case_type'];
    if (!in_array($r['sex_name'], $ct_genders)) $ct_genders[] = $r['sex_name'];
    $ct_map[$r['case_type']][$r['sex_name']] = $r['count'];
}

$ct_datasets = [];
$gender_colors = [
    'ชาย' => 'rgba(54, 162, 235, 0.7)',
    'หญิง' => 'rgba(255, 99, 132, 0.7)',
    'เพศทางเลือก' => 'rgba(153, 102, 255, 0.7)',
    'default' => 'rgba(75, 192, 192, 0.7)'
];

foreach ($ct_genders as $g) {
    $data = [];
    foreach ($ct_labels as $l) {
        $data[] = $ct_map[$l][$g] ?? 0;
    }
    $ct_datasets[] = [
        'label' => $g,
        'data' => $data,
        'backgroundColor' => $gender_colors[$g] ?? $gender_colors['default'],
        'borderColor' => 'rgba(255,255,255,1)',
        'borderWidth' => 1
    ];
}

// 2. ข้อมูลสถานะ (ส่งต่อ/ยุติ/ติดตาม) แยกเพศ
function getGenderCounts($conn, $sql) {
    $stmt = $conn->query($sql);
    return $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
}

// กำหนดเพศหลัก 3 เพศ เพื่อให้แสดงครบถ้วนเสมอ
$fixed_genders = ['ชาย', 'หญิง', 'เพศทางเลือก'];

$stat_forward = getGenderCounts($conn, "SELECT s.sex_name, COUNT(DISTINCT fc.pid) FROM forward_case fc JOIN student_data sd ON fc.pid = sd.pid JOIN sex s ON sd.sex = s.sex_id GROUP BY s.sex_name");
$stat_closure = getGenderCounts($conn, "SELECT s.sex_name, COUNT(DISTINCT cr.pid) FROM closure_report cr JOIN student_data sd ON cr.pid = sd.pid JOIN sex s ON sd.sex = s.sex_id GROUP BY s.sex_name");
$stat_ongoing = getGenderCounts($conn, "SELECT s.sex_name, COUNT(DISTINCT ac.pid) FROM add_caselog ac JOIN student_data sd ON ac.pid = sd.pid JOIN sex s ON sd.sex = s.sex_id WHERE ac.pid NOT IN (SELECT pid FROM forward_case) AND ac.pid NOT IN (SELECT pid FROM closure_report) GROUP BY s.sex_name");

$data_forward = [];
$data_closure = [];
$data_ongoing = [];

// Map ข้อมูลให้ตรงกับลำดับเพศที่กำหนด (เพื่อให้กราฟแสดงครบ 3 เพศเสมอ)
foreach ($fixed_genders as $g) {
    $data_forward[] = $stat_forward[$g] ?? 0;
    $data_closure[] = $stat_closure[$g] ?? 0;
    $data_ongoing[] = $stat_ongoing[$g] ?? 0;
}

// 3. ข้อมูลหน่วยงานที่ส่งต่อ
// ใช้ referral_other หาก referral_agency เป็น 'อื่นๆ' หรือว่าง
$sql_ag = "SELECT CASE WHEN referral_agency = 'อื่นๆ' OR referral_agency IS NULL OR referral_agency = '' THEN referral_other ELSE referral_agency END as agency_name, COUNT(*) as count FROM forward_case GROUP BY agency_name HAVING agency_name IS NOT NULL AND agency_name != ''";
$stmt_ag = $conn->query($sql_ag);
$raw_ag = $stmt_ag->fetchAll(PDO::FETCH_ASSOC);
$ag_labels = array_column($raw_ag, 'agency_name');
$ag_data = array_column($raw_ag, 'count');
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - PHQ System</title>
    <!-- Bootstrap CSS and JS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <!-- Global Stylesheet (for background) -->
    <link href="../css/style.css" rel="stylesheet">
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        .card {
            border: none;
            border-radius: 1rem;
            transition: all 0.2s ease-in-out;
        }
        .card:hover {
            transform: translateY(-5px);
            box-shadow: 0 0.5rem 1rem rgba(0,0,0,.15)!important;
        }
        #genderChart, #depressionChart {
            max-height: 350px;
        }
    </style>
</head>
<body>
    <?php require_once '../navbar.php'; ?>
    <div class="container mt-5">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2 class="mb-0"><i class="bi bi-bar-chart-line-fill me-2"></i>Dashboard สรุปผล</h2>
            <?php if (isset($_SESSION['user'])): ?>
                <a href="../main.php" class="btn btn-danger">↩ กลับหน้ารายชื่อ</a>
            <?php else: ?>
                <a href="../index.php" class="btn btn-danger">ย้อนกลับ</a>
            <?php endif; ?>
        </div>

        <div class="row">
            <div class="col-lg-6 mb-4">
                <div class="card shadow-sm h-100">
                    <div class="card-header bg-white border-0 pt-3">
                        <h5 class="card-title">สถิตินักเรียนที่ทำแบบประเมิน (แยกตามเพศ)</h5>
                        <p class="small text-muted mb-0">จำนวนรวมทั้งหมด: <?php echo $total_gender_count; ?> คน</p>
                    </div>
                    <div class="card-body d-flex justify-content-center align-items-center">
                        <canvas id="genderChart"></canvas>
                    </div>
                </div>
            </div>
            <div class="col-lg-6 mb-4">
                <div class="card shadow-sm h-100">
                    <div class="card-header bg-white border-0 pt-3">
                        <h5 class="card-title">ผลการประเมินภาวะซึมเศร้า</h5>
                        <p class="small text-muted mb-0">จำนวนรวมทั้งหมด: <?php echo $dep_normal + $dep_moderate + $dep_severe; ?> คน</p>
                    </div>
                    <div class="card-body">
                        <canvas id="depressionChart"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <!-- ส่วนที่เพิ่มใหม่ -->
        <div class="row">
            <!-- 1. กราฟแท่งแสดงจำนวนอาการแยกตามเพศ -->
            <div class="col-12 mb-4">
                <div class="card shadow-sm">
                    <div class="card-header bg-white border-0 pt-3"><h5 class="card-title">จำนวนอาการแยกตามประเภทและเพศ</h5></div>
                    <div class="card-body">
                        <canvas id="caseTypeChart" style="max-height: 400px;"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <!-- 2. กราฟสถานะ (Dropdown) -->
            <div class="col-lg-6 mb-4">
                <div class="card shadow-sm h-100">
                    <div class="card-header bg-white border-0 pt-3 d-flex justify-content-between align-items-center">
                        <h5 class="card-title mb-0">สถานะการติดตาม (แยกเพศ)</h5>
                        <select id="statusSelector" class="form-select form-select-sm" style="width: auto;">
                            <option value="ongoing">อยู่ระหว่างการติดตาม</option>
                            <option value="forward">ส่งต่อกรณี</option>
                            <option value="closure">ยุติการช่วยเหลือ</option>
                        </select>
                    </div>
                    <div class="card-body"><canvas id="statusChart"></canvas></div>
                </div>
            </div>
            <!-- 3. กราฟหน่วยงานที่ส่งต่อ -->
            <div class="col-lg-6 mb-4">
                <div class="card shadow-sm h-100">
                    <div class="card-header bg-white border-0 pt-3"><h5 class="card-title">สถิติการส่งต่อหน่วยงานภายนอก</h5></div>
                    <div class="card-body"><canvas id="agencyChart"></canvas></div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        const ctx = document.getElementById('genderChart').getContext('2d');
        
        // สร้างสีรุ้ง (Gradient)
        const rainbow = ctx.createLinearGradient(0, 0, 300, 300);
        rainbow.addColorStop(0, 'red');
        rainbow.addColorStop(0.2, 'orange');
        rainbow.addColorStop(0.4, 'yellow');
        rainbow.addColorStop(0.6, 'green');
        rainbow.addColorStop(0.8, 'blue');
        rainbow.addColorStop(1, 'violet');

        // รับค่าสีจาก PHP และแปลง 'RAINBOW' ให้เป็นตัวแปร gradient
        let bgColors = <?php echo json_encode($colors); ?>;
        bgColors = bgColors.map(c => c === 'RAINBOW' ? rainbow : c);

        new Chart(ctx, {
            type: 'pie',
            data: {
                labels: <?php echo json_encode($labels); ?>,
                datasets: [{
                    label: 'จำนวนนักเรียน (คน)',
                    data: <?php echo json_encode($data); ?>,
                    backgroundColor: bgColors,
                    borderWidth: 2,
                    borderColor: '#ffffff'
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        display: true,
                        position: 'bottom'
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                let value = context.parsed;
                                let total = <?php echo array_sum($data); ?>;
                                let percentage = total > 0 ? (value / total * 100).toFixed(2) : 0;
                                let prefix = (context.label === 'เพศทางเลือก') ? '' : 'เพศ';
                                return [prefix + context.label + ' : ' + value + ' คน', 'คิดเป็น ' + percentage + '% ของทั้งหมด'];
                            }
                        }
                    }
                }
            }
        });

        // กราฟภาวะซึมเศร้า (Bar Chart)
        const ctxDep = document.getElementById('depressionChart').getContext('2d');
        new Chart(ctxDep, {
            type: 'bar',
            data: {
                labels: ['ปกติ (คะแนน ≤ 7)', 'ปานกลาง (คะแนน 8-13)', 'รุนแรง (คะแนน > 13)'],
                datasets: [{
                    label: 'จำนวนนักเรียน (คน)',
                    data: [<?php echo $dep_normal; ?>, <?php echo $dep_moderate; ?>, <?php echo $dep_severe; ?>],
                    backgroundColor: [
                        'rgba(75, 192, 192, 0.6)', // สีเขียว
                        'rgba(255, 206, 86, 0.6)', // สีเหลือง
                        'rgba(255, 99, 132, 0.6)'  // สีแดง
                    ],
                    borderColor: [
                        'rgba(75, 192, 192, 1)',
                        'rgba(255, 206, 86, 1)',
                        'rgba(255, 99, 132, 1)'
                    ],
                    borderWidth: 1
                }]
            },
            options: {
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: { precision: 0 }
                    }
                },
                plugins: {
                    legend: { display: false },
                    title: {
                        display: true,
                        text: 'ผลการประเมินจาก แบบประเมินภาวะซึมเศร้าในวัยรุ่น'
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                let value = context.parsed.y;
                                let total = <?php echo ($dep_normal + $dep_moderate + $dep_severe); ?>;
                                let percentage = total > 0 ? (value / total * 100).toFixed(2) : 0;
                                return [context.label, 'คิดเป็น ' + percentage + '% ของทั้งหมด'];
                            }
                        }
                    }
                }
            }
        });

        // ส่งข้อมูลจาก PHP ไปยัง JavaScript ผ่านตัวแปร global
        const dashboardData = {
            labels: <?php echo json_encode($labels); ?>,
            data: <?php echo json_encode($data); ?>,
            colors: <?php echo json_encode($colors); ?>
        };

        // --- Script สำหรับกราฟใหม่ ---

        // 1. Case Type Chart (Stacked Bar)
        new Chart(document.getElementById('caseTypeChart'), {
            type: 'bar',
            data: {
                labels: <?php echo json_encode($ct_labels); ?>,
                datasets: <?php echo json_encode($ct_datasets); ?>
            },
            options: {
                responsive: true,
                scales: {
                    x: { stacked: true },
                    y: { stacked: true, beginAtZero: true }
                },
                plugins: {
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                return context.dataset.label + ': ' + context.raw + ' คน';
                            }
                        }
                    }
                }
            }
        });

        // 2. Status Chart (Dynamic)
        const statusDataRaw = {
            labels: <?php echo json_encode($fixed_genders); ?>,
            forward: <?php echo json_encode($data_forward); ?>,
            closure: <?php echo json_encode($data_closure); ?>,
            ongoing: <?php echo json_encode($data_ongoing); ?>
        };

        const ctxStatus = document.getElementById('statusChart').getContext('2d');
        let statusChart = new Chart(ctxStatus, {
            type: 'bar',
            data: {
                labels: statusDataRaw.labels,
                datasets: [{
                    label: 'จำนวน (คน)',
                    data: statusDataRaw.ongoing, // Default
                    backgroundColor: [
                        'rgba(54, 162, 235, 0.7)', // ชาย
                        'rgba(255, 99, 132, 0.7)', // หญิง
                        'rgba(153, 102, 255, 0.7)' // เพศทางเลือก
                    ],
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                scales: { y: { beginAtZero: true, ticks: { precision: 0 } } }
            }
        });

        document.getElementById('statusSelector').addEventListener('change', function() {
            const val = this.value;
            statusChart.data.datasets[0].data = statusDataRaw[val];
            statusChart.update();
        });

        // 3. Agency Chart
        new Chart(document.getElementById('agencyChart'), {
            type: 'bar',
            data: {
                labels: <?php echo json_encode($ag_labels); ?>,
                datasets: [{
                    label: 'จำนวนเคส',
                    data: <?php echo json_encode($ag_data); ?>,
                    backgroundColor: 'rgba(153, 102, 255, 0.6)',
                    borderColor: 'rgba(153, 102, 255, 1)',
                    borderWidth: 1
                }]
            },
            options: {
                indexAxis: 'y', // แนวนอนเพื่อให้อ่านชื่อหน่วยงานง่าย
                responsive: true,
                scales: { x: { beginAtZero: true, ticks: { precision: 0 } } },
                plugins: { legend: { display: false } }
            }
        });
    </script>
</body>
</html>