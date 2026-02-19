<?php
session_start();
require_once dirname(__DIR__) . '/app/core/Database.php';

// ตรวจสอบสิทธิ์การเข้าใช้งาน (ถ้าต้องการให้เฉพาะผู้ดูแลระบบเข้าได้)
if (!isset($_SESSION['user'])) {
    header("Location: login.php");
    exit;
}

// --- ส่วน API สำหรับดึงข้อมูล (AJAX) ---
if (isset($_GET['action']) && $_GET['action'] === 'fetch_data') {
    header('Content-Type: application/json');

    try {
        $db = Database::connect();
        $limit = 10;
        $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
        $offset = ($page - 1) * $limit;
        // จัดการคำค้นหา: ตัดช่องว่างหัวท้าย และเปลี่ยนช่องว่างหลายอันเป็นอันเดียว
        $search = isset($_GET['search']) ? preg_replace('/\s+/', ' ', trim($_GET['search'])) : '';
        $filter = isset($_GET['filter']) ? $_GET['filter'] : 'all';

        // Query ข้อมูลจาก student_data
        $sql = "SELECT s.*, p.prefix_name, sc.school_name, sx.sex_name,
                (SELECT COUNT(*) FROM closure_report cr WHERE cr.pid = s.pid) as has_closure,
                (SELECT COUNT(*) FROM forward_case fc WHERE fc.pid = s.pid) as has_forward
                FROM student_data s
                LEFT JOIN prefix p ON s.prefix_id = p.prefix_id
                LEFT JOIN school sc ON s.school_id = sc.school_id
                LEFT JOIN sex sx ON s.sex = sx.sex_id";

        $params = [];
        $conditions = [];

        if (!empty($search)) {
            // ค้นหาครอบคลุมทั้ง ชื่อ-นามสกุล (พิมพ์ต่อเนื่องได้) และ เลขบัตรประชาชน
            $conditions[] = "(CONCAT(IFNULL(s.fname,''), ' ', IFNULL(s.lname,'')) LIKE :search_name OR s.pid LIKE :search_pid)";
            $params[':search_name'] = "%$search%";
            $params[':search_pid'] = "%$search%";
        }

        if ($filter === 'forward') {
            $conditions[] = "(SELECT COUNT(*) FROM forward_case fc WHERE fc.pid = s.pid) > 0";
        } elseif ($filter === 'closure') {
            $conditions[] = "(SELECT COUNT(*) FROM closure_report cr WHERE cr.pid = s.pid) > 0";
        } elseif ($filter === 'score_normal') {
            $conditions[] = "(SELECT score FROM assessment WHERE pid = s.pid ORDER BY id DESC LIMIT 1) < 7";
        } elseif ($filter === 'score_moderate') {
            $conditions[] = "(SELECT score FROM assessment WHERE pid = s.pid ORDER BY id DESC LIMIT 1) BETWEEN 8 AND 13";
        } elseif ($filter === 'score_severe') {
            $conditions[] = "(SELECT score FROM assessment WHERE pid = s.pid ORDER BY id DESC LIMIT 1) > 13";
        }

        if (!empty($conditions)) {
            $sql .= " WHERE " . implode(' AND ', $conditions);
        }

        $sql .= " ORDER BY s.date_time DESC LIMIT :limit OFFSET :offset";

        $stmt = $db->prepare($sql);
        foreach ($params as $key => $val) {
            $stmt->bindValue($key, $val);
        }
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);

        $stmt->execute();
        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

        echo json_encode(['status' => 'success', 'data' => $data]);
    } catch (Exception $e) {
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
    }
    exit;
}
?>
<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>จัดการข้อมูลการประเมิน | PHQ System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap JS Bundle (includes Popper) -->
    <!-- Global Stylesheet (for background) -->
    <link href="css/style.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Sarabun:wght@400;700&display=swap" rel="stylesheet">
    <style>
        body {
            /* font-family: 'Sarabun', sans-serif; */

            .loading {
                text-align: center;
                padding: 20px;
                display: none;
            }

            .score-badge {
                min-width: 30px;
                display: inline-block;
                text-align: center;
            }
        }
    </style>
</head>

<body>
    <?php include 'navbar.php'; ?>
    <div class="container">
        <div class="card shadow-sm mb-4">
            <div class="card-body">
                <h4 class="card-title mb-3">🔍 ค้นหาข้อมูล</h4>
                <div class="d-flex gap-2">
                    <select id="filterStatus" class="form-select form-select-lg rounded-pill shadow-sm" style="max-width: 220px;" onchange="loadData(true)">
                        <option value="all">ทั้งหมด</option>
                        <option value="forward">ส่งต่อกรณี</option>
                        <option value="closure">ยุติการช่วยเหลือ</option>
                        <option value="score_normal">ภาวะซึมเศร้า: ปกติ (น้อยกว่า 7)</option>
                        <option value="score_moderate">ภาวะซึมเศร้า: ปานกลาง (8-13)</option>
                        <option value="score_severe">ภาวะซึมเศร้า: รุนแรง (มากกว่า 13)</option>
                    </select>
                    <input type="text" id="searchInput" class="form-control form-control-lg rounded-pill shadow-sm" placeholder="ระบุ ชื่อ, นามสกุล หรือ เลขบัตรประชาชน...">
                    <button class="btn btn-primary btn-lg rounded-pill px-4 shadow-sm bg-gradient" type="button" onclick="loadData(true)">ค้นหา</button>
                    <button class="btn btn-warning btn-lg rounded-pill px-4 shadow-sm bg-gradient" type="button" onclick="document.getElementById('searchInput').value = ''; document.getElementById('filterStatus').value = 'all'; loadData(true);"> รีเซ็ต</button>
                </div>
            </div>
        </div>



        <div class="card shadow-sm">
            <div class="card-header bg-white d-flex justify-content-between align-items-center">
                <h5 class="mb-0">📋 รายชื่อนักเรียน (Student Data)</h5>
                <a href="edit_students.php" class="btn btn-warning btn-sm">✏️ แก้ไขข้อมูลนักเรียน</a>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive" id="dataContainer" style="max-height: 65vh; overflow-y: auto;">
                    <table class="table table-hover table-striped mb-0 align-middle">
                        <thead class="table-light">
                            <tr>
                                <!-- <th style="width: 15%">เลขบัตรประชาชน</th> -->
                                <th style="width: 20%">ชื่อ-นามสกุล</th>
                                <th style="width: 10%">เพศ</th>
                                <th style="width: 5%">อายุ</th>
                                <th style="width: 20%">โรงเรียน</th>
                                <th style="width: 15%" class="text-center">ระดับชั้น/ห้อง</th>
                                <th style="width: 15%" class="text-center">เบอร์โทร</th>
                                <th style="width: 10%" class="text-center">ประวัติ</th>
                            </tr>
                        </thead>
                        <tbody id="tableBody">
                            <!-- Data will be loaded here -->
                        </tbody>
                    </table>
                    <div id="loading" class="loading">
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden">Loading...</span>
                        </div>
                        <p class="mt-2 text-muted">กำลังโหลดข้อมูล...</p>
                    </div>

                    <div id="noMoreData" class="text-center p-4 text-muted" style="display: none;">
                        -- แสดงข้อมูลครบถ้วนแล้ว --
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="script/javascript/main.js"></script>
</body>

</html>