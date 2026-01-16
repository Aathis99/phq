<?php
session_start();
require_once dirname(__DIR__) . '/app/core/Database.php';

if (!isset($_SESSION['user'])) {
    header("Location: login.php");
    exit;
}

$db = Database::connect();

// ดึงข้อมูล Master Data สำหรับ Dropdown ใน Modal
$schools = $db->query("SELECT school_id, school_name FROM school ORDER BY school_id")->fetchAll();
$prefixes = $db->query("SELECT prefix_id, prefix_name FROM prefix ORDER BY prefix_id")->fetchAll();
$sexes = $db->query("SELECT sex_id, sex_name FROM sex ORDER BY sex_id")->fetchAll();
?>
<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>แก้ไขข้อมูลนักเรียน | PHQ System</title>
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
    </style>
</head>

<body class="bg-light">
    <?php include 'navbar.php'; ?>
    <div class="container">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h3>✏️ แก้ไขข้อมูลนักเรียน</h3>
            <a href="main.php" class="btn btn-danger">↩ กลับหน้ารายชื่อ</a>
        </div>


        <div class="card shadow-sm mb-4">
            <div class="card-body">
                <h4 class="card-title mb-3">🔍 ค้นหาข้อมูล</h4>
                <div class="d-flex gap-2">
                    <input type="text" id="searchInput" class="form-control form-control-lg rounded-pill shadow-sm" placeholder="ระบุ ชื่อ, นามสกุล หรือ เลขบัตรประชาชน...">
                    <button class="btn btn-primary btn-lg rounded-pill px-4 shadow-sm bg-gradient" type="button" onclick="loadData(true)"> ค้นหา</button>
                    <button class="btn btn-warning btn-lg rounded-pill px-4 shadow-sm bg-gradient" type="button" onclick="document.getElementById('searchInput').value = ''; loadData(true);"> รีเซ็ต</button>
                </div>
            </div>
        </div>



        <div class="card shadow-sm">
            <div class="card-header bg-white d-flex justify-content-between align-items-center">
                <h5 class="mb-0">รายชื่อนักเรียน</h5>
                <button class="btn btn-success btn-sm" onclick="openAddModal()">➕ เพิ่มนักเรียน</button>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive" id="dataContainer" style="max-height: 60vh; overflow-y: auto;">
                    <table class="table table-hover table-striped mb-0 align-middle">
                        <thead class="table-light">
                            <tr>
                                <th style="width: 25%">ชื่อ-นามสกุล</th>
                                <th style="width: 10%">เพศ</th>
                                <th style="width: 5%">อายุ</th>
                                <th style="width: 20%">โรงเรียน</th>
                                <th style="width: 15%">ชั้น/ห้อง</th>
                                <th style="width: 15%">เบอร์โทร</th>
                                <th style="width: 10%" class="text-center">จัดการ</th>
                            </tr>
                        </thead>
                        <tbody id="tableBody"></tbody>
                    </table>
                    <div id="loading" class="loading">
                        <div class="spinner-border text-primary" role="status"></div>
                    </div>
                    <div id="noMoreData" class="text-center p-4 text-muted" style="display: none;">-- แสดงข้อมูลครบถ้วนแล้ว --</div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal แก้ไขข้อมูล -->
    <div class="modal fade" id="editModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header bg-warning">
                    <h5 class="modal-title">แก้ไขข้อมูลนักเรียน</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="editForm">
                        <input type="hidden" id="old_pid" name="old_pid">

                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label">เลขบัตรประชาชน <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="pid" name="pid" maxlength="13" readonly style="background-color: #e9ecef;" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">คำนำหน้า</label>
                                <select class="form-select" id="prefix_id" name="prefix_id">
                                    <option value="">-- เลือก --</option>
                                    <?php foreach ($prefixes as $p): ?>
                                        <option value="<?= $p['prefix_id'] ?>"><?= $p['prefix_name'] ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label">ชื่อ <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="fname" name="fname" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">นามสกุล <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="lname" name="lname" required>
                            </div>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-4">
                                <label class="form-label">เพศ</label>
                                <select class="form-select" id="sex" name="sex">
                                    <option value="">-- เลือก --</option>
                                    <?php foreach ($sexes as $s): ?>
                                        <option value="<?= $s['sex_id'] ?>"><?= $s['sex_name'] ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">อายุ</label>
                                <input type="number" class="form-control" id="age" name="age" min="1" max="20">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">เบอร์โทร <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="tel" name="tel" maxlength="10" required>
                            </div>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label">โรงเรียน <span class="text-danger">*</span></label>
                                <select class="form-select" id="school_id" name="school_id" required>
                                    <option value="">-- เลือก --</option>
                                    <?php foreach ($schools as $sc): ?>
                                        <option value="<?= $sc['school_id'] ?>"><?= $sc['school_name'] ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">ชั้น (ม.)</label>
                                <select class="form-select" id="class" name="class">
                                    <option value="">-- เลือก --</option>
                                    <?php for ($i = 1; $i <= 6; $i++): ?>
                                        <option value="<?= $i ?>"><?= $i ?></option>
                                    <?php endfor; ?>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">ห้อง</label>
                                <select class="form-select" id="room" name="room">
                                    <option value="">-- เลือก --</option>
                                    <?php for ($i = 1; $i <= 20; $i++): ?>
                                        <option value="<?= $i ?>"><?= $i ?></option>
                                    <?php endfor; ?>
                                </select>
                            </div>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">ยกเลิก</button>
                    <button type="button" class="btn btn-primary" onclick="saveStudent()">บันทึก</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        let page = 1;
        let isLoading = false;
        let hasMore = true;
        const editModal = new bootstrap.Modal(document.getElementById('editModal'));

        function loadData(reset = false) {
            if (reset) {
                page = 1;
                hasMore = true;
                document.getElementById('tableBody').innerHTML = '';
                document.getElementById('noMoreData').style.display = 'none';
            }
            if (isLoading || !hasMore) return;

            isLoading = true;
            document.getElementById('loading').style.display = 'block';
            const search = document.getElementById('searchInput').value;

            // เรียกใช้ API จาก main.php เพื่อดึงข้อมูล (Reuse Logic)
            fetch(`main.php?action=fetch_data&page=${page}&search=${encodeURIComponent(search)}`)
                .then(res => res.json())
                .then(json => {
                    if (json.status === 'success' && json.data.length > 0) {
                        json.data.forEach(row => {
                            const tr = document.createElement('tr');
                            // เก็บข้อมูล row ไว้ในปุ่มเพื่อดึงมาใส่ Modal
                            const rowData = encodeURIComponent(JSON.stringify(row));
                            tr.innerHTML = `
                                <td>${(row.prefix_name || '')} ${row.fname} ${row.lname}</td>
                                <td>${row.sex_name || '-'}</td>
                                <td>${row.age || '-'}</td>
                                <td>${row.school_name || '-'}</td>
                                <td>${row.class || '-'}/${row.room || '-'}</td>
                                <td>${row.tel || '-'}</td>
                                <td class="text-center text-nowrap">
                                    <button class="btn btn-sm btn-warning" onclick="openEditModal('${rowData}')">
                                        ✏️ แก้ไข
                                    </button>
                                    <button class="btn btn-sm btn-danger ms-2" onclick="deleteStudent('${row.pid}', '${row.fname} ${row.lname}')">
                                        ⛔ ลบ
                                    </button>
                                </td>
                            `;
                            document.getElementById('tableBody').appendChild(tr);
                        });
                        page++;
                    } else {
                        hasMore = false;
                        document.getElementById('noMoreData').style.display = 'block';
                    }
                })
                .finally(() => {
                    isLoading = false;
                    document.getElementById('loading').style.display = 'none';
                });
        }

        function openAddModal() {
            // ล้างค่าในฟอร์ม
            document.getElementById('editForm').reset();
            document.getElementById('old_pid').value = ''; // เคลียร์ old_pid เพื่อบอกว่าเป็นโหมดเพิ่ม

            // ปรับแต่ง Modal สำหรับโหมดเพิ่ม (แก้ไข PID ได้)
            const pidInput = document.getElementById('pid');
            pidInput.readOnly = false;
            pidInput.style.backgroundColor = '';
            
            // เปลี่ยนชื่อ Modal
            document.querySelector('#editModal .modal-title').innerText = 'เพิ่มข้อมูลนักเรียน';
            editModal.show();
        }

        function openEditModal(encodedData) {
            const data = JSON.parse(decodeURIComponent(encodedData));

            // เติมข้อมูลลงฟอร์ม
            document.getElementById('old_pid').value = data.pid;
            document.getElementById('pid').value = data.pid;
            document.getElementById('prefix_id').value = data.prefix_id;
            document.getElementById('fname').value = data.fname;
            document.getElementById('lname').value = data.lname;
            document.getElementById('sex').value = data.sex;
            document.getElementById('age').value = data.age;
            document.getElementById('tel').value = data.tel;
            document.getElementById('school_id').value = data.school_id;
            document.getElementById('class').value = data.class;
            document.getElementById('room').value = data.room;

            // ปรับแต่ง Modal สำหรับโหมดแก้ไข (PID ห้ามแก้)
            const pidInput = document.getElementById('pid');
            pidInput.readOnly = true;
            pidInput.style.backgroundColor = '#e9ecef';

            document.querySelector('#editModal .modal-title').innerText = 'แก้ไขข้อมูลนักเรียน';
            editModal.show();
        }

        function saveStudent() {
            const form = document.getElementById('editForm');
            if (!form.checkValidity()) {
                form.reportValidity();
                return;
            }

            const formData = new FormData(form);
            const oldPid = document.getElementById('old_pid').value;
            const apiEndpoint = oldPid ? 'api/update_student.php' : 'api/add_student.php';

            fetch(apiEndpoint, {
                    method: 'POST',
                    body: formData
                })
                .then(res => res.json())
                .then(data => {
                    if (data.status === 'success') {
                        alert('บันทึกข้อมูลเรียบร้อย');
                        editModal.hide();
                        loadData(true); // รีโหลดตาราง
                    } else {
                        alert('เกิดข้อผิดพลาด: ' + data.message);
                    }
                });
        }

        function deleteStudent(pid, name) {
            if (confirm(`คุณต้องการลบข้อมูลของ "${name}" ใช่หรือไม่?\n\n⚠️ การกระทำนี้ไม่สามารถย้อนกลับได้ และข้อมูลการประเมินทั้งหมดจะถูกลบด้วย`)) {
                const formData = new FormData();
                formData.append('pid', pid);

                fetch('api/delete_student.php', {
                        method: 'POST',
                        body: formData
                    })
                    .then(res => res.json())
                    .then(data => {
                        if (data.status === 'success') {
                            alert('ลบข้อมูลเรียบร้อยแล้ว');
                            loadData(true); // รีโหลดตาราง
                        } else {
                            alert('เกิดข้อผิดพลาด: ' + data.message);
                        }
                    });
            }
        }

        document.addEventListener('DOMContentLoaded', () => loadData());
        document.getElementById('dataContainer').addEventListener('scroll', function() {
            if (this.scrollTop + this.clientHeight >= this.scrollHeight - 50) loadData();
        });

        document.getElementById('searchInput').addEventListener('keyup', function(event) {
            if (event.key === 'Enter') {
                loadData(true);
            }
        });
    </script>
</body>

</html>