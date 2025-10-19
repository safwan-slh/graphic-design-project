<?php
if (!isset($_SESSION['customer_id'])) {
    // ไม่แสดง modal หรือแสดง modal ว่าง
    return;
}
$customer_id = $_SESSION['customer_id'];
$customer = $conn->query("SELECT fullname, email, phone FROM customers WHERE customer_id = $customer_id")->fetch_assoc();
?>

<!-- Modal โปรไฟล์ -->
<div id="profileModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-40 backdrop-blur-sm ">
    <div class="bg-gray-100 p-3 rounded-3xl shadow-xl w-full max-w-3xl mx-auto relative flex space-x-3 max-h-[80vh]" style="height: 500px;">
        <div class="w-64 bg-white rounded-2xl overflow-y-auto ring-1 ring-gray-200 shadow-sm">
            <div class="p-2 pl-4 font-semibold text-zinc-900 border-b bg-gray-50">การตั้งค่า</div>
            <ul>
                <li class="p-2 text-sm pb-0">
                    <button type="button"
                        class="w-full text-left px-4 py-3 bg-gray-100 hover:bg-gray-200 transition rounded-xl flex justify-between items-center font-medium text-gray-500 ring-1 ring-gray-300">
                        โปรไฟล์ของฉัน
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="size-6">
                            <path fill-rule="evenodd" d="M18.685 19.097A9.723 9.723 0 0 0 21.75 12c0-5.385-4.365-9.75-9.75-9.75S2.25 6.615 2.25 12a9.723 9.723 0 0 0 3.065 7.097A9.716 9.716 0 0 0 12 21.75a9.716 9.716 0 0 0 6.685-2.653Zm-12.54-1.285A7.486 7.486 0 0 1 12 15a7.486 7.486 0 0 1 5.855 2.812A8.224 8.224 0 0 1 12 20.25a8.224 8.224 0 0 1-5.855-2.438ZM15.75 9a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0Z" clip-rule="evenodd" />
                        </svg>
                    </button>
                </li>
                <li class="p-2 text-sm pb-0">
                    <button type="button"
                        class="w-full text-left px-4 py-3 bg-gray-100 hover:bg-gray-200 transition rounded-xl flex justify-between items-center font-medium text-gray-500 ring-1 ring-gray-300">
                        เปลี่ยนรหัสผ่านบัญชี
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="size-6">
                            <path fill-rule="evenodd" d="M15.75 1.5a6.75 6.75 0 0 0-6.651 7.906c.067.39-.032.717-.221.906l-6.5 6.499a3 3 0 0 0-.878 2.121v2.818c0 .414.336.75.75.75H6a.75.75 0 0 0 .75-.75v-1.5h1.5A.75.75 0 0 0 9 19.5V18h1.5a.75.75 0 0 0 .53-.22l2.658-2.658c.19-.189.517-.288.906-.22A6.75 6.75 0 1 0 15.75 1.5Zm0 3a.75.75 0 0 0 0 1.5A2.25 2.25 0 0 1 18 8.25a.75.75 0 0 0 1.5 0 3.75 3.75 0 0 0-3.75-3.75Z" clip-rule="evenodd" />
                        </svg>
                    </button>
                </li>
                <li class="p-2 text-sm pb-0">
                    <button type="button"
                        class="w-full text-left px-4 py-3 bg-gray-100 hover:bg-gray-200 transition rounded-xl flex justify-between items-center font-medium text-gray-500 ring-1 ring-gray-300">
                        ประวัติการชำระเงิน
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="size-6">
                            <path fill-rule="evenodd" d="M12 2.25c-5.385 0-9.75 4.365-9.75 9.75s4.365 9.75 9.75 9.75 9.75-4.365 9.75-9.75S17.385 2.25 12 2.25ZM12.75 6a.75.75 0 0 0-1.5 0v6c0 .414.336.75.75.75h4.5a.75.75 0 0 0 0-1.5h-3.75V6Z" clip-rule="evenodd" />
                        </svg>
                    </button>
                </li>
            </ul>
        </div>
        <!-- Main Chat Area -->
        <div class="flex-1 flex flex-col bg-white rounded-2xl overflow-y-auto ring-1 ring-gray-200 shadow-sm">
            <button onclick="document.getElementById('profileModal').classList.add('hidden')" class="absolute top-2 right-2 bg-zinc-900 text-white rounded-full p-2 ring-1 ring-gray-200 shadow-md hover:bg-zinc-700 transition-all duration-300 ease-in-out hover:scale-105">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
            <div class="p-2 pl-4 font-semibold text-zinc-900 border-b bg-gray-50">
                <h2 class="text-md font-semibold">โปรไฟล์ของฉัน</h2>
            </div>
            <div class="flex-1 overflow-y-auto p-4" style="min-height:200px;">
                <div class="flex flex-col items-center gap-4">
                    <div class="w-full flex justify-center mx-auto max-h-10 bg-gray-100 rounded-xl p-4 ring-1 ring-gray-200 relative" style="padding-top: 4.5rem;">
                        <!-- วงกลมตัวอักษรแรก -->
                        <div class="absolute left-1/2 -translate-x-1/2 -bottom-10 w-20 h-20 rounded-full bg-zinc-900 text-white flex items-center justify-center text-3xl font-bold select-none ring-4 ring-white">
                            <?= strtoupper(mb_substr($customer['fullname'], 0, 1, 'UTF-8')) ?>
                        </div>
                    </div>
                    <!-- วงกลมตัวอักษรแรก -->
                    <div class="mt-4 w-full">
                        <div class="w-full mx-auto p-3">
                            <div class="text-zinc-700 text-2xl font-semibold text-center"><?= htmlspecialchars($customer['fullname']) ?></div>
                        </div>
                        <div class="space-y-4 p-2 profile-view">
                            <div class="w-full flex space-x-4 justify-between mx-auto">
                                <div class="w-full">
                                    <label class="mb-2 block text-sm font-medium text-gray-700">ชื่อ-นามสกุล:</label>
                                    <input type="tel" name="phone" value="<?= htmlspecialchars($customer['fullname']) ?>" readonly
                                        class="block w-full rounded-xl border border-gray-300 bg-gray-50 p-2.5 text-sm text-gray-400 cursor-not-allowed">
                                </div>
                                <div class="w-full">
                                    <label class="mb-2 block text-sm font-medium text-gray-700">เบอร์โทรศัพท์:</label>
                                    <input type="tel" name="phone" value="<?= htmlspecialchars($customer['phone']) ?>" readonly
                                        class="block w-full rounded-xl border border-gray-300 bg-gray-50 p-2.5 text-sm text-gray-400 cursor-not-allowed">
                                </div>
                            </div>
                            <div class="w-full mx-auto">
                                <label class="mb-2 block text-sm font-medium text-gray-700">อีเมล:</label>
                                <input type="email" name="email" value="<?= htmlspecialchars($customer['email']) ?>" readonly
                                    class="block w-full rounded-xl border border-gray-300 bg-gray-50 p-2.5 text-sm text-gray-400 cursor-not-allowed">
                            </div>
                        </div>
                        <div class="w-full mx-auto p-2 flex justify-end text-center">
                            <button type="button" id="editProfileBtn"
                                class="border transition font-medium rounded-xl text-sm px-5 py-2 text-center flex items-center justify-center bg-gray-900 hover:bg-gray-700 text-white border-gray-900">
                                แก้ไขโปรไฟล์
                            </button>
                        </div>

                        <!-- ฟอร์มแก้ไข (ซ่อนตอนแรก) -->
                        <form id="editProfileForm" method="post" action="/graphic-design/src/includes/profile_update.php" class="space-y-4 p-2 hidden">
                            <div class="flex space-x-4 justify-between">
                                <div class="w-full">
                                    <label class="mb-2 block text-sm font-medium text-gray-700">ชื่อ-นามสกุล:</label>
                                    <input type="text" name="fullname" value="<?= htmlspecialchars($customer['fullname']) ?>"
                                        class="block w-full rounded-xl border border-gray-300 bg-gray-50 p-2.5 text-sm text-gray-900" required>
                                </div>
                                <div class="w-full">
                                    <label class="mb-2 block text-sm font-medium text-gray-700">เบอร์โทรศัพท์:</label>
                                    <input
                                        type="tel"
                                        name="phone"
                                        value="<?= htmlspecialchars($customer['phone']) ?>"
                                        class="block w-full rounded-xl border border-gray-300 bg-gray-50 p-2.5 text-sm text-gray-900"
                                        required
                                        maxlength="10"
                                        pattern="^[0-9]{10}$"
                                        inputmode="numeric"
                                        placeholder="กรอกเบอร์โทร 10 หลัก">
                                </div>
                            </div>
                            <div class="">
                                <label class="mb-2 block text-sm font-medium text-gray-700">อีเมล:</label>
                                <input type="email" name="email" value="<?= htmlspecialchars($customer['email']) ?>"
                                    class="block w-full rounded-xl border border-gray-300 bg-gray-50 p-2.5 text-sm text-gray-400 cursor-not-allowed" readonly>
                            </div>
                            <div class="flex justify-end gap-2 mt-4">
                                <button type="button" id="cancelEditBtn"
                                    class="border transition font-medium rounded-xl text-sm px-5 py-2 text-center flex items-center justify-center bg-white text-gray-600 border-gray-300 hover:bg-gray-100">
                                    ยกเลิก
                                </button>
                                <button type="submit"
                                    class="border transition font-medium rounded-xl text-sm px-5 py-2 text-center flex items-center justify-center bg-gray-900 hover:bg-gray-700 text-white border-gray-900">
                                    อัปเดทโปรไฟล์
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
                <form id="changePasswordForm" method="post" action="/graphic-design/src/includes/change_password.php" class="space-y-4 p-2 hidden">
                    <div>
                        <label class="mb-2 block text-sm font-medium text-gray-700">รหัสผ่านเดิม:</label>
                        <input type="password" name="current_password" required class="block w-full rounded-xl border border-gray-300 bg-gray-50 p-2.5 text-sm">
                    </div>
                    <div>
                        <label class="mb-2 block text-sm font-medium text-gray-700">รหัสผ่านใหม่:</label>
                        <input type="password" name="new_password" required minlength="6" class="block w-full rounded-xl border border-gray-300 bg-gray-50 p-2.5 text-sm">
                    </div>
                    <div>
                        <label class="mb-2 block text-sm font-medium text-gray-700">ยืนยันรหัสผ่านใหม่:</label>
                        <input type="password" name="confirm_password" required minlength="6" class="block w-full rounded-xl border border-gray-300 bg-gray-50 p-2.5 text-sm">
                    </div>
                    <div class="flex justify-end gap-2 mt-4">
                        <button type="button" id="cancelChangePasswordBtn"
                            class="border transition font-medium rounded-xl text-sm px-5 py-2 text-center flex items-center justify-center bg-white text-gray-600 border-gray-300 hover:bg-gray-100">
                            ยกเลิก
                        </button>
                        <button type="submit"
                            class="border transition font-medium rounded-xl text-sm px-5 py-2 text-center flex items-center justify-center bg-gray-900 hover:bg-gray-700 text-white border-gray-900">
                            เปลี่ยนรหัสผ่าน
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
<script>
    // เปิด modal
    document.getElementById('editProfileBtn').onclick = function() {
        document.getElementById('editProfileForm').classList.remove('hidden');
        this.parentElement.classList.add('hidden');
        document.querySelectorAll('.profile-view').forEach(el => el.classList.add('hidden'));
    };

    // เปิด modal (navbar หรือปุ่มอื่น)
    function openProfileModal() {
        document.getElementById('profileModal').classList.remove('hidden');
        document.body.classList.add('overflow-hidden');
    }

    // ปิด modal
    function closeProfileModal() {
        document.getElementById('profileModal').classList.add('hidden');
        document.body.classList.remove('overflow-hidden');
    }

    // ตัวอย่างปุ่มปิด
    document.querySelector('#profileModal .absolute.top-2.right-2').onclick = closeProfileModal;

    document.getElementById('cancelEditBtn').onclick = function() {
        document.getElementById('editProfileForm').classList.add('hidden');
        document.querySelector('.w-full.mx-auto.p-2.flex.justify-end.text-center').classList.remove('hidden');
        document.querySelectorAll('.profile-view').forEach(el => el.classList.remove('hidden'));
    };

    // เปิดฟอร์มเปลี่ยนรหัสผ่าน
    document.querySelectorAll('button').forEach(btn => {
        if (btn.textContent.includes('เปลี่ยนรหัสผ่านบัญชี')) {
            btn.onclick = function() {
                document.getElementById('changePasswordForm').classList.remove('hidden');
                document.querySelectorAll('.profile-view, #editProfileForm, .w-full.mx-auto.p-2.flex.justify-end.text-center').forEach(el => el.classList.add('hidden'));
            };
        }
    });

    // ยกเลิกฟอร์มเปลี่ยนรหัสผ่าน
    document.getElementById('cancelChangePasswordBtn').onclick = function() {
        document.getElementById('changePasswordForm').classList.add('hidden');
        document.querySelectorAll('.profile-view').forEach(el => el.classList.remove('hidden'));
        document.querySelector('.w-full.mx-auto.p-2.flex.justify-end.text-center').classList.remove('hidden');
    };

    // เมื่อกดปุ่ม "โปรไฟล์ของฉัน" ให้กลับไปหน้าโปรไฟล์ปกติ
    document.querySelectorAll('button').forEach(btn => {
        if (btn.textContent.trim() === 'โปรไฟล์ของฉัน') {
            btn.onclick = function() {
                document.getElementById('editProfileForm').classList.add('hidden');
                document.getElementById('changePasswordForm').classList.add('hidden');
                document.querySelectorAll('.profile-view').forEach(el => el.classList.remove('hidden'));
                document.querySelector('.w-full.mx-auto.p-2.flex.justify-end.text-center').classList.remove('hidden');
            };
        }
    });
</script>