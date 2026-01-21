<?php
session_start();
require_once 'includes/db.php';

if (!isset($_SESSION['user_id']) || empty($_SESSION['is_seller'])) {
    header('Location: profile.php');
    exit;
}

$db = new DB();
$userId = $_SESSION['user_id'];

// Get seller's products to identify relevant orders
$allProducts = $db->read('products');
$sellerProductIds = [];
$productMap = [];
foreach ($allProducts as $p) {
    if ($p['seller_id'] == $userId) {
        $sellerProductIds[] = $p['id'];
    }
    $productMap[$p['id']] = $p;
}

// Get orders containing these products
$allOrders = $db->read('orders');
$sellerOrders = [];

foreach ($allOrders as $order) {
    $relevantItems = [];
    $isRelevant = false;
    
    foreach ($order['items'] as $item) {
        // Check if this item belongs to the current seller:
        // 1. Check seller_id directly in item (for newer orders)
        // 2. Fall back to checking product_id against seller's products
        $itemSellerId = $item['seller_id'] ?? null;
        $matchesBySellerId = ($itemSellerId !== null && $itemSellerId == $userId);
        $matchesByProductId = in_array($item['product_id'], $sellerProductIds);
        
        if ($matchesBySellerId || $matchesByProductId) {
            $isRelevant = true;
            $relevantItems[] = $item;
        }
    }
    
    if ($isRelevant) {
        $order['relevant_items'] = $relevantItems; // Only show items this seller sold
        $sellerOrders[] = $order;
    }
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>จัดการคำสั่งซื้อ - Seller Center</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link href="https://fonts.googleapis.com/css2?family=Prompt:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Prompt', sans-serif; }
        
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

        .order-count-badge {
            background: linear-gradient(135deg, #722ed1, #eb2f96);
            color: white;
            padding: 8px 16px;
            border-radius: 20px;
            font-size: 0.9rem;
            font-weight: 600;
            box-shadow: 0 4px 12px rgba(114, 46, 209, 0.3);
        }

        .glass-card {
            background: rgba(255, 255, 255, 0.9);
            backdrop-filter: blur(20px);
            border-radius: 24px;
            padding: 30px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.05);
            border: 1px solid rgba(255,255,255,0.8);
        }

        .order-card {
            background: white;
            border-radius: 16px;
            padding: 25px;
            margin-bottom: 20px;
            border: 1px solid #eee;
            box-shadow: 0 4px 15px rgba(0,0,0,0.03);
            transition: all 0.3s;
            position: relative;
            overflow: hidden;
        }
        .order-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 25px rgba(214, 123, 179, 0.15);
            border-color: var(--primary);
        }
        
        .status-badge {
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 600;
        }
        .status-pending { background: #fff3cd; color: #856404; }
        .status-paid { background: #d4edda; color: #155724; }
        .status-review { background: #cce5ff; color: #004085; }
        .status-cancelled { background: #f8d7da; color: #721c24; }

        .btn-update {
            background: var(--primary);
            color: white;
            border: none;
            padding: 8px 20px;
            border-radius: 50px;
            cursor: pointer;
            transition: 0.3s;
            box-shadow: 0 4px 10px rgba(214, 123, 179, 0.3);
        }
        .btn-update:hover { 
            transform: translateY(-2px);
            box-shadow: 0 6px 15px rgba(214, 123, 179, 0.4);
        }
    </style>
</head>
<body>
    <?php include 'includes/header.php'; ?>

    <div class="container section">
        
        <div class="page-header">
            <div class="header-content">
                <div class="header-left">
                    <a href="profile.php" class="back-button">
                        <i class="fas fa-arrow-left"></i>
                        <span>ย้อนกลับ</span>
                    </a>
                    <div class="header-title-group">
                        <h1>จัดการคำสั่งซื้อ</h1>
                        <span class="order-count-badge">
                            <i class="fas fa-boxes"></i> <?php echo count($sellerOrders); ?> รายการ
                        </span>
                    </div>
                </div>
            </div>
        </div>

        <div class="glass-card">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px;">
                <h2 style="color: var(--text-main); margin: 0;">รายการคำสั่งซื้อ</h2>
                <a href="seller_dashboard.php" class="btn btn-outline">📊 ดูแดชบอร์ด</a>
            </div>

            <?php if (empty($sellerOrders)): ?>
                <div style="text-align: center; padding: 60px;">
                    <div style="font-size: 5rem; margin-bottom: 20px; opacity: 0.5;">📭</div>
                    <h3 style="color: var(--text-muted);">ยังไม่มีคำสั่งซื้อเข้ามา</h3>
                    <p style="color: #999;">เมื่อลูกค้าสั่งซื้อสินค้าของคุณ รายการจะปรากฏที่นี่</p>
                </div>
            <?php else: ?>
                <?php foreach (array_reverse($sellerOrders) as $order): ?>
                    <div class="order-card">
                        <div style="display: flex; justify-content: space-between; margin-bottom: 20px; padding-bottom: 15px; border-bottom: 1px dashed #eee;">
                            <div style="display: flex; align-items: center; gap: 15px;">
                                <div style="background: var(--primary); color: white; width: 40px; height: 40px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: bold;">
                                    #<?php echo $order['id']; ?>
                                </div>
                                <div>
                                    <div style="font-weight: bold; color: var(--text-main);">คำสั่งซื้อ #<?php echo $order['id']; ?></div>
                                    <div style="font-size: 0.85rem; color: #999;">
                                        🕒 <?php echo $order['created_at']; ?>
                                    </div>
                                </div>
                            </div>
                            <div>
                                <?php
                                    $statusClass = 'status-pending';
                                    if ($order['status'] == 'ชำระเงินแล้ว') $statusClass = 'status-paid';
                                    if ($order['status'] == 'รอตรวจสอบ') $statusClass = 'status-review';
                                    if ($order['status'] == 'ยกเลิก') $statusClass = 'status-cancelled';
                                ?>
                                <span class="status-badge <?php echo $statusClass; ?>">
                                    <?php echo $order['status']; ?>
                                </span>
                            </div>
                        </div>

                        <div class="grid grid-2" style="gap: 30px;">
                            <!-- Items -->
                            <div>
                                <h4 style="margin-bottom: 15px; color: var(--primary);">📦 รายการสินค้า</h4>
                                <div style="background: #fafafa; padding: 15px; border-radius: 12px;">
                                    <?php foreach ($order['relevant_items'] as $item): ?>
                                        <div style="display: flex; justify-content: space-between; margin-bottom: 8px; font-size: 0.95rem;">
                                            <span style="color: var(--text-main);"><?php echo htmlspecialchars($item['name']); ?></span>
                                            <span style="color: var(--text-muted);">x<?php echo $item['qty']; ?></span>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>

                            <!-- Customer Info -->
                            <div>
                                <h4 style="margin-bottom: 15px; color: var(--accent);">👤 ข้อมูลจัดส่ง</h4>
                                <div style="font-size: 0.95rem; line-height: 1.6; color: var(--text-muted);">
                                    <strong><?php echo htmlspecialchars($order['shipping_info']['fullname']); ?></strong><br>
                                    <?php echo htmlspecialchars($order['shipping_info']['address']); ?><br>
                                    📞 <?php echo htmlspecialchars($order['shipping_info']['phone']); ?>
                                </div>
                            </div>
                        </div>

                        <!-- Actions -->
                        <?php if ($order['status'] !== 'ยกเลิก'): ?>
                        <div style="margin-top: 25px; padding-top: 20px; border-top: 1px solid #f0f0f0;">
                            <?php if (!empty($order['tracking_number'])): ?>
                                <div style="display: flex; justify-content: flex-end; align-items: center; gap: 15px;">
                                    <span style="color: var(--text-muted);">เลขพัสดุ:</span>
                                    <span style="font-family: 'Courier New', monospace; font-weight: 700; color: var(--primary); font-size: 1.2rem; background: #fff5f9; padding: 5px 15px; border-radius: 10px;">
                                        <?php echo htmlspecialchars($order['tracking_number']); ?>
                                    </span>
                                    
                                    <div style="height: 30px; border-left: 1px solid #ddd; margin: 0 10px;"></div>

                                    <div style="display: flex; gap: 10px;">
                                        <input type="text" id="tracking_<?php echo $order['id']; ?>" class="form-control" placeholder="แก้ไขเลขพัสดุ" style="width: 150px; padding: 8px 15px;">
                                        
                                        <label for="receipt_<?php echo $order['id']; ?>" class="btn-outline" style="cursor: pointer; padding: 8px 15px; font-size: 0.9rem; display: flex; align-items: center; gap: 5px;">
                                            <input type="file" id="receipt_<?php echo $order['id']; ?>" accept="image/*" style="display: none;" onchange="document.getElementById('file_label_<?php echo $order['id']; ?>').innerText = this.files[0].name.substring(0, 8) + '...'">
                                            📸 <span id="file_label_<?php echo $order['id']; ?>">สลิป</span>
                                        </label>

                                        <button onclick="updateStatus(<?php echo $order['id']; ?>)" class="btn-update">บันทึก</button>
                                    </div>
                                </div>
                            <?php else: ?>
                                <div style="display: flex; justify-content: flex-end; align-items: center; gap: 10px;">
                                    <input type="text" id="tracking_<?php echo $order['id']; ?>" class="form-control" placeholder="ระบุเลขพัสดุ..." style="width: 200px;">
                                    
                                    <label for="receipt_<?php echo $order['id']; ?>" class="btn-outline" style="cursor: pointer; padding: 8px 15px; font-size: 0.9rem; display: flex; align-items: center; gap: 5px;">
                                        <input type="file" id="receipt_<?php echo $order['id']; ?>" accept="image/*" style="display: none;" onchange="document.getElementById('file_label_<?php echo $order['id']; ?>').innerText = this.files[0].name.substring(0, 8) + '...'">
                                        📸 <span id="file_label_<?php echo $order['id']; ?>">AI ตรวจสลิป</span>
                                    </label>
                                    
                                    <button onclick="updateStatus(<?php echo $order['id']; ?>)" class="btn-update">ยืนยันการส่ง</button>
                                </div>
                            <?php endif; ?>
                        </div>
                        <?php else: ?>
                            <div style="text-align: right; margin-top: 20px; color: #dc3545; background: #fff5f5; padding: 10px; border-radius: 8px;">
                                ❌ ลูกค้าได้ยกเลิกคำสั่งซื้อนี้แล้ว (เหตุผล: <?php echo htmlspecialchars($order['cancel_reason'] ?? '-'); ?>)
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

    <script>
        async function updateStatus(orderId) {
            const trackingNumber = document.getElementById('tracking_' + orderId).value;
            const fileInput = document.getElementById('receipt_' + orderId);
            const file = fileInput ? fileInput.files[0] : null;
            
            if (!trackingNumber) {
                Swal.fire({
                    icon: 'warning',
                    title: 'กรุณาระบุเลขพัสดุ',
                    text: 'ต้องใส่เลขพัสดุก่อนบันทึกนะ'
                });
                return;
            }

            // Require file upload for new submissions
            if (!file && !document.querySelector(`#tracking_${orderId}`).parentElement.innerHTML.includes('แก้ไข')) {
                 const result = await Swal.fire({
                    title: 'ไม่ได้แนบสลิป?',
                    text: "คุณไม่ได้อัปโหลดรูปสลิป ต้องการดำเนินการต่อโดยไม่ใช้ AI ตรวจสอบหรือไม่?",
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonText: 'ดำเนินการต่อ',
                    cancelButtonText: 'กลับไปแนบ'
                 });
                 if (!result.isConfirmed) return;
            }

            const confirmResult = await Swal.fire({
                title: 'ยืนยันข้อมูล?',
                text: "ตรวจสอบเลขพัสดุถูกต้องแล้วใช่ไหม?",
                icon: 'info',
                showCancelButton: true,
                confirmButtonText: 'ยืนยัน',
                cancelButtonText: 'ยกเลิก'
            });

            if (!confirmResult.isConfirmed) return;
            
            // Show loading state
            const btn = event.target;
            const originalText = btn.innerText;
            btn.innerText = '✨ AI กำลังวิเคราะห์...';
            btn.disabled = true;

            try {
                const formData = new FormData();
                formData.append('action', 'update_status');
                formData.append('order_id', orderId);
                formData.append('tracking_number', trackingNumber);
                if (file) {
                    formData.append('receipt_image', file);
                }

                const response = await fetch('api/seller_action.php', {
                    method: 'POST',
                    body: formData
                });
                const result = await response.json();

                if (result.success) {
                    // Show AI verification results if available
                    if (result.ai_verification) {
                        const verification = result.ai_verification;
                        
                        // Build HTML for verification steps
                        let stepsHtml = '<div style="text-align: left; margin: 20px 0;">';
                        stepsHtml += '<h4 style="margin-bottom: 15px;">ผลการตรวจสอบ AI:</h4>';
                        
                        verification.steps.forEach(step => {
                            const borderColor = step.passed ? '#28a745' : '#dc3545';
                            const bgColor = step.passed ? '#f0fff4' : '#fff5f5';
                            
                            stepsHtml += `
                                <div style="padding: 15px; margin: 10px 0; border-left: 4px solid ${borderColor}; background: ${bgColor}; border-radius: 8px;">
                                    <div style="font-size: 1.5rem; float: left; margin-right: 12px;">${step.icon}</div>
                                    <div>
                                        <div style="font-weight: 600; margin-bottom: 5px;">${step.step}</div>
                                        <div style="font-size: 0.9rem; color: #666;">${step.message}</div>
                                    </div>
                                    <div style="clear: both;"></div>
                                </div>
                            `;
                        });
                        
                        stepsHtml += `</div><div style="padding: 15px; background: white; border-radius: 12px; font-weight: 600; text-align: center;">${verification.overall_message}</div>`;
                        
                        await Swal.fire({
                            icon: verification.success ? 'success' : 'warning',
                            title: verification.success ? '✅ ตรวจสอบผ่าน!' : '⚠️ ตรวจพบข้อผิดพลาด',
                            html: stepsHtml,
                            confirmButtonText: 'เข้าใจแล้ว',
                            width: '600px'
                        });
                    } else {
                        await Swal.fire({
                            icon: 'success',
                            title: 'เรียบร้อย!',
                            text: 'อัปเดตสถานะการจัดส่งแล้ว',
                            timer: 2000
                        });
                    }
                    location.reload();
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'เกิดข้อผิดพลาด',
                        text: result.message
                    });
                }
            } catch (error) {
                console.error('Error:', error);
                Swal.fire({
                    icon: 'error',
                    title: 'เกิดข้อผิดพลาด',
                    text: 'ไม่สามารถติดต่อเซิร์ฟเวอร์ได้'
                });
            } finally {
                // Reset button state
                btn.innerText = originalText;
                btn.disabled = false;
            }
        }
    </script>
</body>
</html>
