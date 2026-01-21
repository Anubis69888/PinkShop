<?php
session_start();
if (empty($_SESSION['user_id']) || empty($_SESSION['is_admin'])) {
    header('Location: index.php');
    exit;
}

require_once 'includes/db.php';
$db = new DB();
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>จัดการการเงิน - Admin Panel</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="assets/css/admin-modern.css">
    <link href="https://fonts.googleapis.com/css2?family=Prompt:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        body { 
            font-family: 'Prompt', sans-serif; 
            background: var(--bg-gradient); 
            padding-top: 20px;
        }
        
        .admin-container {
            max-width: 1400px;
            margin: 40px auto;
            padding: 20px;
        }
        
        .page-header {
            background: white;
            padding: 30px;
            border-radius: 24px;
            box-shadow: var(--shadow);
            margin-bottom: 30px;
        }
        
        .page-header h1 {
            color: var(--primary);
            margin: 0 0 10px 0;
        }
        
        /* Stats */
        .stats-row {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .stat-card {
            background: white;
            padding: 25px;
            border-radius: 18px;
            box-shadow: var(--shadow);
        }
        
        .stat-card .label {
            color: var(--text-secondary);
            font-size: 0.9rem;
            margin-bottom: 10px;
        }
        
        .stat-card .number {
            font-size: 2.5rem;
            font-weight: 700;
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            margin-bottom: 5px;
        }
        
        .stat-card .subtext {
            color: var(--text-secondary);
            font-size: 0.85rem;
        }
        
        /* Payments Table */
        .payments-card {
            background: white;
            padding: 30px;
            border-radius: 20px;
            box-shadow: var(--shadow);
            overflow: auto;
        }
        
        .payments-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        
        .payments-table th {
            background: linear-gradient(135deg, #f8f9fa, #e9ecef);
            padding: 15px;
            text-align: left;
            font-weight: 600;
            color: var(--text-main);
            border-bottom: 3px solid var(--primary);
        }
        
        .payments-table td {
            padding: 18px 15px;
            border-bottom: 1px solid #f0f0f0;
        }
        
        .payments-table tr:hover {
            background: #fafafa;
        }
        
        .payment-status {
            padding: 6px 16px;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 500;
            display: inline-block;
        }
        
        .status-success {
            background: #f6ffed;
            color: #52c41a;
        }
        
        .status-pending {
            background: #fff7e6;
            color: #fa8c16;
        }
        
        .status-failed {
            background: #fff1f0;
            color: #f5222d;
        }
        
        .btn-action {
            padding: 8px 16px;
            border: none;
            border-radius: 10px;
            cursor: pointer;
            font-family: 'Prompt', sans-serif;
            font-size: 0.85rem;
            font-weight: 500;
            transition: all 0.3s ease;
            margin: 0 3px;
        }
        
        .btn-view {
            background: #e6f7ff;
            color: #1890ff;
        }
        
        .btn-view:hover {
            background: #1890ff;
            color: white;
        }
        
        .btn-export {
            padding: 12px 25px;
            background: linear-gradient(135deg, #52c41a, #73d13d);
            color: white;
            border: none;
            border-radius: 12px;
            font-family: 'Prompt', sans-serif;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s ease;
            margin-bottom: 20px;
        }
        
        .btn-export:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(82, 196, 26, 0.4);
        }
        
        .filter-panel {
            background: white;
            padding: 20px 30px;
            border-radius: 20px;
            box-shadow: var(--shadow);
            margin-bottom: 20px;
            display: flex;
            gap: 15px;
            flex-wrap: wrap;
            align-items: flex-end;
        }
        
        .filter-group {
            display: flex;
            flex-direction: column;
            gap: 5px;
        }
        
        .filter-group label {
            font-size: 0.85rem;
            color: var(--text-secondary);
            font-weight: 500;
        }
        
        .filter-input {
            padding: 10px 15px;
            border: 2px solid #f0f0f0;
            border-radius: 12px;
            font-family: 'Prompt', sans-serif;
        }
        
        .filter-input:focus {
            outline: none;
            border-color: var(--primary);
        }
    </style>
</head>
<body>
    <?php include 'includes/header.php'; ?>
    
    <div class="admin-container">
        <div class="page-header">
            <h1>💰 จัดการการเงิน</h1>
            <p>ดูประวัติการชำระเงิน ออกรายงาน และจัดการคืนเงิน</p>
        </div>
        
        <!-- Financial Statistics -->
        <div class="stats-row" id="financialStats">
            <div class="stat-card">
                <div class="label">รายได้รวม (เดือนนี้)</div>
                <div class="number" id="totalRevenue">-</div>
                <div class="subtext">อัปเดตล่าสุด: <span id="lastUpdate">-</span></div>
            </div>
            <div class="stat-card">
                <div class="label">ยอดชำระเงินสำเร็จ</div>
                <div class="number" id="successfulPayments">-</div>
                <div class="subtext">จำนวนธุรกรรม</div>
            </div>
            <div class="stat-card">
                <div class="label">รอตรวจสอบ</div>
                <div class="number" id="pendingPayments">-</div>
                <div class="subtext">รอการยืนยัน</div>
            </div>
        </div>
        
        <!-- Filter & Export -->
        <div class="filter-panel">
            <div class="filter-group">
                <label>ตั้งแต่วันที่</label>
                <input type="date" id="dateFrom" class="filter-input">
            </div>
            <div class="filter-group">
                <label>ถึงวันที่</label>
                <input type="date" id="dateTo" class="filter-input">
            </div>
            <button onclick="loadPayments()" class="btn-export">
                🔍 ค้นหา
            </button>
            <button onclick="exportToExcel()" class="btn-export">
                📊 Export Excel
            </button>
        </div>
        
        <!-- Payments Table -->
        <div class="payments-card">
            <h2 style="margin-top: 0;">ประวัติการชำระเงิน</h2>
            
            <table class="payments-table" id="paymentsTable">
                <thead>
                    <tr>
                        <th>รหัสธุรกรรม</th>
                        <th>คำสั่งซื้อ</th>
                        <th>ลูกค้า</th>
                        <th>ยอดเงิน</th>
                        <th>สถานะ</th>
                        <th>วันที่</th>
                        <th>การจัดการ</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td colspan="7" style="text-align: center; padding: 40px;">กำลังโหลดข้อมูล...</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
    
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            // Set default date range (last 30 days)
            const today = new Date();
            const lastMonth = new Date(today.getTime() - (30 * 24 * 60 * 60 * 1000));
            
            document.getElementById('dateTo').valueAsDate = today;
            document.getElementById('dateFrom').valueAsDate = lastMonth;
            
            loadPayments();
        });
        
        async function loadPayments() {
            const dateFrom = document.getElementById('dateFrom').value;
            const dateTo = document.getElementById('dateTo').value;
            
            try {
                const params = new URLSearchParams();
                if (dateFrom) params.append('date_from', dateFrom);
                if (dateTo) params.append('date_to', dateTo);
                
                const response = await fetch(`api/admin_payments.php?${params}`);
                const data = await response.json();
                
                if (!data.success) {
                    throw new Error(data.message);
                }
                
                // Update stats
                document.getElementById('totalRevenue').textContent = 
                    parseFloat(data.stats.total_revenue || 0).toLocaleString() + ' ฿';
                document.getElementById('successfulPayments').textContent = 
                    data.stats.successful_count || 0;
                document.getElementById('pendingPayments').textContent = 
                    data.stats.pending_count || 0;
                document.getElementById('lastUpdate').textContent = 
                    new Date().toLocaleString('th-TH');
                
                // Update table
                const tbody = document.querySelector('#paymentsTable tbody');
                tbody.innerHTML = '';
                
                if (data.payments.length === 0) {
                    tbody.innerHTML = '<tr><td colspan="7" style="text-align: center; padding: 40px;">ไม่พบข้อมูลการชำระเงิน</td></tr>';
                    return;
                }
                
                data.payments.forEach(payment => {
                    const statusClass = payment.status === 'paid' ? 'status-success' : 
                                       payment.status === 'pending' ? 'status-pending' : 'status-failed';
                    const statusText = payment.status === 'paid' ? 'ชำระสำเร็จ' :
                                      payment.status === 'pending' ? 'รอตรวจสอบ' : 'ล้มเหลว';
                    
                    const row = `
                        <tr>
                            <td><strong>#P${payment.id}</strong></td>
                            <td>#${payment.order_id}</td>
                            <td>${payment.customer_name}</td>
                            <td><strong>${parseFloat(payment.amount).toLocaleString()} ฿</strong></td>
                            <td><span class="payment-status ${statusClass}">${statusText}</span></td>
                            <td>${new Date(payment.created_at).toLocaleDateString('th-TH')}</td>
                            <td>
                                <button class="btn-action btn-view" onclick="viewPaymentSlip(${payment.id})">
                                    🔍 ดูสลิป
                                </button>
                            </td>
                        </tr>
                    `;
                    tbody.insertAdjacentHTML('beforeend', row);
                });
                
            } catch (error) {
                console.error('Error loading payments:', error);
                Swal.fire({
                    icon: 'error',
                    title: 'เกิดข้อผิดพลาด',
                    text: error.message
                });
            }
        }
        
        async function viewPaymentSlip(paymentId) {
            try {
                // Fetch order details to get payment slip
                const params = new URLSearchParams();
                params.append('date_from', '2020-01-01'); // Get all orders
                params.append('date_to', '2030-12-31');
                
                const response = await fetch(`api/admin_payments.php?${params}`);
                const data = await response.json();
                
                if (!data.success) {
                    throw new Error(data.message);
                }
                
                // Find the specific payment
                const payment = data.payments.find(p => p.id == paymentId);
                
                if (!payment) {
                    throw new Error('ไม่พบข้อมูลการชำระเงิน');
                }
                
                // Check if payment slip exists (check both payment_slip and shipping_proof)
                const slipImage = payment.payment_slip || payment.shipping_proof || '';
                
                if (!slipImage || slipImage === '') {
                    Swal.fire({
                        icon: 'warning',
                        title: 'ไม่มีหลักฐานการโอนเงิน',
                        html: `
                            <p>ออเดอร์นี้ไม่มีสลิปการโอนเงินบันทึกไว้</p>
                            <p style="font-size: 0.9rem; color: #666; margin-top: 10px;">
                                (อาจเป็นออเดอร์เก่าก่อนระบบอัปเดต)
                            </p>
                        `,
                        confirmButtonText: 'ตกลง'
                    });
                    return;
                }
                
                // Show payment slip in SweetAlert with image
                Swal.fire({
                    title: `หลักฐานการโอนเงิน #P${paymentId}`,
                    html: `
                        <div style="margin: 20px 0;">
                            <p style="margin-bottom: 10px;"><strong>คำสั่งซื้อ:</strong> #${payment.order_id}</p>
                            <p style="margin-bottom: 10px;"><strong>ลูกค้า:</strong> ${payment.customer_name}</p>
                            <p style="margin-bottom: 20px;"><strong>ยอดเงิน:</strong> ${parseFloat(payment.amount).toLocaleString()} ฿</p>
                            <div id="slip-container">
                                <img src="${slipImage}" 
                                    id="slip-image"
                                    style="max-width: 100%; max-height: 500px; border-radius: 12px; box-shadow: 0 4px 12px rgba(0,0,0,0.15);"
                                    onerror="handleSlipError()">
                            </div>
                        </div>
                    `,
                    confirmButtonText: 'ปิด',
                    width: '700px',
                    customClass: {
                        popup: 'swal-wide'
                    },
                    didOpen: () => {
                        // Add error handler function to window scope
                        window.handleSlipError = function() {
                            const container = document.getElementById('slip-container');
                            if (container) {
                                container.innerHTML = '<div style="padding: 40px; color: #f5222d;"><h3>❌ ไม่สามารถโหลดรูปภาพได้</h3><p style="margin-top: 10px; color: #666;">ไฟล์อาจถูกลบหรือย้ายตำแหน่ง</p></div>';
                            }
                        };
                    }
                });
                
            } catch (error) {
                Swal.fire({
                    icon: 'error',
                    title: 'เกิดข้อผิดพลาด',
                    text: error.message
                });
            }
        }
        
        function exportToExcel() {
            const dateFrom = document.getElementById('dateFrom').value;
            const dateTo = document.getElementById('dateTo').value;
            
            window.location.href = `api/admin_payments.php?action=export&date_from=${dateFrom}&date_to=${dateTo}`;
            
            Swal.fire({
                icon: 'success',
                title: 'กำลังดาวน์โหลด',
                text: 'ไฟล์รายงานกำลังถูกสร้าง...',
                timer: 2000,
                showConfirmButton: false
            });
        }
    </script>
</body>
</html>
