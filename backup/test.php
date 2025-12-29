<?php
require_once dirname(__DIR__) . '/app/core/Database.php';
$db = Database::connect();
$sql = "SELECT school_id, school_name FROM school ORDER BY school_id";
$stmt = $db->query($sql);
$schools = $stmt->fetchAll();

/* ดึงคำนำหน้า */
$sql_prefix = "SELECT prefix_id, prefix_name FROM prefix ORDER BY prefix_id";
$stmt_prefix = $db->query($sql_prefix);
$prefixes = $stmt_prefix->fetchAll();

/* ดึงเพศ */
$sql_sex = "SELECT sex_id, sex_name FROM sex ORDER BY sex_id";
$stmt_sex = $db->query($sql_sex);
$sexes = $stmt_sex->fetchAll();
?>
<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>แบบฟอร์มรายงานการช่วยเหลือรายกรณี</title>
    <link href="css/test.css" rel="stylesheet">
</head>
<!-- form2.css -->

<body>
    <div class="container">
        <h1>แบบฟอร์มข้อมูลส่วนตัวนักเรียน</h1>

        <form>
            <hr
                style="margin: 1.5rem 0; border: 0; border-top: 1px solid #e2e8f0" />
            <!-- Personal Info -->
            <section class="personal-info">
                <h2>ข้อมูลส่วนตัวนักเรียน</h2>
                <div class="form-row">
                    <div class="form-col">
                        <label for="id_card">เลขบัตรประชาชน</label>
                        <input
                            type="text"
                            inputmode="numeric"
                            id="id_card"
                            name="id_card"
                            maxlength="13"
                            oninput="this.value = this.value.replace(/[^0-9]/g, '')" />
                    </div>
                </div>
                <div class="form-row" style="margin-top: 1rem">
                    <div class="form-col">
                        <label for="prefix_id">คำนำหน้า</label>
                        <select id="prefix_id" name="prefix_id">
                            <option value="">-- คำนำหน้า --</option>
                            <?php foreach ($prefixes as $prefix): ?>
                                <option value="<?= $prefix['prefix_id'] ?>">
                                    <?= htmlspecialchars($prefix['prefix_name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-col">
                        <label for="firstname">ชื่อ</label>
                        <input type="text" id="firstname" name="firstname" />
                    </div>
                    <div class="form-col">
                        <label for="lastname">นามสกุล</label>
                        <input type="text" id="lastname" name="lastname" />
                    </div>
                </div>

                <div class="form-row" style="margin-top: 1rem">
                    <div class="form-col">
                        <label for="gender">เพศ</label>
                        <select id="gender" name="gender">
                            <option value="">-- เลือกเพศ --</option>
                            <?php foreach ($sexes as $sex): ?>
                                <option value="<?= $sex['sex_id'] ?>">
                                    <?= htmlspecialchars($sex['sex_name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-col">
                        <label for="age">อายุ</label>
                        <select id="age" name="age">
                            <option value="">-- เลือกอายุ --</option>
                        </select>
                    </div>
                </div>

                <div class="form-row" style="margin-top: 1rem">
                    <div class="form-col">
                        <label for="edu_level">ระดับชั้น</label>
                        <select id="edu_level" name="edu_level">
                            <option value="">-- เลือกระดับชั้น --</option>
                        </select>
                    </div>
                    <div class="form-col">
                        <label for="edu_room">ห้อง</label>
                        <select id="edu_room" name="edu_room">
                            <option value="">-- เลือกห้อง --</option>
                        </select>
                    </div>
                </div>

                <div class="form-group" style="margin-top: 1rem">
                    <label for="school">โรงเรียน</label>
                    <select id="school" name="school">
                        <option value="">-- เลือกโรงเรียน --</option>
                        <?php foreach ($schools as $school): ?>
                            <option value="<?= $school['school_id'] ?>">
                                <?= htmlspecialchars($school['school_name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </section>

            <!-- Footer -->
            <section class="footer-info">
                <div class="form-row">
                    <div class="form-col">
                        <label for="recorder">เบอร์โทรศัพท์</label>
                        <input 
                            type="text" 
                            id="recorder" 
                            name="recorder" 
                            maxlength="10" 
                            minlength="10"
                            inputmode="numeric"
                            oninput="this.value = this.value.replace(/[^0-9]/g, '')"
                            title="กรุณากรอกเบอร์โทรศัพท์ให้ครบ 10 หลัก"
                            required />
                    </div>
                    <div class="form-col">
                        <label for="record_date">วันที่บันทึก (แก้ไขไม่ได้)</label>
                        <input type="text" id="record_date" name="record_date" readonly />
                    </div>
                </div>
            </section>

            <!-- <button type="button" class="submit-btn">บันทึกข้อมูล</button> -->
        </form>
    </div>
    <script src="../public/script/form2.js"></script>
</body>

</html>