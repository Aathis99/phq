<?php
session_start();
// Redirect to main page if already logged in
if (isset($_SESSION['user_id'])) {
    header("Location: main.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>เข้าสู่ระบบ | ระบบดูแลช่วยเหลือนักเรียน</title>
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Google Fonts (Kanit) -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Kanit:wght@400;600;700&display=swap" rel="stylesheet">

    <!-- Custom CSS -->
    <link rel="stylesheet" href="css/style.css">

</head>
<body>

    <div class="login-container">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-md-6 col-lg-5 col-xl-4">
                    <div class="login-card">
                        <div class="card-header">
                            <h3>ระบบดูแลช่วยเหลือนักเรียน</h3>
                            <p class="mb-0">สำหรับเจ้าหน้าที่</p>
                        </div>
                        <div class="card-body">
                            <form method="post" action="login_process.php" class="needs-validation" novalidate>
                                
                                <?php if (isset($_SESSION['error'])): ?>
                                    <div class="alert alert-danger" role="alert">
                                        <?php 
                                            echo htmlspecialchars($_SESSION['error']);
                                            unset($_SESSION['error']); // Clear the error message after displaying
                                        ?>
                                    </div>
                                <?php endif; ?>

                                <div class="form-floating mb-3">
                                    <input type="text" class="form-control" id="username" name="username" placeholder="Username" required value="admin1">
                                    <label for="username">ชื่อผู้ใช้งาน</label>
                                </div>

                                <div class="form-floating mb-4">
                                    <input type="password" class="form-control" id="password" name="password" placeholder="Password" required value="@1234">
                                    <label for="password">รหัสผ่าน</label>
                                </div>

                                <div class="d-grid">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="bi bi-box-arrow-in-right"></i> เข้าสู่ระบบ
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap 5 JS Bundle -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>