<?php
require_once 'includes/init.php';

require_once 'includes/db.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$db = new DB();
$user = $db->find('users', 'id', $_SESSION['user_id']);
$orders = $db->read('orders');
$myOrders = array_filter($orders, function($o) {
    return $o['user_id'] == $_SESSION['user_id'];
});
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>โปรไฟล์ของฉัน - Doll Paradise</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="assets/css/modal.css">
    <link href="https://fonts.googleapis.com/css2?family=Prompt:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <script src="assets/js/modal.js" defer></script>
    <style>
        body { font-family: 'Prompt', sans-serif; }
        .profile-header {
            text-align: center;
            margin-bottom: 40px;
        }
        .profile-grid {
            display: grid;
            grid-template-columns: 1fr 2fr;
            gap: 40px;
        }
        @media (max-width: 900px) {
            .profile-grid { grid-template-columns: 1fr; }
        }
        
        /* Avatar Card */
        .avatar-card {
            background: rgba(255, 255, 255, 0.9);
            border-radius: 24px;
            padding: 30px;
            text-align: center;
            box-shadow: 0 20px 40px rgba(0,0,0,0.05);
            border: 1px solid rgba(255,255,255,0.8);
            position: sticky;
            top: 100px;
        }
        .avatar-img {
            width: 100%;
            border-radius: 20px;
            margin-bottom: 20px;
            box-shadow: 0 10px 25px rgba(0,0,0,0.1);
            transition: transform 0.3s;
        }
        .avatar-img:hover { transform: scale(1.02); }
        
        /* Info Section */
        .info-card {
            background: rgba(255, 255, 255, 0.8);
            border-radius: 20px;
            padding: 25px;
            margin-top: 20px;
            text-align: left;
        }
        .info-item {
            margin-bottom: 15px;
            display: flex;
            align-items: center;
            gap: 10px;
            color: var(--text-main);
        }
        .info-icon {
            width: 35px;
            height: 35px;
            background: var(--primary-light);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 1rem;
        }

        /* Orders */
        .order-card {
            background: rgba(255, 255, 255, 0.95);
            padding: 25px;
            border-radius: 20px;
            margin-bottom: 20px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.03);
            transition: all 0.3s;
            border: 1px solid rgba(255,255,255,0.5);
        }
        .order-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 30px rgba(155, 89, 182, 0.1);
        }
        .order-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
            padding-bottom: 15px;
            border-bottom: 1px dashed #eee;
        }
        .status-badge {
            background: var(--primary-light);
            color: white;
            padding: 5px 15px;
            border-radius: 50px;
            font-size: 0.85rem;
            font-weight: 600;
        }
        .order-total {
            font-size: 1.2rem;
            font-weight: 700;
            color: var(--primary);
        }
    </style>
