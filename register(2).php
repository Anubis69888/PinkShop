<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>สมัครสมาชิก - Doll Paradise</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;600;800&family=Sarabun:wght@300;400;600&display=swap" rel="stylesheet">
    <!-- SweetAlert2 CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@sweetalert2/theme-borderless/borderless.css">
    <style>
        body { font-family: 'Sarabun', 'Outfit', sans-serif; }
        
        /* Custom SweetAlert Glassmorphism */
        div:where(.swal2-container) div:where(.swal2-popup) {
            background: rgba(255, 255, 255, 0.9) !important;
            backdrop-filter: blur(20px) !important;
            border-radius: 20px !important;
            border: 1px solid rgba(255, 255, 255, 0.5) !important;
            box-shadow: 0 15px 40px rgba(214, 123, 179, 0.2) !important;
        }
        div:where(.swal2-icon) {
            border-color: var(--primary) !important;
            color: var(--primary) !important;
        }
        div:where(.swal2-confirm) {
            background: linear-gradient(135deg, var(--primary), var(--secondary)) !important;
            border-radius: 12px !important;
            box-shadow: 0 4px 15px rgba(214, 123, 179, 0.4) !important;
        }
        div:where(.swal2-title) {
            color: var(--text-main) !important;
            font-family: 'Outfit', sans-serif !important;
        }
        div:where(.swal2-html-container) {
            color: var(--text-muted) !important;
            font-family: 'Sarabun', sans-serif !important;
        }
    </style>
