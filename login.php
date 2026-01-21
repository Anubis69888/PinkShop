<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>เข้าสู่ระบบ - Doll Paradise</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="assets/css/modal.css">
    <link
        href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;600;800&family=Sarabun:wght@300;400;600&display=swap"
        rel="stylesheet">
    <script src="assets/js/custom-alert.js"></script>
    <style>
        body {
            font-family: 'Sarabun', 'Outfit', sans-serif;
        }
    </style>
</head>

<body>
    <?php include 'includes/header.php'; ?>

    <div class="container" style="display: flex; justify-content: center; align-items: center; min-height: 85vh;">
        <div class="glass-card"
            style="width: 100%; max-width: 440px; padding: 50px 40px; position: relative; overflow: hidden;">
            <!-- Decorative circle -->
            <div
                style="position: absolute; top: -50px; left: -50px; width: 150px; height: 150px; background: var(--primary-light); opacity: 0.2; border-radius: 50%; filter: blur(30px);">
            </div>
            <div
                style="position: absolute; bottom: -30px; right: -30px; width: 120px; height: 120px; background: var(--accent); opacity: 0.2; border-radius: 50%; filter: blur(30px);">
            </div>

            <div style="text-align: center; margin-bottom: 35px; position: relative;">
                <div
                    style="background: rgba(255,255,255,0.5); width: 80px; height: 80px; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 20px; font-size: 2.5rem; box-shadow: 0 10px 20px rgba(0,0,0,0.05); border: 2px solid rgba(255,255,255,0.8);">
                    🦄</div>
                <h2 style="font-size: 2rem; margin-bottom: 5px; color: var(--primary);">ยินดีต้อนรับ</h2>
                <p style="color: var(--text-muted);">กรุณาเข้าสู่ระบบเพื่อดำเนินการต่อ</p>
            </div>

            <form id="loginForm" method="POST" action="javascript:void(0)">
                <input type="hidden" name="action" value="login">
                <div class="form-group" style="margin-bottom: 20px; text-align: left;">
                    <label
                        style="display: block; margin-bottom: 8px; font-weight: 500; color: var(--text-main); margin-left: 5px;">ชื่อผู้ใช้</label>
                    <div style="position: relative;">
                        <span
                            style="position: absolute; left: 15px; top: 14px; font-size: 1.1rem; opacity: 0.5;">👤</span>
                        <input type="text" name="username" class="form-control"
                            style="padding-left: 45px; border-radius: 12px; height: 48px;" placeholder="Username"
                            required>
                    </div>
                </div>
                <div class="form-group" style="margin-bottom: 30px; text-align: left;">
                    <label
                        style="display: block; margin-bottom: 8px; font-weight: 500; color: var(--text-main); margin-left: 5px;">รหัสผ่าน</label>
                    <div style="position: relative;">
                        <span
                            style="position: absolute; left: 15px; top: 14px; font-size: 1.1rem; opacity: 0.5;">🔒</span>
                        <input type="password" name="password" class="form-control"
                            style="padding-left: 45px; border-radius: 12px; height: 48px;" placeholder="Password"
                            required>
                    </div>
                </div>
                <button type="submit" class="btn btn-primary"
                    style="width: 100%; padding: 14px; font-size: 1.1rem; border-radius: 12px; box-shadow: 0 8px 16px rgba(214, 123, 179, 0.4);">
                    ✨ เข้าสู่ระบบ
                </button>
            </form>

            <div style="margin-top: 30px; text-align: center; color: var(--text-muted); font-size: 0.95rem;">
                ยังไม่มีบัญชีใช่ไหม? <a href="register.php"
                    style="color: var(--primary); font-weight: 600; text-decoration: underline; text-decoration-style: dashed;">สมัครสมาชิกที่นี่</a>
            </div>
        </div>
    </div>

    <script>
        document.getElementById('loginForm').addEventListener('submit', async (e) => {
            e.preventDefault();
            const formData = new FormData(e.target);

            try {
                const response = await fetch('api/auth.php', {
                    method: 'POST',
                    body: formData
                });
                const result = await response.json();

                if (result.success) {
                    window.location.href = 'index.php';
                } else {
                    showAlert(result.message, 'error');
                }
            } catch (error) {
                console.error('Error:', error);
            }
        });
    </script>
</body>

</html>