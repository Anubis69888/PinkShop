<?php require_once 'includes/init.php'; ?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ติดต่อเรา - Doll Paradise</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;600;800&family=Sarabun:wght@300;400;600&display=swap" rel="stylesheet">
    <style>body { font-family: 'Sarabun', 'Outfit', sans-serif; }</style>
</head>
<body>
    <?php include 'includes/header.php'; ?>

    <div class="container section">
        <div class="global-header-style">
            <h1>ติดต่อเรา</h1>
            <p>มีคำถามหรือข้อสงสัย? ติดต่อทีมงาน Doll Paradise ได้เลย!</p>
        </div>

        <div class="glass-card" style="width: 100%; max-width: 1000px; margin: 0 auto; padding: 50px; position: relative; overflow: hidden;">
            <!-- Decorative circle -->
            <div style="position: absolute; top: -50px; left: -50px; width: 150px; height: 150px; background: var(--primary-light); opacity: 0.2; border-radius: 50%; filter: blur(30px);"></div>
            <div style="position: absolute; bottom: -30px; right: -30px; width: 120px; height: 120px; background: var(--accent); opacity: 0.2; border-radius: 50%; filter: blur(30px);"></div>


            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 40px;">
                <!-- Contact Info -->
                <div>
                    <h3 style="color: var(--primary-dark); margin-bottom: 20px; border-bottom: 2px dashed var(--primary-light); padding-bottom: 10px;">ช่องทางการติดต่อ</h3>
                    
                    <div style="display: flex; align-items: center; margin-bottom: 20px;">
                        <div style="width: 50px; height: 50px; background: white; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 1.5rem; box-shadow: 0 5px 15px rgba(0,0,0,0.05); margin-right: 15px;">📍</div>
                        <div>
                            <strong style="color: var(--text-main); display: block;">ที่อยู่ร้าน</strong>
                            <span style="color: var(--text-muted);">60/1 หมู่ 11 ต.บ้านพริก อ.บ้านนา จ.นครนายก</span>
                        </div>
                    </div>

                    <div style="display: flex; align-items: center; margin-bottom: 20px;">
                        <div style="width: 50px; height: 50px; background: white; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 1.5rem; box-shadow: 0 5px 15px rgba(0,0,0,0.05); margin-right: 15px;">📞</div>
                        <div>
                            <strong style="color: var(--text-main); display: block;">เบอร์โทรศัพท์</strong>
                            <span style="color: var(--text-muted);">095-507-7213</span>
                        </div>
                    </div>

                    <div style="display: flex; align-items: center; margin-bottom: 20px;">
                        <div style="width: 50px; height: 50px; background: white; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 1.5rem; box-shadow: 0 5px 15px rgba(0,0,0,0.05); margin-right: 15px;">💌</div>
                        <div>
                            <strong style="color: var(--text-main); display: block;">อีเมล</strong>
                            <span style="color: var(--text-muted);">banbanfp@gmail.com</span>
                        </div>
                    </div>

                    <div style="display: flex; align-items: center;">
                        <div style="width: 50px; height: 50px; background: white; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 1.5rem; box-shadow: 0 5px 15px rgba(0,0,0,0.05); margin-right: 15px;">🕒</div>
                        <div>
                            <strong style="color: var(--text-main); display: block;">เวลาทำการ</strong>
                            <span style="color: var(--text-muted);">ทุกวัน 10:00 - 20:00 น.</span>
                        </div>
                    </div>
                </div>

                <!-- Simple Map Placeholder / Image -->
                <div style="display: flex; align-items: center; justify-content: center;">
                    <div style="background: rgba(255,255,255,0.5); border-radius: 20px; padding: 20px; width: 100%; height: 100%; min-height: 250px; display: flex; flex-direction: column; align-items: center; justify-content: center; border: 2px dashed var(--primary-light);">
                        <span style="font-size: 3rem; margin-bottom: 10px; opacity: 0.5;">🗺️</span>
                        <span style="color: var(--text-muted);">Google Maps Placeholder</span>
                        <a href="#" class="btn btn-outline" style="margin-top: 15px; border-radius: 12px;">เปิดใน Google Maps ➜</a>
                    </div>
                </div>
            </div>
        </div>
        </div>
    </div>
    
    <?php include 'includes/footer.php'; ?>
</body>
</html>
