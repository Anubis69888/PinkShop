<?php
session_start();
require_once 'includes/db.php';

// Check if user is a seller
if (!isset($_SESSION['user_id']) || empty($_SESSION['is_seller'])) {
    header('Location: profile.php');
    exit;
}

$db = new DB();
$userId = $_SESSION['user_id'];
$products = $db->read('products');
$orders = $db->read('orders');
$users = $db->read('users');

// Get seller's products
$sellerProducts = array_filter($products, function($p) use ($userId) {
    return ($p['seller_id'] ?? null) == $userId;
});
$sellerProductIds = array_column($sellerProducts, 'id');

// Calculate statistics
$totalRevenue = 0;
$monthlyRevenue = 0;
$totalOrders = 0;
$pendingOrders = 0;
$completedOrders = 0;
$productsSold = 0;
$customers = [];
$productStats = [];
$recentOrders = [];
$dailyRevenue = [];

$currentMonth = date('Y-m');

foreach ($orders as $order) {
    $orderRevenue = 0;
    $isRelevant = false;
    
    foreach ($order['items'] as $item) {
        // Check if this item belongs to the current seller:
        // 1. Check seller_id directly in item (for newer orders)
        // 2. Fall back to checking product_id against seller's products
        $itemSellerId = $item['seller_id'] ?? null;
        $matchesBySellerId = ($itemSellerId !== null && $itemSellerId == $userId);
        $matchesByProductId = in_array($item['product_id'], $sellerProductIds);
        
        if ($matchesBySellerId || $matchesByProductId) {
            $isRelevant = true;
            $itemTotal = $item['price'] * $item['qty'];
            $orderRevenue += $itemTotal;
            $productsSold += $item['qty'];
            
            // Track product stats
            $pid = $item['product_id'];
            if (!isset($productStats[$pid])) {
                $productStats[$pid] = ['qty' => 0, 'revenue' => 0, 'name' => $item['name']];
            }
            $productStats[$pid]['qty'] += $item['qty'];
            $productStats[$pid]['revenue'] += $itemTotal;
        }
    }
    
    if ($isRelevant) {
        $totalOrders++;
        $totalRevenue += $orderRevenue;
        
        // Monthly revenue
        if (strpos($order['created_at'], $currentMonth) === 0) {
            $monthlyRevenue += $orderRevenue;
        }
        
        // Daily revenue for chart
        $orderDate = date('Y-m-d', strtotime($order['created_at']));
        if (!isset($dailyRevenue[$orderDate])) {
            $dailyRevenue[$orderDate] = 0;
        }
        $dailyRevenue[$orderDate] += $orderRevenue;
        
        // Order status
        $status = $order['status'] ?? '';
        if (strpos($status, 'ชำระเงินแล้ว') !== false || strpos($status, 'กำลังจัดส่ง') !== false) {
            $pendingOrders++;
        } elseif (strpos($status, 'สำเร็จ') !== false || strpos($status, 'จัดส่งแล้ว') !== false) {
            $completedOrders++;
        }
        
        // Track customers
        $customerId = $order['user_id'];
        if (!isset($customers[$customerId])) {
            $customers[$customerId] = [
                'orders' => 0,
                'total_spent' => 0,
                'last_order' => $order['created_at'],
                'products' => []
            ];
        }
        $customers[$customerId]['orders']++;
        $customers[$customerId]['total_spent'] += $orderRevenue;
        
        // Recent orders (last 10)
        if (count($recentOrders) < 10) {
            $recentOrders[] = $order;
        }
    }
}

// Sort products by quantity sold
uasort($productStats, function($a, $b) {
    return $b['qty'] - $a['qty'];
});

// Get customer details
$customerDetails = [];
foreach ($customers as $custId => $custData) {
    $custInfo = array_filter($users, function($u) use ($custId) {
        return $u['id'] == $custId;
    });
    $custInfo = reset($custInfo);
    if ($custInfo) {
        $customerDetails[] = [
            'id' => $custId,
            'name' => $custInfo['username'] ?? 'ไม่ระบุ',
            'avatar' => $custInfo['avatar_config']['src'] ?? 'assets/images/default-avatar.png',
            'orders' => $custData['orders'],
            'total_spent' => $custData['total_spent'],
            'last_order' => $custData['last_order']
        ];
    }
}

// Sort customers by total spent
usort($customerDetails, function($a, $b) {
    return $b['total_spent'] - $a['total_spent'];
});

