document.addEventListener('DOMContentLoaded', () => {
    initBackground();
    initSideDock();
});

function initBackground() {
    const container = document.createElement('div');
    container.className = 'bg-decoration-container';
    document.body.prepend(container);

    // Cozy Vibe Icons: Autumn leaves, coffee, books, yarn, cats, cookies
    const icons = ['🍂', '☕', '📖', '🧶', '🐱', '🍪', '🪴', '📔', '🧦'];
    const count = 15; // Number of floating items

    for (let i = 0; i < count; i++) {
        createFloatingItem(container, icons);
    }
}

function createFloatingItem(container, icons) {
    const el = document.createElement('div');
    el.className = 'floating-item';
    el.innerText = icons[Math.floor(Math.random() * icons.length)];

    // Random Position (Keep away from center to avoid covering text)
    // We prefer left 0-15% and right 85-100%
    let left;
    if (Math.random() > 0.5) {
        left = Math.random() * 15; // Left side
    } else {
        left = 85 + Math.random() * 15; // Right side
    }

    el.style.left = `${left}%`;
    el.style.animationDuration = `${10 + Math.random() * 20}s`;
    el.style.animationDelay = `-${Math.random() * 20}s`;
    el.style.fontSize = `${1.5 + Math.random() * 2}rem`;

    // Make them slightly brownish/warm
    el.style.color = '#8D6E63';

    container.appendChild(el);
}

function initSideDock() {
    // Dock Actions
    const topBtn = document.getElementById('btn-scroll-top');
    const historyBtn = document.getElementById('btn-history');
    const drawer = document.getElementById('history-drawer');
    const closeDrawer = document.getElementById('close-drawer');

    // Search Elements
    const searchBtn = document.getElementById('btn-search');
    const searchDrawer = document.getElementById('search-drawer');
    const closeSearch = document.getElementById('close-search');
    const searchInput = document.getElementById('dock-search-input');
    const searchResults = document.getElementById('dock-search-results');

    if (topBtn) {
        topBtn.addEventListener('click', (e) => {
            e.preventDefault();
            window.scrollTo({ top: 0, behavior: 'smooth' });
        });
    }

    if (historyBtn && drawer) {
        historyBtn.addEventListener('click', (e) => {
            e.preventDefault();
            drawer.classList.add('active');
            if (searchDrawer) searchDrawer.classList.remove('active'); // Close search if open
            renderHistory();
        });

        closeDrawer.addEventListener('click', () => {
            drawer.classList.remove('active');
        });
    }

    if (searchBtn && searchDrawer) {
        searchBtn.addEventListener('click', (e) => {
            e.preventDefault();
            searchDrawer.classList.add('active');
            if (drawer) drawer.classList.remove('active'); // Close history if open
            setTimeout(() => searchInput.focus(), 100);
        });

        closeSearch.addEventListener('click', () => {
            searchDrawer.classList.remove('active');
        });

        // Search Logic
        let timeout = null;
        searchInput.addEventListener('input', (e) => {
            const query = e.target.value;
            if (timeout) clearTimeout(timeout);

            if (query.length < 1) {
                searchResults.innerHTML = '';
                return;
            }

            timeout = setTimeout(async () => {
                try {
                    searchResults.innerHTML = '<div style="text-align:center; padding:20px; color:#8D6E63;">กำลังค้นหา... 🍃</div>';
                    const response = await fetch(`api/search.php?q=${encodeURIComponent(query)}`);
                    const data = await response.json();

                    if (data.results && data.results.length > 0) {
                        searchResults.innerHTML = data.results.map(p => `
                            <div class="history-item" onclick="window.location.href='product.php?id=${p.id}'" style="border-color: #EFEBE9;">
                                <img src="${p.image || 'assets/images/placeholder.png'}" class="history-img" alt="${p.name}">
                                <div>
                                    <div style="font-size:0.9rem; font-weight:600; color:#4E342E;">${p.name}</div>
                                    <div style="font-size:0.8rem; color:var(--primary);">฿${new Intl.NumberFormat().format(p.price)}</div>
                                </div>
                            </div>
                        `).join('');
                    } else {
                        searchResults.innerHTML = '<div style="text-align:center; padding:20px; color:#8D6E63;">ไม่พบสินค้าที่ใกล้เคียง 🍂</div>';
                    }
                } catch (error) {
                    console.error('Search error:', error);
                    searchResults.innerHTML = '<div style="text-align:center; color:red;">เกิดข้อผิดพลาด</div>';
                }
            }, 300);
        });
    }

    // Close when clicking outside (Generic)
    document.addEventListener('click', (e) => {
        // Close History
        if (drawer && drawer.classList.contains('active') && !drawer.contains(e.target) && !historyBtn.contains(e.target)) {
            drawer.classList.remove('active');
        }
        // Close Search
        if (searchDrawer && searchDrawer.classList.contains('active') && !searchDrawer.contains(e.target) && !searchBtn.contains(e.target)) {
            searchDrawer.classList.remove('active');
        }
    });
}

// History Manager
const HISTORY_KEY = 'akp_viewed_products';
const MAX_HISTORY = 10;

function addToHistory(product) {
    let history = JSON.parse(localStorage.getItem(HISTORY_KEY) || '[]');
    // Remove if exists to re-add at top
    history = history.filter(p => p.id !== product.id);
    history.unshift(product);
    if (history.length > MAX_HISTORY) history.pop();
    localStorage.setItem(HISTORY_KEY, JSON.stringify(history));
}

function renderHistory() {
    const list = document.getElementById('history-list');
    if (!list) return;

    const history = JSON.parse(localStorage.getItem(HISTORY_KEY) || '[]');
    if (history.length === 0) {
        list.innerHTML = '<div style="text-align:center; color:#8D6E63; margin-top:20px;">ยังไม่มีประวัติการเข้าชม 😅</div>';
        return;
    }

    list.innerHTML = history.map(p => `
        <div class="history-item" onclick="window.location.href='product.php?id=${p.id}'" style="border-color: #EFEBE9;">
            <img src="${p.image}" class="history-img" alt="${p.name}">
            <div>
                <div style="font-size:0.9rem; font-weight:600; color:#4E342E;">${p.name}</div>
                <div style="font-size:0.8rem; color:var(--primary);">฿${new Intl.NumberFormat().format(p.price)}</div>
            </div>
        </div>
    `).join('');
}
