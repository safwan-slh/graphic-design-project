<?php
// ตรวจสอบ error
error_reporting(E_ALL);
ini_set('display_errors', 1);
require __DIR__ . '/../includes/db_connect.php';
require_once '../auth/auth.php';
requireRole('admin'); // ให้เข้าหน้านี้ได้เฉพาะ admin

$toastType = isset($_GET['toastType']) ? $_GET['toastType'] : '';
$toastMessage = isset($_GET['toastMessage']) ? urldecode($_GET['toastMessage']) : '';

// ดึงข้อมูลลูกค้าทั้งหมด
$sql = "SELECT customer_id, fullname, email, phone, role, created_at FROM customers ORDER BY customer_id DESC";
$result = $conn->query($sql);
?>
<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8">
    <title>ข้อมูลลูกค้า - Admin Panel</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=IBM+Plex+Sans+Thai:wght@300;400;500;600;700&display=swap"
        rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .font-thai {
            font-family: 'IBM Plex Sans Thai', sans-serif;
        }
    </style>
</head>

<body class="font-thai bg-zinc-100">
    <?php include '../includes/sidebar.php'; ?>

    <div class="ml-64">
        <!-- breadcrumb -->
        <?php
        $breadcrumb = ['Dashboard', 'จัดการลูกค้า'];
        $breadcrumb_links = ['/graphic-design/src/admin/index.php', '/graphic-design/src/admin/customer_list.php'];
        include '../includes/admin_navbar.php';
        ?>

        <div class="p-6">
            <!-- Header -->
            <div class=" text-zinc-900 bg-white rounded-2xl border border-slate-200 mb-2">
                <!-- Header -->
                <div class="flex items-center p-4">
                    <div class="mr-4 rounded-xl bg-zinc-900 p-3">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="h-6 w-6 text-white">
                            <path fill-rule="evenodd" d="M8.25 6.75a3.75 3.75 0 1 1 7.5 0 3.75 3.75 0 0 1-7.5 0ZM15.75 9.75a3 3 0 1 1 6 0 3 3 0 0 1-6 0ZM2.25 9.75a3 3 0 1 1 6 0 3 3 0 0 1-6 0ZM6.31 15.117A6.745 6.745 0 0 1 12 12a6.745 6.745 0 0 1 6.709 7.498.75.75 0 0 1-.372.568A12.696 12.696 0 0 1 12 21.75c-2.305 0-4.47-.612-6.337-1.684a.75.75 0 0 1-.372-.568 6.787 6.787 0 0 1 1.019-4.38Z" clip-rule="evenodd" />
                            <path d="M5.082 14.254a8.287 8.287 0 0 0-1.308 5.135 9.687 9.687 0 0 1-1.764-.44l-.115-.04a.563.563 0 0 1-.373-.487l-.01-.121a3.75 3.75 0 0 1 3.57-4.047ZM20.226 19.389a8.287 8.287 0 0 0-1.308-5.135 3.75 3.75 0 0 1 3.57 4.047l-.01.121a.563.563 0 0 1-.373.486l-.115.04c-.567.2-1.156.349-1.764.441Z" />
                        </svg>
                    </div>
                    <div class="">
                        <h1 class="flex items-center text-2xl font-bold text-zinc-900">
                            รายชื่อลูกค้าทั้งหมด
                        </h1>
                        <p class="text-gray-600">
                            จัดการและติดตามข้อมูลลูกค้าทั้งหมด
                        </p>
                    </div>
                </div>
            </div>
            <div class="flex items-center justify-between bg-white rounded-2xl mb-2 p-4 ring-1 ring-gray-200">
                <div class="flex space-x-2">
                    <button class="filter-btn border transition font-medium rounded-xl text-sm px-5 py-2 text-center flex items-center justify-center bg-zinc-900 hover:bg-gray-3000 text-white" data-filter="all">ทั้งหมด</button>
                    <button class="filter-btn border transition font-medium rounded-xl text-sm px-5 py-2 text-center flex items-center justify-center bg-gray-200 text-gray-700 hover:bg-gray-300" data-filter="admin">Admin</button>
                    <button class="filter-btn border transition font-medium rounded-xl text-sm px-5 py-2 text-center flex items-center justify-center bg-gray-200 text-gray-700 hover:bg-gray-300" data-filter="customer">Customer</button>
                </div>
                <div class="flex items-center">
                    <a href="customer_add.php" class="border transition font-medium rounded-xl text-sm px-5 py-2 text-center flex items-center justify-center bg-zinc-900 hover:bg-zinc-700 text-white border-zinc-900">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                        </svg>
                        เพิ่มลูกค้าใหม่
                    </a>
                </div>
            </div>

            <!-- Table Container -->
            <div class=" text-zinc-900 bg-white rounded-2xl border border-slate-200 overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-200">
                    <div class="flex items-center justify-between">
                        <h3 class="text-lg font-bold text-gray-800">รายชื่อลูกค้าทั้งหมด</h3>
                        <div class="flex items-center space-x-3">
                            <div class="relative w-64">
                                <div class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none">
                                    <svg class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                                    </svg>
                                </div>
                                <input type="text" id="searchInput" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-xl block w-full pl-10 p-2.5" placeholder="ค้นหาลูกค้า...">
                            </div>
                        </div>
                    </div>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead class="bg-gray-50 border-b border-gray-200">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">ID</th>
                                <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">ชื่อ-นามสกุล</th>
                                <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">อีเมล</th>
                                <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">เบอร์โทร</th>
                                <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">ตำแหน่ง</th>
                                <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">วันที่สมัคร</th>
                                <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">การดำเนินการ</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($result && $result->num_rows > 0): ?>
                                <?php while ($row = $result->fetch_assoc()): ?>
                                    <tr class="bg-white border-b hover:bg-gray-50 transition-colors">

                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="">
                                                <?php echo htmlspecialchars($row['customer_id']); ?>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4">
                                            <div class="text-sm font-medium text-gray-900">
                                                <?php echo htmlspecialchars($row['fullname']); ?>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4">
                                            <div class="text-sm font-medium text-gray-900">
                                                <?php echo htmlspecialchars($row['email']); ?>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($row['phone']); ?></div>
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
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            <?php
                                            $date = new DateTime($row['created_at']);
                                            echo $date->format('d/m/Y H:i');
                                            ?>
                                        </td>
                                        <td class="px-6 py-4 text-right">
                                            <div class="flex justify-center">
                                                <button id="dropdownDefaultButton" data-dropdown-toggle="cancel" class="flex items-center p-1 text-sm font-medium text-center text-gray-400 rounded-lg hover:bg-gray-100 focus:ring-4 focus:outline-none focus:ring-gray-200" type="button">
                                                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="size-6">
                                                        <path fill-rule="evenodd" d="M10.5 6a1.5 1.5 0 1 1 3 0 1.5 1.5 0 0 1-3 0Zm0 6a1.5 1.5 0 1 1 3 0 1.5 1.5 0 0 1-3 0Zm0 6a1.5 1.5 0 1 1 3 0 1.5 1.5 0 0 1-3 0Z" clip-rule="evenodd" />
                                                    </svg>
                                                </button>
                                                <div id="cancel" class="z-10 hidden bg-white divide-y rounded-xl shadow-md w-44 border-1.5 border-gray-200 ring-1 ring-gray-200">
                                                    <ul class="space-y-2 p-2 py-2 text-sm text-gray-700" aria-labelledby="dropdownDefaultButton">
                                                        <li>
                                                            <a href="customer_add.php?id=<?php echo $row['customer_id']; ?>" class=" flex items-center justify-between px-3 py-2 text-sm rounded-lg bg-zinc-50 text-blue-600 hover:bg-zinc-100 transition-colors duration-200 ring-1 ring-gray-200">
                                                                แก้ไข
                                                                <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor">
                                                                    <path d="M21.731 2.269a2.625 2.625 0 0 0-3.712 0l-1.157 1.157 3.712 3.712 1.157-1.157a2.625 2.625 0 0 0 0-3.712ZM19.513 8.199l-3.712-3.712-12.15 12.15a5.25 5.25 0 0 0-1.32 2.214l-.8 2.685a.75.75 0 0 0 .933.933l2.685-.8a5.25 5.25 0 0 0 2.214-1.32L19.513 8.2Z" />
                                                                </svg>
                                                            </a>
                                                        </li>
                                                        <li>
                                                            <a href="customer_delete.php?id=<?php echo $row['customer_id']; ?>" class=" flex items-center justify-between px-3 py-2 text-sm rounded-lg bg-zinc-50 text-red-600 hover:bg-zinc-100 transition-colors duration-200 ring-1 ring-gray-200">
                                                                ลบ
                                                                <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor">
                                                                    <path fill-rule="evenodd" d="M16.5 4.478v.227a48.816 48.816 0 0 1 3.878.512.75.75 0 1 1-.256 1.478l-.209-.035-1.005 13.07a3 3 0 0 1-2.991 2.77H8.084a3 3 0 0 1-2.991-2.77L4.087 6.66l-.209.035a.75.75 0 0 1-.256-1.478A48.567 48.567 0 0 1 7.5 4.705v-.227c0-1.564 1.213-2.9 2.816-2.951a52.662 52.662 0 0 1 3.369 0c1.603.051 2.815 1.387 2.815 2.951Zm-6.136-1.452a51.196 51.196 0 0 1 3.273 0C14.39 3.05 15 3.684 15 4.478v.113a49.488 49.488 0 0 0-6 0v-.113c0-.794.609-1.428 1.364-1.452Zm-.355 5.945a.75.75 0 1 0-1.5.058l.347 9a.75.75 0 1 0 1.499-.058l-.346-9Zm5.48.058a.75.75 0 1 0-1.498-.058l-.347 9a.75.75 0 0 0 1.5.058l.345-9Z" clip-rule="evenodd" />
                                                                </svg>
                                                            </a>
                                                        </li>
                                                    </ul>
                                                </div>
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
            </div>
        </div>

    </div>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/flowbite/1.7.0/flowbite.min.js"></script>
    <!-- include toast component -->
    <?php include '../includes/toast.php'; ?>

    <?php if (!empty($message)): ?>
        <script>
            showToast(<?= json_encode($message) ?>, <?= json_encode($messageType) ?>);
        </script>
    <?php endif; ?>
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
                                return isAscending ?
                                    bValue.localeCompare(aValue) :
                                    aValue.localeCompare(bValue);
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