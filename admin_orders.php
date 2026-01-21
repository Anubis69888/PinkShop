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
    <title>จัดการคำสั่งซื้อ - Admin Panel</title>
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

        .order-count-badge {
            background: linear-gradient(135deg, #722ed1, #eb2f96);
            color: white;
            padding: 8px 16px;
            border-radius: 20px;
            font-size: 0.9rem;
            font-weight: 600;
            box-shadow: 0 4px 12px rgba(114, 46, 209, 0.3);
        }
        
        .page-header p {
            color: var(--text-secondary);
            margin: 0;
        }
        
        /* Filter Panel */
        .filter-panel {
            background: white;
            padding: 20px 30px;
            border-radius: 20px;
            box-shadow: var(--shadow);
            margin-bottom: 20px;
            display: flex;
            gap: 15px;
            flex-wrap: wrap;
            align-items: center;
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
        
        .filter-select, .filter-input {
            padding: 10px 15px;
            border: 2px solid #f0f0f0;
            border-radius: 12px;
            font-family: 'Prompt', sans-serif;
            transition: all 0.3s ease;
        }
        
        .filter-select:focus, .filter-input:focus {
            outline: none;
            border-color: var(--primary);
        }
        
        .filter-btn {
            padding: 10px 25px;
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            color: white;
            border: none;
            border-radius: 12px;
            font-family: 'Prompt', sans-serif;
            font-weight: 500;
            cursor: pointer;
            margin-top: 20px;
            transition: all 0.3s ease;
        }
        
        .filter-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(255, 105, 180, 0.4);
        }
        
        /* Stats Summary */
        .stats-row {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .stat-box {
            background: white;
            padding: 20px;
            border-radius: 18px;
            box-shadow: var(--shadow);
            text-align: center;
        }
        
        .stat-box .number {
            font-size: 2rem;
            font-weight: 700;
            margin: 10px 0;
        }
        
        .stat-box .label {
            color: var(--text-secondary);
            font-size: 0.9rem;
        }
        
        .stat-pending { border-left: 5px solid #ffa940; }
        .stat-paid { border-left: 5px solid #52c41a; }
        .stat-shipping { border-left: 5px solid #1890ff; }
        .stat-complete { border-left: 5px solid #722ed1; }
        
        /* Orders Table */
        .orders-card {
            background: white;
            padding: 30px;
            border-radius: 20px;
            box-shadow: var(--shadow);
            overflow: auto;
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
            padding: 18px 15px;
            border-bottom: 1px solid #f0f0f0;
            vertical-align: middle;
        }
        
        .orders-table tr:hover {
            background: #fafafa;
        }
        
        .status-badge {
            padding: 6px 16px;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 500;
            display: inline-block;
            white-space: nowrap;
        }
        
        .status-pending {
            background: #fff7e6;
            color: #fa8c16;
        }
        
        .status-paid {
            background: #f6ffed;
            color: #52c41a;
        }
        
        .status-shipping {
            background: #e6f7ff;
            color: #1890ff;
        }
        
        .status-complete {
            background: #f9f0ff;
            color: #722ed1;
        }
        
        .status-cancelled {
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
        
        .btn-update {
            background: #f6ffed;
            color: #52c41a;
        }
        
        .btn-update:hover {
            background: #52c41a;
            color: white;
        }
        
        .btn-track {
            background: #fff7e6;
            color: #fa8c16;
        }
        
        .btn-track:hover {
            background: #fa8c16;
            color: white;
        }
        
        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: var(--text-secondary);
        }
        
        .empty-state-icon {
            font-size: 4rem;
            margin-bottom: 20px;
            opacity: 0.5;
        }
        
        /* Modal Styles */
        .modal {
            display: none;
            position: fixed;
            z-index: 9999;
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
            max-width: 600px;
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
        
        .form-group select,
        .form-group input {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid #f0f0f0;
            border-radius: 12px;
            font-family: 'Prompt', sans-serif;
            transition: all 0.3s ease;
        }
        
        .form-group select:focus,
        .form-group input:focus {
            outline: none;
            border-color: var(--primary);
        }
        
        .modal-actions {
            display: flex;
            gap: 10px;
            justify-content: flex-end;
            margin-top: 30px;
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
        
        .btn-secondary {
            padding: 12px 30px;
            background: #f0f0f0;
            color: var(--text-main);
            border: none;
            border-radius: 12px;
            font-family: 'Prompt', sans-serif;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .btn-secondary:hover {
            background: #e0e0e0;
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
                        <h1>จัดการคำสั่งซื้อ</h1>
                        <span id="orderCountBadge" class="order-count-badge">
                            <i class="fas fa-shopping-cart"></i> กำลังโหลด...
                        </span>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Order Statistics -->
        <div class="stats-row" id="orderStats">
            <div class="stat-box stat-pending">
                <div class="label">รอชำระเงิน</div>
                <div class="number" id="pendingCount">-</div>
            </div>
            <div class="stat-box stat-paid">
                <div class="label">ชำระเงินแล้ว</div>
                <div class="number" id="paidCount">-</div>
            </div>
            <div class="stat-box stat-shipping">
                <div class="label">กำลังจัดส่ง</div>
                <div class="number" id="shippingCount">-</div>
            </div>
            <div class="stat-box stat-complete">
                <div class="label">เสร็จสิ้น</div>
                <div class="number" id="completeCount">-</div>
            </div>
        </div>
        
        <!-- Filter Panel -->
        <div class="filter-panel">
            <div class="filter-group">
                <label>สถานะ</label>
                <select id="filterStatus" class="filter-select">
                    <option value="">ทั้งหมด</option>
                    <option value="pending">รอชำระเงิน</option>
                    <option value="paid">ชำระเงินแล้ว</option>
                    <option value="shipping">กำลังจัดส่ง</option>
                    <option value="completed">เสร็จสิ้น</option>
                    <option value="cancelled">ยกเลิก</option>
                </select>
            </div>
            
            <div class="filter-group">
                <label>ค้นหา (รหัสคำสั่งซื้อ / ลูกค้า)</label>
                <input type="text" id="filterSearch" class="filter-input" placeholder="ค้นหา...">
            </div>
            
            <button onclick="loadOrders()" class="filter-btn">🔍 ค้นหา</button>
        </div>
        
        <!-- Orders Table -->
        <div class="orders-card">
            <h2 style="margin-top: 0; color: var(--text-main);">รายการคำสั่งซื้อ</h2>
            
            <table class="orders-table" id="ordersTable">
                <thead>
                    <tr>
                        <th>รหัสคำสั่งซื้อ</th>
                        <th>ลูกค้า</th>
                        <th>ยอดรวม</th>
                        <th>สถานะ</th>
                        <th>วันที่สั่งซื้อ</th>
                        <th>การจัดการ</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td colspan="6" style="text-align: center; padding: 40px;">
                            <div class="loading">กำลังโหลดข้อมูล...</div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
    
    <!-- Update Status Modal -->
    <div id="statusModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>เปลี่ยนสถานะคำสั่งซื้อ</h2>
                <span class="modal-close" onclick="closeModal('statusModal')">&times;</span>
            </div>
            
            <div class="form-group">
                <label>คำสั่งซื้อ #<span id="modalOrderId"></span></label>
                <select id="newStatus" class="form-group">
                    <option value="pending">รอชำระเงิน</option>
                    <option value="paid">ชำระเงินแล้ว</option>
                    <option value="shipping">กำลังจัดส่ง</option>
                    <option value="completed">เสร็จสิ้น</option>
                    <option value="cancelled">ยกเลิก</option>
                </select>
            </div>
            
            <div class="modal-actions">
                <button class="btn-secondary" onclick="closeModal('statusModal')">ยกเลิก</button>
                <button class="btn-primary" onclick="updateOrderStatus()">บันทึก</button>
            </div>
        </div>
    </div>
    
    <!-- Tracking Modal -->
    <div id="trackingModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>เพิ่มเลขพัสดุ</h2>
                <span class="modal-close" onclick="closeModal('trackingModal')">&times;</span>
            </div>
            
            <div class="form-group">
                <label>คำสั่งซื้อ #<span id="trackingOrderId"></span></label>
                <input type="text" id="trackingNumber" placeholder="กรอกเลขพัสดุ" />
            </div>
            
            <div class="modal-actions">
                <button class="btn-secondary" onclick="closeModal('trackingModal')">ยกเลิก</button>
                <button class="btn-primary" onclick="saveTracking()">บันทึก</button>
            </div>
        </div>
    </div>
    
    <script>
        let currentOrderId = null;
        
        // Load orders on page load
        document.addEventListener('DOMContentLoaded', loadOrders);
        
        async function loadOrders() {
            const status = document.getElementById('filterStatus').value;
            const search = document.getElementById('filterSearch').value;
            
            try {
                const params = new URLSearchParams();
                if (status) params.append('status', status);
                if (search) params.append('search', search);
                
                const response = await fetch(`api/admin_orders.php?${params}`);
                const data = await response.json();
                
                if (!data.success) {
                    throw new Error(data.message);
                }
                
                // Update statistics
                document.getElementById('pendingCount').textContent = data.stats.pending || 0;
                document.getElementById('paidCount').textContent = data.stats.paid || 0;
                document.getElementById('shippingCount').textContent = data.stats.shipping || 0;
                document.getElementById('completeCount').textContent = data.stats.completed || 0;
                
                // Update count badge in header
                const totalOrders = (data.stats.pending || 0) + (data.stats.paid || 0) + (data.stats.shipping || 0) + (data.stats.completed || 0);
                const badge = document.getElementById('orderCountBadge');
                if (badge) {
                    badge.innerHTML = `<i class="fas fa-shopping-cart"></i> ${totalOrders} รายการ`;
                }
                
                // Update table
                const tbody = document.querySelector('#ordersTable tbody');
                tbody.innerHTML = '';
                
                if (data.orders.length === 0) {
                    tbody.innerHTML = `
                        <tr>
                            <td colspan="6">
                                <div class="empty-state">
                                    <div class="empty-state-icon">📦</div>
                                    <p>ไม่พบคำสั่งซื้อ</p>
                                </div>
                            </td>
                        </tr>
                    `;
                    return;
                }
                
                data.orders.forEach(order => {
                    // Use normalized status for CSS and Thai status for display
                    const normalizedStatus = order.status_normalized || order.status;
                    const displayStatus = order.status_thai || order.status;
                    const statusClass = `status-${normalizedStatus}`;
                    const statusText = getStatusText(normalizedStatus) || displayStatus;
                    
                    // Fix amount - use total_amount or total
                    const amount = parseFloat(order.total_amount || order.total || 0);
                    
                    const row = `
                        <tr>
                            <td><strong>#${order.id}</strong></td>
                            <td>${order.customer_name}<br><small style="color: var(--text-secondary);">${order.customer_email || ''}</small></td>
                            <td><strong>${amount.toLocaleString()} ฿</strong></td>
                            <td><span class="status-badge ${statusClass}">${statusText}</span></td>
                            <td>${formatDate(order.created_at)}</td>
                            <td>
                                <button class="btn-action btn-view" onclick="viewOrder(${order.id})">🔍 ดู</button>
                                <button class="btn-action btn-update" onclick="showStatusModal(${order.id}, '${normalizedStatus}')">✏️ สถานะ</button>
                                ${normalizedStatus === 'shipping' || normalizedStatus === 'paid' ? 
                                    `<button class="btn-action btn-track" onclick="showTrackingModal(${order.id})">📮 Tracking</button>` : ''}
                            </td>
                        </tr>
                    `;
                    tbody.insertAdjacentHTML('beforeend', row);
                });
                
            } catch (error) {
                console.error('Error loading orders:', error);
                Swal.fire({
                    icon: 'error',
                    title: 'เกิดข้อผิดพลาด',
                    text: error.message
                });
            }
        }
        
        function getStatusText(status) {
            const statusMap = {
                'pending': 'รอชำระเงิน',
                'paid': 'ชำระเงินแล้ว',
                'shipping': 'กำลังจัดส่ง',
                'completed': 'เสร็จสิ้น',
                'cancelled': 'ยกเลิก'
            };
            return statusMap[status] || status;
        }
        
        function formatDate(dateString) {
            const date = new Date(dateString);
            return date.toLocaleDateString('th-TH', {
                year: 'numeric',
                month: 'short',
                day: 'numeric',
                hour: '2-digit',
                minute: '2-digit'
            });
        }
        
        async function viewOrder(orderId) {
            // Find order from loaded data
            try {
                const response = await fetch(`api/admin_orders.php`);
                const data = await response.json();
                
                if (!data.success) throw new Error('Failed to load order');
                
                const order = data.orders.find(o => o.id == orderId);
                if (!order) throw new Error('Order not found');
                
                // Build items HTML
                let itemsHtml = '<div style="max-height: 200px; overflow-y: auto;">';
                if (order.items && order.items.length > 0) {
                    order.items.forEach(item => {
                        itemsHtml += `
                            <div style="display: flex; justify-content: space-between; padding: 8px 0; border-bottom: 1px solid #eee;">
                                <span>${item.name}</span>
                                <span><strong>x${item.qty}</strong> = ${(item.price * item.qty).toLocaleString()} ฿</span>
                            </div>
                        `;
                    });
                }
                itemsHtml += '</div>';
                
                // Shipping info
                const ship = order.shipping_info || {};
                const shippingHtml = `
                    <div style="background: #f8f9fa; padding: 15px; border-radius: 10px; margin-top: 15px;">
                        <strong>📦 ข้อมูลจัดส่ง</strong><br>
                        👤 ${ship.fullname || '-'}<br>
                        📍 ${ship.address || '-'}<br>
                        📞 ${ship.phone || '-'}<br>
                        ${order.tracking_number ? `🚚 <strong>Tracking:</strong> ${order.tracking_number}` : ''}
                    </div>
                `;
                
                // Amount
                const amount = parseFloat(order.total_amount || order.total || 0);
                
                Swal.fire({
                    title: `📋 คำสั่งซื้อ #${order.id}`,
                    html: `
                        <div style="text-align: left;">
                            <div style="display: flex; justify-content: space-between; margin-bottom: 15px;">
                                <span><strong>สถานะ:</strong></span>
                                <span class="status-badge status-${order.status_normalized || 'pending'}">${order.status_thai || order.status}</span>
                            </div>
                            <div style="display: flex; justify-content: space-between; margin-bottom: 15px;">
                                <span><strong>ลูกค้า:</strong></span>
                                <span>${order.customer_name}</span>
                            </div>
                            <div style="display: flex; justify-content: space-between; margin-bottom: 15px;">
                                <span><strong>วันที่สั่ง:</strong></span>
                                <span>${formatDate(order.created_at)}</span>
                            </div>
                            <hr>
                            <strong>🛒 รายการสินค้า:</strong>
                            ${itemsHtml}
                            <div style="display: flex; justify-content: space-between; margin-top: 15px; font-size: 1.3em; color: var(--primary);">
                                <span><strong>ยอดรวม:</strong></span>
                                <span><strong>${amount.toLocaleString()} ฿</strong></span>
                            </div>
                            ${shippingHtml}
                        </div>
                    `,
                    width: 550,
                    showCloseButton: true,
                    confirmButtonText: 'ปิด',
                    confirmButtonColor: '#d76bb3'
                });
                
            } catch (error) {
                Swal.fire({
                    icon: 'error',
                    title: 'เกิดข้อผิดพลาด',
                    text: error.message
                });
            }
        }
        
        function showStatusModal(orderId, currentStatus) {
            currentOrderId = orderId;
            document.getElementById('modalOrderId').textContent = orderId;
            document.getElementById('newStatus').value = currentStatus;
            document.getElementById('statusModal').style.display = 'block';
        }
        
        function showTrackingModal(orderId) {
            currentOrderId = orderId;
            document.getElementById('trackingOrderId').textContent = orderId;
            document.getElementById('trackingNumber').value = '';
            document.getElementById('trackingModal').style.display = 'block';
        }
        
        function closeModal(modalId) {
            document.getElementById(modalId).style.display = 'none';
            currentOrderId = null;
        }
        
        async function updateOrderStatus() {
            const newStatus = document.getElementById('newStatus').value;
            
            try {
                const response = await fetch('api/admin_orders.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        action: 'update_status',
                        order_id: currentOrderId,
                        status: newStatus
                    })
                });
                
                const data = await response.json();
                
                if (!data.success) {
                    throw new Error(data.message);
                }
                
                await Swal.fire({
                    icon: 'success',
                    title: 'สำเร็จ!',
                    text: 'อัปเดตสถานะคำสั่งซื้อแล้ว',
                    timer: 1500,
                    showConfirmButton: false
                });
                
                closeModal('statusModal');
                loadOrders();
                
            } catch (error) {
                Swal.fire({
                    icon: 'error',
                    title: 'เกิดข้อผิดพลาด',
                    text: error.message
                });
            }
        }
        
        async function saveTracking() {
            const trackingNumber = document.getElementById('trackingNumber').value.trim();
            
            if (!trackingNumber) {
                Swal.fire({
                    icon: 'warning',
                    title: 'กรุณากรอกเลขพัสดุ'
                });
                return;
            }
            
            try {
                const response = await fetch('api/admin_orders.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        action: 'update_tracking',
                        order_id: currentOrderId,
                        tracking_number: trackingNumber
                    })
                });
                
                const data = await response.json();
                
                if (!data.success) {
                    throw new Error(data.message);
                }
                
                await Swal.fire({
                    icon: 'success',
                    title: 'สำเร็จ!',
                    text: 'บันทึกเลขพัสดุแล้ว',
                    timer: 1500,
                    showConfirmButton: false
                });
                
                closeModal('trackingModal');
                loadOrders();
                
            } catch (error) {
                Swal.fire({
                    icon: 'error',
                    title: 'เกิดข้อผิดพลาด',
                    text: error.message
                });
            }
        }
        
        // Close modal when clicking outside
        window.onclick = function(event) {
            if (event.target.classList.contains('modal')) {
                event.target.style.display = 'none';
            }
        }
    </script>
</body>
</html>
