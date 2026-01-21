<?php
$current_page = basename($_SERVER['PHP_SELF']);
?>
<!-- SweetAlert2 -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">

<style>
    /* Main Content Padding - comfortably spaced from top */
    body {
        padding-top: 20px;
    }

    /* Vertical Navigation Sidebar Container */
    .nav-sidebar {
        position: fixed;
        right: 20px;
        top: 50%;
        transform: translateY(-50%);
        max-height: 85vh;
        overflow-y: auto;
        overflow-x: visible;
        display: flex;
        flex-direction: column;
        gap: 18px;
        z-index: 1000;
        transition: all 0.5s cubic-bezier(0.4, 0, 0.2, 1);
        padding: 15px 0;

        /* Custom Scrollbar */
        scrollbar-width: thin;
        scrollbar-color: rgba(255, 105, 180, 0.3) transparent;
    }

    /* Webkit Scrollbar Styling */
    .nav-sidebar::-webkit-scrollbar {
        width: 6px;
    }

    .nav-sidebar::-webkit-scrollbar-track {
        background: transparent;
    }

    .nav-sidebar::-webkit-scrollbar-thumb {
        background: rgba(255, 105, 180, 0.3);
        border-radius: 10px;
    }

    .nav-sidebar::-webkit-scrollbar-thumb:hover {
        background: rgba(255, 105, 180, 0.5);
    }

    /* Left-handed mode */
    body.left-handed .nav-sidebar {
        right: auto;
        left: 20px;
    }

    body.left-handed .nav-sidebar .nav-tooltip {
        left: auto;
        right: 70px;
        transform: translateX(-10px);
    }

    body.left-handed .nav-sidebar .nav-btn:hover .nav-tooltip {
        transform: translateX(0);
    }

    body.left-handed .nav-section-label {
        left: auto;
        right: 70px;
    }

    /* Section Label */
    .nav-section-label {
        position: absolute;
        left: -155px;
        background: rgba(0, 0, 0, 0.75);
        color: white;
        padding: 4px 12px;
        border-radius: 8px;
        font-size: 0.7rem;
        white-space: nowrap;
        font-family: 'Prompt', sans-serif;
        font-weight: 600;
        letter-spacing: 0.5px;
        text-transform: uppercase;
        opacity: 0.6;
        pointer-events: none;
    }

    /* Section Container */
    .nav-section {
        display: flex;
        flex-direction: column;
        gap: 12px;
        position: relative;
    }

    /* Navigation Button */
    .nav-btn {
        width: 60px;
        height: 60px;
        background: rgba(255, 255, 255, 0.98);
        backdrop-filter: blur(15px);
        border: 2px solid rgba(255, 255, 255, 0.95);
        border-radius: 18px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.6rem;
        cursor: pointer;
        box-shadow: 0 6px 20px rgba(0, 0, 0, 0.1);
        transition: all 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275);
        position: relative;
        text-decoration: none;
        color: var(--text-main, #333);
    }

    .nav-btn:hover {
        transform: scale(1.12) translateY(-2px);
        background: white;
        box-shadow: 0 10px 30px rgba(255, 105, 180, 0.3);
        border-color: var(--primary, #ff69b4);
    }

    .nav-btn.active {
        background: linear-gradient(135deg, #ff6b81, #ff9f43);
        /* More vibrant gradient */
        color: white;
        border: 4px solid white;
        /* Thick white border to pop from background */
        box-shadow:
            0 12px 25px rgba(255, 107, 129, 0.4),
            /* Drop shadow */
            0 0 0 4px rgba(255, 255, 255, 0.2);
        /* Outer subtle ring */
        transform: scale(1.15);
        /* Slightly larger */
        z-index: 10;
        font-size: 1.7rem;
    }

    .nav-btn.active:hover {
        transform: scale(1.2) translateY(-3px);
        box-shadow:
            0 15px 35px rgba(255, 107, 129, 0.5),
            0 0 0 4px rgba(255, 255, 255, 0.3);
    }

    /* Tooltip */
    .nav-tooltip {
        position: absolute;
        left: -155px;
        background: linear-gradient(135deg, var(--primary, #ff69b4), var(--secondary, #ff8fab));
        color: white;
        padding: 10px 18px;
        border-radius: 12px;
        font-size: 0.9rem;
        white-space: nowrap;
        opacity: 0;
        pointer-events: none;
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        transform: translateX(10px);
        font-family: 'Prompt', sans-serif;
        font-weight: 500;
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
    }

    .nav-tooltip::after {
        content: '';
        position: absolute;
        right: -8px;
        top: 50%;
        transform: translateY(-50%);
        border: 8px solid transparent;
        border-left-color: var(--secondary, #ff8fab);
    }

    body.left-handed .nav-tooltip::after {
        right: auto;
        left: -8px;
        border-left-color: transparent;
        border-right-color: var(--secondary, #ff8fab);
    }

    .nav-btn:hover .nav-tooltip {
        opacity: 1;
        transform: translateX(0);
    }

    /* Badge for cart */
    .nav-badge {
        position: absolute;
        top: -6px;
        right: -6px;
        background: linear-gradient(135deg, #ff4757, #ff6b81);
        color: white;
        font-size: 0.75rem;
        min-width: 24px;
        height: 24px;
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        border: 3px solid white;
        font-weight: bold;
        padding: 0 6px;
        box-shadow: 0 3px 10px rgba(255, 71, 87, 0.4);
        animation: pulse 2s infinite;
    }

    @keyframes pulse {

        0%,
        100% {
            transform: scale(1);
        }

        50% {
            transform: scale(1.1);
        }
    }

    /* Divider - More Prominent */
    .nav-divider {
        height: 2px;
        background: linear-gradient(90deg, transparent, rgba(255, 105, 180, 0.2), transparent);
        margin: 8px 0;
        border-radius: 1px;
    }

    /* Handedness Toggle Button */
    .handedness-toggle {
        position: fixed;
        bottom: 30px;
        right: 20px;
        width: 60px;
        height: 60px;
        background: linear-gradient(135deg, var(--primary), var(--secondary));
        border: none;
        border-radius: 50%;
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 30px;
        box-shadow: 0 6px 25px rgba(255, 105, 180, 0.45);
        z-index: 9999;
        transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
    }

    body.left-handed .handedness-toggle {
        right: auto;
        left: 20px;
    }

    .handedness-toggle:hover {
        transform: scale(1.15) rotate(15deg);
        box-shadow: 0 8px 35px rgba(255, 105, 180, 0.6);
    }

    .handedness-toggle:active {
        transform: scale(0.95);
    }

    /* Responsive */
    @media (max-width: 768px) {
        .nav-sidebar {
            gap: 15px;
            max-height: 80vh;
        }

        .nav-section {
            gap: 10px;
        }

        .nav-btn {
            width: 55px;
            height: 55px;
            font-size: 1.4rem;
        }

        .handedness-toggle {
            width: 55px;
            height: 55px;
            font-size: 26px;
        }

        .nav-tooltip {
            font-size: 0.85rem;
            padding: 8px 14px;
        }
    }

    @media (max-height: 700px) {
        .nav-sidebar {
            gap: 12px;
        }

        .nav-section {
            gap: 8px;
        }

        .nav-btn {
            width: 50px;
            height: 50px;
            font-size: 1.3rem;
        }
    }
</style>

<!-- Background -->
<link rel="stylesheet" href="assets/css/background.css">
<script src="assets/js/background.js" defer></script>

<!-- Vertical Navigation Sidebar -->
<nav class="nav-sidebar">
    <!-- Main Navigation Section -->
    <div class="nav-section">
        <span class="nav-section-label">หลัก</span>
        <a href="index.php" class="nav-btn <?php echo $current_page == 'index.php' ? 'active' : ''; ?>" title="หน้าแรก">
            🏠
            <span class="nav-tooltip">หน้าแรก</span>
        </a>

        <a href="shop.php" class="nav-btn <?php echo $current_page == 'shop.php' ? 'active' : ''; ?>" title="สินค้า">
            🛍️
            <span class="nav-tooltip">สินค้า</span>
        </a>

        <a href="promotion.php" class="nav-btn <?php echo $current_page == 'promotion.php' ? 'active' : ''; ?>"
            title="โปรโมชั่น">
            🔥
            <span class="nav-tooltip">โปรโมชั่น</span>
        </a>

        <a href="contact.php" class="nav-btn <?php echo $current_page == 'contact.php' ? 'active' : ''; ?>"
            title="ติดต่อเรา">
            💌
            <span class="nav-tooltip">ติดต่อเรา</span>
        </a>
    </div>

    <div class="nav-divider"></div>

    <!-- Actions Section -->
    <div class="nav-section">
        <span class="nav-section-label">การกระทำ</span>
        <?php if (isset($_SESSION['is_seller']) && $_SESSION['is_seller']): ?>
            <a href="add_product.php" class="nav-btn" title="ลงขายสินค้า">
                ✨
                <span class="nav-tooltip">ลงขายสินค้า</span>
            </a>
        <?php endif; ?>

        <a href="cart.php" class="nav-btn" title="ตะกร้าสินค้า">
            🛒
            <?php
            $cartCount = isset($_SESSION['cart']) ? array_sum($_SESSION['cart']) : 0;
            if ($cartCount > 0):
                ?>
                <span class="nav-badge"><?php echo $cartCount; ?></span>
            <?php endif; ?>
            <span class="nav-tooltip">ตะกร้าสินค้า</span>
        </a>

        <?php if (isset($_SESSION['user_id'])): ?>
            <a href="profile.php" class="nav-btn <?php echo $current_page == 'profile.php' ? 'active' : ''; ?>"
                title="โปรไฟล์">
                👤
                <span class="nav-tooltip">โปรไฟล์</span>
            </a>
        <?php else: ?>
            <a href="login.php" class="nav-btn" title="เข้าสู่ระบบ">
                🔐
                <span class="nav-tooltip">เข้าสู่ระบบ</span>
            </a>
        <?php endif; ?>
    </div>

    <div class="nav-divider"></div>

    <!-- Utility Section -->
    <div class="nav-section">
        <span class="nav-section-label">เครื่องมือ</span>
        <a href="#" id="btn-search" class="nav-btn" title="ค้นหาสินค้า">
            🔍
            <span class="nav-tooltip">ค้นหาสินค้า</span>
        </a>

        <a href="#" id="btn-history" class="nav-btn" title="ประวัติการเข้าชม">
            🕒
            <span class="nav-tooltip">ดูล่าสุด</span>
        </a>

        <a href="#" id="btn-scroll-top" class="nav-btn" title="กลับด้านบน">
            ⬆️
            <span class="nav-tooltip">กลับด้านบน</span>
        </a>
    </div>
</nav>

<!-- Search & History Drawers -->
<?php include 'includes/side_dock.php'; ?>

<!-- Handedness Toggle Button -->
<button class="handedness-toggle" id="handednessToggle" title="สลับตำแหน่งเมนู">
    <span id="handIcon">👉</span>
</button>

<script>
    // Handedness Toggle Functionality
    const toggle = document.getElementById('handednessToggle');
    const handIcon = document.getElementById('handIcon');
    const body = document.body;

    // Load saved preference
    const savedHandedness = localStorage.getItem('handedness');
    if (savedHandedness === 'left') {
        body.classList.add('left-handed');
        handIcon.textContent = '👈';
    }

    // Toggle on click
    toggle.addEventListener('click', function () {
        body.classList.toggle('left-handed');

        if (body.classList.contains('left-handed')) {
            handIcon.textContent = '👈';
            localStorage.setItem('handedness', 'left');

            Swal.fire({
                icon: 'success',
                title: 'เมนูย้ายไปด้านซ้ายแล้ว! 👈',
                text: 'เหมาะสำหรับคนถนัดซ้าย',
                timer: 1500,
                showConfirmButton: false,
                toast: true,
                position: 'top-end'
            });
        } else {
            handIcon.textContent = '👉';
            localStorage.setItem('handedness', 'right');

            Swal.fire({
                icon: 'success',
                title: 'เมนูย้ายไปด้านขวาแล้ว! 👉',
                text: 'เหมาะสำหรับคนถนัดขวา',
                timer: 1500,
                showConfirmButton: false,
                toast: true,
                position: 'top-end'
            });
        }
    });
</script>