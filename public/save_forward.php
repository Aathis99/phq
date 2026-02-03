<?php
session_start();
require_once dirname(__DIR__) . '/app/core/Database.php';

if (!isset($_SESSION['user'])) {
    header("Location: login.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $pid = $_POST['pid'] ?? '';
    $referral_agency = $_POST['referral_agency'] ?? '';
    $referral_other = $_POST['referral_other'] ?? '';
    $recorder = $_SESSION['user']['username'];

    if (empty($pid)) {
        echo "<script>alert('ไม่พบข้อมูลนักเรียน'); window.history.back();</script>";
        exit;
    }

    try {
        $db = Database::connect();
        
        $sql = "INSERT INTO forward_case (pid, referral_agency, referral_other, recorder) 
                VALUES (:pid, :referral_agency, :referral_other, :recorder)";
        
        $stmt = $db->prepare($sql);
        $stmt->execute([
            ':pid' => $pid,
            ':referral_agency' => $referral_agency,
            ':referral_other' => $referral_other,
            ':recorder' => $recorder
        ]);

        echo "<script>
            alert('บันทึกการส่งต่อเรียบร้อยแล้ว');
            window.location.href = 'add_case_history.php?pid=" . htmlspecialchars($pid) . "';
        </script>";
        exit;

    } catch (PDOException $e) {
        echo "Error: " . $e->getMessage();
        exit;
    }
} else {
    header("Location: main.php");
    exit;
}