<?php require_once 'includes/init.php'; ?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>โปรโมชั่น - Doll Paradise</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;600;800&family=Sarabun:wght@300;400;600&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Sarabun', 'Outfit', sans-serif; }
        .promo-card {
            background: rgba(255,255,255,0.8);
            border-radius: 20px;
            padding: 30px;
            margin-bottom: 20px;
            border: 1px solid rgba(255,255,255,0.6);
            display: flex;
            align-items: center;
            gap: 30px;
            transition: transform 0.3s, box-shadow 0.3s;
        }
        .promo-card:hover {
            transform: translateY(-5px) scale(1.02);
            box-shadow: 0 15px 30px rgba(0,0,0,0.1);
        }
        .promo-tag {
            background: linear-gradient(45deg, var(--primary), var(--accent));
            color: white;
            padding: 5px 15px;
            border-radius: 50px;
            font-size: 0.9rem;
            font-weight: bold;
            display: inline-block;
            margin-bottom: 10px;
        }
        .coupon-code {
            border: 2px dashed var(--primary);
            color: var(--primary);
            padding: 10px 20px;
            border-radius: 10px;
            font-size: 1.2rem;
            font-weight: 800;
            background: var(--primary-light-10);
            cursor: pointer;
            transition: all 0.2s;
        }
        .coupon-code:hover {
            background: var(--primary);
            color: white;
        }
        @media (max-width: 768px) {
            .promo-card { flex-direction: column; text-align: center; }
        }
    </style>
</head>
<body>
    <?php include 'includes/header.php'; ?>

    <div class="container section" style="position: relative;">
        <!-- Blobs -->
        <div style="position: absolute; top: -50px; left: -50px; width: 300px; height: 300px; background: var(--secondary); opacity: 0.1; border-radius: 50%; filter: blur(80px); z-index: -1;"></div>
        <div style="position: absolute; top: 100px; right: -100px; width: 400px; height: 400px; background: var(--primary); opacity: 0.05; border-radius: 50%; filter: blur(100px); z-index: -1;"></div>

        <div class="text-center" style="margin-bottom: 50px;">
            <h1 style="font-size: 3rem; color: var(--text-heading); margin-bottom: 15px;">🔥 โปรโมชั่นสุดพิเศษ</h1>
            <p style="font-size: 1.2rem; color: var(--text-muted);">ดีลเด็ดๆ สำหรับคนรักตุ๊กตาโดยเฉพาะ</p>
        </div>

        <div class="promo-card">
            <div style="font-size: 5rem;">🎉</div>
            <div style="flex: 1;">
                <span class="promo-tag">ลูกค้าใหม่</span>
                <h2 style="margin: 0 0 10px 0; color: var(--text-heading);">ส่วนลด 15% สำหรับบิลแรก</h2>
                <p style="color: var(--text-muted);">เพียงสมัครสมาชิกและสั่งซื้อครั้งแรก ไม่มีขั้นต่ำ!</p>
            </div>
            <div class="coupon-code" onclick="copyCode(this)">NEWDOLL15</div>
        </div>

        <div class="promo-card">
            <div style="font-size: 5rem;">🚚</div>
            <div style="flex: 1;">
                <span class="promo-tag">ส่งฟรี</span>
                <h2 style="margin: 0 0 10px 0; color: var(--text-heading);">ส่งฟรีทั่วไทย</h2>
                <p style="color: var(--text-muted);">เมื่อสั่งซื้อครบ 999 บาทขึ้นไป จัดส่งด่วน EMS ฟรีทันที</p>
            </div>
            <div class="coupon-code" onclick="copyCode(this)">FREESHIP99</div>
        </div>

        <div class="promo-card">
            <div style="font-size: 5rem;">👗</div>
            <div style="flex: 1;">
                <span class="promo-tag">Bundle Set</span>
                <h2 style="margin: 0 0 10px 0; color: var(--text-heading);">ซื้อชุดตุ๊กตา 3 แถม 1</h2>
                <p style="color: var(--text-muted);">เลือกช้อปเสื้อผ้าตุ๊กตารุ่นใดก็ได้ 3 ชุด รับฟรีอีก 1 ชุดทันที</p>
            </div>
            <div class="coupon-code" onclick="copyCode(this)">B3G1FASHION</div>
        </div>

    </div>

    <?php include 'includes/footer.php'; ?>

    <script>
        function copyCode(element) {
            const code = element.innerText;
            navigator.clipboard.writeText(code);
            const originalText = element.innerText;
            element.innerText = "COPIED!";
            element.style.background = "#2ecc71";
            element.style.color = "white";
            element.style.borderColor = "#2ecc71";
            
            setTimeout(() => {
                element.innerText = originalText;
                element.style.background = "var(--primary-light-10)";
                element.style.color = "var(--primary)";
                element.style.borderColor = "var(--primary)";
            }, 2000);
        }
    </script>
</body>
</html>
