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
        $sql = "SELECT m.*, u.typeuser, p.prefix_name 
                FROM member m 
                JOIN users u ON m.username = u.username 
                LEFT JOIN prefix p ON m.prefix_id = p.prefix_id";
        
        // ถ้าไม่ใช่ Admin ให้ดึงเฉพาะข้อมูลตัวเอง
        if (!$isAdmin) {
            $sql .= " WHERE m.username = '" . $currentUser . "'";
        }
        
        $sql .= " ORDER BY m.fname ASC";
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

            // ลบจาก users (member จะถูกลบด้วย Cascade ตาม Schema)
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
                
                // 1. อัปเดตตาราง users
                if (!empty($password)) {
                    // ถ้ากรอกรหัสผ่านใหม่ ให้แก้ด้วย (Plain text ตามระบบเดิม)
                    if ($isAdmin) {
                        // Admin แก้ได้ทุกอย่างรวมถึง Type
                        $sql_u = "UPDATE users SET password = :pass, typeuser = :type WHERE username = :user";
                        $stmt_u = $db->prepare($sql_u);
                        $stmt_u->execute([':pass' => $password, ':type' => $typeuser, ':user' => $old_username]);
                    } else {
                        // Member แก้ได้แค่ Password (ห้ามแก้ Type)
                        $sql_u = "UPDATE users SET password = :pass WHERE username = :user";
                        $stmt_u = $db->prepare($sql_u);
                        $stmt_u->execute([':pass' => $password, ':user' => $old_username]);
                    }
                } else {
                    // ถ้าไม่กรอกรหัสผ่าน
                    if ($isAdmin) {
                        $sql_u = "UPDATE users SET typeuser = :type WHERE username = :user";
                        $stmt_u = $db->prepare($sql_u);
                        $stmt_u->execute([':type' => $typeuser, ':user' => $old_username]);
                    }
                    // ถ้าเป็น Member และไม่เปลี่ยนรหัสผ่าน ก็ไม่ต้องทำอะไรกับตาราง users
                }

                // 2. อัปเดตตาราง member
                $sql_m = "UPDATE member SET prefix_id = :prefix, fname = :fname, lname = :lname, position = :pos 
                          WHERE username = :user";
                $stmt_m = $db->prepare($sql_m);
                $stmt_m->execute([':prefix' => $prefix_id, ':fname' => $fname, ':lname' => $lname, ':pos' => $position, ':user' => $old_username]);

            } else {
                // --- กรณีเพิ่มใหม่ (Insert) ---
                
                // ตรวจสอบสิทธิ์: เฉพาะ Admin เท่านั้นที่เพิ่มได้
                if (!$isAdmin) {
                    throw new Exception("Permission denied: Only admin can add new members.");
                }
                
                // 1. เพิ่ม users
                $sql_u = "INSERT INTO users (username, password, typeuser) VALUES (:user, :pass, :type)";
                $stmt_u = $db->prepare($sql_u);
                $stmt_u->execute([':user' => $username, ':pass' => $password, ':type' => $typeuser]);

                // 2. เพิ่ม member
                $sql_m = "INSERT INTO member (pid, username, prefix_id, fname, lname, position) 
                          VALUES (:pid, :user, :prefix, :fname, :lname, :pos)";
                $stmt_m = $db->prepare($sql_m);
                $stmt_m->execute([':pid' => $pid, ':user' => $username, ':prefix' => $prefix_id, ':fname' => $fname, ':lname' => $lname, ':pos' => $position]);
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