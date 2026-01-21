<?php
require_once 'includes/init.php';
require_once 'includes/db.php';
$db = new DB();
$allProducts = $db->read('products');

// Get filter parameters
$categoryFilter = $_GET['category'] ?? '';
$minPrice = isset($_GET['min_price']) && $_GET['min_price'] !== '' ? (int)$_GET['min_price'] : 0;
$maxPrice = isset($_GET['max_price']) && $_GET['max_price'] !== '' ? (int)$_GET['max_price'] : 999999;
$searchQuery = $_GET['search'] ?? '';
$sortBy = $_GET['sort'] ?? 'newest';

// Filter products
$products = array_filter($allProducts, function($p) use ($categoryFilter, $minPrice, $maxPrice, $searchQuery) {
    // Status filter
    $status = $p['status'] ?? 'active';
    if ($status !== 'active') return false;
    
    // Category filter
    if ($categoryFilter && ($p['category'] ?? '') !== $categoryFilter) return false;
    
    // Price filter (use sale_price if available)
    $price = !empty($p['sale_price']) ? $p['sale_price'] : $p['price'];
    if ($price < $minPrice || $price > $maxPrice) return false;
    
    // Search filter
    if ($searchQuery) {
        $searchLower = mb_strtolower($searchQuery);
        $nameLower = mb_strtolower($p['name']);
        $descLower = mb_strtolower($p['description'] ?? '');
        if (strpos($nameLower, $searchLower) === false && strpos($descLower, $searchLower) === false) {
            return false;
        }
    }
    
    return true;
});

// Sort products
usort($products, function($a, $b) use ($sortBy) {
    switch ($sortBy) {
        case 'price_low':
            $priceA = !empty($a['sale_price']) ? $a['sale_price'] : $a['price'];
            $priceB = !empty($b['sale_price']) ? $b['sale_price'] : $b['price'];
            return $priceA - $priceB;
        case 'price_high':
            $priceA = !empty($a['sale_price']) ? $a['sale_price'] : $a['price'];
            $priceB = !empty($b['sale_price']) ? $b['sale_price'] : $b['price'];
            return $priceB - $priceA;
        case 'name':
            return strcmp($a['name'], $b['name']);
        default: // newest
            return ($b['id'] ?? 0) - ($a['id'] ?? 0);
    }
});

