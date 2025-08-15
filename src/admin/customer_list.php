<?php
// ตรวจสอบ error
error_reporting(E_ALL);
ini_set('display_errors', 1);
require '../includes/db_connect.php';
require '../auth/auth.php';
requireRole('admin'); // ให้เข้าหน้านี้ได้เฉพาะ admin

// ดึงข้อมูลลูกค้าทั้งหมด
$sql = "SELECT customer_id, fullname, email, phone, role, created_at FROM customers ORDER BY customer_id DESC";
$result = $conn->query($sql);
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <title>ข้อมูลลูกค้า - Admin Panel</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/flowbite/1.7.0/flowbite.min.css" rel="stylesheet" />
    <link href="../../dist/output.css" rel="stylesheet" />
    <style>
        .table-container {
            max-height: calc(100vh - 200px);
            overflow-y: auto;
        }
        .sticky-header {
            position: sticky;
            top: 0;
            background-color: #f9fafb;
            z-index: 10;
        }
        th[data-sortable] {
            cursor: pointer;
            position: relative;
        }
        th[data-sortable]:hover {
            background-color: #f3f4f6;
        }
        th[data-sortable].asc::after {
            content: '↑';
            position: absolute;
            right: 8px;
            font-size: 12px;
        }
        th[data-sortable].desc::after {
            content: '↓';
            position: absolute;
            right: 8px;
            font-size: 12px;
        }
    </style>
