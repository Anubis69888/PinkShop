<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}
include 'includes/db.php';
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ลงทะเบียนร้านค้า - AKP Shop</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Prompt:wght@300;400;600;700&display=swap" rel="stylesheet">
</head>
<body>
    <?php include 'includes/header.php'; ?>

    <div class="container section">
        <div class="glass-card" style="max-width: 800px; margin: 0 auto;">
            <h2 class="text-center text-primary" style="margin-bottom: 30px;">📝 ลงทะเบียนร้านค้า</h2>
            
            <div class="alert-box" style="background: rgba(88, 204, 176, 0.2); padding: 15px; border-radius: 10px; margin-bottom: 20px; color: #2E7D67; font-size: 0.9rem;">
                ℹ️ กรุณากรอกข้อมูลจริงและอัพโหลดเอกสารที่ชัดเจนเพื่อความรวดเร็วในการตรวจสอบ
            </div>

            <form id="sellerForm" enctype="multipart/form-data">
                
                <h4 style="margin-bottom: 15px; border-bottom: 2px dashed #eee; padding-bottom: 10px;">ข้อมูลส่วนตัว</h4>
                
                <div class="grid grid-2">
                    <div class="form-group">
                        <label>ชื่อ-นามสกุล (ตามบัตรประชาชน)</label>
                        <input type="text" name="real_name" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label>เลขบัตรประจำตัวประชาชน</label>
                        <input type="text" name="id_card_number" class="form-control" maxlength="13" required>
                    </div>
                </div>

                <div class="form-group" style="margin-top: 15px;">
                    <label>ที่อยู่ (สำหรับจัดส่งเอกสาร/ติดต่อ)</label>
                    <textarea name="address" class="form-control" rows="3" required></textarea>
                </div>

                <h4 style="margin-top: 30px; margin-bottom: 15px; border-bottom: 2px dashed #eee; padding-bottom: 10px;">ข้อมูลทางการเงิน</h4>

                <div class="grid grid-2">
                    <div class="form-group">
                        <label>ธนาคาร</label>
                        <select name="bank_name" class="form-control" required>
                            <option value="">เลือกธนาคาร...</option>
                            <option value="kbank">กสิกรไทย (KBANK)</option>
                            <option value="scb">ไทยพาณิชย์ (SCB)</option>
                            <option value="bbl">กรุงเทพ (BBL)</option>
                            <option value="ktb">กรุงไทย (KTB)</option>
                            <option value="ttb">ที่เอ็มบีธนชาต (ttb)</option>
                            <option value="gsb">ออมสิน (GSB)</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>เลขบัญชีธนาคาร</label>
                        <input type="text" name="bank_account" class="form-control" required>
                    </div>
                </div>

                <h4 style="margin-top: 30px; margin-bottom: 15px; border-bottom: 2px dashed #eee; padding-bottom: 10px;">เอกสารยืนยันตัวตน</h4>

                <div class="grid grid-3">
                    <div class="upload-box text-center">
                        <label style="display: block; margin-bottom: 10px; font-size: 0.9rem;">รูปถ่ายหน้าบัตร ปชช.</label>
                        <input type="file" name="id_card_front" class="form-control" accept="image/*" required>
                    </div>
                    <div class="upload-box text-center">
                        <label style="display: block; margin-bottom: 10px; font-size: 0.9rem;">รูปถ่ายหลังบัตร ปชช.</label>
                        <input type="file" name="id_card_back" class="form-control" accept="image/*" required>
                    </div>
                    <div class="upload-box text-center">
                        <label style="display: block; margin-bottom: 10px; font-size: 0.9rem;">รูปถ่ายหน้าสมุดบัญชี</label>
                        <input type="file" name="bank_book" class="form-control" accept="image/*" required>
                    </div>
                </div>

                <div style="margin-top: 40px; text-align: center;">
                    <button type="submit" class="btn btn-primary" style="padding: 12px 40px; font-size: 1.1rem;">
                        ส่งคำขอเปิดร้านค้า 🚀
                    </button>
                    <div style="margin-top: 15px;">
                        <a href="profile.php" style="color: var(--text-muted); font-size: 0.9rem;">ยกเลิกและกลับไปหน้าโปรไฟล์</a>
                    </div>
                </div>

            </form>
        </div>
    </div>

    <script>
        document.getElementById('sellerForm').addEventListener('submit', async (e) => {
            e.preventDefault();
            
            // Basic validation
            const formData = new FormData(e.target);
            const btn = e.target.querySelector('button[type="submit"]');
            const originalText = btn.innerHTML;
            
            btn.innerHTML = '🤖 AI กำลังตรวจสอบความถูกต้อง...';
            btn.disabled = true;

            try {
                const response = await fetch('api/seller_request.php', {
                    method: 'POST',
                    body: formData
                });
                const result = await response.json();

                if (result.success) {
                    Swal.fire({
                        icon: 'success',
                        title: 'สำเร็จ!',
                        text: result.message,
                        timer: 2000,
                        showConfirmButton: false
                    }).then(() => {
                        window.location.href = 'profile.php';
                    });
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'เกิดข้อผิดพลาด',
                        text: result.message
                    });
                    btn.innerHTML = originalText;
                    btn.disabled = false;
                }
            } catch (error) {
                console.error('Error:', error);
                Swal.fire({
                    icon: 'error',
                    title: 'ข้อผิดพลาด',
                    text: 'เกิดข้อผิดพลาดในการเชื่อมต่อ'
                });
                btn.innerHTML = originalText;
                btn.disabled = false;
            }
        });
    </script>
</body>
</html>
