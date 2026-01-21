<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>เลือกตัวละคร - Doll Paradise</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="assets/css/modal.css">
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;600;800&family=Sarabun:wght@300;400;600&display=swap" rel="stylesheet">
    <script src="assets/js/modal.js" defer></script>
    <style>
        body { font-family: 'Sarabun', 'Outfit', sans-serif; }
        .character-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
            gap: 20px;
            padding: 20px;
        }
        .char-card {
            background: rgba(255, 255, 255, 0.5);
            border-radius: 16px;
            padding: 10px;
            cursor: pointer;
            transition: all 0.3s ease;
            border: 2px solid transparent;
            text-align: center;
        }
        .char-card:hover {
            transform: translateY(-5px);
            background: white;
            box-shadow: 0 10px 20px rgba(0,0,0,0.1);
        }
        .char-card.selected {
            border-color: var(--primary);
            background: white;
            box-shadow: 0 0 0 4px rgba(106, 13, 173, 0.2);
        }
        .char-img {
            width: 100%;
            height: auto;
            border-radius: 12px;
            object-fit: cover;
        }
        .preview-section {
            text-align: center;
            margin-bottom: 30px;
            padding: 20px;
            background: rgba(255,255,255,0.8);
            border-radius: 20px;
            display: none; /* Hidden initially */
        }
        .preview-section.active {
            display: block;
            animation: fadeIn 0.5s ease;
        }
        @keyframes fadeIn { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }
    </style>
</head>
<body>
    <?php include 'includes/header.php'; ?>

    <div class="container">
        <div class="header-section">
            <h1>เลือกตัวละครของคุณ</h1>
            <p>เลือกคาแรคเตอร์ที่ใช่ เพื่อใช้เป็นตัวแทนของคุณ</p>
            <a href="profile.php" class="btn btn-secondary" style="margin-top: 10px;">&larr; กลับไปหน้าโปรไฟล์</a>
        </div>

        <!-- Preview Section -->
        <div id="previewSection" class="preview-section">
            <h2 style="color: var(--primary); margin-bottom: 15px;">ตัวละครที่เลือก</h2>
            <img id="previewImg" src="" alt="Selected Character" style="max-height: 300px; border-radius: 16px; box-shadow: 0 10px 30px rgba(0,0,0,0.15);">
            <div style="margin-top: 20px;">
                <button onclick="saveCharacter()" class="btn btn-primary" style="padding: 12px 30px; font-size: 1.1rem;">
                    บันทึกตัวละครนี้ ✨
                </button>
            </div>
        </div>

        <!-- Grid -->
        <div class="character-grid">
            <?php 
            // Generate grid for a01.png to a15.png
            for ($i = 1; $i <= 15; $i++) {
                $num = str_pad($i, 2, '0', STR_PAD_LEFT);
                $img = "assets/images/a$num.png";
                echo "
                <div class='char-card' onclick='selectChar(\"$img\", this)'>
                    <img src='$img' class='char-img' loading='lazy'>
                    <div style='margin-top: 10px; font-weight: bold; color: var(--text-muted);'>แบบที่ $i</div>
                </div>";
            }
            ?>
        </div>
    </div>

    <script>
        let selectedImage = null;

        function selectChar(imgSrc, element) {
            // Remove active class from all
            document.querySelectorAll('.char-card').forEach(c => c.classList.remove('selected'));
            
            // Add to clicked
            element.classList.add('selected');
            
            // Update state
            selectedImage = imgSrc;
            
            // Update preview
            const previewSection = document.getElementById('previewSection');
            const previewImg = document.getElementById('previewImg');
            
            previewImg.src = imgSrc;
            previewSection.classList.add('active');
            
            // Scroll to preview on mobile
            if (window.innerWidth < 768) {
                previewSection.scrollIntoView({ behavior: 'smooth', block: 'center' });
            }
        }

        async function saveCharacter() {
            if (!selectedImage) return;

            try {
                const response = await fetch('api/avatar.php', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/json'},
                    body: JSON.stringify({ 
                        action: 'save', 
                        config: { type: 'image', src: selectedImage } 
                    })
                });
                
                const result = await response.json();
                
                if (result.success) {
                    showModal('บันทึกตัวละครเรียบร้อยแล้ว!', 'สำเร็จ', '✨', () => {
                        window.location.href = 'profile.php';
                    });
                } else {
                    showModal(result.message || 'เกิดข้อผิดพลาด', 'แจ้งเตือน', '⚠️');
                }
            } catch (e) {
                console.error(e);
                showModal('เกิดข้อผิดพลาดในการเชื่อมต่อ', 'Error', '❌');
            }
        }
    </script>
</body>
</html>
