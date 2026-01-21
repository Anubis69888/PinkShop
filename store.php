<?php
require_once 'includes/init.php';
require_once 'includes/db.php';

$db = new DB();
$sellerId = $_GET['id'] ?? 0;

if (!$sellerId) {
    header('Location: shop.php');
    exit;
}

// Get Seller Info
$users = $db->read('users');
$seller = null;
foreach ($users as $u) {
    if ($u['id'] == $sellerId) {
        $seller = $u;
        break;
    }
}

if (!$seller) {
    echo "ไม่พบร้านค้านี้";
    exit;
}

// Get Seller Products
$allProducts = $db->read('products');
$products = array_filter($allProducts, function($p) use ($sellerId) {
    return isset($p['seller_id']) && $p['seller_id'] == $sellerId && ($p['status'] ?? 'active') == 'active';
});

// Shop Details (Fallback to user info if not set)
$shopName = $seller['shop_name'] ?? $seller['username'] . "'s Shop";
$shopDesc = $seller['shop_description'] ?? 'ร้านค้านี้ยังไม่มีคำอธิบาย';
$shopAvatar = $seller['avatar_config']['src'] ?? 'assets/images/default-avatar.png'; // Use user avatar for now

$isOwner = isset($_SESSION['user_id']) && $_SESSION['user_id'] == $sellerId;
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($shopName); ?> - AKP Shop</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Prompt:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body { font-family: 'Prompt', sans-serif; background: linear-gradient(135deg, #a18cd1 0%, #fbc2eb 100%); min-height: 100vh; }
        
        .shop-cover {
            height: 320px;
            background: <?php echo !empty($seller['shop_cover']) ? "url('".htmlspecialchars($seller['shop_cover'])."') center/cover no-repeat" : "url('assets/images/default_shop_cover.png') center/cover no-repeat"; ?>;
            position: relative;
        }
        
        /* Glassmorphism Profile Card */
        .shop-profile-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            border-radius: 24px;
            padding: 25px 40px;
            margin: -60px auto 30px;
            width: 90%;
            max-width: 900px;
            box-shadow: 0 15px 35px rgba(0,0,0,0.1);
            position: relative;
            display: flex;
            align-items: center;
            justify-content: space-between;
            border: 2px solid rgba(255,255,255,0.8);
        }
        
        .shop-info-left {
            display: flex;
            align-items: center;
            gap: 20px;
        }

        .shop-avatar {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            background: white;
            padding: 3px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            object-fit: contain;
            border: 2px solid #eee;
        }
        
        .shop-name {
            font-size: 1.5rem;
            color: var(--primary, #ff6b81);
            font-weight: 700;
            margin-bottom: 5px;
        }
        
        .shop-desc {
            font-size: 0.9rem;
            color: var(--text-muted, #777);
            display: flex;
            align-items: center;
            gap: 15px;
        }
        
        /* Stats & Social */
        .shop-stats-badge {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            background: #fff0f3;
            color: #ff6b81;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
        }

        .social-links {
            display: flex;
            gap: 10px;
            margin-top: 5px;
        }
        
        .social-btn {
            width: 32px;
            height: 32px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            text-decoration: none;
            transition: transform 0.2s;
            font-size: 0.9rem;
        }
        .social-btn:hover { transform: translateY(-3px); }
        .bg-line { background: #00B900; }
        .bg-fb { background: #1877F2; }
        .bg-phone { background: #555555; }

        /* Navigation Tabs */
        .shop-nav {
            background: white;
            border-radius: 15px;
            padding: 10px;
            max-width: 900px;
            margin: 0 auto 30px;
            display: flex;
            justify-content: center;
            gap: 10px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.05);
        }
        
        .nav-item {
            padding: 10px 30px;
            border-radius: 10px;
            font-weight: 600;
            color: #888;
            cursor: pointer;
            transition: all 0.3s;
            text-decoration: none;
        }
        
        .nav-item.active {
            background: #ffeefa;
            color: #ff6b81;
        }
        
        .nav-item:hover {
            color: #ff6b81;
            background-color: #fff0f5;
        }

        /* Edit Button */
        .edit-shop-btn {
            border: 1px solid #ff6b81;
            color: #ff6b81;
            padding: 8px 20px;
            border-radius: 20px;
            text-decoration: none;
            font-size: 0.9rem;
            transition: all 0.3s;
        }
        .edit-shop-btn:hover {
            background: #ff6b81;
            color: white;
        }
        
        /* Product Card Override if needed */
        .product-card {
            border-radius: 15px;
            border: none;
            box-shadow: 0 5px 15px rgba(0,0,0,0.05);
        }

    </style>
</head>
<body>
    <?php include 'includes/header.php'; ?>

    <div class="container" style="margin-top: 20px;">
        <div class="shop-cover-wrapper" style="border-radius: 30px; overflow: hidden; box-shadow: 0 10px 30px rgba(0,0,0,0.1);">
            <img src="<?php echo !empty($seller['shop_cover']) ? htmlspecialchars($seller['shop_cover']) : 'assets/images/default_shop_cover.png'; ?>" 
                 style="width: 100%; height: auto; display: block;" 
                 alt="Shop Cover">
        </div>
    </div>

    <!-- Profile Card -->
    <div class="shop-profile-card" style="margin-top: -80px;">
        <div class="shop-info-left">
            <img src="<?php echo htmlspecialchars($shopAvatar); ?>" class="shop-avatar">
            <div>
                <h1 class="shop-name"><?php echo htmlspecialchars($shopName); ?></h1>
                <div class="shop-desc">
                    <span class="shop-stats-badge">📦 <?php echo count($products); ?> รายการ</span>
                    <span style="font-size: 0.8rem;">📅 เปิดร้าน <?php echo date('d/m/Y', strtotime($seller['created_at'] ?? 'now')); ?></span>
                </div>
                
                <!-- Social Links -->
                <?php if (!empty($seller['shop_contact'])): ?>
                <div class="social-links">
                    <?php if(!empty($seller['shop_contact']['line'])): ?>
                        <a href="https://line.me/ti/p/~<?php echo $seller['shop_contact']['line']; ?>" target="_blank" class="social-btn bg-line" title="Line ID"><i class="fab fa-line"></i></a>
                    <?php endif; ?>
                    <?php if(!empty($seller['shop_contact']['facebook'])): ?>
                        <a href="<?php echo $seller['shop_contact']['facebook']; ?>" target="_blank" class="social-btn bg-fb" title="Facebook"><i class="fab fa-facebook-f"></i></a>
                    <?php endif; ?>
                    <?php if(!empty($seller['shop_contact']['phone'])): ?>
                        <a href="tel:<?php echo $seller['shop_contact']['phone']; ?>" class="social-btn bg-phone" title="Phone"><i class="fas fa-phone"></i></a>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <?php if ($isOwner): ?>
            <a href="edit_shop.php" class="edit-shop-btn"><i class="fas fa-cog"></i> แก้ไขร้านค้า</a>
        <?php else: ?>
            <button class="edit-shop-btn" style="background:#ff6b81; color:white;">+ ติดตาม</button>
        <?php endif; ?>
    </div>

    <div class="container">
        <!-- Navigation Tabs -->
        <div class="shop-nav">
            <div class="nav-item active" onclick="switchTab('products', this)">รายการสินค้า</div>
            <div class="nav-item" onclick="switchTab('reviews', this)">รีวิวร้านค้า</div>
            <div class="nav-item" onclick="switchTab('about', this)">เกี่ยวกับร้าน</div>
        </div>

        <!-- Products Tab (Default) -->
        <div id="tab-products" class="tab-content active" style="background: rgba(255,255,255,0.6); padding: 30px; border-radius: 20px;">
            <?php if (empty($products)): ?>
                <div style="text-align: center; padding: 50px; color: var(--text-muted);">
                    <span style="font-size: 3rem; display: block; margin-bottom: 15px;">🛍️</span>
                    <h3>ยังไม่มีสินค้าในร้านนี้</h3>
                    <p>กดติดตามร้านค้าเพื่อรออัปเดตสินค้าใหม่ๆ ได้เลย!</p>
                </div>
            <?php else: ?>
                <div class="grid grid-4" style="grid-template-columns: repeat(auto-fill, minmax(220px, 1fr)); gap: 20px;">
                    <?php foreach ($products as $p): 
                        $hasSalePrice = !empty($p['sale_price']) && $p['sale_price'] < $p['price'];
                        $discount = $hasSalePrice ? round((1 - $p['sale_price'] / $p['price']) * 100) : 0;
                        $stock = $p['stock'] ?? 0;
                        $isOutOfStock = $stock <= 0;
                        $tags = array_filter(explode(',', $p['tags'] ?? ''));
                    ?>
                    <div class="product-card" onclick="window.location.href='product.php?id=<?php echo $p['id']; ?>'" style="cursor: pointer;">
                        <div class="product-img" style="height: 200px; padding: 0;">
                            <?php if ($hasSalePrice): ?>
                                <div class="discount-tag" style="position: absolute; top: 10px; right: 10px; z-index: 2; background: #ff4757; color: white;">-<?php echo $discount; ?>%</div>
                            <?php endif; ?>
                            
                            <?php 
                                $imageSrc = $p['image'] ?? '';
                                if (!empty($imageSrc) && !str_starts_with($imageSrc, 'http') && !str_starts_with($imageSrc, '/')) {
                                    $imageSrc = '/AKP/' . $imageSrc;
                                }
                            ?>
                            <?php if (!empty($imageSrc)): ?>
                                <img src="<?php echo htmlspecialchars($imageSrc); ?>" 
                                     alt="<?php echo htmlspecialchars($p['name']); ?>" 
                                     style="width: 100%; height: 100%; object-fit: cover;"
                                     onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                                <div style="display:none; align-items:center; justify-content:center; height:100%; background:#f5f5f5;">
                                    <span style="font-size:4rem;">📦</span>
                                </div>
                            <?php else: ?>
                                <div style="display:flex; align-items:center; justify-content:center; height:100%; background:#f5f5f5;">
                                    <span style="font-size:4rem;">📦</span>
                                </div>
                            <?php endif; ?>
                        </div>
                        
                        <div class="product-info" style="padding: 15px;">
                            <h3 class="product-name" style="font-size: 1rem; margin-bottom: 5px;"><?php echo htmlspecialchars($p['name']); ?></h3>
                            
                            <div class="price-row" style="margin-bottom: 10px;">
                                <div class="price-container">
                                    <?php if ($hasSalePrice): ?>
                                        <span class="sale-price" style="color: #ff6b81; font-weight: bold; font-size: 1.1rem;">฿<?php echo number_format($p['sale_price']); ?></span>
                                        <span class="original-price" style="text-decoration: line-through; color: #aaa; font-size: 0.8rem; margin-left: 5px;">฿<?php echo number_format($p['price']); ?></span>
                                    <?php else: ?>
                                        <span class="regular-price" style="color: #ff6b81; font-weight: bold; font-size: 1.1rem;">฿<?php echo number_format($p['price']); ?></span>
                                    <?php endif; ?>
                                </div>
                            </div>
                            
                            <div class="btn-row" style="display: flex; gap: 8px;">
                                <a href="product.php?id=<?php echo $p['id']; ?>" class="btn-detail" onclick="event.stopPropagation()" style="flex: 1; text-align: center; padding: 6px; border: 1px solid #ddd; border-radius: 8px; color: #666; font-size: 0.8rem;">รายละเอียด</a>
                                <button onclick="event.stopPropagation(); addToCart(<?php echo $p['id']; ?>)" class="btn-cart" <?php echo $isOutOfStock ? 'disabled' : ''; ?> style="flex: 1; padding: 6px; border: none; background: linear-gradient(135deg, #ff6b81, #ff8fa3); color: white; border-radius: 8px; font-size: 0.8rem; cursor: pointer;">
                                    <?php echo $isOutOfStock ? 'หมด' : 'ใส่ตะกร้า'; ?>
                                </button>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <!-- Reviews Tab -->
        <div id="tab-reviews" class="tab-content" style="background: rgba(255,255,255,0.6); padding: 30px; border-radius: 20px; display: none;">
            <div style="text-align: center; margin-bottom: 30px;">
                <div style="font-size: 3rem; margin-bottom: 10px;">⭐⭐⭐⭐⭐</div>
                <h3 style="color: #ff6b81; margin-bottom: 5px;">5.0 คะแนนเฉลี่ย</h3>
                <p style="color: #999; font-size: 0.9rem;">จาก 0 รีวิว</p>
            </div>
            
            <div style="text-align: center; padding: 40px; color: var(--text-muted);">
                <span style="font-size: 4rem; display: block; margin-bottom: 15px;">💬</span>
                <h3 style="margin-bottom: 10px;">ยังไม่มีรีวิว</h3>
                <p style="font-size: 0.9rem; color: #999;">เป็นคนแรกที่รีวิวร้านนี้!</p>
            </div>
        </div>

        <!-- About Tab -->
        <div id="tab-about" class="tab-content" style="background: rgba(255,255,255,0.6); padding: 30px; border-radius: 20px; display: none;">
            <div style="max-width: 600px; margin: 0 auto;">
                <!-- Shop Description -->
                <div style="margin-bottom: 30px;">
                    <h3 style="color: #ff6b81; margin-bottom: 15px; font-size: 1.2rem;">
                        <i class="fas fa-store" style="margin-right: 8px;"></i>เกี่ยวกับร้านค้า
                    </h3>
                    <p style="color: #555; line-height: 1.8; background: white; padding: 20px; border-radius: 15px; box-shadow: 0 3px 10px rgba(0,0,0,0.05); word-wrap: break-word; overflow-wrap: break-word; word-break: break-all;">
                        <?php echo nl2br(htmlspecialchars($shopDesc)); ?>
                    </p>
                </div>

                <!-- Contact Information -->
                <div style="margin-bottom: 30px;">
                    <h3 style="color: #ff6b81; margin-bottom: 15px; font-size: 1.2rem;">
                        <i class="fas fa-address-book" style="margin-right: 8px;"></i>ช่องทางติดต่อ
                    </h3>
                    <div style="background: white; padding: 20px; border-radius: 15px; box-shadow: 0 3px 10px rgba(0,0,0,0.05);">
                        <?php if (!empty($seller['shop_contact']['line'])): ?>
                        <div style="display: flex; align-items: center; gap: 15px; padding: 12px 0; border-bottom: 1px solid #f0f0f0;">
                            <div style="width: 40px; height: 40px; background: #00B900; border-radius: 50%; display: flex; align-items: center; justify-content: center;">
                                <i class="fab fa-line" style="color: white; font-size: 1.2rem;"></i>
                            </div>
                            <div>
                                <div style="font-size: 0.8rem; color: #999;">Line ID</div>
                                <div style="font-weight: 600; color: #333;"><?php echo htmlspecialchars($seller['shop_contact']['line']); ?></div>
                            </div>
                        </div>
                        <?php endif; ?>
                        
                        <?php if (!empty($seller['shop_contact']['facebook'])): ?>
                        <div style="display: flex; align-items: center; gap: 15px; padding: 12px 0; border-bottom: 1px solid #f0f0f0;">
                            <div style="width: 40px; height: 40px; background: #1877F2; border-radius: 50%; display: flex; align-items: center; justify-content: center;">
                                <i class="fab fa-facebook-f" style="color: white; font-size: 1.2rem;"></i>
                            </div>
                            <div>
                                <div style="font-size: 0.8rem; color: #999;">Facebook</div>
                                <a href="<?php echo htmlspecialchars($seller['shop_contact']['facebook']); ?>" target="_blank" style="font-weight: 600; color: #1877F2; text-decoration: none;">ไปที่หน้า Facebook</a>
                            </div>
                        </div>
                        <?php endif; ?>
                        
                        <?php if (!empty($seller['shop_contact']['phone'])): ?>
                        <div style="display: flex; align-items: center; gap: 15px; padding: 12px 0;">
                            <div style="width: 40px; height: 40px; background: #555; border-radius: 50%; display: flex; align-items: center; justify-content: center;">
                                <i class="fas fa-phone" style="color: white; font-size: 1rem;"></i>
                            </div>
                            <div>
                                <div style="font-size: 0.8rem; color: #999;">โทรศัพท์</div>
                                <a href="tel:<?php echo $seller['shop_contact']['phone']; ?>" style="font-weight: 600; color: #333; text-decoration: none;"><?php echo htmlspecialchars($seller['shop_contact']['phone']); ?></a>
                            </div>
                        </div>
                        <?php endif; ?>
                        
                        <?php if (empty($seller['shop_contact']['line']) && empty($seller['shop_contact']['facebook']) && empty($seller['shop_contact']['phone'])): ?>
                        <div style="text-align: center; padding: 20px; color: #999;">
                            <i class="fas fa-info-circle" style="font-size: 1.5rem; margin-bottom: 10px; display: block;"></i>
                            ยังไม่มีข้อมูลช่องทางติดต่อ
                        </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Shop Stats -->
                <div>
                    <h3 style="color: #ff6b81; margin-bottom: 15px; font-size: 1.2rem;">
                        <i class="fas fa-chart-bar" style="margin-right: 8px;"></i>ข้อมูลร้านค้า
                    </h3>
                    <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 15px;">
                        <div style="background: white; padding: 20px; border-radius: 15px; text-align: center; box-shadow: 0 3px 10px rgba(0,0,0,0.05);">
                            <div style="font-size: 1.5rem; color: #ff6b81; font-weight: 700;"><?php echo count($products); ?></div>
                            <div style="color: #999; font-size: 0.85rem;">สินค้าทั้งหมด</div>
                        </div>
                        <div style="background: white; padding: 20px; border-radius: 15px; text-align: center; box-shadow: 0 3px 10px rgba(0,0,0,0.05);">
                            <div style="font-size: 1.5rem; color: #ff6b81; font-weight: 700;"><?php echo date('d/m/Y', strtotime($seller['created_at'] ?? 'now')); ?></div>
                            <div style="color: #999; font-size: 0.85rem;">วันที่เปิดร้าน</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <?php include 'includes/footer.php'; ?>
    <script src="assets/js/cart.js"></script>
    
    <script>
    function switchTab(tabName, element) {
        // Hide all tab contents
        document.querySelectorAll('.tab-content').forEach(tab => {
            tab.style.display = 'none';
            tab.classList.remove('active');
        });
        
        // Remove active class from all nav items
        document.querySelectorAll('.nav-item').forEach(nav => {
            nav.classList.remove('active');
        });
        
        // Show selected tab
        const selectedTab = document.getElementById('tab-' + tabName);
        if (selectedTab) {
            selectedTab.style.display = 'block';
            selectedTab.classList.add('active');
        }
        
        // Add active class to clicked nav item
        element.classList.add('active');
    }
    </script>
</body>
</html>
