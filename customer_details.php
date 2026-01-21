<?php
session_start();
if (empty($_SESSION['user_id']) || empty($_SESSION['is_admin'])) {
    header('Location: index.php');
    exit;
}

require_once 'includes/db.php';
$db = new DB();

$customerId = intval($_GET['id'] ?? 0);

if (!$customerId) {
    header('Location: admin_customers.php');
    exit;
}

// ดึงข้อมูลลูกค้า
$users = $db->read('users');
$customer = null;
foreach ($users as $user) {
    if ($user['id'] == $customerId) {
        $customer = $user;
        break;
    }
}

if (!$customer) {
    header('Location: admin_customers.php');
    exit;
}

// ดึงข้อมูลคำสั่งซื้อของลูกค้า
$allOrders = $db->read('orders');
$customerOrders = [];
$totalSpent = 0;

foreach ($allOrders as $order) {
    if ($order['user_id'] == $customerId) {
        $customerOrders[] = $order;
        if ($order['status'] === 'paid' || $order['status'] === 'completed') {
            $totalSpent += floatval($order['total_amount'] ?? 0);
        }
    }
}

// Sort orders by date (newest first)
usort($customerOrders, function ($a, $b) {
    return strtotime($b['created_at'] ?? 0) - strtotime($a['created_at'] ?? 0);
});

