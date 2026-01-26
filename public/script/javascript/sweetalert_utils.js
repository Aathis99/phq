/**
 * public/script/javascript/sweetalert_utils.js
 * ไฟล์สำหรับรวมฟังก์ชัน SweetAlert2 ที่สามารถเรียกใช้ซ้ำได้
 */

/**
 * แสดง SweetAlert2 สำหรับข้อความสำเร็จ
 * @param {string} title - หัวข้อ
 * @param {string} text - ข้อความ
 */
function showSuccessAlert(title, text = '') {
    Swal.fire({
        icon: 'success',
        title: title,
        text: text,
        showConfirmButton: false,
        timer: 2000,
        timerProgressBar: true
    });
}

/**
 * แสดง SweetAlert2 สำหรับข้อความผิดพลาด
 * @param {string} title - หัวข้อ
 * @param {string} text - ข้อความ
 */
function showErrorAlert(title, text = '') {
    Swal.fire({
        icon: 'error',
        title: title,
        text: text,
        confirmButtonText: 'ตกลง'
    });
}

/**
 * แสดง SweetAlert2 สำหรับข้อความแจ้งเตือนทั่วไป
 * @param {string} title - หัวข้อ
 * @param {string} text - ข้อความ
 */
function showInfoAlert(title, text = '') {
    Swal.fire({
        icon: 'info',
        title: title,
        text: text,
        confirmButtonText: 'ตกลง'
    });
}

/**
 * แสดง SweetAlert2 สำหรับข้อความยืนยันการกระทำ
 * @param {string} title - หัวข้อ
 * @param {string} text - ข้อความ
 * @param {function} callback - ฟังก์ชันที่จะเรียกเมื่อผู้ใช้ยืนยัน
 */
function showConfirmAlert(title, text, callback) {
    Swal.fire({
        title: title,
        text: text,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#3085d6',
        cancelButtonColor: '#d33',
        confirmButtonText: 'ใช่, ยืนยัน!',
        cancelButtonText: 'ยกเลิก'
    }).then((result) => {
        if (result.isConfirmed) {
            callback();
        }
    });
}

// ตรวจสอบ session message จาก PHP และแสดง SweetAlert2 เมื่อ DOM โหลดเสร็จ
document.addEventListener('DOMContentLoaded', () => {
    const sessionMessage = document.getElementById('session-message');
    if (sessionMessage) {
        const messageType = sessionMessage.dataset.type; // 'success' or 'error'
        const messageText = sessionMessage.dataset.text;

        if (messageType === 'success') {
            showSuccessAlert('สำเร็จ!', messageText);
        } else if (messageType === 'error') {
            showErrorAlert('เกิดข้อผิดพลาด!', messageText);
        }
        // ลบ element ออกจาก DOM เพื่อไม่ให้แสดงซ้ำหากมีการโหลดหน้าใหม่โดยไม่ผ่าน session
        sessionMessage.remove();
    }
});