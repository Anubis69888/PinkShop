let currentConfig = {
    hair: 'h1',
    eyes: 'e1',
    outfit: 'o1'
};

function init() {
    renderOptions('hair');
    renderOptions('eyes');
    renderOptions('outfit');
    updatePreview();
}

function renderOptions(category) {
    const container = document.getElementById(`options-${category}`);
    dollData[category].forEach(item => {
        const div = document.createElement('div');
        div.className = `option-card ${currentConfig[category] === item.id ? 'selected' : ''}`;
        div.onclick = () => selectPart(category, item.id);

        // Simple preview text or icon
        div.innerHTML = `<div style="font-size: 0.8rem; text-align: center;">${item.name}</div>`;
        container.appendChild(div);
    });
}

function selectPart(category, id) {
    currentConfig[category] = id;

    // Update UI
    document.querySelectorAll(`#options-${category} .option-card`).forEach(el => {
        el.classList.remove('selected');
        if (el.innerText.includes(dollData[category].find(i => i.id === id).name)) {
            el.classList.add('selected');
        }
    });

    updatePreview();
}

function updatePreview() {
    const hair = dollData.hair.find(i => i.id === currentConfig.hair);
    const eyes = dollData.eyes.find(i => i.id === currentConfig.eyes);
    const outfit = dollData.outfit.find(i => i.id === currentConfig.outfit);

    if (hair) {
        document.getElementById('layer-hair-front').src = hair.front;
        document.getElementById('layer-hair-back').src = hair.back;
    }
    if (eyes) document.getElementById('layer-eyes').src = eyes.src;
    if (outfit) document.getElementById('layer-outfit').src = outfit.src;
}

function switchTab(tab) {
    document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
    event.target.classList.add('active');

    document.querySelectorAll('.options-grid').forEach(d => d.style.display = 'none');
    document.getElementById(`options-${tab}`).style.display = 'grid';
}

async function saveAvatar() {
    try {
        const response = await fetch('api/avatar.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ action: 'save', config: currentConfig })
        });
        const result = await response.json();
        if (result.success) {
            alert('บันทึกตุ๊กตาเรียบร้อยแล้ว! 💖');
        } else {
            alert(result.message || 'กรุณาเข้าสู่ระบบเพื่อบันทึก');
            if (result.message === 'Unauthorized') window.location.href = 'login.php';
        }
    } catch (e) {
        console.error(e);
        alert('เกิดข้อผิดพลาดในการบันทึก');
    }
}

// Initialize
document.addEventListener('DOMContentLoaded', init);
