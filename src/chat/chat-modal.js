const chatModal = document.getElementById("chatModal");
const openChatModalBtn = document.getElementById("openChatModalBtn");
const chatBoxModal = document.getElementById("chatBoxModal");
const chatFormModal = document.getElementById("chatFormModal");
const chatInputModal = document.getElementById("chatInputModal");
let currentOrderId = null;

function openChatModal() {
  document.getElementById('chatModal').classList.remove('hidden');
  document.body.classList.add('overflow-hidden');
  document.documentElement.classList.add('overflow-hidden'); // เพิ่มบรรทัดนี้
  fetchChatModal();
}

function closeChatModal() {
  document.getElementById('chatModal').classList.add('hidden');
  document.body.classList.remove('overflow-hidden');
  document.documentElement.classList.remove('overflow-hidden'); // เพิ่มบรรทัดนี้
}

openChatModalBtn.onclick = openChatModal;

function selectOrderChat(orderId) {
  currentOrderId = orderId;
  document.getElementById("chatOrderTitle").textContent =
    "แชทกับทีมงาน (ออเดอร์ #" + orderId + ")";
  fetchChatModal();
}

function fetchChatModal() {
  if (!currentOrderId) {
    document.getElementById("chatBoxModal").innerHTML =
      '<div class="text-gray-400 text-center">กรุณาเลือกออเดอร์เพื่อดูแชท</div>';
    return;
  }
  fetch("/graphic-design/src/chat/get_messages.php?order_id=" + currentOrderId)
    .then((res) => res.json())
    .then((data) => {
      const chatBoxModal = document.getElementById("chatBoxModal");
      chatBoxModal.innerHTML = "";
      if (data.success && data.messages.length > 0) {
        data.messages.forEach((msg) => {
          const isMe = msg.sender_role === "customer";
          chatBoxModal.innerHTML += `
                <div class="flex ${
                  isMe ? "flex-row-reverse" : ""
                } items-start mb-2">
                  <div class="${isMe ? "text-right" : "text-left"}">
                    <div class="${
                      isMe
                        ? "bg-zinc-900 text-white text-sm rounded-xl "
                        : "bg-gray-200 text-gray-800 text-sm rounded-xl"
                    } py-2 px-4 inline-block">
                      <p>${msg.message.replace(/\n/g, "<br>")}</p>
                    </div>
                    <p class="text-xs text-gray-400 mt-1 ${
                      isMe ? "text-right" : ""
                    }">
                      ${new Date(msg.created_at).toLocaleString("th-TH", {
                        hour12: false,
                      })}
                    </p>
                  </div>
                </div>
              `;
        });
        chatBoxModal.scrollTop = chatBoxModal.scrollHeight;
      } else {
        chatBoxModal.innerHTML =
          '<div class="text-gray-400 text-center">ยังไม่มีข้อความในแชทนี้</div>';
      }
    });
}

function selectOrderChat(orderId) {
  currentOrderId = orderId;
  document.getElementById("chatOrderTitle").textContent =
    "แชทกับทีมงาน (ออเดอร์ #" + orderId + ")";
  // mark as read
  fetch("/graphic-design/src/chat/mark_chat_read.php", {
    method: "POST",
    headers: {
      "Content-Type": "application/x-www-form-urlencoded",
    },
    body: "order_id=" + orderId,
  }).then(() => {
    fetchChatModal();
    refreshOrderSidebar(); // เรียกฟังก์ชันนี้เพื่อรีเฟรช badge ใน sidebar
    refreshChatBadge();
  });
}

function refreshChatBadge() {
  fetch("/graphic-design/src/chat/unread_chat_count.php")
    .then((res) => res.json())
    .then((data) => {
      const badge = document.querySelector("#openChatModalBtn span");
      if (badge) badge.remove();
      if (data.count > 0) {
        const btn = document.getElementById("openChatModalBtn");
        const span = document.createElement("span");
        span.className =
          "absolute top-0 right-0 bg-red-500 text-white text-xs font-bold rounded-full px-1.5 py-0.5";
        span.textContent = data.count;
        btn.appendChild(span);
      }
    });
}

// ตัวอย่างฟังก์ชันรีเฟรช sidebar
function refreshOrderSidebar() {
  fetch("/graphic-design/src/chat/order_list_with_unread.php")
    .then((res) => res.text())
    .then((html) => {
      document.getElementById("orderList").innerHTML = html;
    });
}

document.getElementById("chatFormModal").onsubmit = function (e) {
  e.preventDefault();
  const msg = document.getElementById("chatInputModal").value.trim();
  if (!msg || !currentOrderId) return;
  fetch("/graphic-design/src/chat/send_message.php", {
    method: "POST",
    headers: {
      "Content-Type": "application/x-www-form-urlencoded",
    },
    body: `order_id=${currentOrderId}&message=${encodeURIComponent(msg)}`,
  })
    .then((res) => res.json())
    .then((data) => {
      if (data.success) {
        document.getElementById("chatInputModal").value = "";
        fetchChatModal();
      }
    });
};
