<?php
require_once 'includes/init.php';

require_once 'includes/db.php';
$db = new DB();
$products = $db->read('products');
$cart = $_SESSION['cart'] ?? [];
$discount = $_SESSION['discount'] ?? 0;
$discountFixed = $_SESSION['discount_fixed'] ?? 0;
$freeShipping = $_SESSION['free_shipping'] ?? false;

$cartItems = [];
$subtotal = 0;

foreach ($cart as $pid => $qty) {
    foreach ($products as $p) {
        if ($p['id'] == $pid) {
            $p['qty'] = $qty;
            $cartItems[] = $p;
            $subtotal += $p['price'] * $qty;
            break;
        }
    }
}

// Calculate discount amount
$discountAmount = 0;
if ($discount > 0) {
    $discountAmount = $subtotal * $discount;
} elseif ($discountFixed > 0) {
    $discountAmount = $discountFixed;
}

// Calculate shipping
$shipping = $freeShipping ? 0 : 40;

// Calculate total
$total = ($subtotal - $discountAmount) + $shipping;
// Ensure total is not negative
if ($total < 0)
    $total = 0;
?>
<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ตะกร้าสินค้า - Doll Paradise</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="assets/css/modal.css">
    <link
        href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;600;800&family=Sarabun:wght@300;400;600&display=swap"
        rel="stylesheet">
    <script src="assets/js/modal.js" defer></script>
    <script src="assets/js/custom-alert.js"></script>
    <style>
        body {
            font-family: 'Sarabun', 'Outfit', sans-serif;
        }
    </style>
</head>

<body>
    <?php include 'includes/header.php'; ?>

    <div class="container section">
        <div class="global-header-style">
            <h1>🛍️ ตะกร้าสินค้าของคุณ</h1>
        </div>

        <div class="grid" style="grid-template-columns: 2fr 1fr; gap: 30px;">
            <!-- Cart Items -->
            <div class="glass-card">
                <?php if (empty($cartItems)): ?>
                    <div style="text-align: center; padding: 40px;">
                        <span style="font-size: 4rem; opacity: 0.5;">🛒</span>
                        <p style="margin-top: 20px; color: var(--text-muted);">ตะกร้าของคุณว่างเปล่า</p>
                        <a href="shop.php" class="btn btn-primary" style="margin-top: 20px;">ไปเลือกซื้อสินค้า ➜</a>
                    </div>
                <?php else: ?>
                    <div
                        style="margin-bottom: 10px; padding-bottom: 10px; border-bottom: 2px dashed #eee; display: flex; justify-content: space-between; color: var(--text-muted); font-size: 0.9rem;">
                        <span>สินค้า</span>
                        <span>รวม</span>
                    </div>

                    <?php foreach ($cartItems as $item): ?>
                        <div
                            style="display: flex; justify-content: space-between; align-items: center; padding: 20px 0; border-bottom: 1px solid rgba(0,0,0,0.05);">
                            <div style="display: flex; gap: 20px; align-items: center;">
                                <div
                                    style="width: 80px; height: 80px; background: #fafafa; border-radius: 12px; overflow: hidden;">
                                    <img src="<?php echo htmlspecialchars($item['image'] ?? 'assets/images/placeholder.png'); ?>"
                                        style="width: 100%; height: 100%; object-fit: cover;" alt="">
                                </div>
                                <div>
                                    <h4 style="margin: 0; color: var(--primary);"><?php echo htmlspecialchars($item['name']); ?>
                                    </h4>
                                    <p style="font-size: 0.9rem; color: var(--text-muted); margin-top: 5px;">
                                        ฿<?php echo number_format($item['price']); ?> x <?php echo $item['qty']; ?>
                                    </p>
                                </div>
                            </div>

                            <div style="text-align: right;">
                                <p style="font-weight: 700; font-size: 1.1rem; color: var(--text-main);">
                                    ฿<?php echo number_format($item['price'] * $item['qty']); ?></p>
                                <button onclick="removeItem(<?php echo $item['id']; ?>)"
                                    style="margin-top: 5px; color: #ff6b6b; background: rgba(255, 107, 107, 0.1); border: none; padding: 5px 12px; border-radius: 20px; cursor: pointer; font-size: 0.8rem; transition: all 0.2s;">
                                    🗑️ ลบ
                                </button>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <!-- Summary -->
            <div class="glass-card" style="height: fit-content; position: sticky; top: 100px;">
                <h3 style="margin-bottom: 20px; color: var(--primary);">สรุปคำสั่งซื้อ</h3>

                <div style="margin-bottom: 25px;">
                    <div class="summary-row">
                        <span style="color: var(--text-muted);">ยอดรวม</span>
                        <span style="font-weight: 500;">฿<?php echo number_format($subtotal); ?></span>
                    </div>
                    <?php if ($discountAmount > 0): ?>
                        <div class="summary-row" style="color: var(--accent);">
                            <span>ส่วนลด</span>
                            <span>-฿<?php echo number_format($discountAmount); ?></span>
                        </div>
                    <?php endif; ?>

                    <div class="summary-row">
                        <span style="color: var(--text-muted);">ค่าส่ง</span>
                        <?php if ($freeShipping): ?>
                            <span style="font-weight: 500; color: #28a745;">ฟรี</span>
                        <?php else: ?>
                            <span style="font-weight: 500;">฿<?php echo number_format($shipping); ?></span>
                        <?php endif; ?>
                    </div>

                    <div class="summary-total">
                        <span>ยอดสุทธิ</span>
                        <span>฿<?php echo number_format($total); ?></span>
                    </div>
                </div>

                <div style="margin-bottom: 25px;">
                    <div style="display: flex; gap: 10px;">
                        <input type="text" id="promoCode" placeholder="ใส่โค้ดส่วนลด" class="form-control"
                            style="flex: 1;">
                        <button onclick="applyCode()" class="btn btn-outline" style="padding: 0 20px;">ใช้</button>
                    </div>
                </div>

                <a href="checkout.php" class="btn btn-primary"
                    style="width: 100%; justify-content: center; padding: 15px;">
                    ดำเนินการชำระเงิน 💳
                </a>
            </div>
        </div>
    </div>

    <script>
        async function removeItem(id) {
            await fetch('api/cart.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ action: 'remove', productId: id })
            });
            location.reload();
        }

        async function applyCode() {
            const code = document.getElementById('promoCode').value;
            const response = await fetch('api/cart.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ action: 'apply_code', code: code })
            });
            const result = await response.json();
            await showAlert(result.message, result.success ? 'success' : 'error');
            if (result.success) location.reload();
        }
    </script>
</body>

</html>