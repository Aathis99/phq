<?php
session_start();
// ตรวจสอบการเข้าสู่ระบบ (ถ้าจำเป็น)
// if (!isset($_SESSION['user'])) { header("Location: ../login.php"); exit; }

require_once dirname(__DIR__, 2) . '/app/core/Database.php';
$db = Database::connect();

// ดึงข้อมูลปีการศึกษาและเทอมที่มีทั้งหมดจากตาราง assessment (หรือตารางอื่นๆ ที่ครอบคลุม)
$sql_years = "SELECT DISTINCT academic_year, semester 
              FROM assessment 
              WHERE academic_year IS NOT NULL AND semester IS NOT NULL 
              ORDER BY academic_year DESC, semester DESC";
$stmt_years = $db->query($sql_years);
$year_options = $stmt_years->fetchAll(PDO::FETCH_ASSOC);

// ตรวจสอบค่า Filter ที่ส่งมา
$selected_filter = isset($_GET['academic_filter']) ? $_GET['academic_filter'] : 'all';
$filter_term = null;
$filter_year = null;

if ($selected_filter !== 'all') {
    list($filter_term, $filter_year) = explode('/', $selected_filter);
}

?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard สถิติ</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <!-- Custom CSS (ถ้ามี) -->
    <link href="../css/style.css" rel="stylesheet">
    <style>
        body { background-color: #f8f9fa; }
        .dashboard-card {
            background: #fff;
            border-radius: 15px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.05);
            padding: 25px;
            margin-bottom: 30px;
            height: 100%;
        }
        .card-header-custom {
            border-bottom: 2px solid #f0f0f0;
            padding-bottom: 15px;
            margin-bottom: 20px;
            font-weight: bold;
            color: #333;
            font-size: 1.1rem;
        }
    </style>
</head>
<body>
    <!-- Navbar -->
    <?php include '../navbar.php'; ?>

    <div class="container py-5">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2 class="fw-bold text-primary">📊 Dashboard สรุปผลการประเมิน</h2>
            <div class="d-flex gap-2 align-items-center">
                <select class="form-select" style="width: auto;" onchange="window.location.href='?academic_filter='+this.value">
                    <option value="all" <?= $selected_filter == 'all' ? 'selected' : '' ?>>แสดงทั้งหมด</option>
                    <?php foreach ($year_options as $opt): ?>
                        <?php $val = $opt['semester'] . '/' . $opt['academic_year']; ?>
                        <option value="<?= $val ?>" <?= $selected_filter == $val ? 'selected' : '' ?>>
                            เทอม <?= $opt['semester'] ?>/<?= $opt['academic_year'] ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <a href="../index.php" class="btn btn-danger text-nowrap">กลับหน้าหลัก</a>
            </div>
        </div>

        <div class="row">
            <!-- Dashboard 1: สถิตินักเรียนที่ทำแบบประเมิน (Pie Chart) -->
            <div class="col-md-6 mb-4">
                <?php include 'assessment_das.php'; ?>
            </div>
            
            <!-- Dashboard 2: ผลการประเมินภาวะซึมเศร้า (Bar Chart) -->
            <div class="col-md-6 mb-4">
                <?php include 'dis_score_das.php'; ?>
            </div>
        </div>

        <div class="row">
            <!-- Dashboard 3: จำนวนอาการแยกตามประเภทและเพศ (Stacked Bar Chart) -->
            <div class="col-12 mb-4">
                <?php include 'case_type_das.php'; ?>
            </div>
        </div>

        <div class="row">
            <!-- Dashboard 4: สถานะการติดตาม (Bar Chart with Dropdown) -->
            <div class="col-md-6 mb-4">
                <?php include 'follow_das.php'; ?>
            </div>
            
            <!-- Dashboard 5: สถิติการส่งต่อหน่วยงานภายนอก -->
            <div class="col-md-6 mb-4">
                <?php include 'forward_das.php'; ?>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>