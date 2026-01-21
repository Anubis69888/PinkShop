<?php
require_once 'includes/init.php';

require_once 'includes/db.php';

if (!isset($_SESSION['user_id']) || empty($_SESSION['cart'])) {
    header('Location: shop.php');
    exit;
}

$db = new DB();
$user = $db->find('users', 'id', $_SESSION['user_id']);
$products = $db->read('products');
$cart = $_SESSION['cart'];
$discount = $_SESSION['discount'] ?? 0;
$discountFixed = $_SESSION['discount_fixed'] ?? 0;
$freeShipping = $_SESSION['free_shipping'] ?? false;

$total = 0;
foreach ($cart as $pid => $qty) {
    foreach ($products as $p) {
        if ($p['id'] == $pid) {
            $total += $p['price'] * $qty;
            break;
        }
    }
}

// Calculate discount amount
$discountAmount = 0;
if ($discount > 0) {
    $discountAmount = $total * $discount;
} elseif ($discountFixed > 0) {
    $discountAmount = $discountFixed;
}

// Calculate shipping
$shipping = $freeShipping ? 0 : 40;
$finalTotal = ($total - $discountAmount) + $shipping;
// Ensure total is not negative
if ($finalTotal < 0) $finalTotal = 0;
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ชำระเงิน | AKP Shop</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="assets/css/modal.css">
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;600;800&family=Sarabun:wght@300;400;600&display=swap" rel="stylesheet">
    <script src="assets/js/modal.js" defer></script>
    <style>
        body { font-family: 'Sarabun', 'Outfit', sans-serif; }
        .checkout-container {
            max-width: 1100px;
            margin: 0 auto;
            padding: 40px 20px;
        }
        .header-title {
            color: var(--primary);
            font-size: 2rem;
            margin-bottom: 30px;
            display: flex;
            align-items: center;
            gap: 15px;
        }
        
        .payment-method-card {
            border: 2px solid transparent;
            border-radius: 12px;
            padding: 15px;
            cursor: pointer;
            transition: all 0.2s;
            background: rgba(255,255,255,0.8);
            display: flex;
            align-items: center;
            gap: 15px;
        }
        .payment-method-card:hover { transform: translateY(-3px); }
        .payment-method-card.active {
            border-color: var(--primary);
            background: white;
            box-shadow: 0 5px 15px rgba(214, 123, 179, 0.2);
        }
        .payment-icon { font-size: 1.5rem; }

        .qr-section {
            background: white;
            border-radius: 16px;
            padding: 30px;
            text-align: center;
            box-shadow: 0 10px 30px rgba(0,0,0,0.05);
            margin-top: 20px;
        }
        .price-large {
            font-size: 2.5rem;
            color: var(--primary);
            font-weight: 800;
            margin: 15px 0;
        }
        
        .file-upload-box {
            border: 2px dashed #ddd;
            border-radius: 12px;
            padding: 30px;
            text-align: center;
            cursor: pointer;
            transition: all 0.2s;
            background: #f9f9f9;
            margin-top: 20px;
        }
        .file-upload-box:hover {
            border-color: var(--primary);
            background: #fff5f9;
        }
    </style>
