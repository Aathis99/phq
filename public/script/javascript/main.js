 document.addEventListener('DOMContentLoaded', function() {
            let currentPage = 1;
            const tableBody = document.getElementById('tableBody');
            const loading = document.getElementById('loading');
            const searchInput = document.getElementById('searchInput');
            const filterStatus = document.getElementById('filterStatus');
            const paginationContainer = document.getElementById('pagination');

            // Restore state from sessionStorage (กู้คืนค่าที่บันทึกไว้)
            if (sessionStorage.getItem('phq_search')) {
                searchInput.value = sessionStorage.getItem('phq_search');
            }
            if (sessionStorage.getItem('phq_filter') && filterStatus) {
                filterStatus.value = sessionStorage.getItem('phq_filter');
            }
            if (sessionStorage.getItem('phq_page')) {
                currentPage = parseInt(sessionStorage.getItem('phq_page'));
            }

            window.loadData = function(page = 1) {
                // ถ้า page เป็น true (จากปุ่ม reset) ให้เป็น 1
                if (page === true) page = 1;
                
                currentPage = page;
                
                // Save state to sessionStorage (บันทึกค่าลงใน sessionStorage)
                sessionStorage.setItem('phq_search', searchInput.value);
                if (filterStatus) {
                    sessionStorage.setItem('phq_filter', filterStatus.value);
                }
                sessionStorage.setItem('phq_page', currentPage);

                loading.style.display = 'block';
                tableBody.innerHTML = ''; // ล้างข้อมูลเก่า
                if(paginationContainer) paginationContainer.innerHTML = '';

                const search = searchInput.value;
                const filter = filterStatus ? filterStatus.value : 'all';
                const url = `main.php?action=fetch_data&page=${page}&search=${encodeURIComponent(search)}&filter=${encodeURIComponent(filter)}`;

                fetch(url)
                    .then(response => response.json())
                    .then(json => {
                        if (json.status === 'success') {
                            const data = json.data;
                            const pagination = json.pagination;

                            if (data.length > 0) {
                                data.forEach((row) => {
                                    const tr = document.createElement('tr');
                                    
                                    // ตรวจสอบว่ามีรายงานการยุติหรือไม่ ถ้ามีให้เปลี่ยนสีพื้นหลัง
                                    if (row.has_closure > 0) {
                                        tr.style.backgroundColor = '#5DD3B6';
                                        tr.style.setProperty('--bs-table-bg', '#5DD3B6');
                                    } else if (row.has_forward > 0) {
                                        tr.style.backgroundColor = '#B7BDF7';
                                        tr.style.setProperty('--bs-table-bg', '#B7BDF7');
                                    }

                                    // ${row.pid} อยากแสดง เลขบัตรประชาชนด้วย ให้เพิ่ม ไปตรงกลาง <br><small class="text-muted">+++++++++++++</small></td>
                                    tr.innerHTML = `
                                        <td>${(row.prefix_name || '')} ${row.fname} ${row.lname} <br><small class="text-muted"></small></td>
                                        <td>${row.sex_name || '-'}</td>
                                        <td>${row.age || '-'}</td>
                                        <td>${row.school_name || '-'}</td>
                                        <td class="text-center">${row.class || '-'}/${row.room || '-'}</td>
                                        <td class="text-center">${row.tel || '-'}</td>
                                        <td class="text-center">
                                            <a href="phq_history.php?pid=${row.pid}" class="btn btn-sm btn-info text-white">
                                                📜 ดูประวัติ
                                            </a>
                                        </td>
                                    `;
                                    tableBody.appendChild(tr);
                                });
                                renderPagination(pagination);
                            } else {
                                tableBody.innerHTML = '<tr><td colspan="7" class="text-center p-4 text-muted">ไม่พบข้อมูล</td></tr>';
                            }
                        }
                    })
                    .catch(err => console.error(err))
                    .finally(() => {
                        loading.style.display = 'none';
                    });
            };

            function renderPagination(pagination) {
                const totalPages = pagination.total_pages;
                const current = pagination.current_page;
                let html = '';

                // ปุ่มหน้าแรก และ ย้อนกลับ
                html += `<li class="page-item ${current === 1 ? 'disabled' : ''}">
                            <a class="page-link" href="#" onclick="loadData(1); return false;">หน้าแรก</a>
                         </li>`;
                html += `<li class="page-item ${current === 1 ? 'disabled' : ''}">
                            <a class="page-link" href="#" onclick="loadData(${current - 1}); return false;">ย้อนกลับ</a>
                         </li>`;

                // แสดงเลขหน้าปัจจุบัน / ทั้งหมด
                html += `<li class="page-item disabled"><span class="page-link">หน้า ${current} จาก ${totalPages}</span></li>`;

                // ปุ่มถัดไป
                html += `<li class="page-item ${current === totalPages ? 'disabled' : ''}">
                            <a class="page-link" href="#" onclick="loadData(${current + 1}); return false;">ถัดไป</a>
                         </li>`;

                paginationContainer.innerHTML = html;
            }

            // Initial load
            loadData(currentPage);

            // Enter key on search
            searchInput.addEventListener('keyup', function(event) {
                if (event.key === 'Enter') {
                    loadData(1);
                }
            });
        });