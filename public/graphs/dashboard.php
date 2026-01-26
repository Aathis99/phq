<?php
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
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard</title>
    <!-- Bootstrap CSS and JS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body class="bg-light p-4">
    <div class="container">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1>PHQ Dashboard</h1>
            <a href="../index.php" class="btn btn-primary">📊 กลับไปหน้าหลัก</a>
        </div>

        <div class="row">
            <div class="col-lg-6 mb-4">
                <div class="card shadow-sm h-100">
                    <div class="card-body">
                        <h3 class="card-title text-center mb-4">สถิตินักเรียนที่ทำแบบประเมินแยกตามเพศ</h3>
                        <canvas id="genderChart"></canvas>
                    </div>
                </div>
            </div>
            <div class="col-lg-6 mb-4">
                <div class="card shadow-sm h-100">
                    <div class="card-body">
                        <h3 class="card-title text-center mb-4">กราฟแสดงจำนวนนักเรียนที่มีภาวะซึมเศร้า</h3>
                        <p class="text-center small text-muted">จำนวนรวมทั้งหมด: <?php echo $dep_normal + $dep_moderate + $dep_severe; ?> คน</p>
                        <canvas id="depressionChart"></canvas>
                    </div>
                </div>
            </div>
        </div>
    </div>

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
    </script>
    <script src="dashboard.js"></script>
</body>
</html>