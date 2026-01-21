<?php
require_once 'includes/init.php';
require_once 'includes/db.php';

if (!isset($_SESSION['user_id']) || !($_SESSION['is_seller'] ?? false)) {
    header('Location: index.php');
    exit;
}

$db = new DB();
$user = $db->find('users', 'id', $_SESSION['user_id']);

if (!$user) {
    header('Location: logout.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ตั้งค่าร้านค้า - AKP Shop</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Prompt:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>body { font-family: 'Prompt', sans-serif; }</style>
</head>
<body>
    <?php include 'includes/header.php'; ?>

    <div class="container section">
        <div class="global-header-style">
            <h1>⚙️ ตั้งค่าร้านค้า</h1>
            <p>ปรับแต่งหน้าร้านค้าของคุณให้ดูดี</p>
        </div>

        <div class="glass-card" style="max-width: 800px; margin: 0 auto; padding: 40px;">
            <form id="shopForm" enctype="multipart/form-data">
                <div class="form-group">
                    <label>รูปภาพปกร้านค้า (Cover Image)</label>
                    <div style="margin-bottom: 10px;">
                        <?php if (!empty($user['shop_cover'])): ?>
                            <img src="<?php echo htmlspecialchars($user['shop_cover']); ?>" style="width: 100%; height: auto; border-radius: 15px; border: 2px solid white; box-shadow: 0 5px 15px rgba(0,0,0,0.1);">
                        <?php else: ?>
                            <div style="width: 100%; height: 150px; background: #f0f0f0; border-radius: 15px; display: flex; align-items: center; justify-content: center; color: #aaa;">
                                ตัวอย่างรูปปก
                            </div>
                        <?php endif; ?>
                    </div>
                    <input type="file" name="shop_cover" class="form-control" accept="image/*">
                    <small style="color: var(--text-muted);">แนะนำขนาด 1200x300 px (ไฟล์ JPG, PNG)</small>
                </div>

                <div class="row" style="display: flex; gap: 20px; margin-top: 20px;">
                    <div class="form-group" style="flex: 1;">
                        <label>ชื่อร้านค้า</label>
                        <input type="text" name="shop_name" class="form-control" value="<?php echo htmlspecialchars($user['shop_name'] ?? $user['username']); ?>" required>
                    </div>
                </div>

                <div class="form-group" style="margin-top: 20px;">
                    <label>คำอธิบายร้านค้า</label>
                    <textarea name="shop_description" class="form-control" rows="4"><?php echo htmlspecialchars($user['shop_description'] ?? ''); ?></textarea>
                </div>

                <h3 style="margin-top: 30px; font-size: 1.2rem; border-bottom: 2px solid #eee; padding-bottom: 10px;">ช่องทางการติดต่อ</h3>
                
                <div class="row" style="display: flex; gap: 20px; margin-top: 20px;">
                    <div class="form-group" style="flex: 1;">
                        <label>Line ID</label>
                        <input type="text" name="line_id" class="form-control" value="<?php echo htmlspecialchars($user['shop_contact']['line'] ?? ''); ?>" placeholder="@yourshop">
                    </div>
                    <div class="form-group" style="flex: 1;">
                        <label>Facebook URL</label>
                        <input type="text" name="facebook" class="form-control" value="<?php echo htmlspecialchars($user['shop_contact']['facebook'] ?? ''); ?>" placeholder="facebook.com/yourshop">
                    </div>
                </div>
                
                <div class="row" style="display: flex; gap: 20px; margin-top: 20px;">
                    <div class="form-group" style="flex: 1;">
                        <label>เบอร์โทรศัพท์</label>
                        <input type="tel" name="phone" class="form-control" value="<?php echo htmlspecialchars($user['shop_contact']['phone'] ?? ''); ?>" placeholder="08x-xxx-xxxx">
                    </div>
                </div>

                <div style="margin-top: 40px; display: flex; gap: 15px;">
                    <a href="store.php?id=<?php echo $user['id']; ?>" class="btn btn-outline" style="flex: 1; text-align: center;">ยกเลิก</a>
                    <button type="submit" class="btn btn-primary" style="flex: 2;">บันทึกการเปลี่ยนแปลง 💾</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        document.getElementById('shopForm').addEventListener('submit', async (e) => {
            e.preventDefault();
            
            const formData = new FormData(e.target);
            
            try {
                const response = await fetch('api/update_shop.php', {
                    method: 'POST',
                    body: formData
                });
                const result = await response.json();

                if (result.success) {
                    Swal.fire({
                        icon: 'success',
                        title: 'บันทึกสำเร็จ!',
                        timer: 1500,
                        showConfirmButton: false
                    }).then(() => {
                        window.location.href = 'store.php?id=<?php echo $user['id']; ?>';
                    });
                } else {
                    Swal.fire('เกิดข้อผิดพลาด', result.message, 'error');
                }
            } catch (error) {
                console.error(error);
                Swal.fire('ข้อผิดพลาด', 'ไม่สามารถเชื่อมต่อเซิร์ฟเวอร์', 'error');
            }
        });
    </script>
</body>
</html>
