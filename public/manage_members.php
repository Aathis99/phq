<?php
session_start();
require_once dirname(__DIR__) . '/app/core/Database.php';

// ตรวจสอบสิทธิ์ (ต้อง Login ก่อน)
if (!isset($_SESSION['user'])) {
    header("Location: login.php");
    exit;
}

$db = Database::connect();

// ดึงข้อมูล Master Data สำหรับ Dropdown
$prefixes = $db->query("SELECT prefix_id, prefix_name FROM prefix ORDER BY prefix_id")->fetchAll();
// ดึงข้อมูล Type User (ถ้ามีตาราง type หรือ hardcode เอา)
$types = $db->query("SELECT * FROM type")->fetchAll(); 
// หากไม่มีตาราง type ให้ใช้ array แทน: $types = [['type_name'=>'admin'], ['type_name'=>'user']];

// --- ตรวจสอบสิทธิ์ว่าเป็น Admin หรือไม่ ---
$currentUser = $_SESSION['user']['username'];
$stmtRole = $db->prepare("SELECT typeuser FROM users WHERE username = :u");
$stmtRole->execute([':u' => $currentUser]);
$isAdmin = ($stmtRole->fetchColumn() === 'admin');
?>
<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>จัดการข้อมูลสมาชิก | PHQ System</title>
    <!-- SweetAlert2 CSS (optional, but good practice if customizing) -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Global Stylesheet (for background) -->
    <link href="css/style.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Sarabun:wght@400;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Sarabun', sans-serif; }
        .loading { text-align: center; padding: 20px; display: none; }
    </style>
</head>

