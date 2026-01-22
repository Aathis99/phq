<?php
session_start();
if (isset($_SESSION['user'])) {
    header("Location: index.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login | PHQ System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #ACBAC4; /* สีพื้นหลังสำรอง */
            position: relative;
        }
        /* ใช้ pseudo-element เพื่อปรับ Opacity ของภาพพื้นหลังโดยไม่กระทบเนื้อหา */
        body::before {
            content: "";
            position: fixed;
            top: 0; left: 0; width: 100%; height: 100%;
            background-image: url('image/bg_login.jpg');
            background-size: cover;
            background-position: center;
            background-repeat: no-repeat;
            opacity: 0.8; /* ปรับระดับความจาง (0.0 - 1.0) */
            z-index: -1;
        }
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .card {
            border-radius: 1rem; /* ปรับความโค้งมนของการ์ดให้ดูทันสมัยขึ้น */
            animation: fadeIn 0.7s ease-out;
        }
    </style>
</head>

<body class="d-flex align-items-center min-vh-100 py-4">

    <div class="container">
        <div class="row justify-content-center">
            <div class="col-12 col-sm-10 col-md-8 col-lg-5 col-xl-4">
                <div class="card shadow">
                    <div class="card-body">
                        <h4 class="text-center mb-3">🔐 เข้าสู่ระบบ</h4>

                        <?php if (isset($_GET['error'])): ?>
                            <div class="alert alert-danger">ชื่อผู้ใช้หรือรหัสผ่านไม่ถูกต้อง</div>
                        <?php endif; ?>

                        <form method="post" action="login_process.php">
                            <div class="mb-3">
                                <label class="form-label">Username</label>
                                <input type="text" name="username" class="form-control" value="admin1" required>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Password</label>
                                <input type="password" name="password" class="form-control" value="@1234" required>
                            </div>

                            <button type="submit" class="btn btn-primary w-100">
                                เข้าสู่ระบบ
                            </button>
                        </form>

                        <div class="mt-4 pt-3 border-top text-center">
                            <a href="index.php" class="text-decoration-none text-secondary">← กลับหน้าแบบประเมิน</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

</body>

</html>