</head>
<body>
    <?php include '../includes/sidebar.php'; ?>
    
    <div class="ml-64 p-8">
        <!-- Header -->
        <div class="flex justify-between items-center mb-6">
            <h1 class="text-2xl font-bold text-gray-800">รายชื่อลูกค้าทั้งหมด</h1>
            <a href="customer_add.php" class="text-white bg-zinc-900 hover:bg-zinc-800 font-medium rounded-lg text-sm px-5 py-2.5 flex items-center">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                </svg>
                เพิ่มลูกค้าใหม่
            </a>
        </div>

        <!-- Search and Filter -->
        <div class="flex justify-between items-center mb-6">
            <div class="relative w-64">
                <div class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none">
                    <svg class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                    </svg>
                </div>
                <input type="text" id="searchInput" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full pl-10 p-2.5" placeholder="ค้นหาลูกค้า...">
            </div>
            <div class="flex space-x-2">
                <button class="filter-btn px-4 py-2 text-sm font-medium rounded-lg bg-zinc-900 text-white" data-filter="all">ทั้งหมด</button>
                <button class="filter-btn px-4 py-2 text-sm font-medium rounded-lg bg-gray-200 text-gray-700 hover:bg-gray-300" data-filter="admin">Admin</button>
                <button class="filter-btn px-4 py-2 text-sm font-medium rounded-lg bg-gray-200 text-gray-700 hover:bg-gray-300" data-filter="customer">Customer</button>
            </div>
        </div>

        <!-- Table Container -->
        <div class="relative overflow-x-auto shadow-md sm:rounded-lg">
            <table class="w-full text-sm text-left text-gray-500">
                <thead class="text-xs text-gray-700 uppercase bg-zinc-100 sticky-header">
                    <tr>
                        <th scope="col" class="px-6 py-3" data-sortable>ID</th>
                        <th scope="col" class="px-6 py-3" data-sortable>ชื่อ-นามสกุล</th>
                        <th scope="col" class="px-6 py-3" data-sortable>อีเมล</th>
                        <th scope="col" class="px-6 py-3" data-sortable>เบอร์โทร</th>
                        <th scope="col" class="px-6 py-3">ตำแหน่ง</th>
                        <th scope="col" class="px-6 py-3" data-sortable>วันที่สมัคร</th>
                        <th scope="col" class="px-6 py-3 text-right">การดำเนินการ</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($result && $result->num_rows > 0): ?>
                        <?php while ($row = $result->fetch_assoc()): ?>
                            <tr class="bg-white border-b hover:bg-gray-50 transition-colors">
                                <td class="px-6 py-4 font-medium text-gray-900">
                                    <?php echo htmlspecialchars($row['customer_id']); ?>
                                </td>
                                <td class="px-6 py-4">
                                    <?php echo htmlspecialchars($row['fullname']); ?>
                                </td>
                                <td class="px-6 py-4">
                                    <?php echo htmlspecialchars($row['email']); ?>
                                </td>
                                <td class="px-6 py-4">
                                    <?php echo htmlspecialchars($row['phone']); ?>
                                </td>
                                <td class="px-6 py-4">
                                    <?php if ($row['role'] === 'admin'): ?>
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-purple-100 text-purple-800 border border-purple-300">
                                            <svg class="-ml-0.5 mr-1.5 h-2 w-2 text-purple-400" fill="currentColor" viewBox="0 0 8 8">
                                                <circle cx="4" cy="4" r="3" />
                                            </svg>
                                            <?php echo htmlspecialchars($row['role']); ?>
                                        </span>
                                    <?php else: ?>
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800 border border-blue-300">
                                            <svg class="-ml-0.5 mr-1.5 h-2 w-2 text-blue-400" fill="currentColor" viewBox="0 0 8 8">
                                                <circle cx="4" cy="4" r="3" />
                                            </svg>
                                            <?php echo htmlspecialchars($row['role']); ?>
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <?php 
                                    $date = new DateTime($row['created_at']);
                                    echo $date->format('d/m/Y H:i');
                                    ?>
                                </td>
                                <td class="px-6 py-4 text-right">
                                    <div class="flex justify-end space-x-2">
                                        <a href="customer_add.php?id=<?php echo $row['customer_id']; ?>" 
                                           class="text-blue-600 hover:text-blue-900 font-medium flex items-center">
                                            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                                            </svg>
                                            แก้ไข
                                        </a>
                                        <span class="text-gray-300">|</span>
                                        <a href="customer_delete.php?id=<?php echo $row['customer_id']; ?>" 
                                           class="text-red-600 hover:text-red-900 font-medium flex items-center"
                                           onclick="return confirm('คุณแน่ใจที่จะลบลูกค้านี้?');">
                                            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                            </svg>
                                            ลบ
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="7" class="px-6 py-12 text-center">
                                <div class="flex flex-col items-center justify-center">
                                    <svg class="w-16 h-16 text-gray-400 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"></path>
                                    </svg>
                                    <h3 class="text-lg font-medium text-gray-700 mb-2">ยังไม่มีลูกค้า</h3>
                                    <p class="text-gray-500 mb-4">เริ่มต้นด้วยการเพิ่มลูกค้าใหม่</p>
                                    <a href="customer_add.php" class="text-white bg-zinc-900 hover:bg-zinc-800 font-medium rounded-lg text-sm px-5 py-2.5 inline-flex items-center">
                                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                                        </svg>
                                        เพิ่มลูกค้าใหม่
                                    </a>
                                </div>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <!-- <div class="flex justify-between items-center mt-4">
            <div class="text-sm text-gray-500">
                แสดง <span id="showingFrom">1</span>-<span id="showingTo">10</span> จาก <span id="totalItems"><?php echo $result->num_rows; ?></span> รายการ
            </div>
            <nav class="flex items-center space-x-2">
                <button id="prevPage" class="p-2 rounded-lg border border-gray-300 text-gray-700 hover:bg-gray-100 disabled:opacity-50" disabled>
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
                    </svg>
                </button>
                <div id="pageNumbers" class="flex space-x-1">
                    ตัวเลขหน้า จะถูกเติมโดย JavaScript -->
                <!-- </div>
                <button id="nextPage" class="p-2 rounded-lg border border-gray-300 text-gray-700 hover:bg-gray-100">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                    </svg>
                </button>
            </nav>
        </div> -->
    </div>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/flowbite/1.7.0/flowbite.min.js"></script>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // ตัวแปรสำหรับ Pagination
        const rowsPerPage = 10;
        let currentPage = 1;
        let filteredRows = Array.from(document.querySelectorAll('tbody tr'));
        let sortColumn = null;
        let sortDirection = 'asc';

        // ฟังก์ชันแสดงผล Pagination
        function updatePagination() {
            const totalPages = Math.ceil(filteredRows.length / rowsPerPage);
            const pageNumbersContainer = document.getElementById('pageNumbers');
            pageNumbersContainer.innerHTML = '';

            // แสดงตัวเลขหน้า
            for (let i = 1; i <= totalPages; i++) {
                const pageBtn = document.createElement('button');
                pageBtn.textContent = i;
                pageBtn.className = `px-3 py-1 rounded-lg ${i === currentPage ? 'bg-zinc-900 text-white' : 'bg-gray-200 text-gray-700 hover:bg-gray-300'}`;
                pageBtn.addEventListener('click', () => {
                    currentPage = i;
                    updateTable();
                });
                pageNumbersContainer.appendChild(pageBtn);
            }

            // อัปเดตปุ่ม Previous/Next
            document.getElementById('prevPage').disabled = currentPage === 1;
            document.getElementById('nextPage').disabled = currentPage === totalPages || totalPages === 0;

            // อัปเดตข้อความแสดงผล
            const from = filteredRows.length > 0 ? (currentPage - 1) * rowsPerPage + 1 : 0;
            const to = Math.min(currentPage * rowsPerPage, filteredRows.length);
            document.getElementById('showingFrom').textContent = from;
            document.getElementById('showingTo').textContent = to;
            document.getElementById('totalItems').textContent = filteredRows.length;
        }

        // ฟังก์ชันอัปเดตตาราง
        function updateTable() {
            const start = (currentPage - 1) * rowsPerPage;
            const end = start + rowsPerPage;
            const rowsToShow = filteredRows.slice(start, end);

            // ซ่อนทั้งหมด
            document.querySelectorAll('tbody tr').forEach(row => {
                row.style.display = 'none';
            });

            // แสดงแถวที่เลือก
            rowsToShow.forEach(row => {
                row.style.display = '';
            });

            updatePagination();
        }

        // การค้นหา
        document.getElementById('searchInput').addEventListener('input', function() {
            const searchTerm = this.value.toLowerCase();
            filteredRows = Array.from(document.querySelectorAll('tbody tr')).filter(row => {
                return row.textContent.toLowerCase().includes(searchTerm);
            });
            currentPage = 1;
            updateTable();
        });

        // การกรองบทบาท
        document.querySelectorAll('.filter-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                const filter = this.dataset.filter;
                
                // อัปเดตสถานะปุ่ม
                document.querySelectorAll('.filter-btn').forEach(b => {
                    b.classList.remove('bg-zinc-900', 'text-white');
                    b.classList.add('bg-gray-200', 'text-gray-700', 'hover:bg-gray-300');
                });
                this.classList.add('bg-zinc-900', 'text-white');
                this.classList.remove('bg-gray-200', 'text-gray-700', 'hover:bg-gray-300');

                // กรองข้อมูล
                if (filter === 'all') {
                    filteredRows = Array.from(document.querySelectorAll('tbody tr'));
                } else {
                    filteredRows = Array.from(document.querySelectorAll('tbody tr')).filter(row => {
                        return row.querySelector('td:nth-child(5)').textContent.toLowerCase().includes(filter);
                    });
                }
                currentPage = 1;
                updateTable();
            });
        });

        // การจัดเรียงตาราง
        document.querySelectorAll('th[data-sortable]').forEach(header => {
            header.addEventListener('click', () => {
                const columnIndex = header.cellIndex;
                const isAscending = header.classList.contains('asc');
                
                // ลบคลาสการจัดเรียงจากหัวตอลอื่น
                document.querySelectorAll('th[data-sortable]').forEach(h => {
                    h.classList.remove('asc', 'desc');
                });
                
                // ตั้งค่าการจัดเรียง
                header.classList.toggle('asc', !isAscending);
                header.classList.toggle('desc', isAscending);
                
                // จัดเรียงข้อมูล
                filteredRows.sort((a, b) => {
                    const aValue = a.cells[columnIndex].textContent.trim();
                    const bValue = b.cells[columnIndex].textContent.trim();
                    
                    // ตรวจสอบว่าเป็นตัวเลขหรือไม่
                    if (!isNaN(aValue)) {
                        return isAscending ? bValue - aValue : aValue - bValue;
                    } else {
                        // ตรวจสอบว่าเป็นวันที่หรือไม่ (รูปแบบ dd/mm/yyyy)
                        const dateRegex = /^(\d{2})\/(\d{2})\/(\d{4})/;
                        if (dateRegex.test(aValue) && dateRegex.test(bValue)) {
                            const aDate = new Date(aValue.split('/').reverse().join('-'));
                            const bDate = new Date(bValue.split('/').reverse().join('-'));
                            return isAscending ? bDate - aDate : aDate - bDate;
                        } else {
                            return isAscending 
                                ? bValue.localeCompare(aValue) 
                                : aValue.localeCompare(bValue);
                        }
                    }
                });
                
                currentPage = 1;
                updateTable();
            });
        });

        // ปุ่ม Pagination
        document.getElementById('prevPage').addEventListener('click', () => {
            if (currentPage > 1) {
                currentPage--;
                updateTable();
            }
        });

        document.getElementById('nextPage').addEventListener('click', () => {
            const totalPages = Math.ceil(filteredRows.length / rowsPerPage);
            if (currentPage < totalPages) {
                currentPage++;
                updateTable();
            }
        });

        // เริ่มต้น
        updateTable();
    });
    </script>
</body>
</html>