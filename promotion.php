<?php 
require_once 'includes/init.php'; 
require_once 'includes/db.php';

$db = new DB();
$coupons = $db->read('coupons');

// Filter valid coupons (not expired)
$today = date('Y-m-d');
$validCoupons = array_filter($coupons, function($c) use ($today) {
    if (empty($c['expiry_date'])) return true;
    return $c['expiry_date'] >= $today;
});

$isAdmin = isset($_SESSION['is_admin']) && $_SESSION['is_admin'];
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>โปรโมชั่น - AKP Shop</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Prompt:wght@300;400;600;800&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        body { font-family: 'Prompt', sans-serif; }
        .promo-card {
            background: rgba(255,255,255,0.9);
            border-radius: 20px;
            padding: 25px 30px;
            margin-bottom: 20px;
            border: 2px solid rgba(255,255,255,0.8);
            display: flex;
            align-items: center;
            gap: 25px;
            transition: all 0.3s;
            box-shadow: 0 5px 20px rgba(0,0,0,0.05);
        }
        .promo-card:hover {
            transform: translateY(-5px) scale(1.01);
            box-shadow: 0 15px 40px rgba(0,0,0,0.1);
        }
        .promo-icon {
            font-size: 3.5rem;
            width: 80px;
            height: 80px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, #fff0f5, #ffe6f0);
            border-radius: 20px;
        }
        .promo-tag {
            background: linear-gradient(135deg, var(--primary, #ff6b81), #ff9a9e);
            color: white;
            padding: 5px 15px;
            border-radius: 50px;
            font-size: 0.8rem;
            font-weight: 600;
            display: inline-block;
            margin-bottom: 8px;
        }
        .coupon-code {
            border: 2px dashed var(--primary, #ff6b81);
            color: var(--primary, #ff6b81);
            padding: 12px 25px;
            border-radius: 12px;
            font-size: 1.1rem;
            font-weight: 700;
            background: rgba(255, 107, 129, 0.05);
            cursor: pointer;
            transition: all 0.3s;
            white-space: nowrap;
        }
        .coupon-code:hover {
            background: var(--primary, #ff6b81);
            color: white;
        }
        .promo-expiry {
            font-size: 0.75rem;
            color: #999;
            margin-top: 8px;
        }
        .admin-btn {
            position: fixed;
            bottom: 100px;
            right: 20px;
            width: 60px;
            height: 60px;
            background: linear-gradient(135deg, #667eea, #764ba2);
            border: none;
            border-radius: 50%;
            color: white;
            font-size: 1.5rem;
            cursor: pointer;
            box-shadow: 0 5px 20px rgba(102, 126, 234, 0.4);
            z-index: 1000;
            transition: all 0.3s;
        }
        .admin-btn:hover {
            transform: scale(1.1);
            box-shadow: 0 8px 30px rgba(102, 126, 234, 0.5);
        }
        .delete-btn {
            position: absolute;
            top: 10px;
            right: 10px;
            background: #ff4757;
            color: white;
            border: none;
            border-radius: 50%;
            width: 30px;
            height: 30px;
            cursor: pointer;
            font-size: 0.9rem;
            opacity: 0;
            transition: opacity 0.3s;
        }
        .promo-card:hover .delete-btn {
            opacity: 1;
        }
        @media (max-width: 768px) {
            .promo-card { flex-direction: column; text-align: center; }
            .promo-icon { margin-bottom: 10px; }
        }
    </style>
</head>
<body>
    <?php include 'includes/header.php'; ?>

    <div class="container section">
        <div class="global-header-style">
            <h1>🎁 โปรโมชั่นสุดพิเศษ</h1>
            <p>คูปองส่วนลดและดีลเด็ดๆ สำหรับคุณ</p>
        </div>

        <?php if (empty($validCoupons)): ?>
            <div style="text-align: center; padding: 60px; color: #999;">
                <span style="font-size: 5rem; display: block; margin-bottom: 20px;">🎫</span>
                <h3>ยังไม่มีโปรโมชั่นในขณะนี้</h3>
                <p>กลับมาเช็คใหม่ทีหลังนะคะ!</p>
            </div>
        <?php else: ?>
            <?php foreach ($validCoupons as $coupon): ?>
            <div class="promo-card" style="position: relative;">
                <?php if ($isAdmin): ?>
                <button class="delete-btn" onclick="deleteCoupon(<?php echo $coupon['id']; ?>)" title="ลบคูปอง">✕</button>
                <?php endif; ?>
                
                <div class="promo-icon"><?php echo $coupon['icon'] ?? '🎫'; ?></div>
                <div style="flex: 1;">
                    <span class="promo-tag"><?php echo htmlspecialchars($coupon['tag'] ?? 'โปรโมชั่น'); ?></span>
                    <h2 style="margin: 0 0 8px 0; color: #333; font-size: 1.3rem;">
                        <?php 
                        if ($coupon['discount_type'] === 'percent') {
                            echo "ส่วนลด {$coupon['discount_value']}%";
                        } elseif ($coupon['discount_type'] === 'fixed') {
                            echo "ลด ฿" . number_format($coupon['discount_value']);
                        } elseif ($coupon['discount_type'] === 'freeship') {
                            echo "ส่งฟรี";
                        }
                        ?>
                    </h2>
                    <p style="color: #777; margin: 0; font-size: 0.95rem;"><?php echo htmlspecialchars($coupon['description'] ?? ''); ?></p>
                    <?php if ($coupon['min_order'] > 0): ?>
                    <p style="color: #999; margin: 5px 0 0 0; font-size: 0.85rem;">🛒 ขั้นต่ำ ฿<?php echo number_format($coupon['min_order']); ?></p>
                    <?php endif; ?>
                    <?php if (!empty($coupon['expiry_date'])): ?>
                    <p class="promo-expiry">📅 หมดอายุ: <?php echo date('d/m/Y', strtotime($coupon['expiry_date'])); ?></p>
                    <?php endif; ?>
                </div>
                <div class="coupon-code" onclick="copyCode(this)"><?php echo htmlspecialchars($coupon['code']); ?></div>
            </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <?php if ($isAdmin): ?>
    <!-- Admin: Create Coupon Button -->
    <button class="admin-btn" onclick="showCreateModal()" title="สร้างคูปองใหม่">➕</button>
    <?php endif; ?>

    <?php include 'includes/footer.php'; ?>

    <script>
        function copyCode(element) {
            const code = element.innerText;
            navigator.clipboard.writeText(code);
            const originalText = element.innerText;
            element.innerText = "คัดลอกแล้ว!";
            element.style.background = "#2ecc71";
            element.style.color = "white";
            element.style.borderColor = "#2ecc71";
            
            Swal.fire({
                icon: 'success',
                title: 'คัดลอกโค้ดแล้ว!',
                text: `โค้ด ${originalText} พร้อมใช้งาน`,
                timer: 1500,
                showConfirmButton: false,
                toast: true,
                position: 'top-end'
            });
            
            setTimeout(() => {
                element.innerText = originalText;
                element.style.background = "rgba(255, 107, 129, 0.05)";
                element.style.color = "var(--primary, #ff6b81)";
                element.style.borderColor = "var(--primary, #ff6b81)";
            }, 2000);
        }

        <?php if ($isAdmin): ?>
        function showCreateModal() {
            Swal.fire({
                title: '<span style="color: #ff6b81;">🎫 สร้างคูปองส่วนลด</span>',
                html: `
                    <div style="text-align: left;">
                        <div style="margin-bottom: 15px;">
                            <label style="display: block; margin-bottom: 5px; font-weight: 600; color: #555;">รหัสคูปอง</label>
                            <input type="text" id="coupon-code" class="swal2-input" placeholder="เช่น SAVE20" style="width: 100%; margin: 0;">
                        </div>
                        <div style="margin-bottom: 15px;">
                            <label style="display: block; margin-bottom: 5px; font-weight: 600; color: #555;">ประเภทส่วนลด</label>
                            <select id="discount-type" class="swal2-select" style="width: 100%;">
                                <option value="percent">เปอร์เซ็นต์ (%)</option>
                                <option value="fixed">ลดเท่า (บาท)</option>
                                <option value="freeship">ส่งฟรี</option>
                            </select>
                        </div>
                        <div style="margin-bottom: 15px;">
                            <label style="display: block; margin-bottom: 5px; font-weight: 600; color: #555;">มูลค่าส่วนลด</label>
                            <input type="number" id="discount-value" class="swal2-input" placeholder="เช่น 15" style="width: 100%; margin: 0;">
                        </div>
                        <div style="margin-bottom: 15px;">
                            <label style="display: block; margin-bottom: 5px; font-weight: 600; color: #555;">ยอดขั้นต่ำ (บาท)</label>
                            <input type="number" id="min-order" class="swal2-input" placeholder="0" value="0" style="width: 100%; margin: 0;">
                        </div>
                        <div style="margin-bottom: 15px;">
                            <label style="display: block; margin-bottom: 5px; font-weight: 600; color: #555;">วันหมดอายุ</label>
                            <input type="date" id="expiry-date" class="swal2-input" style="width: 100%; margin: 0;">
                        </div>
                        <div style="margin-bottom: 15px;">
                            <label style="display: block; margin-bottom: 5px; font-weight: 600; color: #555;">คำอธิบาย</label>
                            <input type="text" id="coupon-desc" class="swal2-input" placeholder="รายละเอียดโปรโมชั่น" style="width: 100%; margin: 0;">
                        </div>
                    </div>
                `,
                showCancelButton: true,
                confirmButtonText: 'สร้างคูปอง',
                cancelButtonText: 'ยกเลิก',
                confirmButtonColor: '#ff6b81',
                preConfirm: () => {
                    const code = document.getElementById('coupon-code').value.trim();
                    const discountType = document.getElementById('discount-type').value;
                    const discountValue = parseFloat(document.getElementById('discount-value').value) || 0;
                    const minOrder = parseFloat(document.getElementById('min-order').value) || 0;
                    const expiryDate = document.getElementById('expiry-date').value;
                    const description = document.getElementById('coupon-desc').value.trim();

                    if (!code) {
                        Swal.showValidationMessage('กรุณากรอกรหัสคูปอง');
                        return false;
                    }

                    return { code, discountType, discountValue, minOrder, expiryDate, description };
                }
            }).then((result) => {
                if (result.isConfirmed) {
                    createCoupon(result.value);
                }
            });
        }

        async function createCoupon(data) {
            try {
                const response = await fetch('api/coupons.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        action: 'create',
                        code: data.code,
                        discount_type: data.discountType,
                        discount_value: data.discountValue,
                        min_order: data.minOrder,
                        expiry_date: data.expiryDate,
                        description: data.description,
                        tag: 'โปรโมชั่น',
                        icon: '🎁'
                    })
                });
                const result = await response.json();

                if (result.success) {
                    Swal.fire({
                        icon: 'success',
                        title: 'สร้างคูปองสำเร็จ!',
                        timer: 1500,
                        showConfirmButton: false
                    }).then(() => {
                        location.reload();
                    });
                } else {
                    Swal.fire('เกิดข้อผิดพลาด', result.message, 'error');
                }
            } catch (error) {
                console.error(error);
                Swal.fire('ข้อผิดพลาด', 'ไม่สามารถเชื่อมต่อเซิร์ฟเวอร์', 'error');
            }
        }

        async function deleteCoupon(id) {
            const confirm = await Swal.fire({
                title: 'ลบคูปองนี้?',
                text: 'การลบไม่สามารถย้อนกลับได้',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#ff4757',
                confirmButtonText: 'ลบเลย',
                cancelButtonText: 'ยกเลิก'
            });

            if (confirm.isConfirmed) {
                try {
                    const response = await fetch('api/coupons.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ action: 'delete', id: id })
                    });
                    const result = await response.json();

                    if (result.success) {
                        Swal.fire({
                            icon: 'success',
                            title: 'ลบคูปองแล้ว',
                            timer: 1000,
                            showConfirmButton: false
                        }).then(() => location.reload());
                    } else {
                        Swal.fire('เกิดข้อผิดพลาด', result.message, 'error');
                    }
                } catch (error) {
                    Swal.fire('ข้อผิดพลาด', 'ไม่สามารถเชื่อมต่อเซิร์ฟเวอร์', 'error');
                }
            }
        }
        <?php endif; ?>
    </script>
</body>
</html>

