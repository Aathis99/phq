<?php
// เชื่อมต่อฐานข้อมูล
require_once dirname(__DIR__, 2) . '/app/core/Database.php';

// เตรียมตัวแปรเก็บข้อมูล
$sexLabels = ['ชาย', 'หญิง', 'เพศทางเลือก'];
$dataSets = [
    'assessment' => [0, 0, 0], // อยู่ระหว่างติดตาม
    'forward' => [0, 0, 0],    // ส่งต่อกรณี
    'closure' => [0, 0, 0]     // ยุติช่วยเหลือ
];

try {
    $db = Database::connect();

    // ฟังก์ชันสำหรับดึงข้อมูลจำนวนคนแยกตามเพศ จากตารางที่กำหนด
    function getCountBySex($db, $tableName, $year, $term) {
        $where = "";
        if ($year && $term) {
            // ตาราง assessment, forward_case, closure_report มีคอลัมน์ academic_year, semester เหมือนกัน
            $where = " WHERE t.academic_year = $year AND t.semester = $term";
        }
        $sql = "SELECT s.sex_name, COUNT(DISTINCT t.pid) as count 
                FROM $tableName t
                JOIN student_data sd ON t.pid = sd.pid
                JOIN sex s ON sd.sex = s.sex_id
                $where
                GROUP BY s.sex_name";
        $stmt = $db->query($sql);
        return $stmt->fetchAll(PDO::FETCH_KEY_PAIR); // คืนค่าเป็น [ 'ชาย' => 10, 'หญิง' => 5 ]
    }

    // รับค่า filter จาก dashboard.php (ถ้ามี)
    $f_year = isset($filter_year) ? $filter_year : null;
    $f_term = isset($filter_term) ? $filter_term : null;

    // 1. อยู่ระหว่างติดตาม (เอามาจากตาราง assessment)
    $rawAssessment = getCountBySex($db, 'assessment', $f_year, $f_term);
    
    // 2. ส่งต่อกรณี (เอามาจากตาราง forward_case)
    $rawForward = getCountBySex($db, 'forward_case', $f_year, $f_term);

    // 3. ยุติช่วยเหลือ (เอามาจากตาราง closure_report)
    $rawClosure = getCountBySex($db, 'closure_report', $f_year, $f_term);

    // จัดเรียงข้อมูลให้ตรงกับ Index ของ $sexLabels
    foreach ($sexLabels as $index => $sex) {
        $dataSets['assessment'][$index] = isset($rawAssessment[$sex]) ? (int)$rawAssessment[$sex] : 0;
        $dataSets['forward'][$index] = isset($rawForward[$sex]) ? (int)$rawForward[$sex] : 0;
        $dataSets['closure'][$index] = isset($rawClosure[$sex]) ? (int)$rawClosure[$sex] : 0;
    }

} catch (PDOException $e) {
    echo "<div class='alert alert-danger'>เกิดข้อผิดพลาด: " . $e->getMessage() . "</div>";
}
?>

<div class="dashboard-card">
    <div class="card-header-custom d-flex justify-content-between align-items-center">
        <span>สถานะการติดตาม (แยกตามเพศ)</span>
        <select id="followStatusFilter" class="form-select form-select-sm" style="width: auto;">
            <option value="assessment">อยู่ระหว่างติดตาม</option>
            <option value="forward">ส่งต่อกรณี</option>
            <option value="closure">ยุติช่วยเหลือ</option>
        </select>
    </div>
    <div style="position: relative; height: 300px; width: 100%; display: flex; justify-content: center;">
        <canvas id="followStatusChart"></canvas>
    </div>
</div>

<script>
document.addEventListener("DOMContentLoaded", function() {
    const ctxFollow = document.getElementById('followStatusChart').getContext('2d');
    
    // ข้อมูลจาก PHP ส่งมาเป็น JSON
    const allData = <?php echo json_encode($dataSets); ?>;
    const labels = <?php echo json_encode($sexLabels); ?>;
    
    // สีของแท่งกราฟ (ชาย, หญิง, ทางเลือก)
    const barColors = ['#36A2EB', '#FF6384', '#FFCE56'];

    // สร้างกราฟเริ่มต้น (ค่า Default คือ assessment)
    const followChart = new Chart(ctxFollow, {
        type: 'bar',
        data: {
            labels: labels,
            datasets: [{
                label: 'จำนวนคน',
                data: allData['assessment'],
                backgroundColor: barColors,
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false }, // ซ่อน Legend เพราะแกน X บอกเพศแล้ว
                title: {
                    display: true,
                    text: 'ข้อมูล: อยู่ระหว่างติดตาม' // หัวข้อเริ่มต้น
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: { precision: 0 }
                }
            }
        },
        plugins: [{
            id: 'showDataLabels',
            afterDatasetsDraw: function(chart, args, options) {
                const { ctx } = chart;
                chart.data.datasets.forEach((dataset, i) => {
                    const meta = chart.getDatasetMeta(i);
                    meta.data.forEach((bar, index) => {
                        const data = dataset.data[index];
                        if (data > 0) {
                            ctx.fillStyle = '#444';
                            ctx.font = 'bold 14px sans-serif';
                            ctx.textAlign = 'center';
                            ctx.fillText(data, bar.x, bar.y - 5);
                        }
                    });
                });
            }
        }]
    });

    // เมื่อมีการเลือก Dropdown ให้เปลี่ยนข้อมูลในกราฟ
    document.getElementById('followStatusFilter').addEventListener('change', function() {
        const selectedType = this.value;
        const selectedText = this.options[this.selectedIndex].text;

        followChart.data.datasets[0].data = allData[selectedType];
        followChart.options.plugins.title.text = 'ข้อมูล: ' + selectedText;
        followChart.update();
    });
});
</script>