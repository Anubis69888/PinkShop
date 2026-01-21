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

if (!$order || $order['user_id'] != $_SESSION['user_id']) {
    header('Location: profile.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ติดตามสถานะ - Order #<?php echo $orderId; ?></title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;600;800&family=Sarabun:wght@300;400;600&display=swap" rel="stylesheet">
    <style>
        .timeline {
            position: relative;
            padding: 20px 0;
            margin-left: 20px;
        }
        .timeline::before {
            content: '';
            position: absolute;
            left: 20px;
            top: 10px;
            bottom: 10px;
            width: 4px;
            background: rgba(255, 255, 255, 0.5);
            border-radius: 4px;
        }
        .timeline-item {
            position: relative;
            padding-left: 60px;
            margin-bottom: 40px;
            opacity: 0.6;
            transition: all 0.3s;
        }
        .timeline-item.active {
            opacity: 1;
        }
        .timeline-dot {
            position: absolute;
            left: 10px;
            top: 2px;
            width: 24px;
            height: 24px;
            background: #e0e0e0;
            border-radius: 50%;
            border: 4px solid white;
            box-shadow: 0 4px 10px rgba(0,0,0,0.1);
            z-index: 2;
        }
        .timeline-item.active .timeline-dot {
            background: var(--primary);
            box-shadow: 0 0 0 4px rgba(255, 105, 180, 0.2);
        }
        .timeline-item.cancelled .timeline-dot {
            background: #ff4d4d;
            box-shadow: 0 0 0 4px rgba(255, 77, 77, 0.2);
        }
        .timeline-date {
            font-size: 0.9rem;
            color: var(--text-muted);
            margin-bottom: 4px;
        }
        .timeline-status {
            font-weight: 700;
            font-size: 1.15rem;
            color: var(--text-main);
        }
        .tracking-box {
            background: linear-gradient(135deg, rgba(255, 255, 255, 0.8), rgba(255, 255, 255, 0.4));
            backdrop-filter: blur(10px);
            padding: 20px 30px;
            border-radius: 20px;
            display: inline-block;
            margin-top: 20px;
            border: 2px solid transparent;
            background-clip: padding-box;
            position: relative;
            box-shadow: 0 10px 30px rgba(0,0,0,0.05);
        }
        .tracking-box::before {
            content: '';
            position: absolute;
            top: 0; right: 0; bottom: 0; left: 0;
            z-index: -1;
            margin: -2px;
            border-radius: 22px;
            background: linear-gradient(to right, #ff9a9e, #fad0c4);
        }
        .btn-back {
            background: linear-gradient(45deg, #ff9a9e, #fad0c4);
            color: white;
            border: none;
            padding: 12px 30px;
            border-radius: 50px;
            font-weight: 600;
            box-shadow: 0 5px 15px rgba(255, 154, 158, 0.4);
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }
        .btn-back:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(255, 154, 158, 0.6);
            color: white;
        }
        .glass-panel {
            background: rgba(255, 255, 255, 0.7);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.8);
            box-shadow: 0 20px 40px rgba(0,0,0,0.05);
            border-radius: 30px;
            animation: fadeIn 0.8s ease-out;
        }
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
    </style>
</head>
<body>
    <?php include 'includes/header.php'; ?>

    <div class="container">
        <div class="glass-panel" style="max-width: 600px; margin: 40px auto; padding: 50px;">
            <div style="text-align: center; margin-bottom: 50px;">
                <h1 style="color: var(--primary); font-size: 2.2rem; margin-bottom: 10px; text-shadow: 2px 2px 4px rgba(0,0,0,0.05);">ติดตามพัสดุ 📦</h1>
                <p style="color: var(--text-muted); font-size: 1.1rem;">หมายเลขคำสั่งซื้อ: #<?php echo $orderId; ?></p>
                
                <div class="tracking-box">
                    <span style="color: var(--text-muted); font-size: 0.9rem; text-transform: uppercase; letter-spacing: 1px;">Tracking Number</span><br>
                    <strong style="font-size: 1.4rem; color: #ff6b6b; letter-spacing: 2px;"><?php echo $order['tracking_number']; ?></strong>
                </div>
            </div>

            <div class="timeline">
                <?php 
                $isCancelled = ($order['status'] === 'ยกเลิก');
                
                // Existing timeline events
                if (isset($order['timeline'])) {
                    foreach ($order['timeline'] as $event) {
                        $statusClass = 'active';
                        if ($event['status'] === 'ยกเลิกคำสั่งซื้อ') $statusClass .= ' cancelled';
                        
                        echo '<div class="timeline-item ' . $statusClass . '">';
                        echo '<div class="timeline-dot"></div>';
                        echo '<div class="timeline-status">' . $event['status'] . '</div>';
                        echo '<div class="timeline-date">' . $event['time'] . '</div>';
                        if (isset($event['detail'])) {
                            echo '<div style="font-size:0.95rem; color: #ff4d4d; margin-top:8px; background: #ffecec; padding: 5px 10px; border-radius: 8px; display: inline-block;">' . $event['detail'] . '</div>';
                        }
                        echo '</div>';
                    }
                }

                // If NOT cancelled, show future steps
                if (!$isCancelled) {
                    $futureSteps = [
                        ['status' => 'กำลังจัดส่ง', 'desc' => 'พัสดุกำลังเดินทางไปยังที่อยู่ของคุณ'],
                        ['status' => 'จัดส่งสำเร็จ', 'desc' => 'ได้รับพัสดุเรียบร้อยแล้ว']
                    ];
                    
                    foreach ($futureSteps as $step) {
                        echo '<div class="timeline-item">';
                        echo '<div class="timeline-dot"></div>';
                        echo '<div class="timeline-status">' . $step['status'] . '</div>';
                        echo '<div class="timeline-date">' . $step['desc'] . '</div>';
                        echo '</div>';
                    }
                }
                ?>
            </div>

            <div style="text-align: center; margin-top: 50px; padding-top: 30px; border-top: 1px dashed rgba(0,0,0,0.1);">
                <a href="profile.php" class="btn-back">
                    <i class="fas fa-arrow-left"></i> กลับไปหน้าโปรไฟล์
                </a>
            </div>
        </div>
    </div>
</body>
</html>
