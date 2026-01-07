<?php
// ตรวจสอบชื่อไฟล์ปัจจุบัน
$current_page = basename($_SERVER['PHP_SELF']);
?>
<nav class="navbar navbar-expand-lg navbar-dark bg-primary mb-4">
    <div class="container">
        <a class="navbar-brand" href="index.php">Student Data Management</a>

        <div class="d-flex gap-2">
            <?php if (isset($_SESSION['user'])): ?>

                <?php if ($current_page != 'main.php'): ?>
                    <a href="main.php" class="btn btn-warning btn-sm">จัดการข้อมูล</a>
                <?php endif; ?>
                <!-- ใช้ style เพื่อกำหนด Hex Code สีม่วง -->
                <a href="manage_members.php" class="btn btn-sm text-black" style="background-color: #8ddcdc; border-color: #8ddcdc;">แก้ไขข้อมูลสมาชิก</a>
                <?php
                // --- ส่วนเช็คเงื่อนไขปุ่มย้อนกลับ ---
                // ถ้าอยู่หน้า phq_history.php ให้แสดงปุ่ม "ย้อนกลับ" (กลับไป main.php)
                if ($current_page == 'phq_history.php'):
                ?>
                    <!-- <a href="main.php" class="btn btn-secondary btn-sm border-white text-white">
                        <i class="bi bi-arrow-left"></i> ย้อนกลับ
                    </a> -->
                <?php endif; ?>

                <!-- <a href="index.php" class="btn btn-light text-primary btn-sm">ไปหน้า Index</a> -->



                <a href="logout.php" class="btn btn-danger btn-sm">ออกจากระบบ</a>

            <?php else: ?>
                <!-- <a href="index.php" class="btn btn-success btn-sm">ทำแบบประเมิน</a> -->
                <a href="login.php" class="btn btn-success btn-sm">เข้าสู่ระบบผู้ดูแล</a>
            <?php endif; ?>
        </div>
    </div>
</nav>