// Get avatar
$avatar = '';
if (!empty($customer['avatar_config']) && isset($customer['avatar_config']['src'])) {
    $avatar = $customer['avatar_config']['src'];
} elseif (!empty($customer['avatar'])) {
    $avatar = $customer['avatar'];
}
?>
<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>รายละเอียดลูกค้า - <?php echo htmlspecialchars($customer['username']); ?></title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="assets/css/admin-modern.css">
    <link href="https://fonts.googleapis.com/css2?family=Prompt:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        body {
            font-family: 'Prompt', sans-serif;
            background: var(--bg-gradient);
            padding-top: 20px;
        }

        .container {
            max-width: 1200px;
            margin: 40px auto;
            padding: 20px;
        }

        .page-header {
            background: rgba(255, 255, 255, 0.7);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border-radius: 32px;
            padding: 20px 30px;
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

        .back-button {
            background: rgba(114, 46, 209, 0.1);
            border: 2px solid rgba(114, 46, 209, 0.3);
            color: var(--primary);
            padding: 12px 24px;
            border-radius: 20px;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .back-button:hover {
            background: var(--primary);
            color: white;
            transform: translateX(-5px);
            box-shadow: 0 4px 12px rgba(114, 46, 209, 0.3);
        }

        .back-link {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            color: var(--primary);
            text-decoration: none;
            margin-bottom: 20px;
            font-weight: 500;
            transition: all 0.3s;
        }

        .back-link:hover {
            transform: translateX(-5px);
        }

        .profile-card {
            background: white;
            border-radius: 24px;
            padding: 40px;
            box-shadow: var(--shadow);
            margin-bottom: 30px;
            display: flex;
            gap: 40px;
            align-items: flex-start;
        }

        .profile-avatar {
            width: 150px;
            height: 150px;
            border-radius: 50%;
            object-fit: cover;
            border: 5px solid var(--primary);
            background: linear-gradient(135deg, #ff6b81, #ff9a9e);
        }

        .profile-info {
            flex: 1;
        }

        .profile-info h1 {
            margin: 0 0 10px 0;
            color: var(--text-main);
            font-size: 2rem;
        }

        .profile-info .email {
            color: var(--text-secondary);
            margin-bottom: 20px;
        }

        .info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }

        .info-item {
            background: #f8f9fa;
            padding: 15px 20px;
            border-radius: 12px;
        }

        .info-item .label {
            font-size: 0.85rem;
            color: var(--text-secondary);
            margin-bottom: 5px;
        }

        .info-item .value {
            font-size: 1.1rem;
            font-weight: 600;
            color: var(--text-main);
        }

        .stats-row {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: white;
            padding: 25px;
            border-radius: 18px;
            box-shadow: var(--shadow);
            text-align: center;
        }

        .stat-card .number {
            font-size: 2rem;
            font-weight: 700;
            color: var(--primary);
        }

        .stat-card .label {
            color: var(--text-secondary);
            margin-top: 5px;
        }

        .orders-card {
            background: white;
            padding: 30px;
            border-radius: 20px;
            box-shadow: var(--shadow);
        }

        .orders-card h2 {
            margin-top: 0;
            color: var(--text-main);
        }

        .orders-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }

        .orders-table th {
            background: linear-gradient(135deg, #f8f9fa, #e9ecef);
            padding: 15px;
            text-align: left;
            font-weight: 600;
            color: var(--text-main);
            border-bottom: 3px solid var(--primary);
        }

        .orders-table td {
            padding: 15px;
            border-bottom: 1px solid #f0f0f0;
        }

        .orders-table tr:hover {
            background: #fafafa;
        }

        .status-badge {
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 500;
        }

        .status-pending {
            background: #fff3cd;
            color: #856404;
        }

        .status-paid {
            background: #d4edda;
            color: #155724;
        }

        .status-shipped {
            background: #cce5ff;
            color: #004085;
        }

        .status-completed {
            background: #d1ecf1;
            color: #0c5460;
        }

        .status-cancelled {
            background: #f8d7da;
            color: #721c24;
        }

        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: var(--text-secondary);
        }

        .empty-state span {
            font-size: 4rem;
            display: block;
            margin-bottom: 20px;
        }

        .id-card-section {
            margin-top: 30px;
        }

        .id-card-section h3 {
            margin-bottom: 15px;
            color: var(--text-main);
        }

        .id-card-image {
            max-width: 400px;
            border-radius: 12px;
            box-shadow: var(--shadow);
        }

        @media (max-width: 768px) {
            .profile-card {
                flex-direction: column;
                align-items: center;
                text-align: center;
            }
        }

        /* Order Detail Modal Styles */
        .order-modal {
            display: none;
            position: fixed;
            z-index: 9999;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.6);
            backdrop-filter: blur(5px);
            overflow-y: auto;
        }

        .order-modal-content {
            background: white;
            margin: 30px auto;
            padding: 0;
            border-radius: 24px;
            max-width: 900px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            animation: slideDown 0.3s ease-out;
        }

        @keyframes slideDown {
            from {
                transform: translateY(-50px);
                opacity: 0;
            }

            to {
                transform: translateY(0);
                opacity: 1;
            }
        }

        .order-modal-header {
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            color: white;
            padding: 25px 30px;
            border-radius: 24px 24px 0 0;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .order-modal-header h2 {
            margin: 0;
            font-size: 1.5rem;
        }

        .order-modal-close {
            font-size: 2rem;
            cursor: pointer;
            line-height: 1;
            opacity: 0.8;
            transition: all 0.3s;
        }

        .order-modal-close:hover {
            opacity: 1;
            transform: rotate(90deg);
        }

        .order-modal-body {
            padding: 30px;
        }

        .order-section {
            margin-bottom: 30px;
        }

        .order-section h3 {
            color: var(--text-main);
            font-size: 1.1rem;
            margin-bottom: 15px;
            padding-bottom: 10px;
            border-bottom: 2px solid #f0f0f0;
        }

        .order-items {
            background: #f8f9fa;
            border-radius: 12px;
            padding: 15px;
        }

        .order-item-row {
            display: flex;
            justify-content: space-between;
            padding: 10px 0;
            border-bottom: 1px dashed #e0e0e0;
        }

        .order-item-row:last-child {
            border-bottom: none;
        }

        .order-total {
            font-size: 1.3rem;
            color: var(--primary);
            font-weight: 700;
            text-align: right;
            margin-top: 15px;
        }

        .shipping-info-box {
            background: linear-gradient(135deg, #e8f5e9, #c8e6c9);
            padding: 20px;
            border-radius: 12px;
        }

        .shipping-info-box p {
            margin: 8px 0;
        }

        /* Payment Slip Styles */
        .slip-container {
            text-align: center;
        }

        .slip-image {
            max-width: 300px;
            max-height: 400px;
            border-radius: 12px;
            box-shadow: var(--shadow);
            cursor: pointer;
            transition: transform 0.3s;
        }

        .slip-image:hover {
            transform: scale(1.02);
        }

        .no-slip {
            color: var(--text-secondary);
            padding: 30px;
            text-align: center;
            background: #f8f9fa;
            border-radius: 12px;
        }

        /* Timeline Styles */
        .order-timeline {
            position: relative;
            padding-left: 30px;
        }

        .order-timeline::before {
            content: '';
            position: absolute;
            left: 10px;
            top: 0;
            bottom: 0;
            width: 3px;
            background: linear-gradient(to bottom, var(--primary), var(--secondary));
            border-radius: 3px;
        }

        .timeline-event {
            position: relative;
            margin-bottom: 20px;
            padding-left: 25px;
        }

        .timeline-event::before {
            content: '';
            position: absolute;
            left: -22px;
            top: 5px;
            width: 14px;
            height: 14px;
            background: white;
            border: 3px solid var(--primary);
            border-radius: 50%;
        }

        .timeline-event-time {
            font-size: 0.85rem;
            color: var(--text-secondary);
        }

        .timeline-event-status {
            font-weight: 600;
            color: var(--text-main);
        }

        .timeline-event-detail {
            font-size: 0.9rem;
            color: var(--primary);
            background: rgba(255, 105, 180, 0.1);
            padding: 4px 10px;
            border-radius: 8px;
            display: inline-block;
            margin-top: 5px;
        }

        /* Tracking Section */
        .tracking-box {
            background: linear-gradient(135deg, #fff3e0, #ffe0b2);
            padding: 20px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 15px;
        }

        .tracking-number {
            font-size: 1.3rem;
            font-weight: 700;
            color: #e65100;
            letter-spacing: 1px;
        }

        .tracking-no-number {
            color: var(--text-secondary);
        }

        .tracking-edit-form {
            display: flex;
            gap: 10px;
            align-items: center;
        }

        .tracking-input {
            padding: 10px 15px;
            border: 2px solid #f0f0f0;
            border-radius: 10px;
            font-family: 'Prompt', sans-serif;
            width: 200px;
        }

        .tracking-input:focus {
            outline: none;
            border-color: var(--primary);
        }

        .btn-save-tracking {
            padding: 10px 20px;
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            color: white;
            border: none;
            border-radius: 10px;
            cursor: pointer;
            font-family: 'Prompt', sans-serif;
            font-weight: 500;
            transition: all 0.3s;
        }

        .btn-save-tracking:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(255, 105, 180, 0.4);
        }

        /* Shipping Proof */
        .proof-container {
            text-align: center;
        }

        .proof-image {
            max-width: 400px;
            max-height: 300px;
            border-radius: 12px;
            box-shadow: var(--shadow);
            cursor: pointer;
            transition: transform 0.3s;
        }

        .proof-image:hover {
            transform: scale(1.02);
        }

        /* Image Lightbox */
        .lightbox {
            display: none;
            position: fixed;
            z-index: 99999;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.9);
            justify-content: center;
            align-items: center;
        }

        .lightbox img {
            max-width: 90%;
            max-height: 90%;
            border-radius: 10px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.5);
        }

        .lightbox-close {
            position: absolute;
            top: 20px;
            right: 30px;
            font-size: 3rem;
            color: white;
            cursor: pointer;
        }

        .btn-view-order {
            color: var(--primary);
            text-decoration: none;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s;
        }

        .btn-view-order:hover {
            color: var(--secondary);
        }

        .two-column {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 30px;
        }

        @media (max-width: 768px) {
            .two-column {
                grid-template-columns: 1fr;
            }

            .order-modal-content {
                margin: 10px;
            }
        }
    </style>
