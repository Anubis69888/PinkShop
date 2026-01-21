/**
 * Custom Alert/Confirm Modal System
 * Beautiful replacement for browser default dialogs
 */

// Create modal container on page load
document.addEventListener('DOMContentLoaded', function () {
    if (!document.getElementById('customAlertContainer')) {
        const container = document.createElement('div');
        container.innerHTML = `
            <div id="customAlertOverlay" class="custom-alert-overlay" style="display: none;">
                <div class="custom-alert-box">
                    <div class="custom-alert-icon" id="customAlertIcon">✓</div>
                    <h3 class="custom-alert-title" id="customAlertTitle">สำเร็จ</h3>
                    <p class="custom-alert-message" id="customAlertMessage">ดำเนินการเรียบร้อย</p>
                    <div class="custom-alert-buttons" id="customAlertButtons">
                        <button class="custom-alert-btn custom-alert-btn-primary" id="customAlertOk">ตกลง</button>
                    </div>
                </div>
            </div>
        `;
        document.body.appendChild(container);
    }
});

// Custom Alert Function
function showAlert(message, type = 'success', title = null) {
    return new Promise((resolve) => {
        const overlay = document.getElementById('customAlertOverlay');
        const alertBox = overlay.querySelector('.custom-alert-box');
        const iconEl = document.getElementById('customAlertIcon');
        const titleEl = document.getElementById('customAlertTitle');
        const messageEl = document.getElementById('customAlertMessage');
        const buttonsEl = document.getElementById('customAlertButtons');

        // Reset classes
        alertBox.className = 'custom-alert-box';
        iconEl.className = 'custom-alert-icon';

        // Add type class
        alertBox.classList.add(`alert-type-${type}`);

        // Config
        const types = {
            success: { icon: '✨', title: 'สำเร็จ!' },
            error: { icon: '❌', title: 'เกิดข้อผิดพลาด!' },
            warning: { icon: '⚠️', title: 'คำเตือน!' },
            info: { icon: 'ℹ️', title: 'แจ้งเตือน' },
            ban: { icon: '🚫', title: 'แบนผู้ใช้' },
            unban: { icon: '✅', title: 'ปลดแบน' }
        };

        const config = types[type] || types.success;

        iconEl.textContent = config.icon;
        titleEl.textContent = title || config.title;
        messageEl.textContent = message;

        buttonsEl.innerHTML = `
            <button class="custom-alert-btn btn-primary" onclick="closeAlert(true)">ตกลง</button>
        `;

        overlay.style.display = 'flex';
        // Force reflow
        void overlay.offsetHeight;
        overlay.classList.add('active');

        window.closeAlert = (result) => {
            overlay.classList.remove('active');
            setTimeout(() => {
                overlay.style.display = 'none';
                resolve(result);
            }, 300);
        };
    });
}

// Custom Confirm Function
function showConfirm(message, type = 'warning', title = null) {
    return new Promise((resolve) => {
        const overlay = document.getElementById('customAlertOverlay');
        const alertBox = overlay.querySelector('.custom-alert-box');
        const iconEl = document.getElementById('customAlertIcon');
        const titleEl = document.getElementById('customAlertTitle');
        const messageEl = document.getElementById('customAlertMessage');
        const buttonsEl = document.getElementById('customAlertButtons');

        // Reset classes
        alertBox.className = 'custom-alert-box';
        iconEl.className = 'custom-alert-icon';

        // Add type class
        alertBox.classList.add(`alert-type-${type}`);

        const types = {
            warning: { icon: '⚠️', title: 'ยืนยันการดำเนินการ' },
            danger: { icon: '🚫', title: 'คำเตือน!' },
            info: { icon: '❓', title: 'ยืนยัน' },
            ban: { icon: '🚫', title: 'ยืนยันการแบน' },
            unban: { icon: '✅', title: 'ยืนยันการปลดแบน' }
        };

        const config = types[type] || types.warning;

        iconEl.textContent = config.icon;
        titleEl.textContent = title || config.title;
        messageEl.innerHTML = message;

        buttonsEl.innerHTML = `
            <button class="custom-alert-btn btn-secondary" onclick="closeAlert(false)">ยกเลิก</button>
            <button class="custom-alert-btn btn-primary" onclick="closeAlert(true)">ยืนยัน</button>
        `;

        overlay.style.display = 'flex';
        // Force reflow
        void overlay.offsetHeight;
        overlay.classList.add('active');

        window.closeAlert = (result) => {
            overlay.classList.remove('active');
            setTimeout(() => {
                overlay.style.display = 'none';
                resolve(result);
            }, 300);
        };
    });
}

// Helper function to adjust color brightness
function adjustColor(color, percent) {
    const num = parseInt(color.replace('#', ''), 16);
    const amt = Math.round(2.55 * percent);
    const R = (num >> 16) + amt;
    const G = (num >> 8 & 0x00FF) + amt;
    const B = (num & 0x0000FF) + amt;
    return '#' + (0x1000000 +
        (R < 255 ? (R < 1 ? 0 : R) : 255) * 0x10000 +
        (G < 255 ? (G < 1 ? 0 : G) : 255) * 0x100 +
        (B < 255 ? (B < 1 ? 0 : B) : 255)
    ).toString(16).slice(1);
}
