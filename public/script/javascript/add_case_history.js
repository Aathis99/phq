// function แสดง Sweet Alert เมื่อพยายามบันทึกเคสที่ถูกปิดไปแล้ว
        function showClosureAlert() {
            Swal.fire({
                icon: 'error',
                title: 'ไม่สามารถบันทึกได้',
                text: 'นักเรียนคนนี้ ได้ยุติการช่วยเหลือไปแล้ว ตรวจสอบรายละเอียด หรือ พิมพ์รายงาน ได้ที่ปุ่มดูข้อมูล',
                confirmButtonText: 'ตกลง'
            });
        }

// function ตรวจสอบจำนวนรูปภาพก่อนส่งฟอร์มแก้ไขเคส (จำนวนรูปเดิม - จำนวนรูปที่ติ๊กลบ) + จำนวนรูปที่อัปโหลดใหม่ พร้อม sweet alert
        function validateAndSubmitEditForm(form, existingImageCount) {
            const newImagesInput = form.querySelector('input[name="new_images[]"]');
            const newImagesCount = newImagesInput ? newImagesInput.files.length : 0;
            
            const imagesToDeleteCount = form.querySelectorAll('input[name="delete_images[]"]:checked').length;
            
            const finalImageCount = (existingImageCount - imagesToDeleteCount) + newImagesCount;
            
            if (finalImageCount > 4) {
                Swal.fire({
                    icon: 'warning',
                    title: 'รูปภาพเกินจำนวนที่กำหนด',
                    text: 'รูปต้องไม่เกิน 4 รูป หากต้องการอัพโหลด กรุณาติกลบรูปเดิมก่อน',
                    confirmButtonText: 'ตกลง'
                });
                return false; // หยุดการส่งฟอร์ม
            }
            
            // หากผ่านการตรวจสอบ ให้ส่งฟอร์ม
            form.submit();
        }

        // --- New code for image preview in edit modals ---
        document.addEventListener('DOMContentLoaded', function() {
            const allNewImageInputs = document.querySelectorAll('.new-images-input');
            allNewImageInputs.forEach(input => {
                // Store a DataTransfer object for each input
                input.dataTransfer = new DataTransfer();

                input.addEventListener('change', function(e) {
                    const files = Array.from(this.files);

                    // Add new files to our DataTransfer object
                    files.forEach(file => {
                        let exists = Array.from(this.dataTransfer.files).some(f => f.name === file.name && f.size === file.size);
                        if (!exists) {
                            this.dataTransfer.items.add(file);
                        }
                    });

                    updateImagePreview(this.id);
                });
            });
        });

        function updateImagePreview(inputId) {
            const input = document.getElementById(inputId);
            if (!input) return;

            input.files = input.dataTransfer.files; // Update the actual input's file list
            const previewContainerId = 'preview_container_' + inputId.split('_').pop();
            const previewContainer = document.getElementById(previewContainerId);
            previewContainer.innerHTML = ''; // Clear existing previews

            Array.from(input.dataTransfer.files).forEach((file, index) => {
                const reader = new FileReader();
                reader.onload = function(e) {
                    const div = document.createElement('div');
                    div.className = 'position-relative';
                    div.innerHTML = `
                        <img src="${e.target.result}" class="rounded border shadow-sm" style="width: 80px; height: 80px; object-fit: cover;">
                        <button type="button" class="btn btn-danger btn-sm position-absolute top-0 end-0 rounded-circle p-0 d-flex align-items-center justify-content-center" 
                                style="width: 20px; height: 20px; transform: translate(30%, -30%);" 
                                onclick="removeNewImage('${inputId}', ${index})">
                            &times;
                        </button>
                    `;
                    previewContainer.appendChild(div);
                }
                reader.readAsDataURL(file);
            });
        }

        function removeNewImage(inputId, index) {
            const input = document.getElementById(inputId);
            if (input && input.dataTransfer) {
                input.dataTransfer.items.remove(index);
                updateImagePreview(inputId);
            }
        }