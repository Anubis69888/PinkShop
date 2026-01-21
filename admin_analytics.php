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
    <title>Advanced Analytics - Doll Paradise</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Prompt:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        body { font-family: 'Prompt', sans-serif; background: var(--bg-gradient); }
        .dashboard-container {
            max-width: 1200px;
            margin: 40px auto;
            padding: 20px;
        }
        
        .page-header {
            display: flex;
            align-items: center;
            margin-bottom: 30px;
            gap: 15px;
        }

        .chart-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 25px;
            margin-bottom: 30px;
        }
        @media (max-width: 900px) { .chart-grid { grid-template-columns: 1fr; } }
        
        .chart-card {
            background: rgba(255, 255, 255, 0.95);
            padding: 25px;
            border-radius: 20px;
            box-shadow: var(--shadow);
            border: 1px solid rgba(255,255,255,0.8);
            backdrop-filter: blur(10px);
        }
        
        .chart-title {
            color: var(--text-main);
            font-size: 1.2rem;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .full-width { grid-column: 1 / -1; }
    </style>
</head>
<body>
    <?php include 'includes/header.php'; ?>

    <div class="dashboard-container">
        <div class="page-header">
            <a href="admin_dashboard.php" style="text-decoration: none; color: var(--text-muted); font-size: 1.2rem;">
                <i class="fas fa-arrow-left"></i>
            </a>
            <h1 style="color: var(--primary); margin: 0;">สรุปผลเชิงลึก (Advanced Analytics) 📈</h1>
        </div>

        <div class="chart-grid">
            <!-- User Ranks (Pie Chart) -->
            <div class="chart-card">
                <h3 class="chart-title"><i class="fas fa-users-cog"></i> สัดส่วนสมาชิก (User Ranks)</h3>
                <div style="height: 300px; display: flex; justify-content: center;">
                    <canvas id="ranksChart"></canvas>
                </div>
            </div>

            <!-- User Growth (Line Chart) -->
            <div class="chart-card">
                <h3 class="chart-title"><i class="fas fa-user-plus"></i> การสมัครสมาชิกใหม่ (30 วันล่าสุด)</h3>
                <div style="height: 300px;">
                    <canvas id="growthChart"></canvas>
                </div>
            </div>

            <!-- Top Products (Horizontal Bar) -->
            <div class="chart-card full-width">
                <h3 class="chart-title"><i class="fas fa-eye"></i> 20 อันดับสินค้าที่มีการเข้าชมสูงสุด</h3>
                <div style="height: 400px;">
                    <canvas id="productsChart"></canvas>
                </div>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', loadAnalytics);

        async function loadAnalytics() {
            try {
                const res = await fetch('api/admin_analytics.php');
                const data = await res.json();
                
                if (!data.success) {
                    alert('Error loading data');
                    return;
                }

                // 1. User Ranks Chart
                new Chart(document.getElementById('ranksChart'), {
                    type: 'doughnut',
                    data: {
                        labels: ['ผู้ดูแลระบบ (Admin)', 'ผู้ขาย (Seller)', 'ผู้ใช้ใหม่ (New User)', 'สมาชิกทั่วไป (User)'],
                        datasets: [{
                            data: [
                                data.user_ranks.admin, 
                                data.user_ranks.seller, 
                                data.user_ranks.new_user, 
                                data.user_ranks.user
                            ],
                            backgroundColor: ['#722ed1', '#fa8c16', '#52c41a', '#d9d9d9']
                        }]
                    },
                    options: { responsive: true, maintainAspectRatio: false }
                });

                // 2. User Growth Chart
                new Chart(document.getElementById('growthChart'), {
                    type: 'line',
                    data: {
                        labels: Object.keys(data.user_growth).map(d => new Date(d).toLocaleDateString('th-TH')),
                        datasets: [{
                            label: 'สมาชิกใหม่',
                            data: Object.values(data.user_growth),
                            borderColor: '#1890ff',
                            backgroundColor: 'rgba(24, 144, 255, 0.2)',
                            fill: true,
                            tension: 0.4
                        }]
                    },
                    options: { responsive: true, maintainAspectRatio: false, scales: { y: { beginAtZero: true, ticks: { stepSize: 1 } } } }
                });

                // 3. Top Products Chart
                const topProducts = data.top_products; // Already sorted top 20
                new Chart(document.getElementById('productsChart'), {
                    type: 'bar',
                    data: {
                        labels: topProducts.map(p => p.name),
                        datasets: [{
                            label: 'จำนวนการเข้าชม (ครั้ง)',
                            data: topProducts.map(p => p.views),
                            backgroundColor: '#ff85c0',
                            borderRadius: 5
                        }]
                    },
                    options: { 
                        indexAxis: 'y',
                        responsive: true, 
                        maintainAspectRatio: false,
                        plugins: { legend: { display: false } }
                    }
                });

            } catch (e) {
                console.error(e);
            }
        }
    </script>
</body>
</html>
