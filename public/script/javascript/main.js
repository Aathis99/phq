 document.addEventListener('DOMContentLoaded', function() {
            let page = 1;
            let isLoading = false;
            let hasMore = true;
            const container = document.getElementById('dataContainer');
            const tableBody = document.getElementById('tableBody');
            const loading = document.getElementById('loading');
            const noMoreData = document.getElementById('noMoreData');
            const searchInput = document.getElementById('searchInput');
            const filterStatus = document.getElementById('filterStatus');

            window.loadData = function(reset = false) {
                if (reset) {
                    page = 1;
                    hasMore = true;
                    tableBody.innerHTML = '';
                    noMoreData.style.display = 'none';
                }

                if (isLoading || !hasMore) return;

                isLoading = true;
                loading.style.display = 'block';

                const search = searchInput.value;
                const filter = filterStatus ? filterStatus.value : 'all';
                const url = `main.php?action=fetch_data&page=${page}&search=${encodeURIComponent(search)}&filter=${encodeURIComponent(filter)}`;

                fetch(url)
                    .then(response => response.json())
                    .then(json => {
                        if (json.status === 'success') {
                            const data = json.data;
                            if (data.length > 0) {
                                data.forEach(row => {
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
                                page++;
                            } else {
                                hasMore = false;
                                noMoreData.style.display = 'block';
                            }
                        }
                    })
                    .catch(err => console.error(err))
                    .finally(() => {
                        isLoading = false;
                        loading.style.display = 'none';
                    });
            };

            // Initial load
            loadData();

            // Infinite scroll
            container.addEventListener('scroll', function() {
                if (container.scrollTop + container.clientHeight >= container.scrollHeight - 50) {
                    loadData();
                }
            });

            // Enter key on search
            searchInput.addEventListener('keyup', function(event) {
                if (event.key === 'Enter') {
                    loadData(true);
                }
            });
        });