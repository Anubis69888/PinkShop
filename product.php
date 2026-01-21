<?php
require_once 'includes/init.php';
require_once 'includes/db.php';
$db = new DB();
$products = $db->read('products');

$id = $_GET['id'] ?? 0;
$product = null;
foreach ($products as $p) {
    if ($p['id'] == $id) {
        $product = $p;
        break;
    }
}

if (!$product) {
    header('Location: shop.php');
    exit;
}

// Track Views (Basic Stats)
$statsFile = 'data/product_stats.json';
if (file_exists($statsFile)) {
    $stats = json_decode(file_get_contents($statsFile), true) ?? [];
    $found = false;
    foreach ($stats as &$stat) {
        if ($stat['product_id'] == $id) {
            $stat['views']++;
            $found = true;
            break;
        }
    }
    if (!$found) {
        $stats[] = ['product_id' => $id, 'views' => 1];
    }
    file_put_contents($statsFile, json_encode($stats, JSON_PRETTY_PRINT));
} else {
    // Create if not exists
    file_put_contents($statsFile, json_encode([['product_id' => $id, 'views' => 1]], JSON_PRETTY_PRINT));
}

// Track Detailed Views for Seller Analytics
$viewsFile = 'data/product_views.json';
$views = file_exists($viewsFile) ? json_decode(file_get_contents($viewsFile), true) ?? [] : [];
$views[] = [
    'product_id' => $id,
    'user_id' => $_SESSION['user_id'] ?? null,
    'viewed_at' => date('Y-m-d H:i:s'),
    'product_name' => $product['name'],
    'seller_id' => $product['seller_id'] ?? null
];
// Keep last 1000 views to prevent file from growing too large
if (count($views) > 1000) {
    $views = array_slice($views, -1000);
}
file_put_contents($viewsFile, json_encode($views, JSON_PRETTY_PRINT));

// Recommendations
$recommendations = [];
$others = array_filter($products, function($p) use ($id) { return $p['id'] != $id; });
if (count($others) > 3) {
    $keys = array_rand($others, 3);
    foreach ($keys as $key) $recommendations[] = $others[$key];
} else {
    $recommendations = $others;
}

// Ensure images array exists
$images = $product['images'] ?? [$product['image']];