$categories = [
    'food' => '🍜 อาหาร & ขนม',
    'drinks' => '� เครื่องดื่ม',
    'cosmetics' => '💄 เครื่องสำอาง',
    'skincare' => '🧴 สกินแคร์',
    'fashion' => '👗 แฟชั่น & เสื้อผ้า',
    'bags' => '👜 กระเป๋า',
    'shoes' => '👟 รองเท้า',
    'jewelry' => '💍 เครื่องประดับ',
    'electronics' => '📱 อุปกรณ์อิเล็กทรอนิกส์',
    'home' => '🏠 ของใช้ในบ้าน',
    'kitchen' => '🍳 เครื่องครัว',
    'furniture' => '🛋️ เฟอร์นิเจอร์',
    'toys' => '🧸 ของเล่น & ตุ๊กตา',
    'sports' => '⚽ กีฬา & Outdoor',
    'health' => '💊 สุขภาพ & อาหารเสริม',
    'pets' => '� สัตว์เลี้ยง',
    'books' => '📚 หนังสือ & เครื่องเขียน',
    'baby' => '👶 แม่และเด็ก',
    'automotive' => '🚗 ยานยนต์',
    'others' => '📦 อื่นๆ'
];
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ร้านค้า - AKP Shop</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Prompt:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        body { font-family: 'Prompt', 'Sarabun', sans-serif; }
        
        .shop-header {
            text-align: center;
            padding: 40px 20px;
            position: relative;
            background: rgba(255, 255, 255, 0.8);
            border-radius: 20px;
            border: 2px solid white;
            box-shadow: 0 10px 30px rgba(0,0,0,0.05);
            margin-bottom: 30px;
            /* Glassmorphism */
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
        }
        .shop-header h1 {
            font-size: 3rem;
            background: linear-gradient(45deg, var(--primary), #ff6b81);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            margin-bottom: 10px;
            font-weight: 800;
        }
        .shop-header p {
            color: var(--text-muted);
            font-size: 1.1rem;
            font-weight: 500;
        }
        
        /* Filter Panel */
        .filter-panel {
            background: white;
            border-radius: 20px;
            padding: 25px;
            margin-bottom: 30px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.05);
        }
        .filter-row {
            display: flex;
            flex-wrap: wrap;
            gap: 20px;
            align-items: center;
        }
        .filter-group {
            display: flex;
            flex-direction: column;
            gap: 8px;
        }
        .filter-group label {
            font-size: 0.85rem;
            font-weight: 500;
            color: var(--text-muted);
        }
        .filter-group input, .filter-group select {
            padding: 10px 15px;
            border: 2px solid #e0e0e0;
            border-radius: 12px;
            font-size: 0.95rem;
            transition: all 0.3s;
        }
        .filter-group input:focus, .filter-group select:focus {
            outline: none;
            border-color: var(--primary);
        }
        .category-pills {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
        }
        .category-pill {
            padding: 8px 18px;
            border: 2px solid #e0e0e0;
            border-radius: 25px;
            text-decoration: none;
            color: var(--text-main);
            font-size: 0.9rem;
            transition: all 0.3s;
        }
        .category-pill:hover {
            border-color: var(--primary);
            color: var(--primary);
        }
        .category-pill.active {
            background: var(--primary);
            border-color: var(--primary);
            color: white;
        }
        .btn-filter {
            padding: 10px 25px;
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            color: white;
            border: none;
            border-radius: 12px;
            cursor: pointer;
            font-weight: 500;
            transition: all 0.3s;
        }
        .btn-filter:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(255,105,180,0.3);
        }
        .btn-clear {
            padding: 10px 20px;
            background: #f0f0f0;
            color: var(--text-main);
            border: none;
            border-radius: 12px;
            cursor: pointer;
            transition: all 0.3s;
        }
        .btn-clear:hover {
            background: #e0e0e0;
        }
        .result-count {
            color: var(--text-muted);
            font-size: 0.9rem;
            margin-bottom: 20px;
        }
        
        .products-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 30px;
            padding: 20px 0 60px;
        }
        
        .product-card {
            background: white;
            border-radius: 24px;
            overflow: hidden;
            transition: all 0.3s ease;
            box-shadow: 0 5px 20px rgba(0,0,0,0.05);
            position: relative;
        }
        .product-card:hover {
            transform: translateY(-8px);
            box-shadow: 0 15px 40px rgba(0,0,0,0.12);
        }
        
        .product-img {
            height: 280px;
            width: 100%;
            background: #f9f9f9;
            overflow: hidden;
            position: relative;
        }
        .product-img img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.5s ease;
        }
        .product-card:hover .product-img img {
            transform: scale(1.08);
        }
        
        /* Tags & Badges */
        .product-badges {
            position: absolute;
            top: 15px;
            left: 15px;
            display: flex;
            flex-direction: column;
            gap: 8px;
            z-index: 2;
        }
        .badge {
            padding: 6px 14px;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 600;
            color: white;
        }
        .badge-sale { background: linear-gradient(135deg, #ff6b6b, #f5222d); }
        .badge-new { background: linear-gradient(135deg, #52c41a, #389e0d); }
        .badge-bestseller { background: linear-gradient(135deg, #fa8c16, #d46b08); }
        .badge-limited { background: linear-gradient(135deg, #722ed1, #531dab); }
        .badge-freeship { background: linear-gradient(135deg, #1890ff, #096dd9); }
        .badge-outstock { background: #8c8c8c; }
        
        .product-info {
            padding: 25px;
        }
        .product-name {
            font-size: 1.15rem;
            font-weight: 600;
            color: var(--text-main);
            margin-bottom: 8px;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }
        .product-desc {
            font-size: 0.9rem;
            color: var(--text-muted);
            margin-bottom: 15px;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
            height: 2.8em;
        }
        
        .price-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 15px;
        }
        .price-container {
            display: flex;
            flex-direction: column;
        }
        .original-price {
            text-decoration: line-through;
            color: #999;
            font-size: 0.9rem;
        }
        .sale-price {
            color: #f5222d;
            font-weight: 700;
            font-size: 1.3rem;
        }
        .regular-price {
            color: var(--primary);
            font-weight: 700;
            font-size: 1.3rem;
        }
        .discount-tag {
            background: #fff0f0;
            color: #f5222d;
            padding: 3px 10px;
            border-radius: 10px;
            font-size: 0.75rem;
            font-weight: 600;
            margin-left: 10px;
        }
        
        .btn-row {
            display: flex;
            gap: 10px;
            margin-top: 15px;
        }
        .btn-detail {
            flex: 1;
            padding: 12px;
            border: 2px solid #e0e0e0;
            background: white;
            color: var(--text-main);
            border-radius: 12px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s;
            text-align: center;
            text-decoration: none;
        }
        .btn-detail:hover {
            border-color: var(--primary);
            color: var(--primary);
        }
        .btn-cart {
            flex: 1;
            padding: 12px;
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            color: white;
            border: none;
            border-radius: 12px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s;
        }
        .btn-cart:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(255, 105, 180, 0.4);
        }
        .btn-cart:disabled {
            background: #ccc;
            cursor: not-allowed;
            transform: none;
            box-shadow: none;
        }
        
        .empty-state {
            text-align: center;
            padding: 100px 20px;
        }
        .empty-state .icon {
            font-size: 6rem;
            margin-bottom: 20px;
            animation: float 3s ease-in-out infinite;
        }
        @keyframes float {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-15px); }
        }
    </style>
</head>
<body>
    <?php include 'includes/header.php'; ?>

    <div class="container">
        <div class="shop-header">
            <h1>🛒 คอลเลกชันสินค้า</h1>
            <p>สินค้าคุณภาพ คัดสรรมาเพื่อคุณ</p>
        </div>
        
        <!-- Filter Panel -->
        <form class="filter-panel" method="GET" action="shop.php">
            <div class="filter-row" style="margin-bottom: 20px;">
                <div class="category-pills">
                    <a href="shop.php" class="category-pill <?php echo !$categoryFilter ? 'active' : ''; ?>">ทั้งหมด</a>
                    <?php foreach ($categories as $key => $name): ?>
                        <a href="shop.php?category=<?php echo $key; ?>" class="category-pill <?php echo $categoryFilter === $key ? 'active' : ''; ?>">
                            <?php echo $name; ?>
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>
            
            <div class="filter-row">
                <div class="filter-group" style="flex: 2; min-width: 200px;">
                    <label>🔍 ค้นหาสินค้า</label>
                    <input type="text" name="search" placeholder="พิมพ์ชื่อสินค้า..." value="<?php echo htmlspecialchars($searchQuery); ?>">
                </div>
                
                <div class="filter-group">
                    <label>💰 ราคาต่ำสุด</label>
                    <input type="number" name="min_price" placeholder="0" value="<?php echo $minPrice > 0 ? $minPrice : ''; ?>" style="width: 120px;">
                </div>
                
                <div class="filter-group">
                    <label>💰 ราคาสูงสุด</label>
                    <input type="number" name="max_price" placeholder="ไม่จำกัด" value="<?php echo $maxPrice < 999999 ? $maxPrice : ''; ?>" style="width: 120px;">
                </div>
                
                <div class="filter-group">
                    <label>📊 เรียงตาม</label>
                    <select name="sort">
                        <option value="newest" <?php echo $sortBy === 'newest' ? 'selected' : ''; ?>>สินค้าใหม่</option>
                        <option value="price_low" <?php echo $sortBy === 'price_low' ? 'selected' : ''; ?>>ราคาต่ำ → สูง</option>
                        <option value="price_high" <?php echo $sortBy === 'price_high' ? 'selected' : ''; ?>>ราคาสูง → ต่ำ</option>
                        <option value="name" <?php echo $sortBy === 'name' ? 'selected' : ''; ?>>ชื่อ A-Z</option>
                    </select>
                </div>
                
                <?php if ($categoryFilter): ?>
                    <input type="hidden" name="category" value="<?php echo htmlspecialchars($categoryFilter); ?>">
                <?php endif; ?>
                
                <div class="filter-group" style="margin-top: auto;">
                    <label>&nbsp;</label>
                    <button type="submit" class="btn-filter">🔍 กรอง</button>
                </div>
                
                <?php if ($searchQuery || $minPrice > 0 || $maxPrice < 999999 || $categoryFilter): ?>
                <div class="filter-group" style="margin-top: auto;">
                    <label>&nbsp;</label>
                    <a href="shop.php" class="btn-clear">✕ ล้าง</a>
                </div>
                <?php endif; ?>
            </div>
        </form>
        
        <p class="result-count">พบ <?php echo count($products); ?> สินค้า</p>
        
        <?php if (empty($products)): ?>
            <div class="empty-state">
                <div class="icon">🔍</div>
                <h2 style="color: var(--text-main); margin-bottom: 15px;">ไม่พบสินค้าตามเงื่อนไข</h2>
                <p style="color: var(--text-muted);">ลองปรับตัวกรองหรือค้นหาด้วยคำอื่น</p>
                <a href="shop.php" class="btn btn-primary" style="margin-top: 20px;">ดูสินค้าทั้งหมด</a>
            </div>
        <?php else: ?>
            <div class="products-grid">
                <?php foreach ($products as $product): 
                    $stock = $product['stock'] ?? 999;
                    $isOutOfStock = $stock <= 0;
                    $hasSalePrice = !empty($product['sale_price']) && $product['sale_price'] < $product['price'];
                    $tags = array_filter(explode(',', $product['tags'] ?? ''));
                    
                    // Calculate discount percentage
                    $discountPercent = 0;
                    if ($hasSalePrice) {
                        $discountPercent = round((1 - $product['sale_price'] / $product['price']) * 100);
                    }
                ?>
                <div class="product-card" onclick="window.location.href='product.php?id=<?php echo $product['id']; ?>'">
                    <div class="product-img">
                        <div class="product-badges">
                            <?php if ($isOutOfStock): ?>
                                <span class="badge badge-outstock">หมดสต็อก</span>
                            <?php endif; ?>
                            <?php if ($hasSalePrice): ?>
                                <span class="badge badge-sale">ลด <?php echo $discountPercent; ?>%</span>
                            <?php endif; ?>
                            <?php foreach ($tags as $tag): 
                                $tagClass = '';
                                $tagText = '';
                                switch($tag) {
                                    case 'new': $tagClass = 'badge-new'; $tagText = '✨ ใหม่'; break;
                                    case 'bestseller': $tagClass = 'badge-bestseller'; $tagText = '🔥 ขายดี'; break;
                                    case 'limited': $tagClass = 'badge-limited'; $tagText = '💎 Limited'; break;
                                    case 'freeship': $tagClass = 'badge-freeship'; $tagText = '🚗 ส่งฟรี'; break;
                                }
                                if ($tagClass):
                            ?>
                                <span class="badge <?php echo $tagClass; ?>"><?php echo $tagText; ?></span>
                            <?php endif; endforeach; ?>
                        </div>
                        
                        <?php if (!empty($product['image'])): ?>
                            <img src="<?php echo htmlspecialchars($product['image']); ?>" 
                                 alt="<?php echo htmlspecialchars($product['name']); ?>"
                                 onerror="this.src='assets/img/placeholder.jpg'">
                        <?php else: ?>
                            <div style="display:flex;align-items:center;justify-content:center;height:100%;background:#f5f5f5;">
                                <span style="font-size:5rem;">🧸</span>
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="product-info">
                        <h3 class="product-name"><?php echo htmlspecialchars($product['name']); ?></h3>
                        <p class="product-desc"><?php echo htmlspecialchars($product['description'] ?? ''); ?></p>
                        
                        <div class="price-row">
                            <div class="price-container">
                                <?php if ($hasSalePrice): ?>
                                    <span class="original-price">฿<?php echo number_format($product['price']); ?></span>
                                    <span class="sale-price">฿<?php echo number_format($product['sale_price']); ?></span>
                                <?php else: ?>
                                    <span class="regular-price">฿<?php echo number_format($product['price']); ?></span>
                                <?php endif; ?>
                            </div>
                            <?php if ($hasSalePrice): ?>
                                <span class="discount-tag">-<?php echo $discountPercent; ?>%</span>
                            <?php endif; ?>
                        </div>
                        
                        <div class="btn-row">
                            <a href="product.php?id=<?php echo $product['id']; ?>" class="btn-detail" onclick="event.stopPropagation()">รายละเอียด</a>
                            <button onclick="event.stopPropagation(); addToCart(<?php echo $product['id']; ?>)" class="btn-cart" <?php echo $isOutOfStock ? 'disabled' : ''; ?>>
                                <?php echo $isOutOfStock ? 'หมด' : '🛒 ใส่ตะกร้า'; ?>
                            </button>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <?php include 'includes/footer.php'; ?>


</body>
</html>
