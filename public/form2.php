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
?>
<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>แบบฟอร์มรายงานการช่วยเหลือรายกรณี</title>
    <link href="css/form2.css" rel="stylesheet">
</head>
<!-- form2.css -->

<body>
    <div class="container">
        <h1>แบบฟอร์มรายงานการช่วยเหลือรายกรณี</h1>

        <form>
            <!-- Case Info -->
            <section class="case-info">
                <div class="form-row">
                    <div class="form-col">
                        <label for="case_type">กรณี</label>
                        <select id="case_type" name="case_type">
                            <option value="">-- เลือกกรณี --</option>
                            <option value="ซึมเศร้า">ซึมเศร้า</option>
                            <option value="เครียด">เครียด</option>
                            <option value="วิตกกังวล">วิตกกังวล</option>
                            <option value="ปัญหาครอบครัว">ปัญหาครอบครัว</option>
                            <option value="อื่นๆ">อื่นๆ</option>
                        </select>
                    </div>
                    <div class="form-col">
                        <label for="case_id">ครั้งที่ (รันอัตโนมัติ)</label>
                        <input type="text" id="case_id" name="case_id" readonly />
                    </div>
                    <div class="form-col">
                        <label for="report_date">วัน/เดือน/ปี</label>
                        <input type="date" id="report_date" name="report_date" />
                    </div>
                </div>
            </section>

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
                            <option value="ชาย">ชาย</option>
                            <option value="หญิง">หญิง</option>
                            <option value="ทางเลือก">ทางเลือก</option>
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

            <!-- Details -->
            <section class="details">
                <h2>อาการและข้อมูลทั่วไป</h2>
                <div class="form-group">
                    <label for="presenting_symptoms">อาการนำ (Presenting Symptoms)</label>
                    <textarea
                        id="presenting_symptoms"
                        name="presenting_symptoms"
                        placeholder="ระบุอาการเบื้องต้น..."></textarea>
                </div>

                <h3>ลักษณะทั่วไป</h3>
                <div class="form-group">
                    <label for="history_personal">ประวัติส่วนตัว</label>
                    <textarea id="history_personal" name="history_personal"></textarea>
                </div>
                <div class="form-group">
                    <label for="history_family">ข้อมูลจากครอบครัว</label>
                    <textarea id="history_family" name="history_family"></textarea>
                </div>
                <div class="form-group">
                    <label for="history_school">ข้อมูลจากโรงเรียน</label>
                    <textarea id="history_school" name="history_school"></textarea>
                </div>
                <div class="form-group">
                    <label for="personal_habits">นิสัยส่วนตัว</label>
                    <textarea id="personal_habits" name="personal_habits"></textarea>
                </div>
                <div class="form-group">
                    <label for="history_hospital">ข้อมูลจากโรงพยาบาล</label>
                    <textarea id="history_hospital" name="history_hospital"></textarea>
                </div>
                <div class="form-group">
                    <label for="consultation_details">รายละเอียดการให้การปรึกษา</label>
                    <textarea
                        id="consultation_details"
                        name="consultation_details"></textarea>
                </div>
                <div class="form-group">
                    <label for="event_details">รายละเอียดเหตุการณ์</label>
                    <textarea id="event_details" name="event_details"></textarea>
                </div>
            </section>

            <!-- Assistance Guidelines -->
            <section class="assistance">
                <h2>แนวทางการดูแลช่วยเหลือ</h2>
                <div class="guidelines-grid">
                    <div class="form-group">
                        <label for="assist_school">1. โรงเรียน</label>
                        <textarea id="assist_school" name="assist_school"></textarea>
                    </div>
                    <div class="form-group">
                        <label for="assist_hospital">2. โรงพยาบาล</label>
                        <textarea id="assist_hospital" name="assist_hospital"></textarea>
                    </div>
                    <div class="form-group">
                        <label for="assist_parent">3. ผู้ปกครอง</label>
                        <textarea id="assist_parent" name="assist_parent"></textarea>
                    </div>
                    <div class="form-group">
                        <label for="assist_other">4. หน่วยงานอื่น ๆ ที่เกี่ยวข้อง</label>
                        <textarea id="assist_other" name="assist_other"></textarea>
                    </div>
                </div>
                <div class="form-group">
                    <label for="suggestions">ข้อเสนอแนะ</label>
                    <textarea id="suggestions" name="suggestions"></textarea>
                </div>
            </section>

            <!-- Footer -->
            <section class="footer-info">
                <div class="form-row">
                    <div class="form-col">
                        <label for="recorder">ผู้บันทึก</label>
                        <input type="text" id="recorder" name="recorder" />
                    </div>
                    <div class="form-col">
                        <label for="record_date">วันที่บันทึก (แก้ไขไม่ได้)</label>
                        <input type="text" id="record_date" name="record_date" readonly />
                    </div>
                </div>
            </section>

            <button type="button" class="submit-btn">บันทึกข้อมูล</button>
        </form>
    </div>
    <script src="../public/script/javascript/form2.js"></script>
</body>

</html>