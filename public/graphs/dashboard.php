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
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body class="bg-light p-4">
    <div class="container">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1>PHQ Dashboard</h1>
            <a href="../index.php" class="btn btn-primary">📊 กลับไปหน้าหลัก</a>
        </div>

        <div class="row justify-content-center">
            <div class="col-lg-8 col-md-10">
                <div class="card shadow-sm">
                    <div class="card-body">
                        <h3 class="card-title text-center mb-4">สถิตินักเรียนที่ทำแบบประเมินแยกตามเพศ</h3>
                        <canvas id="genderChart"></canvas>
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