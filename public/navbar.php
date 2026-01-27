<?php
// ตรวจสอบชื่อไฟล์ปัจจุบัน
$current_page = basename($_SERVER['PHP_SELF']);

// ตรวจสอบว่าไฟล์ปัจจุบันอยู่ในโฟลเดอร์ 'graphs' หรือไม่ เพื่อปรับ path ของลิงก์ให้ถูกต้อง
$is_in_graphs_folder = (strpos($_SERVER['SCRIPT_NAME'], '/graphs/') !== false);
// กำหนด path prefix สำหรับไฟล์ที่อยู่ใน root ของ /public
$path_prefix = $is_in_graphs_folder ? '../' : '';
// กำหนด path สำหรับ dashboard โดยเฉพาะ
$dashboard_path = $is_in_graphs_folder ? 'dashboard.php' : 'graphs/dashboard.php';
?>
<!-- เพิ่ม Bootstrap Icons -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

<style>
    .navbar-modern {
        background-color: #F8FCFF; /* Very light, calming blue (Alice Blue) */
        backdrop-filter: blur(12px);
        -webkit-backdrop-filter: blur(12px);
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.03);
        border-bottom: 1px solid rgba(0,0,0,0.05);
        padding: 0.8rem 0;
    }
    .navbar-brand-modern {
        font-weight: 700;
        color: #334155 !important;
        font-size: 1.3rem;
        letter-spacing: -0.5px;
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }
    .nav-btn-modern {
        border-radius: 50px;
        padding: 0.5rem 1.2rem;
        font-size: 0.9rem;
        font-weight: 500;
        transition: all 0.2s ease;
        display: inline-flex;
        align-items: center;
        gap: 0.4rem;
        border: 1px solid transparent;
    }
    .nav-btn-modern:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
    }
    
    /* Soft Button Styles */
    .btn-soft-primary { background-color: #e0f2fe; color: #0284c7; }
    .btn-soft-primary:hover { background-color: #0284c7; color: #ffffff; }

    .btn-soft-teal { background-color: #ccfbf1; color: #0f766e; }
    .btn-soft-teal:hover { background-color: #0f766e; color: #ffffff; }

    .btn-soft-danger { background-color: #fee2e2; color: #991b1b; }
    .btn-soft-danger:hover { background-color: #ef4444; color: #ffffff; }
    
    .user-badge {
        background-color: #f1f5f9;
        padding: 0.4rem 1rem;
        border-radius: 50px;
        font-size: 0.85rem;
        color: #475569;
        font-weight: 600;
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }
</style>

<nav class="navbar navbar-expand-lg navbar-light navbar-modern mb-4 sticky-top">
    <div class="container">
        <a class="navbar-brand navbar-brand-modern" href="<?= $path_prefix ?>index.php">
            <!-- <i class="bi bi-heart-pulse-fill text-primary"></i> -->
            <!-- ไอคอนข้อความและเป็นกล่องหัวใจ -->
            <i class="bi bi-chat-heart-fill text-primary"></i>
            แบบทดสอบภาวะซึมเศร้า PHQ-9
        </a>

        <button class="navbar-toggler border-0 shadow-none" type="button" data-bs-toggle="collapse" data-bs-target="#navbarContent">
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse" id="navbarContent">
            <div class="ms-auto d-flex gap-2 align-items-center flex-wrap mt-3 mt-lg-0">
                <?php if (isset($_SESSION['user'])): ?>
                    
                    <!-- <div class="user-badge me-2 d-none d-lg-flex">
                        <i class="bi bi-person-circle text-secondary"></i>
                        สวัสดี คุณ <?= htmlspecialchars($_SESSION['user']['fname'] ?? '') ?>
                    </div> -->

                    <a href="<?= $path_prefix ?>main.php" class="btn btn-soft-teal nav-btn-modern text-decoration-none">
                        <i class="bi bi-card-checklist"></i> รายชื่อนักเรียน
                    </a>

                    <div class="dropdown">
                        <button class="btn btn-soft-primary nav-btn-modern text-decoration-none dropdown-toggle" type="button" id="manageDataDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="bi bi-database"></i> จัดการข้อมูล
                        </button>
                        <ul class="dropdown-menu" aria-labelledby="manageDataDropdown">
                            <li><a class="dropdown-item" href="<?= $path_prefix ?>edit_students.php"><i class="bi bi-person-vcard-fill me-2 text-info"></i>จัดการข้อมูลนักเรียน</a></li>
                            <li><a class="dropdown-item" href="<?= $path_prefix ?>manage_members.php"><i class="bi bi-people-fill me-2 text-success"></i>จัดการข้อมูลสมาชิก</a></li>
                        </ul>
                    </div>

                    <a href="<?= $dashboard_path ?>" class="btn btn-soft-primary nav-btn-modern text-decoration-none">
                        <i class="bi bi-graph-up"></i> Dashboard
                    </a>
                    
                    <a href="<?= $path_prefix ?>logout.php" class="btn btn-soft-danger nav-btn-modern text-decoration-none">
                        <i class="bi bi-box-arrow-right"></i> ออกจากระบบ
                    </a>

            <?php else: ?>
                    <a href="<?= $path_prefix ?>login.php" class="btn btn-primary nav-btn-modern shadow-sm border-0 px-4" style="background: linear-gradient(135deg, #0ea5e9 0%, #2563eb 100%);">
                        <i class="bi bi-shield-lock"></i> เข้าสู่ระบบผู้ดูแล
                    </a>
            <?php endif; ?>
            </div>
        </div>
    </div>
</nav>