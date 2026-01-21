// Inject Modal HTML
document.body.insertAdjacentHTML('beforeend', `
    <div class="modal-overlay" id="customModalOverlay">
        <div class="custom-modal">
            <span class="modal-icon" id="modalIcon">✨</span>
            <h3 class="modal-title" id="modalTitle">แจ้งเตือน</h3>
            <p class="modal-message" id="modalMessage"></p>
            <div style="display: flex; gap: 10px; justify-content: center;">
                <button class="modal-btn" id="modalCancelBtn" onclick="closeModal()" style="background: #ccc; display: none;">ยกเลิก</button>
                <button class="modal-btn" onclick="confirmModal()">ตกลง</button>
            </div>
        </div>
    </div>
`);

const modalOverlay = document.getElementById('customModalOverlay');
const modalTitle = document.getElementById('modalTitle');
const modalMessage = document.getElementById('modalMessage');
const modalIcon = document.getElementById('modalIcon');
const modalCancelBtn = document.getElementById('modalCancelBtn');
let modalCallback = null;

function showModal(message, title = 'แจ้งเตือน', icon = '✨', callback = null, isConfirm = false) {
    modalMessage.textContent = message;
    modalTitle.textContent = title;
    modalIcon.textContent = icon;
    modalCallback = callback;

    if (isConfirm) {
        modalCancelBtn.style.display = 'inline-block';
    } else {
        modalCancelBtn.style.display = 'none';
        // For alerts, existing logic might rely on just closing. 
    }

    modalOverlay.classList.add('active');
}

function confirmModal() {
    modalOverlay.classList.remove('active');
    if (modalCallback) {
        modalCallback();
        modalCallback = null;
    }
}

function closeModal() {
    modalOverlay.classList.remove('active');
    modalCallback = null; // Clear callback on cancel so it doesn't run
}

// Override default alert
window.alert = function (message) {
    showModal(message);
};