<body>
    <?php include 'navbar.php'; ?>
    
    <div class="container">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h3>👥 จัดการข้อมูลสมาชิก (Members)</h3>
            <a href="main.php" class="btn btn-danger">↩ กลับหน้าหลัก</a>
        </div>

        <div class="card shadow-sm">
            <div class="card-header bg-white d-flex justify-content-between align-items-center">
                <h5 class="mb-0">รายชื่อสมาชิกในระบบ</h5>
                <?php if ($isAdmin): ?>
                    <button class="btn btn-success btn-sm" onclick="openAddModal()">➕ เพิ่มสมาชิกใหม่</button>
                <?php endif; ?>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover table-striped mb-0 align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>ชื่อ-นามสกุล</th>
                                <th>ตำแหน่ง</th>
                                <th>Username</th>
                                <th>สิทธิ์ (Type)</th>
                                <th class="text-center">จัดการ</th>
                            </tr>
                        </thead>
                        <tbody id="tableBody">
                            <!-- Data loaded via JS -->
                        </tbody>
                    </table>
                    <div id="loading" class="loading">
                        <div class="spinner-border text-primary" role="status"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal เพิ่ม/แก้ไข ข้อมูล -->
    <div class="modal fade" id="memberModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header bg-info text-white">
                    <h5 class="modal-title" id="modalTitle">จัดการสมาชิก</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="memberForm">
                        <input type="hidden" id="action" name="action" value="save">
                        <input type="hidden" id="old_username" name="old_username">

                        <h6 class="text-primary border-bottom pb-2">ข้อมูลส่วนตัว (Member)</h6>
                        <div class="row mb-3">
                            <div class="col-md-4">
                                <label class="form-label">เลขบัตรประชาชน <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="pid" name="pid" maxlength="13" required>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">คำนำหน้า</label>
                                <select class="form-select" id="prefix_id" name="prefix_id">
                                    <option value="">-- เลือก --</option>
                                    <?php foreach ($prefixes as $p): ?>
                                        <option value="<?= $p['prefix_id'] ?>"><?= $p['prefix_name'] ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-5">
                                <label class="form-label">ตำแหน่ง</label>
                                <input type="text" class="form-control" id="position" name="position">
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

                        <h6 class="text-primary border-bottom pb-2 mt-4">ข้อมูลเข้าสู่ระบบ (User Account)</h6>
                        <div class="row mb-3">
                            <div class="col-md-4">
                                <label class="form-label">Username <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="username" name="username" required>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Password</label>
                                <input type="password" class="form-control" id="password" name="password" placeholder="ระบุหากต้องการเปลี่ยน">
                                <small class="text-muted" id="passHelp">ว่างไว้หากไม่ต้องการเปลี่ยน</small>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">สิทธิ์การใช้งาน</label>
                                <select class="form-select" id="typeuser" name="typeuser">
                                    <option value="">-- เลือก --</option>
                                    <?php foreach ($types as $t): ?>
                                        <option value="<?= $t['type_name'] ?>"><?= $t['type_name'] ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">ยกเลิก</button>
                    <button type="button" class="btn btn-primary" onclick="saveMember()">บันทึกข้อมูล</button>
                </div>
            </div>
        </div>
    </div>

    <!-- SweetAlert2 JS -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        const memberModal = new bootstrap.Modal(document.getElementById('memberModal'));
        const isAdmin = <?= json_encode($isAdmin) ?>;

        function loadMembers() {
            document.getElementById('loading').style.display = 'block';
            document.getElementById('tableBody').innerHTML = '';

            fetch('api/member_api.php?action=fetch')
                .then(res => res.json())
                .then(data => {
                    if (data.status === 'success') {
                        data.data.forEach(row => {
                            const tr = document.createElement('tr');
                            const rowData = encodeURIComponent(JSON.stringify(row));
                            
                            // ปุ่มลบ แสดงเฉพาะ Admin
                            const deleteBtn = isAdmin ? `<button class="btn btn-sm btn-danger ms-1" onclick="deleteMember('${row.username}')">⛔ ลบ</button>` : '';

                            tr.innerHTML = `
                                <td>${row.prefix_name || ''} ${row.fname} ${row.lname}</td>
                                <td>${row.position || '-'}</td>
                                <td><span class="badge bg-secondary">${row.username}</span></td>
                                <td>${row.typeuser || '-'}</td>
                                <td class="text-center text-nowrap">
                                    <button class="btn btn-sm btn-warning" onclick="openEditModal('${rowData}')">✏️ แก้ไข</button>
                                    ${deleteBtn}
                                </td>
                            `;
                            document.getElementById('tableBody').appendChild(tr);
                        });
                    }
                })
                .finally(() => document.getElementById('loading').style.display = 'none');
        }

        function openAddModal() {
            document.getElementById('memberForm').reset();
            document.getElementById('old_username').value = '';
            document.getElementById('modalTitle').innerText = 'เพิ่มสมาชิกใหม่';
            
            // ปลดล็อคช่อง Username/PID
            document.getElementById('username').readOnly = false;
            document.getElementById('pid').readOnly = false;
            document.getElementById('passHelp').innerText = 'กำหนดรหัสผ่านสำหรับสมาชิกใหม่';
            document.getElementById('password').required = true;

            memberModal.show();
        }

        function openEditModal(encodedData) {
            const data = JSON.parse(decodeURIComponent(encodedData));
            
            document.getElementById('old_username').value = data.username;
            document.getElementById('pid').value = data.pid;
            document.getElementById('prefix_id').value = data.prefix_id;
            document.getElementById('fname').value = data.fname;
            document.getElementById('lname').value = data.lname;
            document.getElementById('position').value = data.position;
            
            document.getElementById('username').value = data.username;
            document.getElementById('typeuser').value = data.typeuser;
            document.getElementById('password').value = ''; // เคลียร์รหัสผ่าน

            document.getElementById('modalTitle').innerText = 'แก้ไขข้อมูลสมาชิก';
            
            // ล็อค Username/PID (แก้ไขไม่ได้เพื่อความปลอดภัยของ FK)
            document.getElementById('username').readOnly = true;
            document.getElementById('pid').readOnly = true;
            document.getElementById('passHelp').innerText = 'ว่างไว้หากไม่ต้องการเปลี่ยน';
            document.getElementById('password').required = false;

            // ถ้าไม่ใช่ Admin ห้ามแก้ Type User และ PID (PID ถูกล็อคอยู่แล้วข้างบน แต่ย้ำเพื่อความชัวร์)
            if (!isAdmin) {
                document.getElementById('typeuser').disabled = true;
            } else {
                document.getElementById('typeuser').disabled = false;
            }

            memberModal.show();
        }

        function saveMember() {
            const form = document.getElementById('memberForm');
            if (!form.checkValidity()) {
                form.reportValidity();
                return;
            }

            const formData = new FormData(form);
            // เนื่องจาก disabled field จะไม่ถูกส่งค่าไปกับ FormData ต้อง append เองถ้าจำเป็น
            // แต่ในกรณีนี้ Member ห้ามแก้ Type User ดังนั้น Backend จะจัดการไม่ให้อัปเดตค่านี้เอง
            
            fetch('api/member_api.php', {
                method: 'POST',
                body: formData
            })
            .then(res => res.json())
            .then(data => {
                if (data.status === 'success') {
                    showSuccessAlert('บันทึกข้อมูลเรียบร้อย');
                    memberModal.hide();
                    loadMembers();
                } else {
                    alert('เกิดข้อผิดพลาด: ' + data.message);
                }
            });
        }

        function deleteMember(username) {
            if (confirm('คุณต้องการลบผู้ใช้ "' + username + '" ใช่หรือไม่?')) {
                const formData = new FormData();
                formData.append('action', 'delete');
                formData.append('username', username);

                fetch('api/member_api.php', {
                    method: 'POST',
                    body: formData
                })
                .then(res => res.json())
                .then(data => {
                    if (data.status === 'success') {
                        loadMembers();
                    } else {
                        alert('ลบไม่สำเร็จ: ' + data.message);
                    }
                });
            }
        }

        document.addEventListener('DOMContentLoaded', loadMembers);
    </script>
</body>
</html>