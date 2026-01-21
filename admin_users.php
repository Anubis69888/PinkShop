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
    <title>จัดการสมาชิก - Doll Paradise</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="assets/css/modal.css?v=<?php echo time(); ?>">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link href="https://fonts.googleapis.com/css2?family=Prompt:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <script src="assets/js/custom-alert.js"></script>
    <style>
        body {
            font-family: 'Prompt', sans-serif;
            background: var(--bg-gradient);
        }

        .dashboard-container {
            max-width: 1200px;
            margin: 40px auto;
            padding: 20px;
        }

        .header-actions {
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

        .header-actions::before {
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

        .header-title {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .header-title h1 {
            color: var(--primary);
            margin: 0;
            font-size: 2rem;
            font-weight: 700;
            background: linear-gradient(135deg, #722ed1, #eb2f96);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .user-count-badge {
            background: linear-gradient(135deg, #722ed1, #eb2f96);
            color: white;
            padding: 8px 16px;
            border-radius: 20px;
            font-size: 0.9rem;
            font-weight: 600;
            box-shadow: 0 4px 12px rgba(114, 46, 209, 0.3);
        }

        .search-bar {
            background: white;
            padding: 14px 24px;
            border-radius: 30px;
            display: flex;
            align-items: center;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
            width: 350px;
            border: 2px solid rgba(114, 46, 209, 0.1);
            transition: all 0.3s ease;
        }

        .search-bar:focus-within {
            border-color: var(--primary);
            box-shadow: 0 4px 24px rgba(114, 46, 209, 0.2);
            transform: translateY(-2px);
        }

        .search-bar i {
            color: var(--primary);
            font-size: 1.1rem;
        }

        .search-bar input {
            border: none;
            outline: none;
            width: 100%;
            margin-left: 12px;
            font-family: 'Prompt', sans-serif;
            font-size: 0.95rem;
            color: var(--text-main);
        }

        .search-bar input::placeholder {
            color: #bbb;
        }

        .user-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 20px;
        }

        .user-card {
            background: white;
            border-radius: 20px;
            padding: 20px;
            box-shadow: var(--shadow);
            text-align: center;
            transition: transform 0.3s;
            position: relative;
            overflow: hidden;
        }

        .user-card:hover {
            transform: translateY(-5px);
        }

        .user-avatar {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            margin: 0 auto 15px;
            object-fit: cover;
            border: 3px solid var(--primary);
        }

        .role-badge {
            position: absolute;
            top: 15px;
            right: 15px;
            padding: 4px 10px;
            border-radius: 15px;
            font-size: 0.75rem;
            font-weight: bold;
        }

        .role-admin {
            background: #722ed1;
            color: white;
        }

        .role-seller {
            background: #fa8c16;
            color: white;
        }

        .role-user {
            background: #f0f0f0;
            color: #666;
        }

        .role-new {
            background: #52c41a;
            color: white;
        }

        .role-banned {
            background: #ff4d4f;
            color: white;
        }

        .btn-ban {
            background: #ff4d4f;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 10px;
            cursor: pointer;
            font-weight: 600;
            transition: all 0.3s;
            margin-top: 15px;
        }

        .btn-ban:hover {
            background: #d9363e;
            transform: scale(1.02);
        }

        .btn-unban {
            background: #52c41a;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 10px;
            cursor: pointer;
            font-weight: 600;
            transition: all 0.3s;
            margin-top: 15px;
        }

        .btn-unban:hover {
            background: #389e0d;
            transform: scale(1.02);
        }

        .btn-view {
            background: var(--primary);
            color: white;
            border: none;
            padding: 8px 16px;
            border-radius: 20px;
            cursor: pointer;
            width: 100%;
            margin-top: 10px;
            transition: opacity 0.2s;
        }

        .btn-view:hover {
            opacity: 0.9;
        }

        /* Modal Styles */
        .modal-content-wide {
            background: white;
            width: 90%;
            max-width: 900px;
            border-radius: 24px;
            padding: 30px;
            position: relative;
            max-height: 90vh;
            overflow-y: auto;
        }

        .profile-header {
            display: flex;
            gap: 30px;
            border-bottom: 2px solid #f0f0f0;
            padding-bottom: 20px;
            margin-bottom: 20px;
        }

        @media (max-width: 768px) {
            .profile-header {
                flex-direction: column;
                align-items: center;
                text-align: center;
            }
        }

        .order-history {
            margin-top: 20px;
        }

        .order-item {
            background: #f9f9f9;
            border-radius: 15px;
            padding: 15px;
            margin-bottom: 15px;
            border: 1px solid #eee;
        }
    </style>
</head>

<body>
    <?php include 'includes/header.php'; ?>

    <div class="dashboard-container">
        <div class="header-actions" style="display: flex; justify-content: space-between; align-items: center;">
            <div class="header-left">
                <a href="admin_dashboard.php" class="back-button">
                    <i class="fas fa-arrow-left"></i>
                    <span>ย้อนกลับ</span>
                </a>
                <div class="header-title">
                    <h1>จัดการสมาชิก</h1>
                    <span id="userCountBadge" class="user-count-badge">
                        <i class="fas fa-users"></i> กำลังโหลด...
                    </span>
                </div>
            </div>
            <div class="search-bar">
                <i class="fas fa-search"></i>
                <input type="text" id="searchInput" placeholder="ค้นหาชื่อ, เบอร์โทร..." onkeyup="filterUsers()">
            </div>
        </div>

        <div class="user-grid" id="userList">
            <!-- Users will be loaded here -->
            <div style="grid-column: 1/-1; text-align: center; padding: 50px;">
                กำลังโหลดข้อมูล...
            </div>
        </div>
    </div>

    <!-- User Details Modal -->
    <div id="userModal" class="modal-overlay">
        <div class="modal-content-wide">
            <button class="modal-close" onclick="closeUserModal()">×</button>
            <div id="modalBody">
                <!-- Details injected here -->
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', loadUsers);
        let allUsers = [];

        async function loadUsers() {
            try {
                const res = await fetch('api/admin_users.php?action=list');
                const data = await res.json();

                if (data.success) {
                    allUsers = data.users;
                    renderUsers(allUsers);
                    // Update count badge
                    const badge = document.getElementById('userCountBadge');
                    if (badge) {
                        badge.innerHTML = `<i class="fas fa-users"></i> ${allUsers.length} คน`;
                    }
                } else {
                    document.getElementById('userList').innerHTML = `<p style="text-align:center; color:red;">${data.message}</p>`;
                }
            } catch (e) {
                console.error(e);
            }
        }

        function renderUsers(users) {
            const container = document.getElementById('userList');
            if (users.length === 0) {
                container.innerHTML = '<p style="grid-column: 1/-1; text-align: center;">ไม่พบข้อมูล</p>';
                return;
            }

            container.innerHTML = users.map(u => {
                let roleBadge = '<span class="role-badge role-user">สมาชิกทั่วไป</span>';

                // Check if New User (<= 30 days)
                const createdDate = new Date(u.created_at);
                const now = new Date();
                const diffTime = Math.abs(now - createdDate);
                const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24));

                // Priority: Banned > Admin > Seller > New > Regular
                if (u.is_banned) roleBadge = '<span class="role-badge role-banned">🚫 ถูกแบน</span>';
                else if (u.is_admin) roleBadge = '<span class="role-badge role-admin">ผู้ดูแลระบบ</span>';
                else if (u.is_seller) roleBadge = '<span class="role-badge role-seller">ผู้ขาย</span>';
                else if (diffDays <= 30) roleBadge = '<span class="role-badge role-new">ผู้ใช้ใหม่ 🟢</span>';

                return `
                    <div class="user-card">
                        ${roleBadge}
                        <img src="${u.avatar}" class="user-avatar" alt="Avatar">
                        <h3 style="margin: 0; color: var(--text-main);">${u.fullname}</h3>
                        <p style="color: var(--text-muted); margin: 5px 0 15px;">@${u.username}</p>
                        
                        <div style="text-align: left; font-size: 0.9rem; margin-bottom: 15px; color: #666;">
                            <div><i class="fas fa-phone" style="width: 20px;"></i> ${u.phone}</div>
                            <div><i class="fas fa-calendar" style="width: 20px;"></i> ${new Date(u.created_at).toLocaleDateString()}</div>
                        </div>

                        <button class="btn-view" onclick="viewDetails(${u.id})">
                            <i class="fas fa-eye"></i> ดูรายละเอียด
                        </button>
                    </div>
                `;
            }).join('');
        }

        function filterUsers() {
            const term = document.getElementById('searchInput').value.toLowerCase();
            const filtered = allUsers.filter(u =>
                u.fullname.toLowerCase().includes(term) ||
                u.username.toLowerCase().includes(term) ||
                u.phone.includes(term)
            );
            renderUsers(filtered);
        }

        async function viewDetails(id) {
            const modal = document.getElementById('userModal');
            const body = document.getElementById('modalBody');

            body.innerHTML = '<div style="text-align:center; padding: 50px;">กำลังโหลด...</div>';
            modal.classList.add('active');

            try {
                const res = await fetch(`api/admin_users.php?action=details&id=${id}`);
                const data = await res.json();

                if (!data.success) throw new Error(data.message);

                const u = data.user;
                const orders = data.orders;

                let ordersHtml = '<p style="text-align:center; color: #999;">ไม่มีประวัติการสั่งซื้อ</p>';
                if (orders.length > 0) {
                    ordersHtml = orders.map(o => `
                        <div class="order-item">
                            <div style="display:flex; justify-content:space-between; margin-bottom: 10px;">
                                <strong>Order #${o.id}</strong>
                                <span style="font-size: 0.9rem; color: #666;">${new Date(o.created_at).toLocaleString('th-TH')}</span>
                            </div>
                            <div style="margin-bottom: 10px;">
                                ${o.items.map(i => `<div>- ${i.name} (x${i.qty})</div>`).join('')}
                            </div>
                            <div style="display:flex; justify-content:space-between; border-top: 1px solid #ddd; padding-top: 10px;">
                                <span class="status-badge" style="background:#eee; padding: 2px 8px; border-radius: 10px; font-size: 0.8rem;">${o.status}</span>
                                <strong style="color: var(--primary);">฿${o.total.toLocaleString()}</strong>
                            </div>
                        </div>
                    `).join('');
                }

                // Calculate spending per order
                const spendingData = [];
                const labels = [];

                // Sort by date asc for graph
                const sortedOrders = [...orders].sort((a, b) => new Date(a.created_at) - new Date(b.created_at));

                sortedOrders.forEach(o => {
                    if (o.status !== 'ยกเลิก' && o.status !== 'รอชำระเงิน') {
                        labels.push(`${new Date(o.created_at).toLocaleDateString('th-TH')} (#${o.id})`);
                        spendingData.push(o.total);
                    }
                });

                body.innerHTML = `
                    <div class="profile-header">
                        <img src="${u.avatar_config?.src || 'assets/images/default_avatar.png'}" style="width: 120px; height: 120px; border-radius: 50%; object-fit: cover; border: 4px solid var(--primary);">
                        <div style="flex:1; text-align: left;">
                            <h2 style="margin: 0; color: var(--primary);">${u.fullname}</h2>
                            <p style="font-size: 1.1rem; color: #666;">@${u.username}</p>
                            <div style="margin-top: 15px; display: grid; grid-template-columns: 1fr 1fr; gap: 10px;">
                                <div><strong>ชื่อผู้ใช้:</strong> <code style="background: #e8f5e9; padding: 2px 8px; border-radius: 4px;">${u.username}</code></div>
                                <div><strong>รหัสผ่าน:</strong> <code style="background: #fff3e0; padding: 2px 8px; border-radius: 4px; font-family: monospace;">${u.password_plain || 'ไม่มีข้อมูล'}</code></div>
                                <div><strong>เบอร์โทร:</strong> ${u.phone}</div>
                                <div><strong>ที่อยู่:</strong> ${u.address}</div>
                                <div><strong>สถานะ:</strong> ${u.is_seller ? 'ผู้ขาย' :
                        (Math.ceil(Math.abs(new Date() - new Date(u.created_at)) / (1000 * 60 * 60 * 24)) <= 30 ? '<span style="color:#52c41a; font-weight:bold;">ผู้ใช้ใหม่ 🟢</span>' : 'สมาชิกทั่วไป')
                    }</div>
                                <div><strong>สมัครเมื่อ:</strong> ${new Date(u.created_at).toLocaleDateString('th-TH')}</div>
                            </div>
                        </div>
                    </div>
                    
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 30px;">
                        <!-- ID Card Section -->
                        <div style="background: #f8f9fa; padding: 20px; border-radius: 16px;">
                            <h4 style="margin-top: 0; color: var(--text-main);">🖼️ รูปบัตรประชาชน</h4>
                            ${u.id_card_image ?
                        `<img src="${u.id_card_image}" style="width: 100%; height: 200px; object-fit: contain; border-radius: 8px; border: 1px solid #ddd; background: white; cursor: zoom-in;" onclick="window.open(this.src)">` :
                        `<div style="height: 200px; display: flex; align-items: center; justify-content: center; color: #999; border: 2px dashed #ddd; border-radius: 8px;">ไม่พบรูปบัตรประชาชน</div>`
                    }
                        </div>

                        <!-- Spending Graph Section -->
                        <div style="background: white; padding: 20px; border-radius: 16px; border: 1px solid #eee;">
                            <h4 style="margin-top: 0; color: var(--text-main);">📈 ยอดคำสั่งซื้อ (บาท)</h4>
                            <canvas id="userSpendingChart" style="max-height: 200px;"></canvas>
                        </div>
                    </div>
                    
                    <h3 style="border-left: 4px solid var(--primary); padding-left: 10px; margin-bottom: 20px;">ประวัติการสั่งซื้อ (${orders.length})</h3>
                    <div class="order-history">
                        ${ordersHtml}
                    </div>
                    
                    ${!u.is_admin ? `
                    <div style="border-top: 2px solid #f0f0f0; padding-top: 20px; margin-top: 20px; text-align: center;">
                        <h4 style="margin-top: 0; color: #666;">⚙️ การจัดการบัญชี</h4>
                        <div style="display: flex; gap: 10px; justify-content: center; flex-wrap: wrap;">
                            <button class="btn-ban" style="background: #ffa502;" onclick="openEditUserModal(${u.id})">
                                <i class="fas fa-edit"></i> แก้ไขข้อมูล
                            </button>
                            ${u.is_banned ?
                                `<button class="btn-unban" onclick="toggleBan(${u.id}, false)">
                                    <i class="fas fa-check-circle"></i> ปลดแบน
                                </button>` :
                                `<button class="btn-ban" onclick="toggleBan(${u.id}, true)">
                                    <i class="fas fa-ban"></i> แบนผู้ใช้
                                </button>`
                            }
                            <button class="btn-ban" style="background: #ff4757;" onclick="deleteUser(${u.id})">
                                <i class="fas fa-trash"></i> ลบผู้ใช้นี้
                            </button>
                        </div>
                        ${u.is_banned ? 
                            `<p style="color: #ff4d4f; margin-top: 10px; font-size: 0.9rem;">
                                ⚠️ ผู้ใช้นี้ถูกแบนเมื่อ: ${u.banned_at ? new Date(u.banned_at).toLocaleString('th-TH') : 'ไม่ทราบ'}
                            </p>` : ''
                        }
                    </div>
                ` : '<p style="text-align:center; color:#722ed1; margin-top:20px;">🛡️ ไม่สามารถจัดการบัญชีผู้ดูแลระบบได้</p>'}
            `;

            // Initialize Chart
            if (spendingData.length > 0) {
                new Chart(document.getElementById('userSpendingChart').getContext('2d'), {
                    type: 'bar',
                    data: {
                        labels: labels,
                        datasets: [{
                            label: 'ยอดคำสั่งซื้อ (บาท)',
                            data: spendingData,
                            borderColor: '#722ed1',
                            backgroundColor: 'rgba(114, 46, 209, 0.5)',
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
                const ctx = document.getElementById('userSpendingChart').getContext('2d');
                ctx.font = "16px Prompt";
                ctx.fillStyle = "#999";
                ctx.textAlign = "center";
                ctx.fillText("ไม่มีข้อมูลการใช้จ่าย", 150, 100);
            }

        } catch (e) {
            body.innerHTML = `<p style="color:red; text-align:center;">เกิดข้อผิดพลาด: ${e.message}</p>`;
        }
    }

    function closeUserModal() {
        document.getElementById('userModal').classList.remove('active');
    }

    // Close on outside click
    document.getElementById('userModal').addEventListener('click', (e) => {
        if (e.target.id === 'userModal') closeUserModal();
    });

    // Edit User Function (Swal)
    function openEditUserModal(userId) {
        // Need to find user data again from the loaded users list (global variable usersData not explicitly defined but we can fetch or pass data)
        // Better to fetch fresh data or pass details. 
        // For simplicity, let's just fetch details again or use what we have if possible.
        // Actually, renderUserModal just ran, we don't have global users list easily accessible without reloading.
        // Let's fetch details for editing.
        
        fetch(`api/admin_users.php?action=details&id=${userId}`)
            .then(res => res.json())
            .then(data => {
                if(!data.success) { showAlert(data.message, 'error'); return; }
                const u = data.user;
                
                Swal.fire({
                    title: 'แก้ไขข้อมูลผู้ใช้',
                    html: `
                        <div style="text-align: left; margin-bottom: 5px;"><label>ชื่อผู้ใช้ (Username)</label></div>
                        <input id="edit-username" class="swal2-input" placeholder="Username" value="${u.username}" style="margin: 0 0 15px 0;">
                        
                        <div style="text-align: left; margin-bottom: 5px;"><label>เปลี่ยนรหัสผ่าน (เว้นว่างหากไม่เปลี่ยน)</label></div>
                        <input id="edit-password" type="password" class="swal2-input" placeholder="รหัสผ่านใหม่" style="margin: 0 0 15px 0;">
                        
                        <div style="text-align: left; margin-bottom: 5px;"><label>สถานะบัญชี</label></div>
                        <select id="edit-role" class="swal2-input" style="margin: 0 0 15px 0;">
                            <option value="member" ${!u.is_seller ? 'selected' : ''}>สมาชิกทั่วไป</option>
                            <option value="seller" ${u.is_seller ? 'selected' : ''}>ผู้ขาย (Seller)</option>
                        </select>
                        
                        <div style="text-align: left; margin-bottom: 5px;"><label>ข้อมูลส่วนตัว</label></div>
                        <input id="edit-fullname" class="swal2-input" placeholder="ชื่อ-นามสกุล" value="${u.fullname || ''}" style="margin: 0 0 10px 0;">
                        <input id="edit-phone" class="swal2-input" placeholder="เบอร์โทรศัพท์" value="${u.phone || ''}" style="margin: 0 0 10px 0;">
                        <textarea id="edit-address" class="swal2-textarea" placeholder="ที่อยู่" style="margin: 0;">${u.address || ''}</textarea>
                    `,
                    focusConfirm: false,
                    showCancelButton: true,
                    confirmButtonText: 'บันทึก',
                    cancelButtonText: 'ยกเลิก',
                    preConfirm: () => {
                        return {
                            id: userId,
                            username: document.getElementById('edit-username').value,
                            password: document.getElementById('edit-password').value,
                            role: document.getElementById('edit-role').value,
                            fullname: document.getElementById('edit-fullname').value,
                            phone: document.getElementById('edit-phone').value,
                            address: document.getElementById('edit-address').value
                        }
                    }
                }).then((result) => {
                    if (result.isConfirmed) {
                        saveUserChanges(result.value);
                    }
                });
            });
    }

    async function saveUserChanges(data) {
        try {
            const res = await fetch('api/admin_users.php?action=update', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(data)
            });
            const result = await res.json();
            if (result.success) {
                await showAlert('บันทึกข้อมูลเรียบร้อยแล้ว', 'success');
                loadUsers(); // Refresh list
                viewUser(data.id); // Refresh modal details
            } else {
                await showAlert(result.message, 'error');
            }
        } catch (e) {
            await showAlert('เกิดข้อผิดพลาด', 'error');
        }
    }

    // Delete User Function
    async function deleteUser(userId) {
        const confirmed = await showConfirm('คุณแน่ใจหรือไม่ที่จะลบผู้ใช้นี้? การกระทำนี้ไม่สามารถเรียกคืนได้!', 'danger');
        if (!confirmed) return;

        try {
            const res = await fetch('api/admin_users.php?action=delete', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ id: userId })
            });
            const result = await res.json();
            if (result.success) {
                await showAlert('ลบผู้ใช้เรียบร้อยแล้ว', 'success');
                closeUserModal();
                loadUsers();
            } else {
                await showAlert(result.message, 'error');
            }
        } catch (e) {
            await showAlert('เกิดข้อผิดพลาด', 'error');
        }
    }

    // Ban/Unban User Function
    async function toggleBan(userId, shouldBan) {
        const action = shouldBan ? 'ban' : 'unban';
        const confirmType = shouldBan ? 'ban' : 'unban';
        const confirmMsg = shouldBan ?
            'ผู้ใช้จะไม่สามารถเข้าสู่ระบบได้จนกว่าจะถูกปลดแบน' :
            'ผู้ใช้จะสามารถเข้าสู่ระบบได้ตามปกติ';

        const confirmed = await showConfirm(confirmMsg, confirmType);
        if (!confirmed) return;

        try {
            const res = await fetch(`api/admin_users.php?action=${action}&id=${userId}`);
            const data = await res.json();

            if (data.success) {
                await showAlert(data.message, shouldBan ? 'ban' : 'unban');
                // Don't close modal, just refresh it or partial update. 
                // But for now closing is safer to ensure state consistency or we reload details.
                // viewUser(userId); // Use this instead of closeUserModal logic ideally.
                // But let's stick to previous behavior of close+reload list unless improved.
                closeUserModal();
                loadUsers(); 
            } else {
                await showAlert(data.message, 'error');
            }
        } catch (e) {
            await showAlert('เกิดข้อผิดพลาดในการเชื่อมต่อ', 'error');
            console.error(e);
        }
    }
    </script>
</body>

</html>