<!-- includes/toast.php -->
<div id="toast" class="hidden fixed bottom-5 right-5 flex items-center max-w-xs p-4 mb-4 text-gray-700 bg-white border border-gray-200 rounded-xl shadow-sm z-50 transition-opacity duration-300" role="alert" aria-live="assertive" aria-atomic="true">
    <div id="toast-icon" class="inline-flex items-center justify-center shrink-0 w-8 h-8 rounded-lg">
        <!-- ไอคอนตามสถานะ -->
    </div>
    <div id="toast-message" class="ml-3 text-sm font-normal"></div>
    <button type="button" class="ml-auto -mx-1.5 -my-1.5 p-1.5 rounded-lg focus:ring-2 focus:ring-white hover:bg-gray-200 inline-flex h-8 w-8" aria-label="Close" onclick="hideToast()">
        <svg aria-hidden="true" class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
            <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"></path>
        </svg>
    </button>
</div>

<script>
  function showToast(message, type) {
    const toast = document.getElementById('toast');
    const toastMessage = document.getElementById('toast-message');
    const toastIcon = document.getElementById('toast-icon');

    toastMessage.textContent = message;

    if (type === "success") {
      toast.className = "fixed bottom-5 right-5 flex items-center w-full max-w-xs p-2 mb-4 text-gray-700 bg-white border border-gray-200 rounded-xl shadow-sm z-50 transition-opacity duration-300";
      toastIcon.innerHTML = `
        <div class="inline-flex items-center justify-center shrink-0 w-8 h-8 text-green-500 bg-green-100 rounded-lg">
          <svg class="w-5 h-5" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 20 20">
            <path d="M10 .5a9.5 9.5 0 1 0 9.5 9.5A9.51 9.51 0 0 0 10 .5Zm3.707 8.207-4 4a1 1 0 0 1-1.414 0l-2-2a1 1 0 0 1 1.414-1.414L9 10.586l3.293-3.293a1 1 0 0 1 1.414 1.414Z"/>
          </svg>
          <span class="sr-only">Success icon</span>
        </div>`;
    } else if (type === "error") {
      toast.className = "fixed bottom-5 right-5 flex items-center w-full max-w-xs p-2 mb-4 text-gray-700 bg-white border border-gray-200 rounded-xl shadow-sm z-50 transition-opacity duration-300";
      toastIcon.innerHTML = `
        <div class="inline-flex items-center justify-center shrink-0 w-8 h-8 text-red-500 bg-red-100 rounded-lg">
          <svg class="w-5 h-5" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 20 20">
            <path d="M10 .5a9.5 9.5 0 1 0 9.5 9.5A9.51 9.51 0 0 0 10 .5Zm3.707 11.793a1 1 0 1 1-1.414 1.414L10 11.414l-2.293 2.293a1 1 0 0 1-1.414-1.414L8.586 10 6.293 7.707a1 1 0 0 1 1.414-1.414L10 8.586l2.293-2.293a1 1 0 0 1 1.414 1.414L11.414 10l2.293 2.293Z"/>
          </svg>
          <span class="sr-only">Error icon</span>
        </div>`;
    } else if (type === "info") {
      toast.className = "fixed bottom-5 right-5 flex items-center w-full max-w-xs p-2 mb-4 text-gray-700 bg-white border border-gray-200 rounded-xl shadow-sm z-50 transition-opacity duration-300";
      toastIcon.innerHTML = `
        <div class="inline-flex items-center justify-center shrink-0 w-8 h-8 text-blue-500 bg-blue-100 rounded-lg">
          <svg class="w-5 h-5" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 20 20">
            <path d="M10 .5a9.5 9.5 0 1 0 9.5 9.5A9.51 9.51 0 0 0 10 .5ZM10 15a1 1 0 1 1 0-2 1 1 0 0 1 0 2Zm1-4a1 1 0 0 1-2 0V6a1 1 0 0 1 2 0v5Z"/>
          </svg>
          <span class="sr-only">Info icon</span>
        </div>`;
    }

    toast.classList.remove('hidden');
    toast.style.opacity = 1;

    setTimeout(() => {
      toast.style.opacity = 0;
      setTimeout(() => toast.classList.add('hidden'), 300);
    }, 4000);
  }

  function hideToast() {
    const toast = document.getElementById('toast');
    toast.style.opacity = 0;
    setTimeout(() => toast.classList.add('hidden'), 300);
  }
</script>
