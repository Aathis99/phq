/**
 * คำนวณปีการศึกษาและเทอมจากวันที่ (รูปแบบ YYYY-MM-DD)
 * @param {string} dateString - วันที่จาก input type="date"
 * @returns {Object} { academicYear, semester }
 */
function getAcademicData(dateString) {
  if (!dateString) return { academicYear: null, semester: null };

  const date = new Date(dateString);
  // ตรวจสอบว่าเป็นวันที่ที่ถูกต้องหรือไม่
  if (isNaN(date.getTime())) return { academicYear: null, semester: null };

  const month = date.getMonth() + 1; // 1-12
  const yearAD = date.getFullYear();
  const yearBE = yearAD + 543;

  let term = 0;
  let academicYear = 0;

  // Logic: พฤษภาคม (5) - ตุลาคม (10) = เทอม 1
  // พฤศจิกายน (11) - เมษายน (4) = เทอม 2
  if (month >= 5 && month <= 10) {
    term = 1;
    academicYear = yearBE;
  } else {
    term = 2;
    // ถ้าเป็นเดือน 11, 12 คือเทอม 2 ของปี พ.ศ. นั้น
    // ถ้าเป็นเดือน 1, 2, 3, 4 คือเทอม 2 ของปีการศึกษา (พ.ศ. - 1)
    if (month >= 11) {
      academicYear = yearBE;
    } else {
      academicYear = yearBE - 1;
    }
  }

  return { academicYear, semester: term };
}

/**
 * ฟังก์ชันสำหรับผูก Event กับ Input Date เพื่ออัปเดตค่าปีการศึกษาและเทอมอัตโนมัติ
 * @param {string} dateInputId - ID ของ input type="date"
 * @param {Object} targetIds - ID ของ input ปลายทาง { academicYearId, semesterId, showYearId, showTermId }
 */
function bindAcademicYearLogic(dateInputId, targetIds) {
  const dateInput = document.getElementById(dateInputId);
  if (!dateInput) return;

  const updateFunc = () => {
    const { academicYear, semester } = getAcademicData(dateInput.value);

    if (academicYear && semester) {
      // อัปเดตค่าลงใน Hidden Fields (สำหรับบันทึกเข้าฐานข้อมูล)
      if (targetIds.academicYearId) {
        const el = document.getElementById(targetIds.academicYearId);
        if (el) el.value = academicYear;
      }
      if (targetIds.semesterId) {
        const el = document.getElementById(targetIds.semesterId);
        if (el) el.value = semester;
      }

      // อัปเดตค่าลงใน Display Fields (สำหรับแสดงผลให้ผู้ใช้เห็น)
      if (targetIds.showYearId) {
        const el = document.getElementById(targetIds.showYearId);
        if (el) el.value = "ปี " + academicYear;
      }
      if (targetIds.showTermId) {
        const el = document.getElementById(targetIds.showTermId);
        if (el) el.value = "เทอม " + semester;
      }
    }
  };

  // ผูก event change เพื่อให้ทำงานเมื่อมีการเปลี่ยนวันที่
  dateInput.addEventListener("change", updateFunc);

  // เรียกทำงานครั้งแรกทันที (กรณีมีการกำหนดค่าเริ่มต้นให้วันที่)
  updateFunc();
}
