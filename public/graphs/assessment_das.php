<?php
// เชื่อมต่อฐานข้อมูล (อ้างอิง path กลับไปที่ app/core/Database.php)
require_once dirname(__DIR__, 2) . '/app/core/Database.php';

$assessmentData = [];
$assessmentLabels = [];

try {
    $db = Database::connect();
    
    // Query เพื่อนับจำนวนนักเรียนที่ทำแบบประเมิน แยกตามเพศ
    // Join ตาราง assessment -> student_data -> sex
    $sql = "SELECT s.sex_name, COUNT(a.id) as count 
            FROM assessment a 
            JOIN student_data sd ON a.pid = sd.pid 
            JOIN sex s ON sd.sex = s.sex_id";
            
    if (isset($filter_year) && isset($filter_term)) {
        $sql .= " WHERE a.academic_year = $filter_year AND a.semester = $filter_term";
    }
    $sql .= " 
            GROUP BY s.sex_id, s.sex_name";
            
    $stmt = $db->query($sql);
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($results as $row) {
        $assessmentLabels[] = $row['sex_name'];
        $assessmentData[] = $row['count'];
    }

} catch (PDOException $e) {
    echo "<div class='alert alert-danger'>เกิดข้อผิดพลาดในการดึงข้อมูล: " . $e->getMessage() . "</div>";
}
?>

<div class="dashboard-card">
    <div class="card-header-custom">
        สถิตินักเรียนที่ทำแบบประเมิน (แยกตามเพศ)
    </div>
    <div style="position: relative; height: 300px; width: 100%; display: flex; justify-content: center;">
        <canvas id="assessmentSexChart"></canvas>
    </div>
</div>

<script>
document.addEventListener("DOMContentLoaded", function() {
    const ctx = document.getElementById('assessmentSexChart').getContext('2d');
    
    // ข้อมูลจาก PHP
    const labels = <?php echo json_encode($assessmentLabels); ?>;
    const data = <?php echo json_encode($assessmentData); ?>;
    
    // สีสำหรับกราฟ (ชาย=ฟ้า, หญิง=ชมพู, ทางเลือก=เหลือง)
    const backgroundColors = [
        '#36A2EB', // สีฟ้า
        '#FF6384', // สีชมพู
        '#FFCE56', // สีเหลือง
        '#4BC0C0'  // สีเขียว (เผื่อมีเพิ่ม)
    ];

    new Chart(ctx, {
        type: 'pie',
        data: {
            labels: labels,
            datasets: [{
                data: data,
                backgroundColor: backgroundColors,
                hoverOffset: 4
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'bottom',
                    labels: { font: { size: 14 } }
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            let label = context.label || '';
                            if (label) { label += ': '; }
                            let value = context.raw;
                            let total = context.chart._metasets[context.datasetIndex].total;
                            let percentage = Math.round((value / total) * 100) + '%';
                            return label + value + ' คน (' + percentage + ')';
                        }
                    }
                }
            }
        }
    });
});
</script>