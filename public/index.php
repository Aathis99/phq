<?php
session_start();
require_once dirname(__DIR__) . '/app/core/Database.php';


$message = '';
if (isset($_SESSION['message'])) {
    $message = $_SESSION['message'];
    unset($_SESSION['message']);
}
$db = null;

try {
    $db = Database::connect();
    $db->exec("SET NAMES utf8mb4");

    // --- ดึงข้อมูลสำหรับ Dropdowns ---
    $sql_schools = "SELECT school_id, school_name FROM school ORDER BY school_id";
    $stmt_schools = $db->query($sql_schools);
    $schools = $stmt_schools->fetchAll();

    $sql_prefix = "SELECT prefix_id, prefix_name FROM prefix ORDER BY prefix_id";
    $stmt_prefix = $db->query($sql_prefix);
    $prefixes = $stmt_prefix->fetchAll();

    $sql_sex = "SELECT sex_id, sex_name FROM sex ORDER BY sex_id";
    $stmt_sex = $db->query($sql_sex);
    $sexes = $stmt_sex->fetchAll();

    // --- ดึงคำถาม PHQ-9 ---
    $sql_phq = "SELECT id, question FROM phq_question WHERE id BETWEEN 1 AND 9 ORDER BY id";
    $stmt_phq = $db->query($sql_phq);
    $phq_questions = $stmt_phq->fetchAll();

    // --- ส่วนประมวลผลฟอร์มเมื่อมีการส่งข้อมูล (POST) ---
    if ($_SERVER["REQUEST_METHOD"] == "POST") {
        // ... (Logic การบันทึกข้อมูล เหมือนเดิม) ...
        // copy logic เดิมมาใส่ตรงนี้ หรือใช้ไฟล์เดิมแต่แก้ส่วน HTML ด้านล่าง
           // --- ตรวจสอบและรับข้อมูล ---
        if (empty($_POST['pid']) || !is_numeric($_POST['pid']) || strlen((string)$_POST['pid']) != 13) {
            $message = '<p class="message error">กรุณากรอกเลขบัตรประชาชน 13 หลักให้ถูกต้อง</p>';
        } else {
            // --- บันทึก/อัปเดต ข้อมูลนักเรียน (student_data) ---
            $student_pid = $_POST['pid'];
            $prefix_id = !empty($_POST['prefix_id']) ? (int)$_POST['prefix_id'] : null;
            $firstname = !empty($_POST['firstname']) ? $_POST['firstname'] : null;
            $lastname = !empty($_POST['lastname']) ? $_POST['lastname'] : null;
            $gender = !empty($_POST['gender']) ? (int)$_POST['gender'] : null;
            $school_id = !empty($_POST['school']) ? (int)$_POST['school'] : null;
            $age = !empty($_POST['age']) ? (int)$_POST['age'] : null;
            $class_level = !empty($_POST['class_level']) ? $_POST['class_level'] : null;
            $room = !empty($_POST['room']) ? $_POST['room'] : null;
            $tel = !empty($_POST['tel']) ? $_POST['tel'] : null;
            $student_update_time = date('Y-m-d H:i:s');

            $sql_student = "INSERT INTO student_data (pid, prefix_id, fname, lname, sex, school_id, age, class, room, tel, date_time)
                            VALUES (:pid, :prefix_id, :fname, :lname, :sex, :school_id, :age, :class, :room, :tel, :date_time)
                            ON DUPLICATE KEY UPDATE
                                prefix_id = VALUES(prefix_id), fname = VALUES(fname), lname = VALUES(lname),
                                sex = VALUES(sex), school_id = VALUES(school_id), age = VALUES(age), class = VALUES(class),
                                room = VALUES(room), tel = VALUES(tel), date_time = VALUES(date_time)";
            
            $stmt_student = $db->prepare($sql_student);
            $stmt_student->execute([
                ':pid' => $student_pid, ':prefix_id' => $prefix_id, ':fname' => $firstname,
                ':lname' => $lastname, ':sex' => $gender, ':school_id' => $school_id, ':age' => $age,
                ':class' => $class_level, ':room' => $room, ':tel' => $tel,
                ':date_time' => $student_update_time
            ]);


            // --- คำนวณคะแนนรวมจาก c1-c9 ---
            $score = 0;
            $c_answers = [];
            for ($i = 1; $i <= 9; $i++) {
                $c_answers['c' . $i] = isset($_POST['c' . $i]) ? (int)$_POST['c' . $i] : null;
                if ($c_answers['c' . $i] !== null) {
                    $score += $c_answers['c' . $i];
                }
            }

            // --- รับข้อมูลจากฟอร์ม ---
            $pid = $_POST['pid'];
            $date_time = date('Y-m-d H:i:s');
            $c10 = isset($_POST['c10']) ? (int)$_POST['c10'] : null;
            $c11 = isset($_POST['c11']) ? (int)$_POST['c11'] : null;
            $stress = !empty($_POST['stress']) ? $_POST['stress'] : null;
            $manage_stress = !empty($_POST['manage_stress']) ? $_POST['manage_stress'] : null;

            // --- SQL INSERT Statement ---
            // ใช้ Prepared Statements เพื่อป้องกัน SQL Injection
            $sql = "INSERT INTO assessment (pid, date_time, c1, c2, c3, c4, c5, c6, c7, c8, c9, c10, c11, stress, manage_stress, score) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            $stmt = $db->prepare($sql);

            // --- Bind Parameters ---
            $stmt->bindParam(1, $pid);
            $stmt->bindParam(2, $date_time);
            for ($i = 1; $i <= 9; $i++) {
                $stmt->bindParam($i + 2, $c_answers['c' . $i]);
            }
            $stmt->bindParam(12, $c10);
            $stmt->bindParam(13, $c11);
            $stmt->bindParam(14, $stress);
            $stmt->bindParam(15, $manage_stress);
            $stmt->bindParam(16, $score);

            // --- Execute และแสดงผล ---
            if ($stmt->execute()) {
                $_SESSION['message'] = '<p class="message success">บันทึกข้อมูลการประเมินเรียบร้อยแล้ว</p>';
                header("Location: " . $_SERVER['PHP_SELF']);
                exit;
            } else {
                $errorInfo = $stmt->errorInfo();
                $message = '<p class="message error">เกิดข้อผิดพลาดในการบันทึกข้อมูล: ' . $errorInfo[2] . '</p>';
            }
        }
    }
} catch (PDOException $e) {
    $message = '<p class="message error">การเชื่อมต่อฐานข้อมูลล้มเหลว: ' . $e->getMessage() . '</p>';
}
$db = null;
?>
<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>แบบประเมินภาวะซึมเศร้าในวัยรุ่น</title>
    <link rel="icon" href="images/favicon.png" type="image/png">
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="css/index.css" rel="stylesheet">
</head>

