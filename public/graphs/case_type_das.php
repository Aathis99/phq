<?php
// เชื่อมต่อฐานข้อมูล
require_once dirname(__DIR__, 2) . '/app/core/Database.php';

$caseTypeLabels = [];
$datasets = [];

try {
    $db = Database::connect();
    $db->exec("SET NAMES utf8mb4"); // บังคับให้การรับส่งข้อมูลเป็น UTF-8
    
    // Query เพื่อนับจำนวนเคส แยกตามประเภทอาการ (case_type) และเพศ (sex_name)
    $sql = "SELECT 
                a.case_type,
                x.sex_name,
                COUNT(a.id) as count
            FROM add_caselog a
            JOIN student_data s ON a.pid = s.pid
            JOIN sex x ON s.sex = x.sex_id
            ";

    if (isset($filter_year) && isset($filter_term)) {
        $sql .= " WHERE a.academic_year = $filter_year AND a.semester = $filter_term";
    }
    $sql .= " GROUP BY a.case_type, x.sex_name
            ORDER BY a.case_type";
            
    $stmt = $db->query($sql);
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // ประมวลผลข้อมูลเพื่อจัดรูปแบบสำหรับ Chart.js
    $caseTypes = [];
    $sexes = ['ชาย', 'หญิง', 'เพศทางเลือก'];
    $dataMap = [];

    foreach ($results as $row) {
        $cType = $row['case_type'] ? $row['case_type'] : 'ไม่ระบุ';
        // ใช้ trim() เพื่อตัดช่องว่างหน้า-หลังที่อาจติดมา และตรวจสอบค่าว่าง
        $sName = isset($row['sex_name']) ? trim($row['sex_name']) : '';
        
        if (!in_array($cType, $caseTypes)) $caseTypes[] = $cType;
        
        $dataMap[$cType][$sName] = $row['count'];
    }

    // กำหนดสีสำหรับแต่ละเพศ
    $colors = [
        'ชาย' => '#36A2EB', // ฟ้า
        'หญิง' => '#FF6384', // ชมพู
        'เพศทางเลือก' => '#FFCE56' // เหลือง
    ];
    $defaultColors = ['#4BC0C0', '#9966FF', '#FF9F40']; // สีสำรอง

    foreach ($sexes as $i => $sex) {
        $data = [];
        foreach ($caseTypes as $type) {
            $data[] = isset($dataMap[$type][$sex]) ? (int)$dataMap[$type][$sex] : 0;
        }
        
        $color = isset($colors[$sex]) ? $colors[$sex] : $defaultColors[$i % count($defaultColors)];
        
        $datasets[] = [
            'label' => $sex,
            'data' => $data,
            'backgroundColor' => $color,
            'stack' => 'Stack 0' // ให้กราฟซ้อนกัน
        ];
    }
    
    $caseTypeLabels = $caseTypes;

} catch (PDOException $e) {
    echo "<div class='alert alert-danger'>เกิดข้อผิดพลาด: " . $e->getMessage() . "</div>";
}
?>

<div class="dashboard-card">
    <div class="card-header-custom d-flex justify-content-between align-items-center">
        <span>จำนวนอาการแยกตามประเภทและเพศ</span>
        <select id="caseTypeSexFilter" class="form-select form-select-sm" style="width: auto;">
            <option value="all">เพศทั้งหมด</option>
            <?php foreach ($sexes as $sex): ?>
                <option value="<?= htmlspecialchars($sex) ?>"><?= htmlspecialchars($sex) ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <div style="position: relative; height: 350px; width: 100%;">
        <canvas id="caseTypeSexChart"></canvas>
    </div>
</div>

<script>
document.addEventListener("DOMContentLoaded", function() {
    const ctxCase = document.getElementById('caseTypeSexChart').getContext('2d');
    
    const caseTypeChart = new Chart(ctxCase, {
        type: 'bar',
        data: {
            labels: <?php echo json_encode($caseTypeLabels); ?>,
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
        }
    });

    // Dropdown Filter Logic
    const filterSelect = document.getElementById('caseTypeSexFilter');
    if (filterSelect) {
        filterSelect.addEventListener('change', function() {
            const selectedSex = this.value;
            caseTypeChart.data.datasets.forEach(function(dataset) {
                // ถ้าเลือก 'all' ให้แสดงทั้งหมด, ถ้าเลือกเพศไหน ให้แสดงเฉพาะเพศนั้น (ซ่อนอันอื่น)
                dataset.hidden = (selectedSex !== 'all' && dataset.label !== selectedSex);
            });
            caseTypeChart.update();
        });
    }
});
</script>