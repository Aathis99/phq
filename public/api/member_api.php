<?php
session_start();
require_once dirname(__DIR__, 2) . '/app/core/Database.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user'])) {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit;
}

$db = Database::connect();
$currentUser = $_SESSION['user']['username'];

// --- ตรวจสอบ Role ของผู้ใช้ปัจจุบัน ---
$stmtRole = $db->prepare("SELECT typeuser FROM users WHERE username = :u");
$stmtRole->execute([':u' => $currentUser]);
$currentRole = $stmtRole->fetchColumn();
$isAdmin = ($currentRole === 'admin');

$method = $_SERVER['REQUEST_METHOD'];

try {
    // --- GET: ดึงข้อมูลสมาชิกทั้งหมด ---
    if ($method === 'GET' && isset($_GET['action']) && $_GET['action'] === 'fetch') {
        $sql = "SELECT u.*, p.prefix_name 
                FROM users u 
                LEFT JOIN prefix p ON u.prefix_id = p.prefix_id";
        
        // ถ้าไม่ใช่ Admin ให้ดึงเฉพาะข้อมูลตัวเอง
        if (!$isAdmin) {
            $sql .= " WHERE u.username = '" . $currentUser . "'";
        }
        
        $sql .= " ORDER BY u.fname ASC";
        $stmt = $db->query($sql);
        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode(['status' => 'success', 'data' => $data]);
        exit;
    }

    // --- POST: บันทึก หรือ ลบ ---
    if ($method === 'POST') {
        $action = $_POST['action'] ?? '';

        if ($action === 'delete') {
            // ตรวจสอบสิทธิ์: เฉพาะ Admin เท่านั้นที่ลบได้
            if (!$isAdmin) {
                echo json_encode(['status' => 'error', 'message' => 'Permission denied']);
                exit;
            }
            // ลบสมาชิก
            $username = $_POST['username'] ?? '';
            if (empty($username)) throw new Exception("Invalid Username");

            // ลบจาก users
            $stmt = $db->prepare("DELETE FROM users WHERE username = :username");
            $stmt->execute([':username' => $username]);
            
            echo json_encode(['status' => 'success']);
            exit;
        }

        if ($action === 'save') {
            // รับค่าจากฟอร์ม
            $old_username = $_POST['old_username'] ?? ''; // ถ้ามีค่า = แก้ไข, ถ้าว่าง = เพิ่มใหม่
            $username = $_POST['username'];
            $password = $_POST['password'] ?? '';
            $typeuser = $_POST['typeuser'] ?? 'user';
            
            $pid = $_POST['pid'];
            $prefix_id = !empty($_POST['prefix_id']) ? $_POST['prefix_id'] : null;
            $fname = $_POST['fname'];
            $lname = $_POST['lname'];
            $position = $_POST['position'];

            $db->beginTransaction();

            if (!empty($old_username)) {
                // --- กรณีแก้ไข (Update) ---
                
                // ตรวจสอบสิทธิ์: ถ้าไม่ใช่ Admin ต้องแก้ไขข้อมูลตัวเองเท่านั้น
                if (!$isAdmin && $old_username !== $currentUser) {
                    throw new Exception("Permission denied: Cannot edit other users.");
                }
                
                // 1. อัปเดตตาราง users (รวมข้อมูลส่วนตัวและ Login)
                $sql_update = "";
                $params = [
                    ':pid' => $pid,
                    ':prefix' => $prefix_id,
                    ':fname' => $fname,
                    ':lname' => $lname,
                    ':pos' => $position,
                    ':user' => $old_username
                ];

                if (!empty($password)) {
                    // กรณีเปลี่ยนรหัสผ่าน
                    if ($isAdmin) {
                        $sql_update = "UPDATE users SET password = :pass, typeuser = :type, pid = :pid, prefix_id = :prefix, fname = :fname, lname = :lname, position = :pos WHERE username = :user";
                        $params[':type'] = $typeuser;
                    } else {
                        $sql_update = "UPDATE users SET password = :pass, pid = :pid, prefix_id = :prefix, fname = :fname, lname = :lname, position = :pos WHERE username = :user";
                    }
                    $params[':pass'] = $password;
                } else {
                    // กรณีไม่เปลี่ยนรหัสผ่าน
                    if ($isAdmin) {
                        $sql_update = "UPDATE users SET typeuser = :type, pid = :pid, prefix_id = :prefix, fname = :fname, lname = :lname, position = :pos WHERE username = :user";
                        $params[':type'] = $typeuser;
                    } else {
                        $sql_update = "UPDATE users SET pid = :pid, prefix_id = :prefix, fname = :fname, lname = :lname, position = :pos WHERE username = :user";
                    }
                }

                $stmt = $db->prepare($sql_update);
                $stmt->execute($params);

            } else {
                // --- กรณีเพิ่มใหม่ (Insert) ---
                
                // ตรวจสอบสิทธิ์: เฉพาะ Admin เท่านั้นที่เพิ่มได้
                if (!$isAdmin) {
                    throw new Exception("Permission denied: Only admin can add new members.");
                }
                
                // เพิ่ม users (รวมข้อมูลทั้งหมด)
                $sql = "INSERT INTO users (username, password, typeuser, pid, prefix_id, fname, lname, position) 
                        VALUES (:user, :pass, :type, :pid, :prefix, :fname, :lname, :pos)";
                $stmt = $db->prepare($sql);
                $stmt->execute([':user' => $username, ':pass' => $password, ':type' => $typeuser, ':pid' => $pid, ':prefix' => $prefix_id, ':fname' => $fname, ':lname' => $lname, ':pos' => $position]);
            }

            $db->commit();
            echo json_encode(['status' => 'success']);
            exit;
        }
    }
} catch (Exception $e) {
    if ($db->inTransaction()) $db->rollBack();
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}