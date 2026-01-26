<?php
session_start();
if (isset($_SESSION['user'])) {
    header("Location: index.php");
    exit;
}

$message = '';
if (isset($_SESSION['message'])) {
    $message = $_SESSION['message'];
    unset($_SESSION['message']);
}
?>
<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login | PHQ System</title>
    <!-- SweetAlert2 CSS (optional, but good practice if customizing) -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap JS Bundle (includes Popper) -->
    <!-- Global Stylesheet (for background) -->
    <link href="css/style.css" rel="stylesheet">
    <!-- แยกไฟล์ CSS ออกไปที่ css/login.css -->
    <link href="css/login.css" rel="stylesheet">
</head>

<body class="d-flex align-items-center min-vh-100 py-4">

    <div class="container">
        <div class="row justify-content-center">
            <div class="col-12 col-sm-10 col-md-8 col-lg-5 col-xl-4">
                <div class="card shadow">
                    <div class="card-body">
                        <h4 class="text-center mb-3">🔐 เข้าสู่ระบบ</h4>

                        <?php if (!empty($message)): ?>
                            <div id="session-message" data-type="<?= strpos($message, 'success') !== false ? 'success' : 'error' ?>" data-text="<?= strip_tags($message) ?>"></div>
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

    <!-- SweetAlert2 JS -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="script/javascript/sweetalert_utils.js"></script>
</body>

</html>