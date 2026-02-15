<?php
// เชื่อมต่อฐานข้อมูล
require_once dirname(__DIR__, 2) . '/app/core/Database.php';

$agencyLabels = [];
$datasets = [];

try {
    $db = Database::connect();
    
    // Query ดึงข้อมูลการส่งต่อ
    // ใช้ CASE WHEN: ถ้า referral_agency เป็น 'อื่นๆ' ให้ใช้ค่าจาก referral_other แทน
    $sql = "SELECT 
                CASE 
                    WHEN f.referral_agency = 'อื่นๆ' THEN f.referral_other 
                    ELSE f.referral_agency 
                END as agency_name,
                x.sex_name,
                COUNT(f.id) as count
            FROM forward_case f
            JOIN student_data s ON f.pid = s.pid
            JOIN sex x ON s.sex = x.sex_id
            WHERE f.referral_agency IS NOT NULL AND f.referral_agency != ''";

    if (isset($filter_year) && isset($filter_term)) {
        $sql .= " AND f.academic_year = $filter_year AND f.semester = $filter_term";
    }
    $sql .= " 
            GROUP BY agency_name, x.sex_name
            ORDER BY count DESC";
            
    $stmt = $db->query($sql);
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // จัดเตรียมข้อมูลสำหรับ Chart.js
    $agencies = [];
    $sexes = ['ชาย', 'หญิง', 'เพศทางเลือก'];
    $dataMap = [];

    foreach ($results as $row) {
        // ถ้าชื่อหน่วยงานว่าง (กรณีอื่นๆ แต่ไม่ได้ระบุ) ให้ใช้คำว่า 'ไม่ระบุ'
        $agency = !empty($row['agency_name']) ? $row['agency_name'] : 'อื่นๆ (ไม่ระบุ)';
        $sex = $row['sex_name'];
        
        if (!in_array($agency, $agencies)) {
            $agencies[] = $agency;
        }
        
        $dataMap[$agency][$sex] = $row['count'];
    }

    // กำหนดสี
    $colors = [
        'ชาย' => '#36A2EB',
        'หญิง' => '#FF6384',
        'เพศทางเลือก' => '#FFCE56'
    ];

    foreach ($sexes as $sex) {
        $data = [];
        foreach ($agencies as $agency) {
            $data[] = isset($dataMap[$agency][$sex]) ? (int)$dataMap[$agency][$sex] : 0;
        }
        
        $datasets[] = [
            'label' => $sex,
            'data' => $data,
            'backgroundColor' => $colors[$sex],
            'stack' => 'Stack 0' // ให้กราฟซ้อนกัน
        ];
    }
    
    $agencyLabels = $agencies;

} catch (PDOException $e) {
    echo "<div class='alert alert-danger'>เกิดข้อผิดพลาด: " . $e->getMessage() . "</div>";
}
?>

<div class="dashboard-card">
    <div class="card-header-custom">
        สถิติการส่งต่อหน่วยงานภายนอก
    </div>
    <div style="position: relative; height: 350px; width: 100%;">
        <canvas id="forwardAgencyChart"></canvas>
    </div>
</div>

<script>
document.addEventListener("DOMContentLoaded", function() {
    const ctxForward = document.getElementById('forwardAgencyChart').getContext('2d');
    
    new Chart(ctxForward, {
        type: 'bar',
        data: {
            labels: <?php echo json_encode($agencyLabels); ?>,
            datasets: <?php echo json_encode($datasets); ?>
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                x: { stacked: true },
                y: { 
                    stacked: true,
                    beginAtZero: true,
                    ticks: { precision: 0 }
                }
            },
            plugins: {
                tooltip: {
                    mode: 'index',
                    intersect: false
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
                            ctx.fillStyle = '#444'; // สีตัวอักษร
                            ctx.font = 'bold 12px sans-serif';
                            ctx.textAlign = 'center';
                            // คำนวณตำแหน่งกึ่งกลางของแท่งกราฟ (เพราะเป็น Stacked)
                            const centerY = (bar.y + bar.base) / 2;
                            ctx.fillText(data, bar.x, centerY + 4); 
                        }
                    });
                });
            }
        }]
    });
});
</script>