// Fetch Seller Info
$sellerName = 'AKP Official';
$sellerId = $product['seller_id'] ?? 0;
if ($sellerId) {
    $sellerUser = $db->find('users', 'id', $sellerId);
    if ($sellerUser) {
        $sellerName = !empty($sellerUser['shop_name']) ? $sellerUser['shop_name'] : $sellerUser['username'];
    }
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($product['name']); ?> - Doll Paradise</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="assets/css/modal.css">
    <link href="https://fonts.googleapis.com/css2?family=Prompt:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <script src="assets/js/modal.js" defer></script>
    <style>
        body { font-family: 'Prompt', sans-serif; }
        .product-detail {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 50px;
            padding: 40px 0;
        }
        @media (max-width: 768px) {
            .product-detail { grid-template-columns: 1fr; gap: 30px; }
        }
        
        /* Slider Styles */
        .slider-container {
            position: relative;
            background: rgba(255, 255, 255, 0.8);
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
            border-radius: 25px;
            padding: 30px;
            box-shadow: 0 15px 35px rgba(0,0,0,0.05);
            border: 2px solid white;
            overflow: hidden;
            /* Decorative background touch */
            background-image: radial-gradient(circle at 50% 50%, rgba(255,255,255,0.8) 0%, rgba(255,255,255,0.4) 100%);
        }
        .main-image-container {
            height: 400px;
            display: flex;
            justify-content: center;
            align-items: center;
            margin-bottom: 20px;
        }
        .main-image {
            max-height: 100%;
            max-width: 100%;
            object-fit: contain;
            transition: opacity 0.3s;
            cursor: zoom-in;
        }
        .thumbnails {
            display: flex;
            gap: 10px;
            overflow-x: auto;
            padding-bottom: 5px;
            scroll-behavior: smooth;
        }
        /* Hide scrollbar for Chrome, Safari and Opera */
        .thumbnails::-webkit-scrollbar {
            display: none;
        }
        /* Hide scrollbar for IE, Edge and Firefox */
        .thumbnails {
            -ms-overflow-style: none;  /* IE and Edge */
            scrollbar-width: none;  /* Firefox */
        }
        .thumb {
            width: 70px;
            height: 70px;
            border-radius: 10px;
            border: 2px solid transparent;
            cursor: pointer;
            object-fit: cover;
            transition: all 0.2s;
            background: #f8f9fa;
        }
        .thumb:hover, .thumb.active {
            border-color: var(--primary);
            transform: scale(1.05);
        }
        .slider-btn {
            position: absolute;
            top: 25%; /* Moved up higher as requested */
            transform: translateY(-50%);
            background: rgba(255,255,255,0.9);
            border: 2px solid white;
            width: 45px;
            height: 45px;
            border-radius: 50%;
            cursor: pointer;
            font-size: 1.2rem;
            color: var(--primary);
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            transition: all 0.2s;
            z-index: 10;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .slider-btn:hover { 
            background: white; 
            transform: translateY(-50%) scale(1.1); 
            color: var(--secondary);
            box-shadow: 0 8px 25px rgba(255, 107, 129, 0.3);
        }
        .prev-btn { left: 10px; }
        .next-btn { right: 10px; }

        /* Lightbox Styles */
        .lightbox-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.9);
            z-index: 2000;
            display: none;
            justify-content: center;
            align-items: center;
            opacity: 0;
            transition: opacity 0.3s ease;
        }
        .lightbox-overlay.active {
            display: flex;
            opacity: 1;
        }
        .lightbox-img {
            max-width: 90%;
            max-height: 90vh;
            object-fit: contain;
            border-radius: 8px;
            box-shadow: 0 0 30px rgba(0,0,0,0.5);
        }
        .lightbox-close {
            position: absolute;
            top: 20px;
            right: 20px;
            background: rgba(255, 255, 255, 0.2);
            border: none;
            color: white;
            font-size: 2rem;
            cursor: pointer;
            width: 50px;
            height: 50px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.2s;
        }
        .lightbox-close:hover {
            background: rgba(255, 255, 255, 0.4);
            transform: rotate(90deg);
        }
        .lightbox-nav {
            position: absolute;
            top: 50%;
            transform: translateY(-50%);
            background: rgba(255, 255, 255, 0.1);
            color: white;
            border: none;
            width: 60px;
            height: 60px;
            border-radius: 50%;
            font-size: 1.5rem;
            cursor: pointer;
            transition: all 0.2s;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .lightbox-nav:hover {
            background: rgba(255, 255, 255, 0.3);
            transform: translateY(-50%) scale(1.1);
        }
        .lightbox-prev { left: 30px; }
        .lightbox-next { right: 30px; }

        .info-panel {
            background: rgba(255, 255, 255, 0.9);
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
            padding: 30px;
            border-radius: 25px;
            border: 2px solid white;
            box-shadow: 0 10px 30px rgba(0,0,0,0.05);
        }

        .info-panel h1 {
            color: var(--primary);
            font-size: 2.5rem;
            margin-bottom: 15px;
            line-height: 1.2;
        }
        .price-tag {
            font-size: 2.5rem;
            font-weight: 800;
            color: var(--primary);
            margin: 20px 0;
            display: inline-block;
            background: rgba(255,255,255,0.5);
            padding: 5px 20px;
            border-radius: 50px;
        }
        .meta-info {
            background: rgba(255,255,255,0.8);
            padding: 25px;
            border-radius: 20px;
            margin: 30px 0;
            border: 1px solid rgba(255,255,255,0.5);
        }
        .meta-row {
            display: flex;
            justify-content: space-between;
            padding: 12px 0;
            border-bottom: 1px solid rgba(0,0,0,0.05);
        }
        .meta-row:last-child { border-bottom: none; }
        
        .related-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 25px;
            margin-top: 25px;
        }
        .product-card {
            background: white;
            border-radius: 24px;
            overflow: hidden;
            transition: all 0.3s;
            border: 1px solid rgba(0,0,0,0.05);
            cursor: pointer;
        }
        .product-card:hover {
            transform: translateY(-5px);
            box-shadow: var(--shadow);
        }
        .product-img {
            height: 250px;
            width: 100%;
            background: #f9f9f9;
            overflow: hidden;
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
        .product-info {
            padding: 20px;
        }
        .price {
            color: var(--primary);
            font-weight: 800;
            font-size: 1.2rem;
        }
    </style>
</head>
<body>
    <?php include 'includes/header.php'; ?>

    <div class="container">
        <div class="product-detail">
            <!-- Left: Slider -->
            <div class="slider-container">
                <button class="slider-btn prev-btn" onclick="changeSlide(-1)">❮</button>
                <div class="main-image-container">
                    <img id="mainImage" src="<?php echo $images[0]; ?>" class="main-image" onclick="openLightbox()">
                </div>
                <button class="slider-btn next-btn" onclick="changeSlide(1)">❯</button>
                
                <div class="thumbnails">
                    <?php foreach ($images as $index => $img): ?>
                        <img src="<?php echo $img; ?>" class="thumb <?php echo $index === 0 ? 'active' : ''; ?>" onclick="setSlide(<?php echo $index; ?>)">
                    <?php endforeach; ?>
                </div>
                
                <!-- Service Highlights -->
                <div style="margin-top: 30px; display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                    <div style="background: rgba(255,255,255,0.6); padding: 15px; border-radius: 15px; text-align: center; border: 1px solid white;">
                        <div style="font-size: 1.8rem; margin-bottom: 5px;">✨</div>
                        <strong style="color: var(--primary); font-size: 0.9rem;">ของแท้ 100%</strong>
                        <p style="font-size: 0.8rem; color: var(--text-muted); margin: 0;">รับประกันคุณภาพ</p>
                    </div>
                    <div style="background: rgba(255,255,255,0.6); padding: 15px; border-radius: 15px; text-align: center; border: 1px solid white;">
                        <div style="font-size: 1.8rem; margin-bottom: 5px;">🚚</div>
                        <strong style="color: var(--primary); font-size: 0.9rem;">จัดส่งรวดเร็ว</strong>
                        <p style="font-size: 0.8rem; color: var(--text-muted); margin: 0;">ส่งของทุกวัน</p>
                    </div>
                    <div style="background: rgba(255,255,255,0.6); padding: 15px; border-radius: 15px; text-align: center; border: 1px solid white;">
                        <div style="font-size: 1.8rem; margin-bottom: 5px;">📦</div>
                        <strong style="color: var(--primary); font-size: 0.9rem;">แพ็คแน่นหนา</strong>
                        <p style="font-size: 0.8rem; color: var(--text-muted); margin: 0;">ห่อกันกระแทกอย่างดี</p>
                    </div>
                    <div style="background: rgba(255,255,255,0.6); padding: 15px; border-radius: 15px; text-align: center; border: 1px solid white;">
                        <div style="font-size: 1.8rem; margin-bottom: 5px;">💬</div>
                        <strong style="color: var(--primary); font-size: 0.9rem;">บริการเป็นกันเอง</strong>
                        <p style="font-size: 0.8rem; color: var(--text-muted); margin: 0;">สอบถามได้ตลอด</p>
                    </div>
                </div>
            </div>

            <!-- Right: Info -->
            <div class="info-panel">
                <?php 
                $stock = $product['stock'] ?? 999;
                $isOutOfStock = $stock <= 0;
                $hasSalePrice = !empty($product['sale_price']) && $product['sale_price'] < $product['price'];
                $tags = array_filter(explode(',', $product['tags'] ?? ''));
                $discountPercent = $hasSalePrice ? round((1 - $product['sale_price'] / $product['price']) * 100) : 0;
                
                $tagLabels = [
                    'new' => '✨ สินค้าใหม่',
                    'bestseller' => '🔥 ขายดี',
                    'limited' => '💎 Limited Edition',
                    'sale' => '🏷️ ลดราคา',
                    'freeship' => '🚗 ส่งฟรี'
                ];
                ?>
                
                <?php if (!empty($tags)): ?>
                <style>
                    .product-tag {
                        background: white; 
                        color: var(--primary); 
                        padding: 6px 15px; 
                        border-radius: 20px; 
                        font-size: 0.85rem; 
                        font-weight: 600;
                        box-shadow: 0 4px 10px rgba(0,0,0,0.05);
                        border: 1px solid rgba(255, 105, 180, 0.2);
                    }
                </style>
                <div style="margin-bottom: 15px; display: flex; flex-wrap: wrap; gap: 8px;">
                    <?php foreach ($tags as $tag): ?>
                        <span class="product-tag">
                            <?php echo $tagLabels[$tag] ?? $tag; ?>
                        </span>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
                
                <h1 class="product-title-gradient"><?php echo htmlspecialchars($product['name']); ?></h1>
                <p style="font-size: 1.1rem; color: var(--text-muted); line-height: 1.8;">
                    <?php echo htmlspecialchars($product['description']); ?>
                </p>
                
                <!-- Price Display -->
                <div style="margin: 25px 0;">
                    <?php if ($hasSalePrice): ?>
                        <span style="text-decoration: line-through; color: #999; font-size: 1.5rem; margin-right: 15px;">
                            ฿<?php echo number_format($product['price']); ?>
                        </span>
                        <span class="price-tag" style="background: linear-gradient(135deg, #ff6b6b, #f5222d); color: white;">
                            ฿<?php echo number_format($product['sale_price']); ?>
                        </span>
                        <span style="background: #fff0f0; color: #f5222d; padding: 8px 15px; border-radius: 10px; font-weight: 600; margin-left: 10px;">
                            ลด <?php echo $discountPercent; ?>%
                        </span>
                    <?php else: ?>
                        <span class="price-tag">฿<?php echo number_format($product['price']); ?></span>
                    <?php endif; ?>
                </div>
                
                <!-- Stock Status -->
                <div style="margin-bottom: 20px;">
                    <?php if ($isOutOfStock): ?>
                        <span style="background: #fff1f0; color: #f5222d; padding: 10px 20px; border-radius: 10px; font-weight: 600;">
                            ❌ สินค้าหมด
                        </span>
                    <?php elseif ($stock <= 10 && $stock !== 999): ?>
                        <span style="background: #fff7e6; color: #fa8c16; padding: 10px 20px; border-radius: 10px; font-weight: 600;">
                            ⚠️ เหลือเพียง <?php echo $stock; ?> ชิ้น
                        </span>
                    <?php else: ?>
                        <span style="background: #f6ffed; color: #52c41a; padding: 10px 20px; border-radius: 10px; font-weight: 600;">
                            ✅ มีสินค้าพร้อมส่ง
                        </span>
                    <?php endif; ?>
                </div>

                    ?>
                    
                    <!-- Seller Store Card -->
                    <?php if($sellerId): ?>
                    <div style="background: linear-gradient(135deg, rgba(255, 107, 129, 0.1), rgba(114, 46, 209, 0.1)); padding: 20px; border-radius: 20px; margin-bottom: 25px; border: 2px solid rgba(255, 107, 129, 0.2);">
                        <div style="display: flex; align-items: center; justify-content: space-between; gap: 15px;">
                            <div style="display: flex; align-items: center; gap: 15px; flex: 1;">
                                <div style="width: 50px; height: 50px; border-radius: 50%; background: linear-gradient(135deg, var(--primary), var(--secondary)); display: flex; align-items: center; justify-content: center; font-size: 1.5rem; color: white; box-shadow: 0 5px 15px rgba(255, 107, 129, 0.3);">
                                    🏪
                                </div>
                                <div style="flex: 1;">
                                    <div style="font-size: 0.85rem; color: var(--text-muted); margin-bottom: 3px;">จำหน่ายโดย</div>
                                    <div style="font-size: 1.2rem; font-weight: 700; color: var(--primary);"><?php echo htmlspecialchars($sellerName); ?></div>
                                </div>
                            </div>
                            <a href="store.php?id=<?php echo $sellerId; ?>" style="background: linear-gradient(135deg, var(--primary), var(--secondary)); color: white; padding: 12px 25px; border-radius: 15px; text-decoration: none; font-weight: 600; box-shadow: 0 5px 15px rgba(255, 107, 129, 0.3); transition: all 0.3s; display: inline-flex; align-items: center; gap: 8px; white-space: nowrap;" onmouseover="this.style.transform='translateY(-2px)'; this.style.boxShadow='0 8px 20px rgba(255, 107, 129, 0.4)'" onmouseout="this.style.transform=''; this.style.boxShadow='0 5px 15px rgba(255, 107, 129, 0.3)'">
                                เยี่ยมชมร้านค้า ➜
                            </a>
                        </div>
                    </div>
                    <?php endif; ?>
                    
                    <div class="meta-info">
                    <?php 
                    // Category name mapping
                    $categoryNames = [
                        'food' => 'อาหาร & ขนม',
                        'drinks' => 'เครื่องดื่ม',
                        'cosmetics' => 'เครื่องสำอาง',
                        'skincare' => 'สกินแคร์',
                        'fashion' => 'แฟชั่น & เสื้อผ้า',
                        'bags' => 'กระเป๋า',
                        'shoes' => 'รองเท้า',
                        'jewelry' => 'เครื่องประดับ',
                        'electronics' => 'อุปกรณ์อิเล็กทรอนิกส์',
                        'home' => 'ของใช้ในบ้าน',
                        'kitchen' => 'เครื่องครัว',
                        'furniture' => 'เฟอร์นิเจอร์',
                        'toys' => 'ของเล่น & ตุ๊กตา',
                        'sports' => 'กีฬา & Outdoor',
                        'health' => 'สุขภาพ & อาหารเสริม',
                        'pets' => 'สัตว์เลี้ยง',
                        'books' => 'หนังสือ & เครื่องเขียน',
                        'baby' => 'แม่และเด็ก',
                        'automotive' => 'ยานยนต์',
                        'others' => 'อื่นๆ'
                    ];
                    $categoryDisplay = $categoryNames[$product['category'] ?? 'others'] ?? 'อื่นๆ';
                    ?>
                    <div class="meta-row">
                        <strong>📂 หมวดหมู่</strong>
                        <span><?php echo $categoryDisplay; ?></span>
                    </div>
                    
                    <div class="meta-row">
                        <strong>🏷️ รหัสสินค้า</strong>
                        <span><?php echo !empty($product['sku']) ? htmlspecialchars($product['sku']) : '-'; ?></span>
                    </div>

                    <div class="meta-row">
                        <strong>📐 ขนาด/ปริมาณ</strong>
                        <span><?php echo !empty($product['size']) ? $product['size'] : '-'; ?></span>
                    </div>

                    <div class="meta-row">
                        <strong>📦 วัสดุ/ส่วนประกอบ</strong>
                        <span><?php echo !empty($product['material']) ? $product['material'] : '-'; ?></span>
                    </div>

                    <div class="meta-row">
                        <strong>🌏 แหล่งผลิต/ยี่ห้อ</strong>
                        <span><?php echo !empty($product['origin']) ? $product['origin'] : '-'; ?></span>
                    </div>

                    <div class="meta-row">
                        <strong>⚖️ น้ำหนัก</strong>
                        <span><?php echo !empty($product['weight']) ? $product['weight'] : '-'; ?></span>
                    </div>

                    <div class="meta-row">
                        <strong>📏 ขนาดบรรจุภัณฑ์</strong>
                        <span><?php echo !empty($product['dimensions']) ? $product['dimensions'] : '-'; ?></span>
                    </div>
                </div>
                
                <?php if (!empty($product['video_url'])): ?>
                <div style="margin: 20px 0;">
                    <h4 style="margin-bottom: 10px; color: var(--primary);">🎬 วิดีโอสินค้า</h4>
                    <?php 
                    $videoUrl = $product['video_url'];
                    // Convert YouTube URL to embed
                    if (strpos($videoUrl, 'youtube.com') !== false || strpos($videoUrl, 'youtu.be') !== false) {
                        preg_match('/(?:youtube\.com\/watch\?v=|youtu\.be\/)([a-zA-Z0-9_-]+)/', $videoUrl, $matches);
                        if (!empty($matches[1])) {
                            echo '<iframe width="100%" height="250" style="border-radius:15px;" src="https://www.youtube.com/embed/'.$matches[1].'" frameborder="0" allowfullscreen></iframe>';
                        }
                    } else {
                        echo '<a href="'.htmlspecialchars($videoUrl).'" target="_blank" class="btn btn-secondary" style="display:block;text-align:center;">🔗 ดูวิดีโอ</a>';
                    }
                    ?>
                </div>
                <?php endif; ?>

                <style>
                    .btn-add-cart {
                        width: 100%;
                        padding: 18px;
                        font-size: 1.2rem;
                        border-radius: 16px;
                        background: #ffffff;
                        color: #ff6b81;
                        border: 2px solid white;
                        cursor: pointer;
                        font-weight: 700;
                        box-shadow: 0 5px 15px rgba(0, 0, 0, 0.08);
                        transition: all 0.3s;
                    }
                    .btn-add-cart:hover {
                        transform: translateY(-3px);
                        box-shadow: 0 10px 25px rgba(255, 107, 129, 0.25);
                        color: #ff4757;
                        background: #fff0f3;
                    }
                    .btn-add-cart:disabled {
                        opacity: 0.5;
                        cursor: not-allowed;
                        transform: none;
                        box-shadow: none;
                    }
                    .product-title-gradient {
                        background: linear-gradient(45deg, var(--primary), #ff6b81);
                        -webkit-background-clip: text;
                        -webkit-text-fill-color: transparent;
                        display: inline-block;
                    }
                </style>
                <button onclick="addToCart(<?php echo $product['id']; ?>)" class="btn-add-cart" <?php echo $isOutOfStock ? 'disabled' : ''; ?>>
                    <?php echo $isOutOfStock ? 'สินค้าหมด' : 'เพิ่มลงตะกร้า 🛍️'; ?>
                </button>
            </div>
        </div>

        <div style="margin: 80px 0;">
            <h2 style="color: var(--primary); margin-bottom: 30px; text-align: center; font-size: 2rem;">สินค้าที่คุณอาจชอบ</h2>
            <div class="related-grid">
                <?php foreach ($recommendations as $p): 
                    // Use $p instead of $rec for consistency
                    $hasSalePrice = !empty($p['sale_price']) && $p['sale_price'] < $p['price'];
                    $discount = $hasSalePrice ? round((1 - $p['sale_price'] / $p['price']) * 100) : 0;
                ?>
                <div class="product-card" onclick="window.location.href='product.php?id=<?php echo $p['id']; ?>'">
                        <?php if ($hasSalePrice): ?>
                            <div class="discount-tag" style="position: absolute; top: 10px; right: 10px; z-index: 2;">-<?php echo $discount; ?>%</div>
                        <?php endif; ?>
                        
                        <div class="product-img">
                             <?php if (!empty($p['image'])): ?>
                                <img src="<?php echo htmlspecialchars($p['image']); ?>" alt="<?php echo htmlspecialchars($p['name']); ?>">
                            <?php else: ?>
                                <span style="font-size: 4rem;">🧸</span>
                            <?php endif; ?>
                        </div>
                        
                        <div class="product-info">
                            <h3 class="product-name"><?php echo htmlspecialchars($p['name']); ?></h3>
                            <p class="product-desc"><?php echo htmlspecialchars($p['description'] ?? ''); ?></p>
                            
                            <div class="price-row">
                                <div class="price-container">
                                    <?php if ($hasSalePrice): ?>
                                        <span class="original-price">฿<?php echo number_format($p['price']); ?></span>
                                        <span class="sale-price">฿<?php echo number_format($p['sale_price']); ?></span>
                                    <?php else: ?>
                                        <span class="regular-price">฿<?php echo number_format($p['price']); ?></span>
                                    <?php endif; ?>
                                </div>
                            </div>
                            
                            <div class="btn-row">
                                <a href="product.php?id=<?php echo $p['id']; ?>" class="btn-detail" onclick="event.stopPropagation()">รายละเอียด</a>
                                <button onclick="event.stopPropagation(); addToCart(<?php echo $p['id']; ?>)" class="btn-cart">
                                    🛒 ใส่ตะกร้า
                                </button>
                            </div>
                        </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <!-- Lightbox Modal -->
    <div id="lightbox" class="lightbox-overlay" onclick="if(event.target === this) closeLightbox()">
        <button class="lightbox-close" onclick="closeLightbox()">×</button>
        <button class="lightbox-nav lightbox-prev" onclick="changeLightboxSlide(-1)">❮</button>
        <img id="lightboxImage" src="" class="lightbox-img">
        <button class="lightbox-nav lightbox-next" onclick="changeLightboxSlide(1)">❯</button>
    </div>

    <script>
        const images = <?php echo json_encode($images); ?>;
        let currentIndex = 0;
        const mainImage = document.getElementById('mainImage');
        const thumbs = document.querySelectorAll('.thumb');

        function updateSlider() {
            mainImage.style.opacity = '0';
            setTimeout(() => {
                mainImage.src = images[currentIndex];
                mainImage.style.opacity = '1';
                thumbs.forEach((t, i) => {
                    if (i === currentIndex) t.classList.add('active');
                    else t.classList.remove('active');
                });
            }, 300);
        }

        function changeSlide(direction) {
            currentIndex += direction;
            if (currentIndex >= images.length) currentIndex = 0;
            if (currentIndex < 0) currentIndex = images.length - 1;
            updateSlider();
        }

        function setSlide(index) {
            currentIndex = index;
            updateSlider();
        }

        // Lightbox Logic
        const lightbox = document.getElementById('lightbox');
        const lightboxImg = document.getElementById('lightboxImage');

        function openLightbox() {
            lightboxImg.src = images[currentIndex];
            lightbox.classList.add('active');
            document.body.style.overflow = 'hidden'; // Prevent scrolling
        }

        function closeLightbox() {
            lightbox.classList.remove('active');
            document.body.style.overflow = 'auto'; // Restore scrolling
        }

        function changeLightboxSlide(direction) {
            currentIndex += direction;
            if (currentIndex >= images.length) currentIndex = 0;
            if (currentIndex < 0) currentIndex = images.length - 1;
            
            // Update both lightbox and main slider
            updateSlider();
            
            // Fade effect for lightbox image
            lightboxImg.style.opacity = '0.5';
            setTimeout(() => {
                lightboxImg.src = images[currentIndex];
                lightboxImg.style.opacity = '1';
            }, 200);
        }

        // Keyboard navigation
        document.addEventListener('keydown', (e) => {
            if (!lightbox.classList.contains('active')) return;
            if (e.key === 'Escape') closeLightbox();
            if (e.key === 'ArrowLeft') changeLightboxSlide(-1);
            if (e.key === 'ArrowRight') changeLightboxSlide(1);
        });

        async function addToCart(productId) {
            try {
                const response = await fetch('api/cart.php', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/json'},
                    body: JSON.stringify({ action: 'add', productId: productId })
                });
                const result = await response.json();
                if (result.success) {
                    showModal('เพิ่มสินค้าลงตะกร้าเรียบร้อยแล้ว!', 'สำเร็จ', '🛒');
                } else {
                    showModal(result.message || 'เกิดข้อผิดพลาด', 'แจ้งเตือน', '⚠️');
                }
            } catch (e) {
                console.error(e);
            }
        }


        // Add to History (Real System)
        document.addEventListener('DOMContentLoaded', () => {
            if (typeof addToHistory === 'function') {
                addToHistory({
                    id: <?php echo $product['id']; ?>,
                    name: "<?php echo addslashes($product['name']); ?>",
                    image: "<?php echo addslashes($images[0]); ?>",
                    price: <?php echo $product['price']; ?>
                });
            }
        });
    </script>
</body>
</html>
