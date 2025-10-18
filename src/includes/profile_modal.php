<?php
if (!isset($_SESSION['customer_id'])) {
    // ไม่แสดง modal หรือแสดง modal ว่าง
    return;
}
$customer_id = $_SESSION['customer_id'];
$customer = $conn->query("SELECT fullname, email, phone FROM customers WHERE customer_id = $customer_id")->fetch_assoc();
?>

<!-- Modal โปรไฟล์ -->
<div id="profileModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-40 backdrop-blur-sm hidden">
    <div class="bg-gray-100 p-3 rounded-3xl shadow-xl w-full max-w-3xl mx-auto relative flex space-x-3 max-h-[80vh]" style="height: 500px;">
        <div class="w-64 bg-white rounded-2xl overflow-y-auto ring-1 ring-gray-200 shadow-sm">
        <div class="p-2 pl-4 font-semibold text-zinc-900 border-b bg-gray-50">การตั้งค่าโปรไฟล์</div>
        <ul id="orderList">
          <!-- ปุ่มสอบถามทั่วไป -->
          <li class="p-2 text-sm pb-0">
            <button type="button"
              class="w-full text-left px-4 py-3 bg-gray-100 hover:bg-gray-200 transition rounded-xl flex justify-between items-center font-medium text-gray-500 ring-1 ring-gray-300">
              โปรไฟล์ของฉัน
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
</script>