</head>
<body>
    <?php include 'includes/header.php'; ?>

    <div class="container">
        <div class="global-header-style">
            <h1>โปรไฟล์ของฉัน</h1>
            <p>จัดการข้อมูลส่วนตัวและประวัติการสั่งซื้อ</p>
        </div>

        <div class="profile-grid">
            <!-- Left Column: Avatar & Personal Info -->
            <div>
                <div class="avatar-card">
                    <h3 style="color: var(--text-main); margin-bottom: 20px; font-weight: 600;">ตัวละครของคุณ</h3>
                    <?php if (isset($user['avatar_config']) && isset($user['avatar_config']['src'])): ?>
                        <img src="<?php echo htmlspecialchars($user['avatar_config']['src']); ?>" class="avatar-img" alt="My Avatar">
                        <a href="customizer.php" class="btn btn-secondary" style="display: block; width: 100%; border-radius: 12px;">
                            ✏️ เปลี่ยนตัวละคร
                        </a>
                    <?php else: ?>
                        <div style="padding: 40px 0; color: var(--text-muted);">
                            <div style="font-size: 4rem; margin-bottom: 15px; opacity: 0.5;">👤</div>
                            <p>ยังไม่ได้เลือกตัวละคร</p>
                        </div>
                        <a href="customizer.php" class="btn btn-primary" style="display: block; width: 100%; border-radius: 12px;">
                            ✨ เลือกตัวละคร
                        </a>
                    <?php endif; ?>

                    <div style="margin-top: 15px;">
                        <?php if (isset($_SESSION['is_seller']) && $_SESSION['is_seller']): ?>
                            <style>
                                .special-seller-btn {
                                    background: #ffffff;
                                    color: #ff6b81;
                                    border: 2px solid white;
                                    font-weight: 700;
                                    font-size: 1.2rem;
                                    box-shadow: 0 5px 15px rgba(0, 0, 0, 0.08);
                                    transition: all 0.3s;
                                }
                                .special-seller-btn:hover {
                                    transform: translateY(-3px);
                                    box-shadow: 0 10px 25px rgba(255, 107, 129, 0.25);
                                    color: #ff4757;
                                    background: #fff0f3;
                                }
                            </style>
                            <a href="add_product.php" class="btn special-seller-btn" style="display: block; width: 100%; border-radius: 20px; margin-bottom: 15px; padding: 15px;">
                                ลงขายสินค้าเลย! 🚀
                            </a>
                            <a href="my_products.php" class="btn btn-outline" style="display: block; width: 100%; border-radius: 12px; border: 2px solid var(--primary); color: var(--primary); margin-bottom: 10px;">
                                 📦 จัดการสินค้าของฉัน
                            </a>
                            <a href="seller_orders.php" class="btn btn-outline" style="display: block; width: 100%; border-radius: 12px; border: 2px solid #00b894; color: #00b894; margin-bottom: 10px;">
                                 📋 จัดการคำสั่งซื้อ (Seller)
                            </a>
                            <a href="store.php?id=<?php echo $user['id']; ?>" class="btn btn-outline" style="display: block; width: 100%; border-radius: 12px; border: 2px solid #a29bfe; color: #a29bfe;">
                                 🏪 หน้าร้านของฉัน
                            </a>
                        <?php else: ?>
                            <a href="seller_register.php" class="btn btn-outline" style="display: block; width: 100%; border-radius: 12px; border-style: dashed;">
                                🏪 ลงทะเบียนร้านค้า
                            </a>
                        <?php endif; ?>
                    </div>

                    <div style="margin-top: 10px;">
                        <a href="logout.php" class="btn" style="display: block; width: 100%; border-radius: 12px; background: #ffefef; color: #ff4d4d; border: 1px solid #ffcccc;">
                            🚪 ออกจากระบบ
                        </a>
                    </div>

                    <div class="info-card">
                        <h4 style="margin-bottom: 15px; color: var(--primary);">ข้อมูลส่วนตัว</h4>
                        <div class="info-item">
                            <div class="info-icon">👤</div>
                            <div>
                                <small style="color: var(--text-muted); display: block;">ชื่อผู้ใช้</small>
                                <?php echo htmlspecialchars($user['username']); ?>
                            </div>
                        </div>
                        <div class="info-item">
                            <div class="info-icon">🏠</div>
                            <div>
                                <small style="color: var(--text-muted); display: block;">ที่อยู่</small>
                                <?php echo htmlspecialchars($user['address']); ?>
                            </div>
                        </div>
                        <div class="info-item">
                            <div class="info-icon">📞</div>
                            <div>
                                <small style="color: var(--text-muted); display: block;">เบอร์โทรศัพท์</small>
                                <?php echo htmlspecialchars($user['phone']); ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Right Column: Order History -->
            <div>
                <h2 style="margin-bottom: 25px; display: flex; align-items: center; gap: 10px;">
                    <span style="background: white; width: 40px; height: 40px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 1.2rem; box-shadow: 0 5px 15px rgba(0,0,0,0.05);">📦</span>
                    ประวัติการสั่งซื้อ
                </h2>
                
                <?php if (empty($myOrders)): ?>
                    <div class="glass-panel" style="text-align: center; padding: 60px 20px;">
                        <span style="font-size: 4rem; display: block; margin-bottom: 20px; opacity: 0.5;">🛍️</span>
                        <h3 style="margin-bottom: 10px;">ยังไม่มีรายการสั่งซื้อ</h3>
                        <p style="color: var(--text-muted); margin-bottom: 20px;">เลือกซื้อสินค้าตุ๊กตาน่ารักๆ ได้ที่ร้านค้าเลย!</p>
                        <a href="shop.php" class="btn btn-primary">ไปช้อปปิ้งกันเลย ➜</a>
                    </div>
                <?php else: ?>
                    <style>
                        .order-scroll-container {
                            max-height: 70vh;
                            overflow-y: auto;
                            padding-right: 10px;
                        }
                        .order-scroll-container::-webkit-scrollbar {
                            width: 6px;
                        }
                        .order-scroll-container::-webkit-scrollbar-track {
                            background: rgba(0,0,0,0.02);
                            border-radius: 10px;
                        }
                        .order-scroll-container::-webkit-scrollbar-thumb {
                            background: rgba(255, 107, 129, 0.3);
                            border-radius: 10px;
                        }
                        .order-scroll-container::-webkit-scrollbar-thumb:hover {
                            background: rgba(255, 107, 129, 0.5);
                        }
                    </style>
                    <div class="order-scroll-container">
                    <?php foreach (array_reverse($myOrders) as $order): ?>
                        <div class="order-card">
                            <div class="order-header">
                                <div>
                                    <div style="font-weight: 700; font-size: 1.1rem;">Order #<?php echo $order['id']; ?></div>
                                    <div style="font-size: 0.85rem; color: var(--text-muted);"><?php echo $order['created_at']; ?></div>
                                </div>
                                <?php 
                                    $badgeStyle = '';
                                    if ($order['status'] == 'ยกเลิก') {
                                        $badgeStyle = 'background: #ffecec; color: #ff4d4d; border: 1px solid #ffcccc;';
                                    } elseif ($order['status'] == 'ชำระเงินแล้ว') {
                                        $badgeStyle = 'background: #e6fffa; color: #00b894; border: 1px solid #b3f5e1;'; // Greenish
                                    }
                                ?>
                                <span class="status-badge" style="<?php echo $badgeStyle; ?>"><?php echo $order['status']; ?></span>
                            </div>
                            
                            <div style="margin-bottom: 20px;">
                                <?php foreach ($order['items'] as $item): ?>
                                    <div style="display: flex; justify-content: space-between; margin-bottom: 8px; font-size: 0.95rem;">
                                        <span style="color: var(--text-main);">• <?php echo $item['name']; ?></span>
                                        <span style="color: var(--text-muted);">x<?php echo $item['qty']; ?></span>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                            
                            <div style="display: flex; justify-content: space-between; align-items: center; border-top: 1px solid rgba(0,0,0,0.05); padding-top: 15px;">
                                <div class="order-total">฿<?php echo number_format($order['total']); ?></div>
                                <div style="display: flex; gap: 10px;">
                                    <?php if (in_array($order['status'], ['รอชำระเงิน', 'ชำระเงินแล้ว'])): ?>
                                        <a href="cancel_order.php?id=<?php echo $order['id']; ?>" class="btn" style="padding: 8px 20px; font-size: 0.9rem; border-radius: 12px; background: #fff5f5; color: #dc3545; border: 1px solid #ffc9c9;">
                                            ยกเลิก ❌
                                        </a>
                                    <?php endif; ?>
                                    <a href="tracking.php?id=<?php echo $order['id']; ?>" class="btn btn-primary" style="padding: 8px 20px; font-size: 0.9rem; border-radius: 12px;">
                                        ติดตามพัสดุ 🚚
                                    </a>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    </div>
    <?php include 'includes/footer.php'; ?>
</body>
</html>