</head>
<body>
    <?php include 'includes/header.php'; ?>

    <div class="checkout-container">
        <h1 class="header-title">💳 ยืนยันการสั่งซื้อ</h1>

        <form id="checkoutForm" onsubmit="handleCheckout(event)">
            <div class="grid grid-2">
                <!-- Left: Info & Payment -->
                <div>
                    <!-- Address -->
                    <div class="glass-card" style="margin-bottom: 25px;">
                        <h3 style="color: var(--primary); margin-bottom: 20px;">📍 ที่อยู่จัดส่ง</h3>
                        <div class="form-group">
                            <label>ชื่อ-นามสกุล</label>
                            <input type="text" name="fullname" value="<?php echo htmlspecialchars($user['fullname']); ?>" class="form-control" required>
                        </div>
                        <div class="form-group">
                            <label>เบอร์โทรศัพท์</label>
                            <input type="tel" name="phone" value="<?php echo htmlspecialchars($user['phone']); ?>" class="form-control" required>
                        </div>
                        <div class="form-group">
                            <label>ที่อยู่</label>
                            <textarea name="address" rows="3" class="form-control" required><?php echo htmlspecialchars($user['address']); ?></textarea>
                        </div>
                    </div>

                    <!-- Payment -->
                    <div class="glass-card">
                        <h3 style="color: var(--primary); margin-bottom: 20px;">💰 วิธีการชำระเงิน</h3>
                        
                        <input type="hidden" name="payment_method" id="paymentMethod" value="promptpay">
                        
                        <div class="grid grid-2" style="gap: 15px;">
                            <div class="payment-method-card active" id=" method-promptpay" onclick="selectPayment('promptpay')">
                                <span class="payment-icon">📸</span>
                                <div>
                                    <div style="font-weight: bold;">สแกนจ่าย QR</div>
                                    <div style="font-size: 0.8rem; color: #666;">PromptPay</div>
                                </div>
                            </div>
                            <div class="payment-method-card" id="method-cod" onclick="selectPayment('cod')">
                                <span class="payment-icon">🚚</span>
                                <div>
                                    <div style="font-weight: bold;">เก็บเงินปลายทาง</div>
                                    <div style="font-size: 0.8rem; color: #666;">Cash on Delivery</div>
                                </div>
                            </div>
                        </div>

                        <!-- QR Valid Section -->
                        <div id="qrSection" class="qr-section">
                            <div style="color: var(--primary); font-weight: bold; margin-bottom: 10px;">สแกน QR Code เพื่อชำระเงิน</div>
                            <img src="assets/images/qr_promptpay.jpg" style="width: 180px; border-radius: 12px; border: 1px solid #eee;">
                            
                            <div class="price-large">฿<?php echo number_format($finalTotal); ?></div>
                            
                            <div class="file-upload-box" onclick="document.getElementById('slipInput').click()">
                                <input type="file" id="slipInput" name="slip" accept="image/*" style="display: none;" onchange="previewFile(this)">
                                <div style="font-size: 2rem;">📤</div>
                                <div style="margin-top: 10px; color: var(--text-muted);">คลิกเพื่ออัปโหลดสลิปโอนเงิน</div>
                                <div id="fileName" style="margin-top: 10px; color: var(--primary); font-weight: bold;"></div>
                            </div>
                        </div>

                        <!-- COD Section -->
                        <div id="codSection" style="display: none; padding: 30px; text-align: center; color: var(--text-muted);">
                            <div style="font-size: 3rem;">📦</div>
                            <p style="margin-top: 10px;">เตรียมเงินสดรอรับของหน้าบ้านได้เลย!</p>
                        </div>
                    </div>
                </div>

                <!-- Right: Summary -->
                <div>
                     <div class="glass-card" style="position: sticky; top: 100px;">
                        <h3 style="color: var(--primary); margin-bottom: 20px; border-bottom: 2px dashed #eee; padding-bottom: 15px;">🛒 สรุปคำสั่งซื้อ</h3>
                        
                        <div style="max-height: 300px; overflow-y: auto; padding-right: 5px;">
                        <?php foreach ($cart as $pid => $qty): 
                            $p = array_filter($products, function($i) use ($pid) { return $i['id'] == $pid; });
                            $p = reset($p);
                        ?>
                            <div style="display: flex; gap: 10px; margin-bottom: 15px;">
                                <img src="<?php echo $p['image'] ?: 'assets/images/placeholder.svg'; ?>" style="width: 50px; height: 50px; border-radius: 8px; object-fit: cover;">
                                <div style="flex: 1;">
                                    <div style="font-weight: 600; font-size: 0.9rem;"><?php echo htmlspecialchars($p['name']); ?></div>
                                    <div style="font-size: 0.8rem; color: #888;">x<?php echo $qty; ?></div>
                                </div>
                                <div style="font-weight: bold; color: var(--primary);">฿<?php echo number_format($p['price'] * $qty); ?></div>
                            </div>
                        <?php endforeach; ?>
                        </div>

                        <div style="border-top: 2px dashed #eee; margin-top: 15px; padding-top: 15px;">
                            <div style="display: flex; justify-content: space-between; margin-bottom: 5px;">
                                <span style="color: #666;">ยอดรวม</span>
                                <span>฿<?php echo number_format($total); ?></span>
                            </div>
                            <?php if ($discountAmount > 0): ?>
                            <div style="display: flex; justify-content: space-between; margin-bottom: 5px; color: var(--accent);">
                                <span>ส่วนลด</span>
                                <span>-฿<?php echo number_format($discountAmount); ?></span>
                            </div>
                            <?php endif; ?>
                            <div style="display: flex; justify-content: space-between; margin-bottom: 5px;">
                                <span style="color: #666;">ค่าส่ง</span>
                                <?php if ($freeShipping): ?>
                                    <span style="font-weight: 500; color: #28a745;">ฟรี</span>
                                <?php else: ?>
                                    <span>฿<?php echo number_format($shipping); ?></span>
                                <?php endif; ?>
                            </div>
                            <div style="display: flex; justify-content: space-between; margin-top: 10px; font-size: 1.5rem; color: var(--primary); font-weight: 800;">
                                <span>ยอดสุทธิ</span>
                                <span>฿<?php echo number_format($finalTotal); ?></span>
                            </div>
                        </div>

                        <button type="submit" class="btn btn-primary" style="width: 100%; margin-top: 25px; padding: 15px; font-size: 1.2rem; border-radius: 50px;">
                            สั่งซื้อเลย! 🚀
                        </button>
                    </div>
                </div>
            </div>
        </form>
    </div>

    <script>
        function selectPayment(method) {
            document.getElementById('paymentMethod').value = method;
            document.querySelectorAll('.payment-method-card').forEach(el => el.classList.remove('active'));
            document.getElementById('method-' + method).classList.add('active');
            
            document.getElementById('qrSection').style.display = method === 'promptpay' ? 'block' : 'none';
            document.getElementById('codSection').style.display = method === 'cod' ? 'block' : 'none';
        }

        function previewFile(input) {
            if (input.files && input.files[0]) {
                document.getElementById('fileName').innerText = '✅ ' + input.files[0].name;
            }
        }

        async function handleCheckout(e) {
            e.preventDefault();
            const formData = new FormData(e.target);
            const method = formData.get('payment_method');
            
            if (method === 'promptpay' && !document.getElementById('slipInput').files.length) {
                Swal.fire({
                    icon: 'warning',
                    title: 'ลืมอะไรไปหรือเปล่า?',
                    text: 'กรุณาแนบสลิปการโอนเงินด้วยนะ'
                });
                return;
            }

            const btn = e.target.querySelector('button[type="submit"]');
            const originalText = btn.innerText;
            btn.innerHTML = '🤖 AI กำลังตรวจสอบสลิป...';
            btn.disabled = true;

            try {
                const response = await fetch('api/order.php', {
                    method: 'POST',
                    body: formData
                });
                const result = await response.json();

                if (result.success) {
                    if (result.status === 'รอตรวจสอบ') {
                        Swal.fire({
                            icon: 'info',
                            title: 'บันทึกคำสั่งซื้อแล้ว',
                            html: `<b>⚠️ รอตรวจสอบสลิปโอนเงิน</b><br>เนื่องจากระบบ AI ขัดข้อง เจ้าหน้าที่จะตรวจสอบสลิปของคุณโดยเร็วที่สุด<br><br>เลขพัสดุ: <b>${result.tracking_number}</b>`,
                            confirmButtonText: 'ตกลง',
                            confirmButtonColor: '#ffc107'
                        }).then(() => {
                            window.location.href = 'profile.php';
                        });
                    } else {
                        Swal.fire({
                            icon: 'success',
                            title: 'สั่งซื้อสำเร็จ!',
                            html: `เลขพัสดุของคุณ: <b>${result.tracking_number}</b><br>ขอบคุณที่อุดหนุนนะ!`,
                            timer: 4000
                        }).then(() => {
                            window.location.href = 'profile.php';
                        });
                    }
                } else {
                    // Build step-by-step display if available
                    let stepsHtml = '';
                    if (result.steps && result.steps.length > 0) {
                        stepsHtml = '<div style="text-align: left; margin-top: 15px; padding: 15px; background: #f8f9fa; border-radius: 10px;">';
                        result.steps.forEach(step => {
                            const icon = step.passed === false ? '❌' : (step.passed === true ? '✅' : '⚠️');
                            const color = step.passed === false ? '#dc3545' : (step.passed === true ? '#28a745' : '#ffc107');
                            stepsHtml += `<div style="margin: 8px 0; color: ${color};">${step.name}: ${step.status}</div>`;
                        });
                        stepsHtml += '</div>';
                    }
                    
                    Swal.fire({
                        icon: 'error',
                        title: 'การตรวจสอบสลิปล้มเหลว',
                        html: `<p style="margin-bottom: 10px;">${result.message}</p>${stepsHtml}`,
                        width: '500px'
                    });
                    btn.innerHTML = originalText;
                    btn.disabled = false;
                }
            } catch (err) {
                console.error(err);
                Swal.fire({
                    icon: 'error',
                    title: 'ข้อผิดพลาด',
                    text: 'ไม่สามารถติดต่อเซิร์ฟเวอร์ได้'
                });
                btn.innerHTML = originalText;
                btn.disabled = false;
            }
        }
    </script>
</body>
</html>
