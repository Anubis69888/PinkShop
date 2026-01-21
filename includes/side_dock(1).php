<!--側邊抽屜 - Search & History Only -->
<!-- Navigation buttons are now in header.php sidebar -->

<!-- Search Drawer -->
<div id="search-drawer" class="history-drawer">
    <div class="drawer-header">
        <h3 style="margin:0; color:var(--text-main);">🔍 ค้นหาสินค้า</h3>
        <button id="close-search"
            style="background:none; border:none; font-size:1.5rem; cursor:pointer; color:#666;">&times;</button>
    </div>
    <div style="padding: 10px 0;">
        <input type="text" id="dock-search-input" placeholder="พิมพ์ชื่อสินค้า..."
            style="width: 100%; padding: 12px; border-radius: 12px; border: 1px solid #ddd; outline: none; font-family: inherit;">
    </div>
    <div id="dock-search-results" style="margin-top: 10px;">
        <!-- Results -->
    </div>
</div>

<!-- History Drawer -->
<div id="history-drawer" class="history-drawer">
    <div class="drawer-header">
        <h3 style="margin:0; color:var(--primary);">🕒 ดูล่าสุด</h3>
        <button id="close-drawer"
            style="background:none; border:none; font-size:1.5rem; cursor:pointer; color:#666;">&times;</button>
    </div>
    <div id="history-list">
        <!-- Rendered by JS -->
    </div>
</div>