// Get last 30 days for chart
$chartLabels = [];
$chartData = [];
for ($i = 29; $i >= 0; $i--) {
    $date = date('Y-m-d', strtotime("-$i days"));
    $chartLabels[] = date('d/m', strtotime($date));
    $chartData[] = $dailyRevenue[$date] ?? 0;
}

$totalCustomers = count($customers);
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Seller Dashboard - AKP Shop</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Prompt:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        body { 
            font-family: 'Prompt', sans-serif; 
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
        }
        
        .dashboard-container {
            max-width: 1400px;
            margin: 40px auto;
            padding: 20px;
        }
        
        .page-header {
            background: rgba(255,255,255,0.95);
            padding: 30px;
            border-radius: 24px;
            margin-bottom: 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 10px 40px rgba(0,0,0,0.1);
        }
        
        .page-header h1 {
            margin: 0;
            color: #667eea;
            font-size: 1.8rem;
        }
        
        .page-header p {
            margin: 5px 0 0 0;
            color: #888;
        }
        
        /* Stats Cards */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .stat-card {
            background: rgba(255,255,255,0.95);
            padding: 25px;
            border-radius: 20px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            position: relative;
            overflow: hidden;
        }
        
        .stat-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 5px;
            height: 100%;
        }
        
        .stat-card.revenue::before { background: linear-gradient(180deg, #667eea, #764ba2); }
        .stat-card.orders::before { background: linear-gradient(180deg, #f093fb, #f5576c); }
        .stat-card.products::before { background: linear-gradient(180deg, #4facfe, #00f2fe); }
        .stat-card.customers::before { background: linear-gradient(180deg, #43e97b, #38f9d7); }
        
        .stat-icon {
            font-size: 2.5rem;
            margin-bottom: 10px;
        }
        
        .stat-label {
            color: #888;
            font-size: 0.9rem;
            margin-bottom: 5px;
        }
        
        .stat-value {
            font-size: 2rem;
            font-weight: 700;
            color: #333;
        }
        
        .stat-sub {
            font-size: 0.85rem;
            color: #888;
            margin-top: 5px;
        }
        
        .stat-sub .highlight {
            color: #667eea;
            font-weight: 600;
        }
        
        /* Chart Card */
        .chart-card {
            background: rgba(255,255,255,0.95);
            padding: 30px;
            border-radius: 24px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.1);
            margin-bottom: 30px;
        }
        
        .chart-card h3 {
            margin: 0 0 20px 0;
            color: #333;
        }
        
        /* Tabs */
        .tabs-container {
            background: rgba(255,255,255,0.95);
            border-radius: 24px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.1);
            overflow: hidden;
        }
        
        .tabs-nav {
            display: flex;
            background: #f8f9fa;
            border-bottom: 1px solid #eee;
        }
        
        .tab-btn {
            flex: 1;
            padding: 18px 20px;
            border: none;
            background: none;
            cursor: pointer;
            font-family: 'Prompt', sans-serif;
            font-size: 0.95rem;
            font-weight: 500;
            color: #888;
            transition: all 0.3s;
            position: relative;
        }
        
        .tab-btn:hover {
            color: #667eea;
            background: rgba(102, 126, 234, 0.05);
        }
        
        .tab-btn.active {
            color: #667eea;
            background: white;
        }
        
        .tab-btn.active::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            width: 100%;
            height: 3px;
            background: linear-gradient(90deg, #667eea, #764ba2);
        }
        
        .tab-content {
            display: none;
            padding: 30px;
        }
        
        .tab-content.active {
            display: block;
        }
        
        /* Tables */
        .data-table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .data-table th {
            background: #f8f9fa;
            padding: 15px;
            text-align: left;
            font-weight: 600;
            color: #555;
            border-bottom: 2px solid #667eea;
        }
        
        .data-table td {
            padding: 15px;
            border-bottom: 1px solid #eee;
            color: #333;
        }
        
        .data-table tr:hover {
            background: #fafafa;
        }
        
        .customer-row {
            display: flex;
            align-items: center;
            gap: 12px;
        }
        
        .customer-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            object-fit: cover;
        }
        
        .status-badge {
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 500;
        }
        
        .status-pending { background: #fff3cd; color: #856404; }
        .status-shipping { background: #cce5ff; color: #004085; }
        .status-completed { background: #d4edda; color: #155724; }
        .status-cancelled { background: #f8d7da; color: #721c24; }
        
        .empty-state {
            text-align: center;
            padding: 60px;
            color: #888;
        }
        
        .empty-state .icon {
            font-size: 4rem;
            margin-bottom: 20px;
        }
        
        .btn-primary {
            padding: 12px 25px;
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            border: none;
            border-radius: 12px;
            font-family: 'Prompt', sans-serif;
            font-weight: 500;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            transition: all 0.3s;
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(102, 126, 234, 0.4);
        }
        
        @media (max-width: 768px) {
            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
            }
            .tabs-nav {
                flex-wrap: wrap;
            }
            .tab-btn {
                flex: none;
                width: 50%;
            }
        }
    </style>
</head>
<body>
    <?php include 'includes/header.php'; ?>
    
    <div class="dashboard-container">
        <div class="page-header">
            <div>
                <h1>📊 Seller Dashboard</h1>
                <p>ภาพรวมร้านค้าและข้อมูลลูกค้าของคุณ</p>
            </div>
            <a href="seller_orders.php" class="btn-primary">📦 จัดการคำสั่งซื้อ</a>
        </div>
        
        <!-- Statistics Cards -->
        <div class="stats-grid">
            <div class="stat-card revenue">
                <div class="stat-icon">💰</div>
                <div class="stat-label">รายได้รวม</div>
                <div class="stat-value">฿<?php echo number_format($totalRevenue); ?></div>
                <div class="stat-sub">เดือนนี้: <span class="highlight">฿<?php echo number_format($monthlyRevenue); ?></span></div>
            </div>
            
            <div class="stat-card orders">
                <div class="stat-icon">🛒</div>
                <div class="stat-label">คำสั่งซื้อทั้งหมด</div>
                <div class="stat-value"><?php echo number_format($totalOrders); ?></div>
                <div class="stat-sub">รอดำเนินการ: <span class="highlight"><?php echo $pendingOrders; ?></span> | สำเร็จ: <?php echo $completedOrders; ?></div>
            </div>
            
            <div class="stat-card products">
                <div class="stat-icon">📦</div>
                <div class="stat-label">สินค้าขายได้</div>
                <div class="stat-value"><?php echo number_format($productsSold); ?> ชิ้น</div>
                <div class="stat-sub">จาก <?php echo count($sellerProducts); ?> รายการสินค้า</div>
            </div>
            
            <div class="stat-card customers">
                <div class="stat-icon">👥</div>
                <div class="stat-label">ลูกค้าทั้งหมด</div>
                <div class="stat-value"><?php echo number_format($totalCustomers); ?></div>
                <div class="stat-sub">ลูกค้าที่ซื้อสินค้าของคุณ</div>
            </div>
        </div>
        
        <!-- Revenue Chart -->
        <div class="chart-card">
            <h3>📈 รายได้ 30 วันล่าสุด</h3>
            <canvas id="revenueChart" height="100"></canvas>
        </div>
        
        <!-- Tabs Section -->
        <div class="tabs-container">
            <div class="tabs-nav">
                <button class="tab-btn active" onclick="switchTab('orders')">🛒 คำสั่งซื้อล่าสุด</button>
                <button class="tab-btn" onclick="switchTab('customers')">👥 ลูกค้าของคุณ</button>
                <button class="tab-btn" onclick="switchTab('products')">📦 สินค้าขายดี</button>
            </div>
            
            <!-- Orders Tab -->
            <div id="tab-orders" class="tab-content active">
                <?php if (empty($recentOrders)): ?>
                <div class="empty-state">
                    <div class="icon">📭</div>
                    <h3>ยังไม่มีคำสั่งซื้อ</h3>
                    <p>เมื่อมีลูกค้าสั่งซื้อสินค้าของคุณ รายการจะปรากฏที่นี่</p>
                </div>
                <?php else: ?>
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Order ID</th>
                            <th>ลูกค้า</th>
                            <th>สินค้า</th>
                            <th>ยอดเงิน</th>
                            <th>สถานะ</th>
                            <th>วันที่</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach (array_reverse($recentOrders) as $order): 
                            $custInfo = array_filter($users, function($u) use ($order) { return $u['id'] == $order['user_id']; });
                            $custInfo = reset($custInfo);
                            $custName = $order['shipping_info']['fullname'] ?? ($custInfo['username'] ?? 'ไม่ระบุ');
                            
                            $orderItems = array_filter($order['items'], function($item) use ($sellerProductIds) {
                                return in_array($item['product_id'], $sellerProductIds);
                            });
                            $itemCount = count($orderItems);
                            $firstItem = reset($orderItems);
                            
                            $orderTotal = 0;
                            foreach ($orderItems as $item) {
                                $orderTotal += $item['price'] * $item['qty'];
                            }
                            
                            $status = $order['status'] ?? '';
                            $statusClass = 'status-pending';
                            if (strpos($status, 'ยกเลิก') !== false) $statusClass = 'status-cancelled';
                            elseif (strpos($status, 'จัดส่ง') !== false) $statusClass = 'status-shipping';
                            elseif (strpos($status, 'สำเร็จ') !== false) $statusClass = 'status-completed';
                        ?>
                        <tr>
                            <td><strong>#<?php echo $order['id']; ?></strong></td>
                            <td><?php echo htmlspecialchars($custName); ?></td>
                            <td>
                                <?php echo htmlspecialchars($firstItem['name'] ?? ''); ?>
                                <?php if ($itemCount > 1): ?>
                                    <span style="color: #888;">(+<?php echo $itemCount - 1; ?> รายการ)</span>
                                <?php endif; ?>
                            </td>
                            <td><strong>฿<?php echo number_format($orderTotal); ?></strong></td>
                            <td><span class="status-badge <?php echo $statusClass; ?>"><?php echo htmlspecialchars($status); ?></span></td>
                            <td><?php echo date('d/m/Y H:i', strtotime($order['created_at'])); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <?php endif; ?>
            </div>
            
            <!-- Customers Tab -->
            <div id="tab-customers" class="tab-content">
                <?php if (empty($customerDetails)): ?>
                <div class="empty-state">
                    <div class="icon">👥</div>
                    <h3>ยังไม่มีข้อมูลลูกค้า</h3>
                    <p>ข้อมูลลูกค้าจะปรากฏเมื่อมีการสั่งซื้อ</p>
                </div>
                <?php else: ?>
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>ลูกค้า</th>
                            <th>จำนวนออเดอร์</th>
                            <th>ยอดซื้อรวม</th>
                            <th>ซื้อล่าสุด</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($customerDetails as $cust): ?>
                        <tr>
                            <td>
                                <div class="customer-row">
                                    <img src="<?php echo htmlspecialchars($cust['avatar']); ?>" class="customer-avatar" onerror="this.src='assets/images/default-avatar.png'">
                                    <strong><?php echo htmlspecialchars($cust['name']); ?></strong>
                                </div>
                            </td>
                            <td><?php echo $cust['orders']; ?> ครั้ง</td>
                            <td><strong>฿<?php echo number_format($cust['total_spent']); ?></strong></td>
                            <td><?php echo date('d/m/Y', strtotime($cust['last_order'])); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <?php endif; ?>
            </div>
            
            <!-- Products Tab -->
            <div id="tab-products" class="tab-content">
                <?php if (empty($productStats)): ?>
                <div class="empty-state">
                    <div class="icon">📦</div>
                    <h3>ยังไม่มีข้อมูลสินค้า</h3>
                    <p>สถิติสินค้าจะปรากฏเมื่อมีการขาย</p>
                </div>
                <?php else: ?>
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>สินค้า</th>
                            <th>จำนวนขาย</th>
                            <th>รายได้</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($productStats as $pid => $pstat): ?>
                        <tr>
                            <td><strong><?php echo htmlspecialchars($pstat['name']); ?></strong></td>
                            <td><?php echo number_format($pstat['qty']); ?> ชิ้น</td>
                            <td><strong>฿<?php echo number_format($pstat['revenue']); ?></strong></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <?php include 'includes/footer.php'; ?>
    
    <script>
        // Tab switching
        function switchTab(tabName) {
            document.querySelectorAll('.tab-btn').forEach(btn => btn.classList.remove('active'));
            document.querySelectorAll('.tab-content').forEach(tab => tab.classList.remove('active'));
            
            document.querySelector(`[onclick="switchTab('${tabName}')"]`).classList.add('active');
            document.getElementById(`tab-${tabName}`).classList.add('active');
        }
        
        // Revenue Chart
        const ctx = document.getElementById('revenueChart').getContext('2d');
        new Chart(ctx, {
            type: 'line',
            data: {
                labels: <?php echo json_encode($chartLabels); ?>,
                datasets: [{
                    label: 'รายได้ (บาท)',
                    data: <?php echo json_encode($chartData); ?>,
                    borderColor: '#667eea',
                    backgroundColor: 'rgba(102, 126, 234, 0.1)',
                    fill: true,
                    tension: 0.4,
                    pointRadius: 3,
                    pointBackgroundColor: '#667eea'
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        display: false
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: function(value) {
                                return '฿' + value.toLocaleString();
                            }
                        }
                    }
                }
            }
        });
    </script>
</body>
</html>
