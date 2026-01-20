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

        // Query ข้อมูลจาก student_data
        $sql = "SELECT s.*, p.prefix_name, sc.school_name, sx.sex_name,
                (SELECT COUNT(*) FROM closure_report cr WHERE cr.pid = s.pid) as has_closure
                FROM student_data s
                LEFT JOIN prefix p ON s.prefix_id = p.prefix_id
                LEFT JOIN school sc ON s.school_id = sc.school_id
                LEFT JOIN sex sx ON s.sex = sx.sex_id";

        $params = [];
        if (!empty($search)) {
            // ค้นหาครอบคลุมทั้ง ชื่อ-นามสกุล (พิมพ์ต่อเนื่องได้) และ เลขบัตรประชาชน
            $sql .= " WHERE (CONCAT(IFNULL(s.fname,''), ' ', IFNULL(s.lname,'')) LIKE :search_name OR s.pid LIKE :search_pid)";
            $params[':search_name'] = "%$search%";
            $params[':search_pid'] = "%$search%";
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
    <link href="https://fonts.googleapis.com/css2?family=Sarabun:wght@400;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Sarabun', sans-serif;
        }

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
    </style>
</head>

<body class="bg-light">
    <?php include 'navbar.php'; ?>
    <div class="container">
        <div class="card shadow-sm mb-4">
            <div class="card-body">
                <h4 class="card-title mb-3">🔍 ค้นหาข้อมูล</h4>
                <div class="d-flex gap-2">
                    <input type="text" id="searchInput" class="form-control form-control-lg rounded-pill shadow-sm" placeholder="ระบุ ชื่อ, นามสกุล หรือ เลขบัตรประชาชน...">
                    <button class="btn btn-primary btn-lg rounded-pill px-4 shadow-sm bg-gradient" type="button" onclick="loadData(true)">ค้นหา</button>
                    <button class="btn btn-warning btn-lg rounded-pill px-4 shadow-sm bg-gradient" type="button" onclick="document.getElementById('searchInput').value = ''; loadData(true);"> รีเซ็ต</button>
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
                                <th style="width: 15%">ระดับชั้น/ห้อง</th>
                                <th style="width: 15%">เบอร์โทร</th>
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

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            let page = 1;
            let isLoading = false;
            let hasMore = true;
            const container = document.getElementById('dataContainer');
            const tableBody = document.getElementById('tableBody');
            const loading = document.getElementById('loading');
            const noMoreData = document.getElementById('noMoreData');
            const searchInput = document.getElementById('searchInput');

            window.loadData = function(reset = false) {
                if (reset) {
                    page = 1;
                    hasMore = true;
                    tableBody.innerHTML = '';
                    noMoreData.style.display = 'none';
                }

                if (isLoading || !hasMore) return;

                isLoading = true;
                loading.style.display = 'block';

                const search = searchInput.value;
                const url = `main.php?action=fetch_data&page=${page}&search=${encodeURIComponent(search)}`;

                fetch(url)
                    .then(response => response.json())
                    .then(json => {
                        if (json.status === 'success') {
                            const data = json.data;
                            if (data.length > 0) {
                                data.forEach(row => {
                                    const tr = document.createElement('tr');
                                    
                                    // ตรวจสอบว่ามีรายงานการยุติหรือไม่ ถ้ามีให้เปลี่ยนสีพื้นหลัง
                                    if (row.has_closure > 0) {
                                        tr.style.backgroundColor = '#5DD3B6';
                                        tr.style.setProperty('--bs-table-bg', '#5DD3B6');
                                    }

                                    // ${row.pid} อยากแสดง เลขบัตรประชาชนด้วย ให้เพิ่ม ไปตรงกลาง <br><small class="text-muted">+++++++++++++</small></td>
                                    tr.innerHTML = `
                                        <td>${(row.prefix_name || '')} ${row.fname} ${row.lname} <br><small class="text-muted"></small></td>
                                        <td>${row.sex_name || '-'}</td>
                                        <td>${row.age || '-'}</td>
                                        <td>${row.school_name || '-'}</td>
                                        <td>${row.class || '-'}/${row.room || '-'}</td>
                                        <td>${row.tel || '-'}</td>
                                        <td class="text-center">
                                            <a href="phq_history.php?pid=${row.pid}" class="btn btn-sm btn-info text-white">
                                                📜 ดูประวัติ
                                            </a>
                                        </td>
                                    `;
                                    tableBody.appendChild(tr);
                                });
                                page++;
                            } else {
                                hasMore = false;
                                noMoreData.style.display = 'block';
                            }
                        }
                    })
                    .catch(err => console.error(err))
                    .finally(() => {
                        isLoading = false;
                        loading.style.display = 'none';
                    });
            };

            // Initial load
            loadData();

            // Infinite scroll
            container.addEventListener('scroll', function() {
                if (container.scrollTop + container.clientHeight >= container.scrollHeight - 50) {
                    loadData();
                }
            });

            // Enter key on search
            searchInput.addEventListener('keyup', function(event) {
                if (event.key === 'Enter') {
                    loadData(true);
                }
            });
        });
    </script>
</body>

</html>