</head>
<body>
    <?php include 'includes/header.php'; ?>

    <div class="container" style="display: flex; justify-content: center; align-items: center; min-height: 90vh; padding: 40px 20px;">
        <div class="glass-card" style="width: 100%; max-width: 500px; padding: 40px; position: relative; overflow: hidden;">
            <!-- Decorative circle -->
            <div style="position: absolute; top: -60px; right: -60px; width: 180px; height: 180px; background: var(--accent); opacity: 0.15; border-radius: 50%; filter: blur(40px);"></div>
            <div style="position: absolute; bottom: -40px; left: -40px; width: 140px; height: 140px; background: var(--primary); opacity: 0.15; border-radius: 50%; filter: blur(40px);"></div>

            <div style="text-align: center; margin-bottom: 30px; position: relative;">
                <div style="background: rgba(255,255,255,0.6); width: 70px; height: 70px; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 15px; font-size: 2rem; box-shadow: 0 8px 20px rgba(0,0,0,0.05); border: 2px solid white;">✨</div>
                <h2 style="font-size: 1.8rem; margin-bottom: 5px; color: var(--primary);">สมัครสมาชิก</h2>
                <p style="color: var(--text-muted);">เริ่มต้นการเดินทางในโลกตุ๊กตาของคุณ</p>
            </div>
            
            <form id="registerForm">
                <input type="hidden" name="action" value="register">
                
                <div class="form-group" style="margin-bottom: 20px; text-align: left;">
                    <label style="display: block; margin-bottom: 8px; font-weight: 500; color: var(--text-main); margin-left: 5px;">ชื่อผู้ใช้</label>
                    <div style="position: relative;">
                        <span style="position: absolute; left: 15px; top: 14px; font-size: 1.1rem; opacity: 0.5;">👤</span>
                        <input type="text" name="username" class="form-control" style="padding-left: 45px; border-radius: 12px; height: 48px;" placeholder="Username" required>
                    </div>
                </div>
                
                <div class="form-group" style="margin-bottom: 20px; text-align: left;">
                    <label style="display: block; margin-bottom: 8px; font-weight: 500; color: var(--text-main); margin-left: 5px;">ชื่อ-นามสกุล</label>
                    <div style="position: relative;">
                        <span style="position: absolute; left: 15px; top: 14px; font-size: 1.1rem; opacity: 0.5;">📝</span>
                        <input type="text" name="fullname" class="form-control" style="padding-left: 45px; border-radius: 12px; height: 48px;" placeholder="Full Name" required>
                    </div>
                </div>
                
                <div class="form-group" style="margin-bottom: 20px; text-align: left;">
                    <label style="display: block; margin-bottom: 8px; font-weight: 500; color: var(--text-main); margin-left: 5px;">ชื่อ-นามสกุล (ภาษาอังกฤษ)</label>
                    <div style="position: relative;">
                        <span style="position: absolute; left: 15px; top: 14px; font-size: 1.1rem; opacity: 0.5;">🔤</span>
                        <input type="text" name="fullname_en" class="form-control" style="padding-left: 45px; border-radius: 12px; height: 48px;" placeholder="Full Name (English)" required>
                    </div>
                </div>

                <div class="form-group" style="margin-bottom: 25px; text-align: left;">
                    <label style="display: block; margin-bottom: 12px; font-weight: 600; color: var(--text-main); margin-left: 5px;">
                        รูปถ่ายบัตรประชาชน <span style="font-weight: 400; opacity: 0.7; font-size: 0.9em;">(สำหรับยืนยันตัวตน)</span>
                    </label>
                    
                    <div class="custom-file-upload" style="position: relative;">
                        <input type="file" name="id_card_image" id="id_card_image" class="form-control" style="display: none;" accept="image/*" required>
                        <label for="id_card_image" style="display: flex; flex-direction: column; align-items: center; justify-content: center; padding: 30px 20px; border: 2px dashed rgba(214, 123, 179, 0.5); border-radius: 16px; background: rgba(255, 255, 255, 0.4); cursor: pointer; transition: all 0.3s ease;">
                            <div style="font-size: 2.5rem; margin-bottom: 10px; filter: drop-shadow(0 4px 6px rgba(0,0,0,0.1));">🆔</div>
                            <span id="file-label" style="color: var(--primary); font-weight: 600;">คลิกเพื่ออัปโหลดรูปภาพ</span>
                            <span style="font-size: 0.85rem; color: var(--text-muted); margin-top: 5px;">หรือลากไฟล์มาวางที่นี่</span>
                        </label>
                    </div>
                    <small style="color: var(--text-muted); font-size: 0.85rem; margin-top: 8px; display: flex; align-items: center; gap: 5px;">
                        <span>✨</span> ระบบ AI จะตรวจสอบชื่อให้ตรงกับบัตรโดยอัตโนมัติ
                    </small>
                </div>

                <script>
                    // Custom File Input Logic
                    const fileInput = document.getElementById('id_card_image');
                    const fileLabel = document.getElementById('file-label');
                    const uploadBox = document.querySelector('label[for="id_card_image"]');

                    fileInput.addEventListener('change', (e) => {
                        if (e.target.files.length > 0) {
                            const fileName = e.target.files[0].name;
                            fileLabel.innerHTML = `✅ ${fileName}`;
                            fileLabel.style.color = '#2ecc71'; // Green for success
                            uploadBox.style.background = 'rgba(255, 255, 255, 0.8)';
                            uploadBox.style.borderColor = '#2ecc71';
                        }
                    });

                    // simple hover effect
                    uploadBox.addEventListener('dragover', (e) => {
                         e.preventDefault();
                         uploadBox.style.background = 'rgba(255, 255, 255, 0.9)';
                    });
                    uploadBox.addEventListener('dragleave', (e) => {
                         e.preventDefault();
                         uploadBox.style.background = 'rgba(255, 255, 255, 0.4)';
                    });
                     uploadBox.addEventListener('drop', (e) => {
                        e.preventDefault();
                        fileInput.files = e.dataTransfer.files;
                        // Trigger change event manually
                        const event = new Event('change');
                        fileInput.dispatchEvent(event);
                    });
                </script>

                <div class="form-group" style="margin-bottom: 20px; text-align: left;">
                    <label style="display: block; margin-bottom: 8px; font-weight: 500; color: var(--text-main); margin-left: 5px;">เบอร์โทรศัพท์</label>
                    <div style="position: relative;">
                        <span style="position: absolute; left: 15px; top: 14px; font-size: 1.1rem; opacity: 0.5;">📞</span>
                        <input type="tel" name="phone" class="form-control" style="padding-left: 45px; border-radius: 12px; height: 48px;" placeholder="Phone Number (10 digits)" pattern="[0-9]{10}" maxlength="10" title="กรุณากรอกเบอร์โทรศัพท์ 10 หลัก" required>
                    </div>
                </div>

                <div class="form-group" style="margin-bottom: 20px; text-align: left;">
                    <label style="display: block; margin-bottom: 8px; font-weight: 500; color: var(--text-main); margin-left: 5px;">ที่อยู่จัดส่ง</label>
                    
                    <div style="position: relative; margin-bottom: 15px;">
                        <span style="position: absolute; left: 15px; top: 14px; font-size: 1.1rem; opacity: 0.5;">🏠</span>
                        <textarea name="address_details" class="form-control" rows="2" style="padding-left: 45px; padding-top: 12px; border-radius: 12px;" placeholder="บ้านเลขที่, หมู่, ซอย, ถนน" required></textarea>
                    </div>

                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin-bottom: 15px;">
                        <div style="position: relative;">
                            <span style="position: absolute; left: 10px; top: 14px; font-size: 1.1rem; opacity: 0.5;">🏙️</span>
                            <input type="text" name="tambon" class="form-control" style="padding-left: 40px; border-radius: 12px; height: 48px;" placeholder="ตำบล/แขวง" required>
                        </div>
                        <div style="position: relative;">
                            <span style="position: absolute; left: 10px; top: 14px; font-size: 1.1rem; opacity: 0.5;">🏢</span>
                            <input type="text" name="amphoe" class="form-control" style="padding-left: 40px; border-radius: 12px; height: 48px;" placeholder="อำเภอ/เขต" required>
                        </div>
                    </div>

                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                        <div style="position: relative;">
                            <span style="position: absolute; left: 10px; top: 14px; font-size: 1.1rem; opacity: 0.5;">🗺️</span>
                            <input type="text" name="province" class="form-control" style="padding-left: 40px; border-radius: 12px; height: 48px;" placeholder="จังหวัด" required>
                        </div>
                        <div style="position: relative;">
                            <span style="position: absolute; left: 10px; top: 14px; font-size: 1.1rem; opacity: 0.5;">📮</span>
                            <input type="text" name="zipcode" class="form-control" style="padding-left: 40px; border-radius: 12px; height: 48px;" placeholder="รหัสไปรษณีย์" pattern="[0-9]{5}" maxlength="5" required>
                        </div>
                    </div>
                </div>

                <div class="form-group" style="margin-bottom: 30px; text-align: left;">
                    <label style="display: block; margin-bottom: 8px; font-weight: 500; color: var(--text-main); margin-left: 5px;">รหัสผ่าน</label>
                    <div style="position: relative;">
                        <span style="position: absolute; left: 15px; top: 14px; font-size: 1.1rem; opacity: 0.5;">🔒</span>
                        <input type="password" name="password" class="form-control" style="padding-left: 45px; border-radius: 12px; height: 48px;" placeholder="Password" required>
                    </div>
                </div>

                <button type="submit" class="btn btn-primary" style="width: 100%; padding: 14px; font-size: 1.1rem; border-radius: 12px; box-shadow: 0 8px 16px rgba(214, 123, 179, 0.4);">
                    สร้างบัญชีใหม่ ➜
                </button>
            </form>
            
            <div style="margin-top: 25px; text-align: center; color: var(--text-muted); font-size: 0.95rem;">
                เป็นสมาชิกอยู่แล้ว? <a href="login.php" style="color: var(--primary); font-weight: 600; text-decoration: underline; text-decoration-style: dashed;">เข้าสู่ระบบที่นี่</a>
            </div>
        </div>
    </div>

    <!-- SweetAlert2 JS -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        document.getElementById('registerForm').addEventListener('submit', async (e) => {
            e.preventDefault();
            
            // Show loading state
            Swal.fire({
                title: 'กำลังตรวจสอบ...',
                text: 'ระบบ AI กำลังวิเคราะห์ข้อมูลและยืนยันตัวตนของคุณ',
                icon: 'info',
                allowOutsideClick: false,
                showConfirmButton: false,
                didOpen: () => {
                    Swal.showLoading();
                }
            });

            const formData = new FormData(e.target);
            
            try {
                const response = await fetch('api/auth.php', {
                    method: 'POST',
                    body: formData
                });
                const result = await response.json();
                
                if (result.success) {
                    await Swal.fire({
                        title: 'สมัครสมาชิกสำเร็จ!',
                        text: 'ยินดีต้อนรับสู่ Doll Paradise กรุณาเข้าสู่ระบบเพื่อเริ่มต้น',
                        icon: 'success',
                        confirmButtonText: 'เข้าสู่ระบบ ➜'
                    });
                    window.location.href = 'login.php';
                } else {
                    // Check if error is AI related (usually starts with AI-)
                    let iconType = 'error';
                    let titleText = 'เกิดข้อผิดพลาด';
                    
                    if (result.message.includes('AI')) {
                        iconType = 'warning';
                        titleText = 'AI ตรวจพบความผิดปกติ';
                    }

                    Swal.fire({
                        title: titleText,
                        text: result.message,
                        icon: iconType,
                        confirmButtonText: 'ตกลง'
                    });
                }
            } catch (error) {
                console.error('Error:', error);
                Swal.fire({
                    title: 'เกิดข้อผิดพลาด',
                    text: 'ไม่สามารถติดต่อเซิร์ฟเวอร์ได้ กรุณาลองใหม่อีกครั้ง',
                    icon: 'error',
                    confirmButtonText: 'ปิด'
                });
            }
        });
    </script>
</body>
</html>