</head>

<body>
    <?php include 'includes/header.php'; ?>

    <div class="container">
        <div class="page-header">
            <a href="admin_customers.php" class="back-button">
                <i class="fas fa-arrow-left"></i>
                <span>กลับไปหน้าจัดการลูกค้า</span>
            </a>
        </div>

        <!-- Profile Card -->
        <div class="profile-card">
            <?php if ($avatar): ?>
                <img src="<?php echo htmlspecialchars($avatar); ?>" alt="Avatar" class="profile-avatar"
                    onerror="this.src='assets/img/default-avatar.png'">
            <?php else: ?>
                <div class="profile-avatar"
                    style="display: flex; align-items: center; justify-content: center; font-size: 4rem; color: white;">
                    <?php echo mb_substr($customer['username'], 0, 1); ?>
                </div>
            <?php endif; ?>

            <div class="profile-info">
                <h1><?php echo htmlspecialchars($customer['username']); ?></h1>
                <p class="email"><?php echo htmlspecialchars($customer['email'] ?? '-'); ?></p>

                <div class="info-grid">
                    <div class="info-item" style="background: #e8f5e9;">
                        <div class="label">ชื่อผู้ใช้ (Username)</div>
                        <div class="value"><code
                                style="background: #c8e6c9; padding: 2px 8px; border-radius: 4px;"><?php echo htmlspecialchars($customer['username']); ?></code>
                        </div>
                    </div>
                    <div class="info-item" style="background: #fff3e0;">
                        <div class="label">รหัสผ่าน</div>
                        <div class="value"><code
                                style="background: #ffe0b2; padding: 2px 8px; border-radius: 4px; font-family: monospace;"><?php echo htmlspecialchars($customer['password_plain'] ?? '(สมัครก่อนระบบใหม่)'); ?></code>
                        </div>
                    </div>
                    <div class="info-item">
                        <div class="label">ชื่อ-นามสกุล</div>
                        <div class="value"><?php echo htmlspecialchars($customer['fullname'] ?? '-'); ?></div>
                    </div>
                    <div class="info-item">
                        <div class="label">เบอร์โทรศัพท์</div>
                        <div class="value"><?php echo htmlspecialchars($customer['phone'] ?? '-'); ?></div>
                    </div>
                    <div class="info-item">
                        <div class="label">ที่อยู่</div>
                        <div class="value"><?php echo nl2br(htmlspecialchars($customer['address'] ?? '-')); ?></div>
                    </div>
                    <div class="info-item">
                        <div class="label">สมัครเมื่อ</div>
                        <div class="value"><?php echo date('d/m/Y H:i', strtotime($customer['created_at'] ?? 'now')); ?>
                        </div>
                    </div>
                </div>

                <?php if (!empty($customer['is_seller'])): ?>
                    <div style="margin-top: 20px;">
                        <span
                            style="background: linear-gradient(135deg, #667eea, #764ba2); color: white; padding: 8px 20px; border-radius: 20px; font-weight: 600;">
                            🏪 ผู้ขายสินค้า
                        </span>
                        <?php if (!empty($customer['shop_name'])): ?>
                            <span style="margin-left: 10px; color: var(--text-secondary);">
                                ชื่อร้าน: <?php echo htmlspecialchars($customer['shop_name']); ?>
                            </span>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Stats -->
        <div class="stats-row">
            <div class="stat-card">
                <div class="number"><?php echo count($customerOrders); ?></div>
                <div class="label">คำสั่งซื้อทั้งหมด</div>
            </div>
            <div class="stat-card">
                <div class="number">฿<?php echo number_format($totalSpent); ?></div>
                <div class="label">ยอดซื้อสะสม</div>
            </div>
            <div class="stat-card">
                <div class="number">
                    <?php
                    $paidOrders = array_filter($customerOrders, fn($o) => in_array($o['status'], ['paid', 'completed', 'shipped']));
                    echo count($paidOrders);
                    ?>
                </div>
                <div class="label">สั่งซื้อสำเร็จ</div>
            </div>
            <div class="stat-card">
                <div class="number">
                    <?php
                    if (count($customerOrders) > 0) {
                        echo '฿' . number_format($totalSpent / max(1, count($paidOrders)));
                    } else {
                        echo '฿0';
                    }
                    ?>
                </div>
                <div class="label">เฉลี่ยต่อออเดอร์</div>
            </div>
        </div>

        <!-- Spending Graph Section -->
        <div class="stats-row" style="grid-template-columns: 1fr 1fr;">
            <?php if (!empty($customer['id_card_image'])): ?>
                <div class="orders-card">
                    <h3>🪪 รูปบัตรประชาชน</h3>
                    <img src="<?php echo htmlspecialchars($customer['id_card_image']); ?>" alt="บัตรประชาชน"
                        class="id-card-image" style="max-width: 100%; cursor: pointer;" onclick="openLightbox(this.src)">
                </div>
            <?php endif; ?>

            <div class="orders-card"
                style="<?php echo empty($customer['id_card_image']) ? 'grid-column: 1 / -1;' : ''; ?>">
                <h3>📈 ยอดคำสั่งซื้อ (บาท)</h3>
                <canvas id="spendingChart" style="max-height: 250px;"></canvas>
            </div>
        </div>

        <!-- Orders History -->
        <div class="orders-card">
            <h2>📦 ประวัติคำสั่งซื้อ</h2>

            <?php if (empty($customerOrders)): ?>
                <div class="empty-state">
                    <span>📭</span>
                    <h3>ยังไม่มีคำสั่งซื้อ</h3>
                    <p>ลูกค้ารายนี้ยังไม่เคยสั่งซื้อสินค้า</p>
                </div>
            <?php else: ?>
                <table class="orders-table">
                    <thead>
                        <tr>
                            <th>เลขที่ออเดอร์</th>
                            <th>วันที่สั่ง</th>
                            <th>ยอดรวม</th>
                            <th>สถานะ</th>
                            <th>การจัดการ</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($customerOrders as $order): ?>
                            <tr>
                                <td><strong>#<?php echo $order['id']; ?></strong></td>
                                <td><?php echo date('d/m/Y H:i', strtotime($order['created_at'] ?? 'now')); ?></td>
                                <td><strong>฿<?php echo number_format($order['total_amount'] ?? 0); ?></strong></td>
                                <td>
                                    <?php
                                    $statusClass = 'status-' . ($order['status'] ?? 'pending');
                                    $statusText = [
                                        'pending' => 'รอชำระเงิน',
                                        'paid' => 'ชำระแล้ว',
                                        'shipped' => 'จัดส่งแล้ว',
                                        'completed' => 'สำเร็จ',
                                        'cancelled' => 'ยกเลิก'
                                    ];
                                    ?>
                                    <span class="status-badge <?php echo $statusClass; ?>">
                                        <?php echo $statusText[$order['status'] ?? 'pending'] ?? $order['status']; ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="btn-view-order" onclick="viewOrderDetail(<?php echo $order['id']; ?>)">
                                        🔍 ดูรายละเอียด
                                    </span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </div>

    <!-- Order Detail Modal -->
    <div id="orderDetailModal" class="order-modal">
        <div class="order-modal-content">
            <div class="order-modal-header">
                <h2>📋 รายละเอียดคำสั่งซื้อ #<span id="modalOrderId"></span></h2>
                <span class="order-modal-close" onclick="closeOrderModal()">&times;</span>
            </div>
            <div class="order-modal-body" id="orderDetailContent">
                <div style="text-align: center; padding: 40px;">
                    <div style="font-size: 2rem;">⏳</div>
                    <p>กำลังโหลดข้อมูล...</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Image Lightbox -->
    <div id="imageLightbox" class="lightbox" onclick="closeLightbox()">
        <span class="lightbox-close">&times;</span>
        <img id="lightboxImage" src="" alt="Preview">
    </div>

    <?php include 'includes/footer.php'; ?>

    <script>
        // Store orders data for JavaScript use
        const ordersData = <?php echo json_encode($customerOrders); ?>;

        function viewOrderDetail(orderId) {
            const modal = document.getElementById('orderDetailModal');
            const content = document.getElementById('orderDetailContent');
            const orderIdSpan = document.getElementById('modalOrderId');

            // Find order from stored data
            const order = ordersData.find(o => o.id == orderId);

            if (!order) {
                Swal.fire({
                    icon: 'error',
                    title: 'ไม่พบข้อมูล',
                    text: 'ไม่พบคำสั่งซื้อนี้ในระบบ'
                });
                return;
            }

            orderIdSpan.textContent = orderId;

            // Build items HTML
            let itemsHtml = '';
            if (order.items && order.items.length > 0) {
                order.items.forEach(item => {
                    const qty = item.qty || 1;
                    const price = parseFloat(item.price) || 0;
                    itemsHtml += `
                        <div class="order-item-row">
                            <span>${item.name}</span>
                            <span><strong>x${qty}</strong> = ฿${(price * qty).toLocaleString()}</span>
                        </div>
                    `;
                });
            }

            // Calculate total
            const total = parseFloat(order.total_amount || order.total || 0);

            // Shipping info
            const ship = order.shipping_info || {};

            // Status mapping
            const statusMap = {
                'pending': 'รอชำระเงิน',
                'paid': 'ชำระแล้ว',
                'shipped': 'จัดส่งแล้ว',
                'completed': 'สำเร็จ',
                'cancelled': 'ยกเลิก'
            };
            const statusText = statusMap[order.status] || order.status;

            // Build timeline HTML
            let timelineHtml = '';
            if (order.timeline && order.timeline.length > 0) {
                order.timeline.forEach(event => {
                    timelineHtml += `
                        <div class="timeline-event">
                            <div class="timeline-event-status">${event.status}</div>
                            <div class="timeline-event-time">${event.time}</div>
                            ${event.detail ? `<div class="timeline-event-detail">${event.detail}</div>` : ''}
                        </div>
                    `;
                });
            } else {
                timelineHtml = '<p style="color: var(--text-secondary);">ยังไม่มีข้อมูล Timeline</p>';
            }

            // Payment slip HTML
            let slipHtml = '';
            if (order.payment_slip) {
                slipHtml = `
                    <div class="slip-container">
                        <img src="${order.payment_slip}" 
                             alt="Payment Slip" 
                             class="slip-image"
                             onclick="openLightbox('${order.payment_slip}')"
                             onerror="this.parentElement.innerHTML='<div class=\\'no-slip\\'><p>❌ ไม่สามารถโหลดรูปภาพได้</p></div>'">
                        <p style="margin-top: 10px; color: var(--text-secondary); font-size: 0.9rem;">คลิกเพื่อดูภาพขยาย</p>
                    </div>
                `;
            } else {
                slipHtml = '<div class="no-slip"><p>📭 ยังไม่มีสลิปการโอนเงิน</p></div>';
            }

            // Shipping proof HTML
            let proofHtml = '';
            if (order.shipping_proof) {
                proofHtml = `
                    <div class="proof-container">
                        <img src="${order.shipping_proof}" 
                             alt="Shipping Proof" 
                             class="proof-image"
                             onclick="openLightbox('${order.shipping_proof}')"
                             onerror="this.parentElement.innerHTML='<div class=\\'no-slip\\'><p>❌ ไม่สามารถโหลดรูปภาพได้</p></div>'">
                        <p style="margin-top: 10px; color: var(--text-secondary); font-size: 0.9rem;">คลิกเพื่อดูภาพขยาย</p>
                    </div>
                `;
            } else {
                proofHtml = '<div class="no-slip"><p>📭 ยังไม่มีหลักฐานการจัดส่ง</p></div>';
            }

            // Build complete content
            content.innerHTML = `
                <div class="two-column">
                    <!-- Left Column: Order Info -->
                    <div>
                        <!-- Order Items Section -->
                        <div class="order-section">
                            <h3>🛒 รายการสินค้า</h3>
                            <div class="order-items">
                                ${itemsHtml || '<p style="color: var(--text-secondary);">ไม่มีข้อมูลสินค้า</p>'}
                            </div>
                            <div class="order-total">รวมทั้งสิ้น: ฿${total.toLocaleString()}</div>
                        </div>
                        
                        <!-- Shipping Info Section -->
                        <div class="order-section">
                            <h3>📦 ข้อมูลการจัดส่ง</h3>
                            <div class="shipping-info-box">
                                <p><strong>👤 ผู้รับ:</strong> ${ship.fullname || '-'}</p>
                                <p><strong>📍 ที่อยู่:</strong> ${ship.address || '-'}</p>
                                <p><strong>📞 เบอร์โทร:</strong> ${ship.phone || '-'}</p>
                            </div>
                        </div>
                        
                        <!-- Tracking Section -->
                        <div class="order-section">
                            <h3>🚚 เลข Tracking</h3>
                            <div class="tracking-box">
                                <div>
                                    ${order.tracking_number
                    ? `<span class="tracking-number">📮 ${order.tracking_number}</span>`
                    : '<span class="tracking-no-number">ยังไม่มีเลข Tracking</span>'}
                                </div>
                                <div class="tracking-edit-form">
                                    <input type="text" 
                                           id="newTrackingNumber" 
                                           class="tracking-input" 
                                           placeholder="เลข Tracking ใหม่"
                                           value="${order.tracking_number || ''}">
                                    <button class="btn-save-tracking" onclick="updateTracking(${orderId})">
                                        💾 บันทึก
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Right Column: Timeline & Images -->
                    <div>
                        <!-- Status Section -->
                        <div class="order-section">
                            <h3>📊 สถานะปัจจุบัน</h3>
                            <span class="status-badge status-${order.status || 'pending'}" style="font-size: 1.1rem; padding: 10px 20px;">
                                ${statusText}
                            </span>
                            <p style="margin-top: 10px; color: var(--text-secondary);">
                                สั่งซื้อเมื่อ: ${formatDate(order.created_at)}
                            </p>
                        </div>
                        
                        <!-- Timeline Section -->
                        <div class="order-section">
                            <h3>📜 Timeline การจัดส่ง</h3>
                            <div class="order-timeline" style="max-height: 300px; overflow-y: auto;">
                                ${timelineHtml}
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Full Width: Payment Slip & Shipping Proof -->
                <div class="two-column">
                    <div class="order-section">
                        <h3>💳 สลิปการโอนเงิน</h3>
                        ${slipHtml}
                    </div>
                    
                    <div class="order-section">
                        <h3>📸 หลักฐานการจัดส่ง (บิลขนส่ง)</h3>
                        ${proofHtml}
                    </div>
                </div>
            `;

            modal.style.display = 'block';
            document.body.style.overflow = 'hidden';
        }

        function closeOrderModal() {
            document.getElementById('orderDetailModal').style.display = 'none';
            document.body.style.overflow = 'auto';
        }

        function openLightbox(imageSrc) {
            document.getElementById('lightboxImage').src = imageSrc;
            document.getElementById('imageLightbox').style.display = 'flex';
        }

        function closeLightbox() {
            document.getElementById('imageLightbox').style.display = 'none';
        }

        function formatDate(dateString) {
            if (!dateString) return '-';
            const date = new Date(dateString);
            return date.toLocaleDateString('th-TH', {
                year: 'numeric',
                month: 'short',
                day: 'numeric',
                hour: '2-digit',
                minute: '2-digit'
            });
        }

        async function updateTracking(orderId) {
            const newTracking = document.getElementById('newTrackingNumber').value.trim();

            if (!newTracking) {
                Swal.fire({
                    icon: 'warning',
                    title: 'กรุณากรอกเลข Tracking'
                });
                return;
            }

            try {
                const response = await fetch('api/admin_orders.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        action: 'update_tracking',
                        order_id: orderId,
                        tracking_number: newTracking
                    })
                });

                const data = await response.json();

                if (!data.success) {
                    throw new Error(data.message || 'ไม่สามารถอัปเดตได้');
                }

                await Swal.fire({
                    icon: 'success',
                    title: 'สำเร็จ!',
                    text: 'อัปเดตเลข Tracking แล้ว',
                    timer: 1500,
                    showConfirmButton: false
                });

                // Update local data and refresh modal
                const order = ordersData.find(o => o.id == orderId);
                if (order) {
                    order.tracking_number = newTracking;
                }
                viewOrderDetail(orderId);

            } catch (error) {
                Swal.fire({
                    icon: 'error',
                    title: 'เกิดข้อผิดพลาด',
                    text: error.message
                });
            }
        }

        // Close modal when clicking outside
        window.onclick = function (event) {
            const modal = document.getElementById('orderDetailModal');
            if (event.target === modal) {
                closeOrderModal();
            }
        }

        // Close on ESC key
        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape') {
                closeOrderModal();
                closeLightbox();
            }
        });

        // Initialize Spending Chart
        document.addEventListener('DOMContentLoaded', function () {
            const ctx = document.getElementById('spendingChart');
            if (!ctx) return;

            // Prepare data for chart
            const chartLabels = [];
            const chartData = [];

            // Sort orders by date (oldest first for chart)
            const sortedOrders = [...ordersData].sort((a, b) =>
                new Date(a.created_at) - new Date(b.created_at)
            );

            sortedOrders.forEach(order => {
                if (order.status !== 'cancelled' && order.status !== 'pending') {
                    const date = new Date(order.created_at);
                    chartLabels.push(`${date.toLocaleDateString('th-TH')} (#${order.id})`);
                    chartData.push(order.total_amount || 0);
                }
            });

            if (chartData.length > 0) {
                new Chart(ctx.getContext('2d'), {
                    type: 'bar',
                    data: {
                        labels: chartLabels,
                        datasets: [{
                            label: 'ยอดคำสั่งซื้อ (บาท)',
                            data: chartData,
                            backgroundColor: 'rgba(114, 46, 209, 0.5)',
                            borderColor: '#722ed1',
                            borderWidth: 1,
                            borderRadius: 5
                        }]
                    },
                    options: {
                        responsive: true,
                        plugins: { legend: { display: false } },
                        scales: { y: { beginAtZero: true } }
                    }
                });
            } else {
                ctx.getContext('2d').font = "16px Prompt";
                ctx.getContext('2d').fillStyle = "#999";
                ctx.getContext('2d').textAlign = "center";
                ctx.getContext('2d').fillText("ไม่มีข้อมูลการสั่งซื้อ", ctx.width / 2, 100);
            }
        });
    </script>
</body>

</html>