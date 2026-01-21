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
    <title>จัดการลูกค้า - Admin Panel</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="assets/css/admin-modern.css">
    <link href="https://fonts.googleapis.com/css2?family=Prompt:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
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

        .header-content {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .header-left {
            display: flex;
            align-items: center;
            gap: 20px;
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
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .back-button:hover {
            background: var(--primary);
            color: white;
            transform: translateX(-5px);
            box-shadow: 0 4px 12px rgba(114, 46, 209, 0.3);
        }

        .header-title-group {
            display: flex;
            align-items: center;
            gap: 15px;
        }
        
        .page-header h1 {
            color: var(--primary);
            margin: 0;
            font-size: 2rem;
            font-weight: 700;
            background: linear-gradient(135deg, #722ed1, #eb2f96);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .customer-count-badge {
            background: linear-gradient(135deg, #722ed1, #eb2f96);
            color: white;
            padding: 8px 16px;
            border-radius: 20px;
            font-size: 0.9rem;
            font-weight: 600;
            box-shadow: 0 4px 12px rgba(114, 46, 209, 0.3);
        }
        
        .btn-primary {
            padding: 12px 30px;
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            color: white;
            border: none;
            border-radius: 12px;
            font-family: 'Prompt', sans-serif;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(255, 105, 180, 0.4);
        }
        
        /* Stats */
        .stats-row {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
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
            font-size: 2.2rem;
            font-weight: 700;
            color: var(--text-main);
        }
        
        /* Customers Table */
        .customers-card {
            background: white;
            padding: 30px;
            border-radius: 20px;
            box-shadow: var(--shadow);
            overflow: auto;
        }
        
        .filter-bar {
            display: flex;
            gap: 15px;
            margin-bottom: 20px;
            flex-wrap: wrap;
        }
        
        .search-input {
            flex: 1;
            min-width: 250px;
            padding: 12px 20px;
            border: 2px solid #f0f0f0;
            border-radius: 12px;
            font-family: 'Prompt', sans-serif;
            transition: all 0.3s ease;
        }
        
        .search-input:focus {
            outline: none;
            border-color: var(--primary);
        }
        
        .customers-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        
        .customers-table th {
            background: linear-gradient(135deg, #f8f9fa, #e9ecef);
            padding: 15px;
            text-align: left;
            font-weight: 600;
            color: var(--text-main);
            border-bottom: 3px solid var(--primary);
        }
        
        .customers-table td {
            padding: 18px 15px;
            border-bottom: 1px solid #f0f0f0;
        }
        
        .customers-table tr:hover {
            background: #fafafa;
        }
        
        .customer-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            object-fit: cover;
            background: linear-gradient(135deg, var(--primary), var(--secondary));
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
        
        .btn-coupon {
            background: #fff7e6;
            color: #fa8c16;
        }
        
        .btn-coupon:hover {
            background: #fa8c16;
            color: white;
        }
        
        /* Modal */
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.6);
            backdrop-filter: blur(5px);
        }
        
        .modal-content {
            background: white;
            margin: 5% auto;
            padding: 30px;
            border-radius: 24px;
            max-width: 500px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
        }
        
        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }
        
        .modal-header h2 {
            margin: 0;
            color: var(--primary);
        }
        
        .modal-close {
            font-size: 2rem;
            cursor: pointer;
            color: var(--text-secondary);
            line-height: 1;
        }
        
        .modal-close:hover {
            color: var(--primary);
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
            color: var(--text-main);
        }
        
        .form-group input,
        .form-group select {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid #f0f0f0;
            border-radius: 12px;
            font-family: 'Prompt', sans-serif;
        }
        
        .form-group input:focus,
        .form-group select:focus {
            outline: none;
            border-color: var(--primary);
        }
        
        .modal-actions {
            display: flex;
            gap: 10px;
            justify-content: flex-end;
            margin-top: 30px;
        }
        
        .btn-secondary {
            padding: 12px 30px;
            background: #f0f0f0;
            color: var(--text-main);
            border: none;
            border-radius: 12px;
            font-family: 'Prompt', sans-serif;
            font-weight: 500;
            cursor: pointer;
        }
        
        .btn-secondary:hover {
            background: #e0e0e0;
        }
        
        .coupon-code {
            background: #f0f0f0;
            padding: 15px;
            border-radius: 12px;
            text-align: center;
            font-size: 1.5rem;
            font-weight: 700;
            letter-spacing: 2px;
            color: var(--primary);
            margin: 20px 0;
        }
    </style>
</head>
<body>
    <?php include 'includes/header.php'; ?>
    
    <div class="admin-container">
        <div class="page-header">
            <div class="header-content">
                <div class="header-left">
                    <a href="admin_dashboard.php" class="back-button">
                        <i class="fas fa-arrow-left"></i>
                        <span>ย้อนกลับ</span>
                    </a>
                    <div class="header-title-group">
                        <h1>จัดการลูกค้า</h1>
                        <span id="customerCountBadge" class="customer-count-badge">
                            <i class="fas fa-users"></i> กำลังโหลด...
                        </span>
                    </div>
                </div>
                <button onclick="showCouponModal()" class="btn-primary">
                    <i class="fas fa-ticket-alt"></i> สร้างคูปองใหม่
                </button>
            </div>
        </div>
        
        <!-- Customer Statistics -->
        <div class="stats-row" id="customerStats">
            <div class="stat-card">
                <div class="label">ลูกค้าทั้งหมด</div>
                <div class="number" id="totalCustomers">-</div>
            </div>
            <div class="stat-card">
                <div class="label">ลูกค้าใหม่ (เดือนนี้)</div>
                <div class="number" id="newCustomers">-</div>
            </div>
            <div class="stat-card">
                <div class="label">ลูกค้า VIP</div>
                <div class="number" id="vipCustomers">-</div>
            </div>
            <div class="stat-card">
                <div class="label">Conversion Rate</div>
                <div class="number" id="conversionRate">-</div>
            </div>
        </div>
        
        <!-- Customers Table -->
        <div class="customers-card">
            <h2 style="margin-top: 0;">รายชื่อลูกค้า</h2>
            
            <div class="filter-bar">
                <input type="text" id="searchCustomer" class="search-input" 
                       placeholder="🔍 ค้นหาชื่อ อีเมล หรือเบอร์โทร..." 
                       onkeyup="loadCustomers()">
            </div>
            
            <table class="customers-table" id="customersTable">
                <thead>
                    <tr>
                        <th>ลูกค้า</th>
                        <th>อีเมล</th>
                        <th>เบอร์โทร</th>
                        <th>คำสั่งซื้อ</th>
                        <th>ยอดรวม</th>
                        <th>สมัครเมื่อ</th>
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
    
    <!-- Create Coupon Modal -->
    <div id="couponModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>สร้างคูปองส่วนลด</h2>
                <span class="modal-close" onclick="closeModal()">&times;</span>
            </div>
            
            <div class="form-group">
                <label>รหัสคูปอง</label>
                <input type="text" id="couponCode" placeholder="เช่น SUMMER2026" maxlength="20" style="text-transform: uppercase;">
            </div>
            
            <div class="form-group">
                <label>ประเภทส่วนลด</label>
                <select id="couponType">
                    <option value="percent">ส่วนลด %</option>
                    <option value="fixed">ส่วนลดเงินสด (บาท)</option>
                    <option value="freeship">ฟรีค่าส่ง</option>
                </select>
            </div>
            
            <div class="form-group">
                <label>มูลค่าส่วนลด</label>
                <input type="number" id="couponValue" placeholder="เช่น 10, 50, 100" min="0">
            </div>
            
            <div class="form-group">
                <label>ยอดซื้อขั้นต่ำ (บาท)</label>
                <input type="number" id="minPurchase" placeholder="0 = ไม่จำกัด" min="0" value="0">
            </div>
            
            <div class="form-group">
                <label>วันเริ่มต้น <span style="color: #999; font-weight: 400;">(ไม่บังคับ)</span></label>
                <input type="date" id="startDate">
            </div>
            
            <div class="form-group">
                <label>วันหมดอายุ <span style="color: #999; font-weight: 400;">(ไม่บังคับ)</span></label>
                <input type="date" id="expiryDate">
            </div>
            
            <div class="modal-actions">
                <button class="btn-secondary" onclick="closeModal()">ยกเลิก</button>
                <button class="btn-primary" onclick="createCoupon()">สร้างคูปอง</button>
            </div>
        </div>
    </div>
    
    <script>
        document.addEventListener('DOMContentLoaded', loadCustomers);
        
        async function loadCustomers() {
            const search = document.getElementById('searchCustomer').value;
            
            try {
                const params = new URLSearchParams();
                if (search) params.append('search', search);
                
                const response = await fetch(`api/admin_customers.php?${params}`);
                const data = await response.json();
                
                if (!data.success) {
                    throw new Error(data.message);
                }
                
                // Update stats
                document.getElementById('totalCustomers').textContent = data.stats.total || 0;
                document.getElementById('newCustomers').textContent = data.stats.new_this_month || 0;
                document.getElementById('vipCustomers').textContent = data.stats.vip || 0;
                document.getElementById('conversionRate').textContent = 
                    (data.stats.conversion_rate || 0) + '%';
                
                // Update count badge in header
                const badge = document.getElementById('customerCountBadge');
                if (badge) {
                    badge.innerHTML = `<i class="fas fa-users"></i> ${data.stats.total || 0} คน`;
                }
                
                // Update table
                const tbody = document.querySelector('#customersTable tbody');
                tbody.innerHTML = '';
                
                if (data.customers.length === 0) {
                    tbody.innerHTML = '<tr><td colspan="7" style="text-align: center; padding: 40px;">ไม่พบข้อมูลลูกค้า</td></tr>';
                    return;
                }
                
                data.customers.forEach(customer => {
                    const row = `
                        <tr>
                            <td>
                                <div style="display: flex; align-items: center; gap: 12px;">
                                    <img src="${customer.avatar || 'assets/img/default-avatar.png'}" 
                                         class="customer-avatar" 
                                         onerror="this.src='assets/img/default-avatar.png'">
                                    <strong>${customer.username}</strong>
                                </div>
                            </td>
                            <td>${customer.email}</td>
                            <td>${customer.phone || '-'}</td>
                            <td>${customer.total_orders || 0} ครั้ง</td>
                            <td><strong>${parseFloat(customer.total_spent || 0).toLocaleString()} ฿</strong></td>
                            <td>${new Date(customer.created_at).toLocaleDateString('th-TH')}</td>
                            <td>
                                <button class="btn-action btn-view" onclick="viewCustomer(${customer.id})">
                                    🔍 ดูรายละเอียด
                                </button>
                            </td>
                        </tr>
                    `;
                    tbody.insertAdjacentHTML('beforeend', row);
                });
                
            } catch (error) {
                console.error('Error loading customers:', error);
                Swal.fire({
                    icon: 'error',
                    title: 'เกิดข้อผิดพลาด',
                    text: error.message
                });
            }
        }
        
        function viewCustomer(customerId) {
            window.location.href = `customer_details.php?id=${customerId}`;
        }
        
        function showCouponModal() {
            document.getElementById('couponModal').style.display = 'block';
        }
        
        function closeModal() {
            document.getElementById('couponModal').style.display = 'none';
        }
        
        async function createCoupon() {
            const code = document.getElementById('couponCode').value.toUpperCase();
            const type = document.getElementById('couponType').value;
            const value = document.getElementById('couponValue').value;
            const minPurchase = document.getElementById('minPurchase').value;
            const startDate = document.getElementById('startDate').value;
            const expiryDate = document.getElementById('expiryDate').value;
            
            if (!code || !value) {
                Swal.fire({
                    icon: 'warning',
                    title: 'กรุณากรอกข้อมูลให้ครบ',
                    text: 'ต้องกรอกรหัสคูปองและมูลค่าส่วนลด'
                });
                return;
            }
            
            try {
                const response = await fetch('api/admin_customers.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        action: 'create_coupon',
                        code: code,
                        type: type,
                        value: value,
                        min_purchase: minPurchase,
                        start_date: startDate,
                        expiry_date: expiryDate
                    })
                });
                
                const data = await response.json();
                
                if (!data.success) {
                    throw new Error(data.message);
                }
                
                await Swal.fire({
                    icon: 'success',
                    title: 'สร้างคูปองสำเร็จ!',
                    html: `<div class="coupon-code">${code}</div>`,
                    timer: 3000
                });
                
                closeModal();
                
            } catch (error) {
                Swal.fire({
                    icon: 'error',
                    title: 'เกิดข้อผิดพลาด',
                    text: error.message
                });
            }
        }
        
        window.onclick = function(event) {
            if (event.target.classList.contains('modal')) {
                closeModal();
            }
        }
    </script>
</body>
</html>
