<?php require_once 'includes/init.php'; ?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AKP Shop | ช้อปปิ้งออนไลน์ ครบทุกสิ่งที่ต้องการ</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Prompt:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        body { font-family: 'Prompt', sans-serif; }
        
        /* Promotion Banner Slider */
        .promo-slider {
            position: relative;
            width: 100%;
            height: 280px;
            border-radius: 30px;
            overflow: hidden;
            margin-bottom: 40px;
            box-shadow: 0 15px 40px rgba(0,0,0,0.1);
        }
        .promo-slide {
            position: absolute;
            width: 100%;
            height: 100%;
            opacity: 0;
            transition: opacity 0.8s ease;
            display: flex;
            align-items: center;
            padding: 40px 60px;
        }
        .promo-slide.active { opacity: 1; }
        .promo-slide-1 { background: linear-gradient(135deg, #ff9a9e 0%, #fad0c4 100%); }
        .promo-slide-2 { background: linear-gradient(135deg, #a18cd1 0%, #fbc2eb 100%); }
        .promo-slide-3 { background: linear-gradient(135deg, #84fab0 0%, #8fd3f4 100%); }
        
        .promo-content { flex: 1; }
        .promo-content h2 { 
            font-size: 2.5rem; 
            color: white; 
            margin-bottom: 15px;
            text-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .promo-content p {
            font-size: 1.2rem;
            color: rgba(255,255,255,0.9);
            margin-bottom: 20px;
        }
        .promo-cta {
            display: inline-block;
            padding: 12px 30px;
            background: white;
            color: var(--primary);
            border-radius: 25px;
            font-weight: 600;
            text-decoration: none;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            transition: all 0.3s;
        }
        .promo-cta:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.15);
        }
        .promo-image {
            font-size: 8rem;
            filter: drop-shadow(0 10px 20px rgba(0,0,0,0.1));
            animation: float 4s ease-in-out infinite;
        }
        .promo-dots {
            position: absolute;
            bottom: 20px;
            left: 50%;
            transform: translateX(-50%);
            display: flex;
            gap: 12px;
        }
        .promo-dot {
            width: 12px;
            height: 12px;
            border-radius: 50%;
            background: rgba(255,255,255,0.5);
            cursor: pointer;
            transition: all 0.3s;
        }
        .promo-dot.active {
            background: white;
            transform: scale(1.2);
        }
        
        @keyframes float {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-15px); }
        }
        
        /* Product Cards */
        .product-card {
            background: white;
            border-radius: 24px;
            overflow: hidden;
            transition: all 0.3s;
            border: 1px solid rgba(0,0,0,0.05);
            cursor: pointer;
            position: relative;
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
        .product-info {
            padding: 20px;
        }
        .price {
            color: var(--primary);
            font-weight: 800;
            font-size: 1.2rem;
        }
        .sale-badge {
            position: absolute;
            top: 15px;
            left: 15px;
            background: linear-gradient(135deg, #ff6b6b, #f5222d);
            color: white;
            padding: 6px 14px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
        }
        
        /* Trust Badges */
        .trust-section {
            background: white;
            border-radius: 25px;
            padding: 40px;
            margin-bottom: 40px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.05);
        }
        .trust-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 30px;
            text-align: center;
        }
        .trust-item {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 10px;
        }
        .trust-icon {
            font-size: 2.5rem;
        }
        .trust-text {
            font-weight: 600;
            color: var(--text-main);
        }
        .trust-sub {
            font-size: 0.85rem;
            color: var(--text-muted);
        }
    </style>
</head>
<body>
    <?php include 'includes/header.php'; ?>

    <div class="container" style="margin-top: 40px;">
        
        <!-- Promotion Banner Slider -->
        <div class="banner-container" style="margin-bottom: 40px; border-radius: 30px; overflow: hidden; box-shadow: 0 15px 40px rgba(0,0,0,0.1);">
            <img src="assets/images/banner_main.png" alt="Banner" style="width: 100%; height: auto; display: block;">
        </div>
        
        <!-- Trust Badges -->
        <div class="trust-section">
            <div class="trust-grid">
                <div class="trust-item">
                    <div class="trust-icon">✅</div>
                    <div class="trust-text">สินค้าคุณภาพ</div>
                    <div class="trust-sub">ของแท้ 100%</div>
                </div>
                <div class="trust-item">
                    <div class="trust-icon">🚚</div>
                    <div class="trust-text">ส่งไว</div>
                    <div class="trust-sub">1-3 วันทำการ</div>
                </div>
                <div class="trust-item">
                    <div class="trust-icon">💳</div>
                    <div class="trust-text">ชำระปลอดภัย</div>
                    <div class="trust-sub">PromptPay, บัตรเครดิต</div>
                </div>
                <div class="trust-item">
                    <div class="trust-icon">🔄</div>
                    <div class="trust-text">คืนสินค้าได้</div>
                    <div class="trust-sub">ภายใน 7 วัน</div>
                </div>
            </div>
        </div>
        
        <!-- Hero Section -->
        <div class="glass-card" style="margin-bottom: 50px;">
            <div class="hero-banner">
                <div style="flex: 1; text-align: left; padding-right: 20px;">
                    <h1 style="font-size: 3rem; color: var(--text-main); margin-bottom: 20px;">ช้อปปิ้งง่าย<br>สินค้าครบครัน</h1>
                    <p style="color: var(--text-muted); margin-bottom: 30px; font-size: 1.1rem;">ครบทุกสิ่งที่ต้องการ ของกิน ของใช้ เครื่องสำอาง แฟชั่น และอีกมากมาย</p>
                    <div style="display: flex; gap: 15px; flex-wrap: wrap;">
                        <a href="shop.php" class="btn btn-primary" style="padding: 12px 35px; font-size: 1.1rem;">เลือกซื้อเลย 🛒</a>
                        <a href="promotion.php" class="btn btn-outline" style="padding: 12px 35px; font-size: 1.1rem;">โปรโมชั่น 🔥</a>
                    </div>
                </div>
                <div style="flex: 1; display: flex; justify-content: center;">
                    <div style="font-size: 10rem; filter: drop-shadow(0 10px 20px rgba(0,0,0,0.1)); animation: float 6s ease-in-out infinite;">🛍️</div>
                </div>
            </div>
        </div>
    </div>

    <div class="container section">
        <h2 class="section-title global-header-style">หมวดหมู่ยอดนิยม</h2>
        <div class="grid grid-4">
            <?php
            $cats = [
                ['name' => 'อาหาร & ขนม', 'icon' => '🍜', 'cat' => 'food'],
                ['name' => 'เครื่องสำอาง', 'icon' => '💄', 'cat' => 'cosmetics'],
                ['name' => 'แฟชั่น', 'icon' => '👗', 'cat' => 'fashion'],
                ['name' => 'อิเล็กทรอนิกส์', 'icon' => '📱', 'cat' => 'electronics'],
                ['name' => 'ของใช้ในบ้าน', 'icon' => '🏠', 'cat' => 'home'],
                ['name' => 'สุขภาพ', 'icon' => '💊', 'cat' => 'health'],
                ['name' => 'กีฬา', 'icon' => '⚽', 'cat' => 'sports'],
                ['name' => 'ของเล่น', 'icon' => '🧸', 'cat' => 'toys']
            ];
            foreach($cats as $c) {
            ?>
            <a href="shop.php?category=<?php echo $c['cat']; ?>" class="category-card">
                <div class="category-icon"><?php echo $c['icon']; ?></div>
                <div style="font-weight: 600; color: var(--text-main);"><?php echo $c['name']; ?></div>
            </a>
            <?php } ?>
        </div>
    </div>

    <div class="container section">
        <h2 class="section-title global-header-style">🔥 สินค้าขายดี</h2>
        <div class="grid grid-4">
            <?php
            require_once 'includes/db.php';
            $db = new DB();
            $allProducts = $db->read('products');
            
            // Filter active products only
            $products = array_filter($allProducts, function($p) {
                $status = $p['status'] ?? 'active';
                return $status === 'active';
            });
            
            $limit = 8;
            $count = 0;

            foreach($products as $p) {
                if ($count >= $limit) break;
                $count++;
                $hasSalePrice = !empty($p['sale_price']) && $p['sale_price'] < $p['price'];
            ?>
            <div class="product-card" onclick="window.location.href='product.php?id=<?php echo $p['id']; ?>'">
                <?php if ($hasSalePrice): 
                    $discount = round((1 - $p['sale_price'] / $p['price']) * 100);
                ?>
                    <div class="discount-tag" style="position: absolute; top: 10px; right: 10px; z-index: 2;">-<?php echo $discount; ?>%</div>
                <?php endif; ?>
                
                <div class="product-img">
                    <?php if (!empty($p['image'])): ?>
                        <img src="<?php echo htmlspecialchars($p['image']); ?>" alt="<?php echo htmlspecialchars($p['name']); ?>">
                    <?php else: ?>
                        <div style="display:flex;align-items:center;justify-content:center;height:100%;">
                            <span style="font-size: 4rem;">🧸</span>
                        </div>
                    <?php endif; ?>
                </div>
                
                <div class="product-info">
                    <h3 class="product-name"><?php echo htmlspecialchars($p['name']); ?></h3>
                    <p class="product-desc"><?php echo htmlspecialchars($p['description']); ?></p>
                    
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
            <?php } ?>
        </div>
        
        <div class="text-center" style="margin-top: 40px;">
            <a href="shop.php" class="btn btn-outline" style="padding: 12px 40px;">ดูสินค้าทั้งหมด →</a>
        </div>
    </div>

    <!-- Newsletter -->
    <div class="container section">
        <div class="glass-card" style="text-align: center; padding: 60px 20px; background: linear-gradient(135deg, rgba(255,255,255,0.9), rgba(255,255,255,0.7));">
            <h2 style="margin-bottom: 10px;">📧 สมัครรับข่าวสารโปรโมชั่น</h2>
            <p style="color: var(--text-muted); margin-bottom: 30px;">อย่าพลาดคอลเลคชั่นใหม่และส่วนลดพิเศษสำหรับสมาชิก</p>
            <div style="max-width: 500px; margin: 0 auto; display: flex; gap: 10px;">
                <input type="email" class="form-control" placeholder="อีเมลของคุณ..." style="border-radius: 50px;">
                <button class="btn btn-primary" style="min-width: 120px;">สมัครเลย</button>
            </div>
        </div>
    </div>

    <?php include 'includes/footer.php'; ?>




    </script>

</body>
</html>
