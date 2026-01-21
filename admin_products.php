<?php
session_start();
if (empty($_SESSION['user_id']) || empty($_SESSION['is_admin'])) {
    header('Location: index.php');
    exit;
}

require_once 'includes/db.php';
$db = new DB();
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>จัดการสินค้า - Admin Panel</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="assets/css/admin-modern.css">
    <link href="https://fonts.googleapis.com/css2?family=Prompt:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        body { 
            font-family: 'Prompt', sans-serif; 
            background: var(--bg-gradient); 
            padding-top: 20px;
        }
        
        .admin-container {
            max-width: 1400px;
            margin: 40px auto;
            padding: 20px;
        }
        
        .page-header {
            background: rgba(255, 255, 255, 0.7);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border-radius: 32px;
            padding: 30px 40px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.3);
            margin-bottom: 30px;
            position: relative;
            overflow: hidden;
        }

        .page-header::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, #722ed1, #eb2f96, #52c41a, #1890ff);
            background-size: 300% 100%;
            animation: gradientShift 3s ease infinite;
        }

        @keyframes gradientShift {
            0%, 100% { background-position: 0% 50%; }
            50% { background-position: 100% 50%; }
        }

        .header-content {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .header-left {
            display: flex;
            align-items: center;
            gap: 20px;
        }

        .back-button {
            background: rgba(114, 46, 209, 0.1);
            border: 2px solid rgba(114, 46, 209, 0.3);
            color: var(--primary);
            padding: 12px 24px;
            border-radius: 20px;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .back-button:hover {
            background: var(--primary);
            color: white;
            transform: translateX(-5px);
            box-shadow: 0 4px 12px rgba(114, 46, 209, 0.3);
        }

        .header-title-group {
            display: flex;
            align-items: center;
            gap: 15px;
        }
        
        .page-header h1 {
            color: var(--primary);
            margin: 0;
            font-size: 2rem;
            font-weight: 700;
            background: linear-gradient(135deg, #722ed1, #eb2f96);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .product-count-badge {
            background: linear-gradient(135deg, #722ed1, #eb2f96);
            color: white;
            padding: 8px 16px;
            border-radius: 20px;
            font-size: 0.9rem;
            font-weight: 600;
            box-shadow: 0 4px 12px rgba(114, 46, 209, 0.3);
        }
        
        .btn-add {
            padding: 12px 30px;
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            color: white;
            text-decoration: none;
            border-radius: 12px;
            font-weight: 500;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            border: none;
            cursor: pointer;
        }
        
        .btn-add:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(255, 105, 180, 0.4);
        }
        
        /* Stats */
        .stats-row {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .stat-card {
            background: white;
            padding: 25px;
            border-radius: 18px;
            box-shadow: var(--shadow);
        }
        
        .stat-card .label {
            color: var(--text-secondary);
            font-size: 0.9rem;
            margin-bottom: 8px;
        }
        
        .stat-card .number {
            font-size: 2rem;
            font-weight: 700;
            color: var(--text-main);
        }
        
        .stat-total { border-left: 5px solid #1890ff; }
        .stat-active { border-left: 5px solid #52c41a; }
        .stat-low { border-left: 5px solid #fa8c16; }
        .stat-out { border-left: 5px solid #f5222d; }
        
        /* Filter Panel */
        .filter-panel {
            background: white;
            padding: 20px 30px;
            border-radius: 20px;
            box-shadow: var(--shadow);
            margin-bottom: 20px;
            display: flex;
            gap: 15px;
            flex-wrap: wrap;
            align-items: center;
        }
        
        .filter-group {
            display: flex;
            flex-direction: column;
            gap: 5px;
        }
        
        .filter-group label {
            font-size: 0.85rem;
            color: var(--text-secondary);
            font-weight: 500;
        }
        
        .filter-select, .filter-input {
            padding: 10px 15px;
            border: 2px solid #f0f0f0;
            border-radius: 12px;
            font-family: 'Prompt', sans-serif;
            transition: all 0.3s ease;
        }
        
        .filter-select:focus, .filter-input:focus {
            outline: none;
            border-color: var(--primary);
        }
        
        /* Products Grid */
        .products-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 25px;
        }
        
        .product-card {
            background: white;
            border-radius: 20px;
            box-shadow: var(--shadow);
            overflow: hidden;
            transition: all 0.3s ease;
            position: relative;
        }
        
        .product-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 40px rgba(0, 0, 0, 0.15);
        }
        
        .product-image {
            width: 100%;
            height: 200px;
            object-fit: cover;
            background: linear-gradient(135deg, #f5f5f5, #e0e0e0);
        }
        
        .product-body {
            padding: 20px;
        }
        
        .product-name {
            font-size: 1.1rem;
            font-weight: 600;
            color: var(--text-main);
            margin: 0 0 8px 0;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }
        
        .product-price {
            font-size: 1.3rem;
            font-weight: 700;
            color: var(--primary);
            margin-bottom: 12px;
        }
        
        .product-stock {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
            padding: 10px;
            background: #f8f9fa;
            border-radius: 10px;
        }
        
        .stock-label {
            font-size: 0.85rem;
            color: var(--text-secondary);
        }
        
        .stock-value {
            font-weight: 600;
            padding: 4px 12px;
            border-radius: 12px;
            font-size: 0.9rem;
        }
        
        .stock-good {
            background: #f6ffed;
            color: #52c41a;
        }
        
        .stock-low {
            background: #fff7e6;
            color: #fa8c16;
        }
        
        .stock-out {
            background: #fff1f0;
            color: #f5222d;
        }
        
        .product-actions {
            display: flex;
            gap: 8px;
        }
        
        .btn-icon {
            flex: 1;
            padding: 10px;
            border: none;
            border-radius: 10px;
            cursor: pointer;
            font-family: 'Prompt', sans-serif;
            font-size: 0.85rem;
            font-weight: 500;
            transition: all 0.3s ease;
            text-align: center;
        }
        
        .btn-edit {
            background: #e6f7ff;
            color: #1890ff;
        }
        
        .btn-edit:hover {
            background: #1890ff;
            color: white;
        }
        
        .btn-delete {
            background: #fff1f0;
            color: #f5222d;
        }
        
        .btn-delete:hover {
            background: #f5222d;
            color: white;
        }
        
        .status-badge {
            position: absolute;
            top: 15px;
            right: 15px;
            padding: 6px 14px;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .status-active {
            background: #52c41a;
            color: white;
        }
        
        .status-inactive {
            background: #8c8c8c;
            color: white;
        }
        
        .empty-state {
            text-align: center;
            padding: 80px 20px;
            background: white;
            border-radius: 20px;
            grid-column: 1 / -1;
        }
        
        .empty-state-icon {
            font-size: 5rem;
            margin-bottom: 20px;
            opacity: 0.3;
        }
        
        .loading {
            text-align: center;
            padding: 60px;
            font-size: 1.2rem;
            color: var(--text-secondary);
        }
    </style>
</head>
<body>
    <?php include 'includes/header.php'; ?>
    
    <div class="admin-container">
        <div class="page-header">
            <div class="header-content">
                <div class="header-left">
                    <a href="admin_dashboard.php" class="back-button">
                        <i class="fas fa-arrow-left"></i>
                        <span>ย้อนกลับ</span>
                    </a>
                    <div class="header-title-group">
                        <h1>จัดการสินค้า</h1>
                        <span id="productCountBadge" class="product-count-badge">
                            <i class="fas fa-box"></i> กำลังโหลด...
                        </span>
                    </div>
                </div>
                <a href="add_product.php" class="btn-add">
                    <i class="fas fa-plus"></i>
                    เพิ่มสินค้าใหม่
                </a>
            </div>
        </div>
        
        <!-- Product Statistics -->
        <div class="stats-row" id="productStats">
            <div class="stat-card stat-total">
                <div class="label">สินค้าทั้งหมด</div>
                <div class="number" id="totalProducts">-</div>
            </div>
            <div class="stat-card stat-active">
                <div class="label">เปิดขาย</div>
                <div class="number" id="activeProducts">-</div>
            </div>
            <div class="stat-card stat-low">
                <div class="label">สต็อกใกล้หมด</div>
                <div class="number" id="lowProducts">-</div>
            </div>
            <div class="stat-card stat-out">
                <div class="label">สต็อกหมด</div>
                <div class="number" id="outProducts">-</div>
            </div>
        </div>
        
        <!-- Filter Panel -->
        <div class="filter-panel">
            <div class="filter-group">
                <label>หมวดหมู่</label>
                <select id="filterCategory" class="filter-select">
                    <option value="">ทั้งหมด</option>
                    <option value="dolls">ตุ๊กตา</option>
                    <option value="accessories">อุปกรณ์เสริม</option>
                    <option value="clothes">เสื้อผ้า</option>
                    <option value="others">อื่นๆ</option>
                </select>
            </div>
            
            <div class="filter-group">
                <label>สถานะสต็อก</label>
                <select id="filterStock" class="filter-select">
                    <option value="">ทั้งหมด</option>
                    <option value="good">สต็อกเพียงพอ (>10)</option>
                    <option value="low">สต็อกต่ำ (1-10)</option>
                    <option value="out">หมดสต็อก</option>
                </select>
            </div>
            
            <div class="filter-group">
                <label>ค้นหาสินค้า</label>
                <input type="text" id="filterSearch" class="filter-input" placeholder="ชื่อสินค้า...">
            </div>
            
            <button onclick="loadProducts()" class="btn-add" style="margin-top: 20px;">
                🔍 ค้นหา
            </button>
        </div>
        
        <!-- Products Grid -->
        <div class="products-grid" id="productsGrid">
            <div class="loading">กำลังโหลดข้อมูล...</div>
        </div>
    </div>
    
    <script>
        document.addEventListener('DOMContentLoaded', loadProducts);
        
        async function loadProducts() {
            const category = document.getElementById('filterCategory').value;
            const stock = document.getElementById('filterStock').value;
            const search = document.getElementById('filterSearch').value;
            
            try {
                const params = new URLSearchParams();
                if (category) params.append('category', category);
                if (stock) params.append('stock', stock);
                if (search) params.append('search', search);
                
                const response = await fetch(`api/admin_products.php?${params}`);
                const data = await response.json();
                
                if (!data.success) {
                    throw new Error(data.message);
                }
                
                // Update statistics
                document.getElementById('totalProducts').textContent = data.stats.total || 0;
                document.getElementById('activeProducts').textContent = data.stats.active || 0;
                document.getElementById('lowProducts').textContent = data.stats.low_stock || 0;
                document.getElementById('outProducts').textContent = data.stats.out_stock || 0;
                
                // Update count badge in header
                const badge = document.getElementById('productCountBadge');
                if (badge) {
                    badge.innerHTML = `<i class="fas fa-box"></i> ${data.stats.total || 0} รายการ`;
                }
                
                // Update products grid
                const grid = document.getElementById('productsGrid');
                grid.innerHTML = '';
                
                if (data.products.length === 0) {
                    grid.innerHTML = `
                        <div class="empty-state">
                            <div class="empty-state-icon">📦</div>
                            <h3>ไม่พบสินค้า</h3>
                            <p style="color: var(--text-secondary);">ลองเปลี่ยนตัวกรองหรือเพิ่มสินค้าใหม่</p>
                        </div>
                    `;
                    return;
                }
                
                data.products.forEach(product => {
                    // Handle stock
                    const stockValue = product.stock !== undefined ? parseInt(product.stock) : 999;
                    const stockDisplay = product.stock !== undefined ? `${stockValue} ชิ้น` : 'ไม่จำกัด';
                    const stockClass = getStockClass(stockValue);
                    
                    // Handle status
                    const status = product.status || 'active';
                    let statusBadge = '';
                    if (status === 'hidden') {
                        statusBadge = '<span class="status-badge" style="background:#ffc107;color:#333;">ซ่อน</span>';
                    } else if (status === 'draft') {
                        statusBadge = '<span class="status-badge" style="background:#8c8c8c;color:white;">Draft</span>';
                    } else if (stockValue <= 0) {
                        statusBadge = '<span class="status-badge status-inactive">หมด</span>';
                    } else {
                        statusBadge = '<span class="status-badge status-active">Active</span>';
                    }
                    
                    // Handle sale price
                    let priceHtml = `<span style="color:var(--primary);font-weight:700;">${parseFloat(product.price).toLocaleString()} ฿</span>`;
                    if (product.sale_price && product.sale_price < product.price) {
                        priceHtml = `
                            <span style="text-decoration:line-through;color:#999;font-size:0.9rem;">${parseFloat(product.price).toLocaleString()} ฿</span>
                            <span style="color:#f5222d;font-weight:700;">${parseFloat(product.sale_price).toLocaleString()} ฿</span>
                        `;
                    }
                    
                    // Tags
                    const tagsBadges = (product.tags || '').split(',').filter(t => t).map(t => {
                        const tagNames = {bestseller:'🔥',new:'✨',limited:'💎',sale:'🏷️',freeship:'🚗'};
                        return `<span style="background:#fff0f5;color:var(--primary);padding:2px 8px;border-radius:10px;font-size:0.7rem;margin-right:3px;">${tagNames[t]||t}</span>`;
                    }).join('');
                    
                    // Simple image handling with emoji fallback
                    let imageHtml = '';
                    let hasImage = false;
                    let imageSrc = '';
                    
                    // Try to get image from various sources
                    if (product.images && Array.isArray(product.images) && product.images.length > 0 && product.images[0]) {
                        imageSrc = product.images[0];
                        hasImage = true;
                    } else if (product.image && product.image.trim()) {
                        imageSrc = product.image;
                        hasImage = true;
                    }
                    
                    if (hasImage && imageSrc !== '-') {
                        // Ensure path starts with /AKP/ for absolute path
                        let displayPath = imageSrc;
                        if (!imageSrc.startsWith('http') && !imageSrc.startsWith('/')) {
                            displayPath = '/AKP/' + imageSrc;
                        }
                        
                        // Show actual image with emoji fallback on error
                        imageHtml = `
                            <div style="width: 100%; height: 200px; position: relative; overflow: hidden; background: #f8f9fa; border-radius: 20px 20px 0 0;">
                                <img src="${displayPath}" 
                                     alt="${product.name}" 
                                     style="width: 100%; height: 100%; object-fit: cover; display: block;"
                                     onload="this.style.display='block'; this.nextElementSibling.style.display='none';"
                                     onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                                <div style="width: 100%; height: 100%; display: none; align-items: center; justify-content: center; font-size: 4rem; color: #ccc; position: absolute; top: 0; left: 0;">
                                    📦
                                </div>
                            </div>
                        `;
                    } else {
                        // No image - show emoji directly
                        imageHtml = `
                            <div style="width: 100%; height: 200px; display: flex; align-items: center; justify-content: center; background: #f8f9fa; font-size: 4rem; color: #ccc; border-radius: 20px 20px 0 0;">
                                📦
                            </div>
                        `;
                    }
                    
                    const card = `
                        <div class="product-card">
                            ${statusBadge}
                            ${imageHtml}
                            <div class="product-body">
                                <h3 class="product-name" title="${product.name}">${product.name}</h3>
                                ${product.sku ? `<div style="font-size:0.8rem;color:#888;margin-bottom:5px;">SKU: ${product.sku}</div>` : ''}
                                <div class="product-price">${priceHtml}</div>
                                ${tagsBadges ? `<div style="margin:8px 0;">${tagsBadges}</div>` : ''}
                                
                                <div class="product-stock">
                                    <span class="stock-label">สต็อก</span>
                                    <span class="stock-value ${stockClass}">${stockDisplay}</span>
                                </div>
                                
                                <div class="product-actions">
                                    <button class="btn-icon btn-edit" onclick="editProduct(${product.id})">
                                        ✏️ แก้ไข
                                    </button>
                                    <button class="btn-icon btn-delete" onclick="deleteProduct(${product.id}, '${product.name.replace(/'/g, "\\'")}')">
                                        🗑️ ลบ
                                    </button>
                                </div>
                            </div>
                        </div>
                    `;
                    grid.insertAdjacentHTML('beforeend', card);
                });
                
            } catch (error) {
                console.error('Error loading products:', error);
                Swal.fire({
                    icon: 'error',
                    title: 'เกิดข้อผิดพลาด',
                    text: error.message
                });
            }
        }
        
        function getStockClass(stock) {
            if (stock === undefined || stock === null || stock === 999) return 'stock-good';
            if (stock <= 0) return 'stock-out';
            if (stock <= 10) return 'stock-low';
            return 'stock-good';
        }
        
        function getStockText(stock) {
            if (stock === undefined || stock === null || stock === 999) return 'ไม่จำกัด';
            if (stock <= 0) return 'หมดสต็อก';
            if (stock <= 10) return 'สต็อกต่ำ';
            return 'สต็อกเพียงพอ';
        }
        
        function editProduct(productId) {
            window.location.href = `edit_product.php?id=${productId}`;
        }
        
        async function deleteProduct(productId, productName) {
            const result = await Swal.fire({
                icon: 'warning',
                title: 'ยืนยันการลบสินค้า',
                text: `คุณต้องการลบสินค้า "${productName}" ใช่หรือไม่?`,
                showCancelButton: true,
                confirmButtonText: 'ลบ',
                cancelButtonText: 'ยกเลิก',
                confirmButtonColor: '#f5222d'
            });
            
            if (!result.isConfirmed) return;
            
            try {
                const response = await fetch('api/admin_products.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        action: 'delete',
                        product_id: productId
                    })
                });
                
                const data = await response.json();
                
                if (!data.success) {
                    throw new Error(data.message);
                }
                
                await Swal.fire({
                    icon: 'success',
                    title: 'ลบสินค้าสำเร็จ!',
                    timer: 1500,
                    showConfirmButton: false
                });
                
                loadProducts();
                
            } catch (error) {
                Swal.fire({
                    icon: 'error',
                    title: 'เกิดข้อผิดพลาด',
                    text: error.message
                });
            }
        }
    </script>
</body>
</html>
