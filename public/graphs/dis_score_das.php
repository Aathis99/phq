<?php
// เชื่อมต่อฐานข้อมูล (อ้างอิง path กลับไปที่ app/core/Database.php)
require_once dirname(__DIR__, 2) . '/app/core/Database.php';

$scoreData = [0, 0, 0];
$scoreLabels = ['ปกติ (≤ 7)', 'ปานกลาง (8-13)', 'รุนแรง (> 13)'];

try {
    $db = Database::connect();
    
    // Query เพื่อนับจำนวนนักเรียนตามช่วงคะแนน
    // ใช้ CASE WHEN ในการจัดกลุ่มคะแนน
    $where_clause = "";
    if (isset($filter_year) && isset($filter_term)) {
        $where_clause = " WHERE academic_year = $filter_year AND semester = $filter_term";
    }

    $sql = "SELECT 
                SUM(CASE WHEN score <= 7 THEN 1 ELSE 0 END) as normal_count,
                SUM(CASE WHEN score >= 8 AND score <= 13 THEN 1 ELSE 0 END) as moderate_count,
                SUM(CASE WHEN score > 13 THEN 1 ELSE 0 END) as severe_count
            FROM assessment" . $where_clause;
            
    $stmt = $db->query($sql);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($result) {
        $scoreData[0] = (int)$result['normal_count'];
        $scoreData[1] = (int)$result['moderate_count'];
        $scoreData[2] = (int)$result['severe_count'];
    }

} catch (PDOException $e) {
    echo "<div class='alert alert-danger'>เกิดข้อผิดพลาดในการดึงข้อมูล: " . $e->getMessage() . "</div>";
}
?>

<div class="dashboard-card">
    <div class="card-header-custom">
        ผลการประเมินภาวะซึมเศร้า (แยกตามระดับความรุนแรง)
    </div>
    <div style="position: relative; height: 300px; width: 100%; display: flex; justify-content: center;">
        <canvas id="scoreLevelChart"></canvas>
    </div>
</div>

<script>
document.addEventListener("DOMContentLoaded", function() {
    const ctxScore = document.getElementById('scoreLevelChart').getContext('2d');
    
    const scoreLabels = <?php echo json_encode($scoreLabels); ?>;
    const scoreData = <?php echo json_encode($scoreData); ?>;
    
    // สีสำหรับกราฟ (เขียว, เหลือง, แดง)
    const backgroundColorsScore = [
        '#4BC0C0', // ปกติ - สีเขียว
        '#FFCE56', // ปานกลาง - สีเหลือง
        '#FF6384'  // รุนแรง - สีแดง
    ];

    new Chart(ctxScore, {
        type: 'bar', // กราฟแท่ง
        data: {
            labels: scoreLabels,
            datasets: [{
                label: 'จำนวนนักเรียน',
                data: scoreData,
                backgroundColor: backgroundColorsScore,
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false } // ซ่อน Legend เพราะแกน X บอกชัดเจนแล้ว
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: { 
                        precision: 0 // บังคับให้แสดงเป็นจำนวนเต็ม ไม่แสดงทศนิยม
                    } 
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
                        if (data > 0) { // แสดงตัวเลขเฉพาะค่าที่มากกว่า 0
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
});
</script>