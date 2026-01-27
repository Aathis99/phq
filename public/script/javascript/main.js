// let page = 1;
// let isLoading = false;
// let hasMore = true;
// let searchTimeout;

// const dataContainer = document.getElementById('dataContainer');
// const tableBody = document.getElementById('tableBody');
// const loadingEl = document.getElementById('loading');
// const noMoreDataEl = document.getElementById('noMoreData');
// const searchInput = document.getElementById('searchInput');

// function loadData(reset = false) {
//     if (isLoading) return;
//     if (!hasMore && !reset) return;

//     isLoading = true;
//     loadingEl.style.display = 'block';
    
//     if (reset) {
//         page = 1;
//         hasMore = true;
//         tableBody.innerHTML = '';
//         noMoreDataEl.style.display = 'none';
//     }

//     const searchTerm = searchInput.value;

//     fetch(`main.php?action=fetch_data&page=${page}&search=${encodeURIComponent(searchTerm)}`)
//         .then(response => response.json())
//         .then(res => {
//             if (res.status === 'success') {
//                 const data = res.data;
                
//                 if (data.length < 10) {
//                     hasMore = false;
//                     noMoreDataEl.style.display = 'block';
//                 }

//                 if (data.length === 0 && page === 1) {
//                         tableBody.innerHTML = '<tr><td colspan="6" class="text-center py-5 text-muted">ไม่พบข้อมูลที่ค้นหา</td></tr>';
//                         noMoreDataEl.style.display = 'none';
//                 } else {
//                     data.forEach(row => {
//                         const tr = document.createElement('tr');
//                         const prefix = row.prefix_name || '';
//                         const fname = row.fname || '-';
//                         const lname = row.lname || '-';
//                         const fullName = `${prefix}${fname} ${lname}`;
                        

//                         // <td>${row.pid}</td> เอาบแสดงเลขบัตรประชาชนออก หากต้องการให้เพิ่มตรงนี้ไป
//                         tr.innerHTML = `
                            
//                             <td>${fullName}</td>
//                             <td>${row.sex_name || '-'}</td>
//                             <td>${row.age || '-'}</td>
//                             <td>${row.school_name || '-'}</td>
//                             <td>${row.class || '-'}/${row.room || '-'}</td>
//                             <td>${row.tel || '-'}</td>
//                             <td class="text-center">
//                                 <a href="phq_history.php?pid=${row.pid}" class="btn btn-sm btn-info text-white">View</a>
//                             </td>
//                         `;
//                         tableBody.appendChild(tr);
//                     });
//                     page++;
//                 }
//             } else {
//                 console.error(res.message);
//             }
//         })
//         .catch(err => console.error('Error fetching data:', err))
//         .finally(() => {
//             isLoading = false;
//             loadingEl.style.display = 'none';
//         });
// }

// // Search Event (Debounce)
// searchInput.addEventListener('input', () => {
//     clearTimeout(searchTimeout);
//     searchTimeout = setTimeout(() => {
//         loadData(true);
//     }, 300);
// });

// // Infinite Scroll
// dataContainer.addEventListener('scroll', () => {
//     if (dataContainer.scrollTop + dataContainer.clientHeight >= dataContainer.scrollHeight - 50) {
//         loadData();
//     }
// });

// // Initial Load
// document.addEventListener('DOMContentLoaded', () => {
//     loadData();
// });


 document.addEventListener('DOMContentLoaded', function() {
            let page = 1;
            let isLoading = false;
            let hasMore = true;
            const container = document.getElementById('dataContainer');
            const tableBody = document.getElementById('tableBody');
            const loading = document.getElementById('loading');
            const noMoreData = document.getElementById('noMoreData');
            const searchInput = document.getElementById('searchInput');

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
                const url = `main.php?action=fetch_data&page=${page}&search=${encodeURIComponent(search)}`;

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