<body>

    <?php include 'navbar.php'; ?>

    <div class="main-card">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1>แบบประเมินภาวะซึมเศร้าในวัยรุ่น</h1>
            <a href="graphs/dashboard.php" class="btn btn-primary">📊 คลิกเพื่อดูสถิติ</a>
        </div>

        <?php if (!empty($message)) {
            echo $message;
        } ?>

        <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
            <section class="personal-info">
                <h2>ข้อมูลส่วนตัวนักเรียน</h2>
                <div class="form-row">
                    <div class="form-col">
                        <label for="pid">เลขบัตรประชาชน</label>
                        <input type="text" inputmode="numeric" id="pid" name="pid" maxlength="13" oninput="this.value = this.value.replace(/[^0-9]/g, '')" required />
                    </div>
                </div>
                <div class="form-row" style="margin-top: 1rem">
                    <div class="form-col">
                        <label for="prefix_id">คำนำหน้า</label>
                        <select id="prefix_id" name="prefix_id">
                            <option value="">-- คำนำหน้า --</option>
                            <?php foreach ($prefixes as $prefix): ?>
                                <option value="<?= $prefix['prefix_id'] ?>"><?= htmlspecialchars($prefix['prefix_name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-col">
                        <label for="firstname">ชื่อ</label>
                        <input type="text" id="firstname" name="firstname" required />
                    </div>
                    <div class="form-col">
                        <label for="lastname">นามสกุล</label>
                        <input type="text" id="lastname" name="lastname" required />
                    </div>
                </div>
                <div class="form-row" style="margin-top: 1rem">
                    <div class="form-col">
                        <label for="gender">เพศ</label>
                        <select id="gender" name="gender" required>
                            <option value="">-- เลือกเพศ --</option>
                            <?php foreach ($sexes as $sex): ?>
                                <option value="<?= $sex['sex_id'] ?>"><?= htmlspecialchars($sex['sex_name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-col">
                        <label for="age">อายุ</label>
                        <select id="age" name="age" required>
                            <option value="">-- เลือกอายุ --</option>
                            <?php for ($i = 12; $i <= 20; $i++): ?>
                                <option value="<?= $i ?>"><?= $i ?></option>
                            <?php endfor; ?>
                        </select>
                    </div>
                </div>
                <div class="form-row" style="margin-top: 1rem;">
                    <div class="form-col">
                        <label for="school">โรงเรียน</label>
                        <select id="school" name="school" required>
                            <option value="">-- เลือกโรงเรียน --</option>
                            <?php foreach ($schools as $school): ?>
                                <option value="<?= $school['school_id'] ?>"><?= htmlspecialchars($school['school_name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="form-row" style="margin-top: 1rem;">
                    <div class="form-col">
                        <label for="class_level">ระดับชั้นมัธยมศึกษา (ม.)</label>
                        <select id="class_level" name="class_level" required>
                            <option value="">-- เลือกระดับชั้น --</option>
                            <?php for ($i = 1; $i <= 6; $i++): ?>
                                <option value="<?= $i ?>"><?= $i ?></option>
                            <?php endfor; ?>
                        </select>
                    </div>
                    <div class="form-col">
                        <label for="room">ห้อง</label>
                        <select id="room" name="room" required>
                            <option value="">-- เลือกห้อง --</option>
                            <?php for ($i = 1; $i <= 20; $i++): ?>
                                <option value="<?= $i ?>"><?= $i ?></option>
                            <?php endfor; ?>
                        </select>
                    </div>
                </div>
                <div class="form-row" style="margin-top: 1rem;">
                    <div class="form-col">
                        <label for="tel">เบอร์โทรศัพท์</label>
                        <input type="text" id="tel" name="tel" inputmode="numeric" pattern="[0-9]{10}" maxlength="10" minlength="10" oninput="this.value = this.value.replace(/[^0-9]/g, '')" title="กรุณากรอกเบอร์โทรศัพท์ 10 หลัก" required />
                    </div>
                </div>
            </section>

            <section class="assessment-section">
                <h2>ส่วนประเมิน (PHQ-9)</h2>
                <p>ในช่วง 2 สัปดาห์ที่ผ่านมา รวมทั้งวันนี้ ท่านมีอาการเหล่านี้บ่อยแค่ไหน</p>

                <?php
                $phq_options = [
                    0 => 'ไม่มีเลย',
                    1 => 'เป็นบางวัน (1-7 วัน)',
                    2 => 'เป็นบ่อย (มากกว่า 7 วัน)',
                    3 => 'เป็นทุกวัน'
                ];
                ?>
                <?php foreach ($phq_questions as $q): ?>
                    <div class="question-group">
                        <label class=""><?= $q['id'] . '. ' . htmlspecialchars($q['question']) ?></label>
                        <div class="radio-group radio-buttons">
                            <?php foreach ($phq_options as $value => $text): ?>
                                <input type="radio" id="c<?= $q['id'] ?>-<?= $value ?>" name="c<?= $q['id'] ?>" value="<?= $value ?>" required>
                                <label for="c<?= $q['id'] ?>-<?= $value ?>"><?= $text ?></label>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endforeach; ?>

                <div class="form-group">
                    <p><strong>คะแนนรวม (c1-c9): <span id="score-display" class="score-display">0</span></strong></p>
                </div>

                <hr style="margin: 1.5rem 0; border: 0; border-top: 1px solid #e2e8f0" />

                <div class="question-group">
                    <label>10. ใน 1 เดือน ที่ผ่านมา มีช่วงไหนที่คุณมีความคิดอยากตายหรือไม่อยากมีชีวิตอยู่อย่างจริงจังหรือไม่</label>
                    <div class="radio-group radio-buttons">
                        <input type="radio" id="c10-1" name="c10" value="1" required><label for="c10-1">มี</label>
                        <input type="radio" id="c10-0" name="c10" value="0" required><label for="c10-0">ไม่มี</label>
                    </div>
                </div>

                <div class="question-group">
                    <label>11. ตลอดชีวิตที่ผ่านมาคุณเคยพยายามที่จะทำให้ตัวเองตายหรือลงมือฆ่าตัวตายหรือไม่</label>
                    <div class="radio-group radio-buttons">
                        <input type="radio" id="c11-1" name="c11" value="1" required><label for="c11-1">เคย</label>
                        <input type="radio" id="c11-0" name="c11" value="0" required><label for="c11-0">ไม่เคย</label>
                    </div>
                </div>

                <div class="form-group">
                    <label for="stress">12. ความเครียดของหนูเกิดจากปัญหาเรื่องใดมากที่สุด (เช่น การเรียน เพื่อน ครอบครัว)</label>
                    <textarea id="stress" name="stress" rows="3" class="form-control"></textarea>
                </div>

                <div class="form-group">
                    <label for="manage_stress">13. หนูคิดว่าความสามารถในการจัดการความเครียดได้ด้วยตัวเองอยู่ในระดับใด (เช่น ดีมาก ดี ค่อนข้างน้อย ไม่ได้เลย)</label>
                    <textarea id="manage_stress" name="manage_stress" rows="3" class="form-control"></textarea>
                </div>
            </section>

            <button type="submit" class="submit-btn">บันทึกข้อมูลการประเมิน</button>
        </form>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const form = document.querySelector('form');
            const scoreDisplay = document.getElementById('score-display');
            const phqRadios = form.querySelectorAll('input[type="radio"][name^="c"]');

            function calculateScore() {
                let totalScore = 0;
                for (let i = 1; i <= 9; i++) {
                    const radio = form.querySelector(`input[name="c${i}"]:checked`);
                    if (radio) {
                        totalScore += parseInt(radio.value, 10);
                    }
                }
                scoreDisplay.textContent = totalScore;
            }

            phqRadios.forEach(radio => {
                // ตรวจสอบเฉพาะ c1-c9
                if (parseInt(radio.name.substring(1)) <= 9) {
                    radio.addEventListener('change', calculateScore);
                }
            });

            // คำนวณคะแนนครั้งแรกเมื่อโหลดหน้า
            calculateScore();
        });
    </script>
</body>

</html>