<?php
session_start();
if (empty($_SESSION['user_id']) || empty($_SESSION['is_admin'])) {
    header('Location: index.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Doll Paradise</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="assets/css/admin-modern.css">
    <link href="https://fonts.googleapis.com/css2?family=Prompt:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        body { font-family: 'Prompt', sans-serif; background: var(--bg-gradient); }
        
        .dashboard-container {
            max-width: 1200px;
            margin: 40px auto;
            padding: 20px;
        }

        .page-header {
            background: rgba(255, 255, 255, 0.7);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border-radius: 32px;
            padding: 30px 40px;
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

        .page-header h1 {
            margin: 0;
            font-size: 2.5rem;
            font-weight: 700;
            background: linear-gradient(135deg, #722ed1, #eb2f96);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            display: flex;
            align-items: center;
            gap: 15px;
        }
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 40px;
        }
        .stat-card {
            background: rgba(255, 255, 255, 0.9);
            padding: 25px;
            border-radius: 20px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.05);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255,255,255,0.5);
            transition: transform 0.3s;
        }
        .stat-card:hover { transform: translateY(-5px); }
        .stat-value {
            font-size: 2.5rem;
            font-weight: 700;
            color: var(--primary);
            margin: 10px 0;
        }
        .stat-label { color: var(--text-muted); font-size: 1rem; }
        
        .charts-section {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 20px;
            margin-bottom: 40px;
        }
        @media (max-width: 900px) { .charts-section { grid-template-columns: 1fr; } }
        
        .chart-card {
            background: white;
            padding: 20px;
            border-radius: 20px;
            box-shadow: var(--shadow);
        }
        
        .table-card {
            background: white;
            padding: 25px;
            border-radius: 20px;
            box-shadow: var(--shadow);
        }
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 15px; text-align: left; border-bottom: 1px solid #eee; }
        th { color: var(--text-muted); font-weight: 500; }
        .status-badge {
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 0.85rem;
            display: inline-block;
        }
        .status-paid { background: #e3fcef; color: #00a854; }
        .status-pending { background: #fff7e6; color: #fa8c16; }
        .status-cancel { background: #fff1f0; color: #f5222d; }
    </style>
</head>
<body>
    <?php include 'includes/header.php'; ?>

    <div class="dashboard-container">
        <div class="page-header">
            <h1>
                <i class="fas fa-chart-line"></i>
                <span>Admin Dashboard</span>
            </h1>
        </div>

        <!-- Stats Overview -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-label">ยอดขายตาวมจริง (บาท)</div>
                <div class="stat-value" id="totalSales">...</div>
                <div style="font-size: 0.9rem; color: #00a854;">💰 รายได้ทั้งหมด</div>
            </div>
            <div class="stat-card">
                <div class="stat-label">คำสั่งซื้อทั้งหมด</div>
                <div class="stat-value" id="totalOrders">...</div>
                <div style="font-size: 0.9rem; color: #1890ff;">📦 ออเดอร์</div>
            </div>
            <div class="stat-card" onclick="window.location.href='admin_users.php'" style="cursor: pointer;">
                <div class="stat-label">สมาชิกทั้งหมด</div>
                <div class="stat-value" id="totalMembers">...</div>
                <div style="font-size: 0.9rem; color: #722ed1;">👥 ผู้ใช้งาน (คลิกเพื่อจัดการ)</div>
            </div>
        </div>

        <!-- Charts -->
        <div class="charts-section">
            <div class="chart-card">
                <h3 style="margin-bottom: 20px; color: var(--text-main);">ยอดขายรายวัน</h3>
                <canvas id="salesChart"></canvas>
            </div>
            <div class="chart-card">
                <h3 style="margin-bottom: 20px; color: var(--text-main);">สินค้าคนดูเยอะสุด</h3>
                <canvas id="viewsChart"></canvas>
            </div>
        </div>

        <!-- Recent Orders -->
        <div class="table-card">
            <h3 style="margin-bottom: 20px; color: var(--text-main);">คำสั่งซื้อล่าสุด</h3>
            <div style="overflow-x: auto;">
                <table id="ordersTable">
                    <thead>
                        <tr>
                            <th>Order ID</th>
                            <th>ลูกค้า</th>
                            <th>ยอดรวม</th>
                            <th>สถานะ</th>
                            <th>วันที่</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr><td colspan="5" style="text-align: center;">Loading...</td></tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', loadStats);

        async function loadStats() {
            try {
                const res = await fetch('api/admin_stats.php');
                const data = await res.json();
                
                if (!data.success) {
                    alert(data.message);
                    window.location.href = 'index.php';
                    return;
                }

                // Update Overview
                document.getElementById('totalSales').textContent = '฿' + data.overview.total_sales.toLocaleString();
                document.getElementById('totalOrders').textContent = data.overview.total_orders.toLocaleString();
                document.getElementById('totalMembers').textContent = data.overview.total_members.toLocaleString();

                // Sales Chart
                const salesCtx = document.getElementById('salesChart').getContext('2d');
                new Chart(salesCtx, {
                    type: 'line',
                    data: {
                        labels: Object.keys(data.sales_chart),
                        datasets: [{
                            label: 'ยอดขาย (บาท)',
                            data: Object.values(data.sales_chart),
                            borderColor: '#ff85c0', // Pink
                            backgroundColor: 'rgba(255, 133, 192, 0.2)',
                            tension: 0.4,
                            fill: true
                        }]
                    },
                    options: { responsive: true, plugins: { legend: { display: false } } }
                });

                // Views Chart
                const viewsCtx = document.getElementById('viewsChart').getContext('2d');
                const topProducts = data.product_stats.slice(0, 5); // Top 5
                new Chart(viewsCtx, {
                    type: 'doughnut',
                    data: {
                        labels: topProducts.map(p => p.name),
                        datasets: [{
                            data: topProducts.map(p => p.views),
                            backgroundColor: [
                                '#ff9c6e', '#ffc069', '#ffd666', '#fff566', '#d3f261'
                            ]
                        }]
                    },
                    options: { responsive: true }
                });

                // Recent Orders Table
                const tbody = document.querySelector('#ordersTable tbody');
                tbody.innerHTML = '';
                data.recent_orders.forEach(order => {
                    let statusClass = 'status-pending';
                    if (order.status.includes('ชำระเงินแล้ว') || order.status.includes('จัดส่ง')) statusClass = 'status-paid';
                    if (order.status.includes('ยกเลิก')) statusClass = 'status-cancel';

                    const row = `
                        <tr>
                            <td>#${order.id}</td>
                            <td>${order.shipping_info.fullname}</td>
                            <td>฿${order.total.toLocaleString()}</td>
                            <td><span class="status-badge ${statusClass}">${order.status}</span></td>
                            <td>${new Date(order.created_at).toLocaleDateString('th-TH')}</td>
                        </tr>
                    `;
                    tbody.insertAdjacentHTML('beforeend', row);
                });

            } catch (e) {
                console.error('Error loading stats:', e);
            }
        }
    </script>
</body>
</html>
