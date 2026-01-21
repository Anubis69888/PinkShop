<?php
session_start();
require_once 'includes/db.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$orderId = $_GET['id'] ?? 0;
$db = new DB();
$order = $db->find('orders', 'id', $orderId);

// Verify ownership
if (!$order || $order['user_id'] != $_SESSION['user_id']) {
    header('Location: profile.php');
    exit;
}

// Verify status (Only allow cancel if not already cancelled or shipped)
// Adjust these conditions as per business logic. For now, allow 'Pending', 'Paid' (ชำระเงินแล้ว).
$cancellableStatuses = ['รอชำระเงิน', 'ชำระเงินแล้ว'];
$canCancel = in_array($order['status'], $cancellableStatuses);

?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ยกเลิกคำสั่งซื้อ #<?php echo $order['id']; ?> - Doll Paradise</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="assets/css/modal.css">
    <link href="https://fonts.googleapis.com/css2?family=Prompt:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <script src="assets/js/modal.js" defer></script>
    <style>
        body { font-family: 'Prompt', sans-serif; }
        .cancel-card {
            background: rgba(255, 255, 255, 0.9);
            border-radius: 20px;
            padding: 30px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.05);
            max-width: 600px;
            margin: 40px auto;
        }
        .order-summary {
            background: rgba(255,255,255,0.5);
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 20px;
            border: 1px dashed #ddd;
        }
        .reason-select {
            width: 100%;
            padding: 12px;
            border-radius: 10px;
            border: 1px solid #ddd;
            font-family: 'Prompt', sans-serif;
            margin-bottom: 20px;
            outline: none;
        }
        .reason-select:focus {
            border-color: var(--primary);
        }
    </style>
</head>
<body>
    <?php include 'includes/header.php'; ?>

    <div class="container">
        <div class="cancel-card">
            <h2 style="text-align: center; color: var(--primary); margin-bottom: 20px;">🚫 ยกเลิกคำสั่งซื้อ</h2>
            
            <?php if ($canCancel): ?>
                <div class="order-summary">
                    <h4 style="margin-bottom: 15px;">รายละเอียดคำสั่งซื้อ #<?php echo $order['id']; ?></h4>
                    <p style="color: var(--text-muted); margin-bottom: 10px;">ยอดรวม: ฿<?php echo number_format($order['total']); ?></p>
                    <ul style="padding-left: 20px; color: var(--text-muted);">
                        <?php foreach ($order['items'] as $item): ?>
                            <li><?php echo $item['name']; ?> (x<?php echo $item['qty']; ?>)</li>
                        <?php endforeach; ?>
                    </ul>
                </div>

                <form id="cancelForm">
                    <div style="margin-bottom: 20px;">
                        <label style="display: block; margin-bottom: 10px; font-weight: 500;">ระบุเหตุผลที่ต้องการยกเลิก</label>
                        <select name="reason" class="reason-select" required>
                            <option value="">-- เลือกเหตุผล --</option>
                            <option value="เปลี่ยนใจ">เปลี่ยนใจ / ไม่ต้องการสินค้านี้แล้ว</option>
                            <option value="สั่งผิดรายการ">สั่งผิดรายการ</option>
                            <option value="พบราคาที่ถูกกว่า">พบราคาที่ถูกกว่า</option>
                            <option value="อื่นๆ">อื่นๆ</option>
                        </select>
                    </div>

                    <div style="display: flex; gap: 10px;">
                        <a href="profile.php" class="btn btn-secondary" style="flex: 1; text-align: center; border-radius: 12px;">กลับ</a>
                        <button type="button" onclick="confirmCancel()" class="btn" style="flex: 1; border-radius: 12px; background: #ff4d4d; color: white;">ยืนยันการยกเลิก</button>
                    </div>
                </form>
            <?php else: ?>
                <div style="text-align: center; padding: 40px 0;">
                    <div style="font-size: 3rem; margin-bottom: 20px;">⚠️</div>
                    <h3>ไม่สามารถยกเลิกคำสั่งซื้อนี้ได้</h3>
                    <p style="color: var(--text-muted); margin-bottom: 20px;">คำสั่งซื้อนี้อยู่ในสถานะที่ไม่สามารถยกเลิกได้ (<?php echo $order['status']; ?>)</p>
                    <a href="profile.php" class="btn btn-primary">กลับสู่หน้าโปรไฟล์</a>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script>
        async function confirmCancel() {
            const form = document.getElementById('cancelForm');
            const reason = form.reason.value;

            if (!reason) {
                showModal('กรุณาเลือกเหตุผลในการยกเลิก', 'แจ้งเตือน', '⚠️');
                return;
            }

            // Custom confirmation using the modal
            showModal('ยืนยันที่จะยกเลิกคำสั่งซื้อนี้? การกระทำนี้ไม่สามารถย้อนกลับได้', 'ยืนยันการยกเลิก', '❓', async () => {
                 try {
                    const formData = new FormData();
                    formData.append('action', 'cancel');
                    formData.append('order_id', <?php echo $orderId; ?>);
                    formData.append('reason', reason);

                    const response = await fetch('api/order.php', {
                        method: 'POST',
                        body: formData
                    });
                    
                    const text = await response.text();
                    try {
                        const result = JSON.parse(text);
                        if (result.success) {
                            showModal('ยกเลิกคำสั่งซื้อเรียบร้อยแล้ว', 'สำเร็จ', '✅', () => {
                                window.location.href = 'profile.php';
                            });
                        } else {
                            showModal('เกิดข้อผิดพลาด: ' + result.message, 'ผิดพลาด', '❌');
                        }
                    } catch (e) {
                        console.error('Server Error:', text);
                        showModal('เกิดข้อผิดพลาดจากเซิร์ฟเวอร์', 'ผิดพลาด', '❌');
                    }
                } catch (error) {
                    console.error(error);
                    showModal('เกิดข้อผิดพลาดในการเชื่อมต่อ', 'ผิดพลาด', '❌');
                }
            }, true);
        }
    </script>

    <?php include 'includes/footer.php'; ?>
</body>
</html>
