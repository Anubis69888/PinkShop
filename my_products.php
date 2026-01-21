<?php
session_start();
require_once 'includes/db.php';

if (!isset($_SESSION['user_id']) || empty($_SESSION['is_seller'])) {
    header('Location: index.php');
    exit;
}

$db = new DB();
$allProducts = $db->read('products');
$myProducts = array_filter($allProducts, function($p) {
    return isset($p['seller_id']) && $p['seller_id'] == $_SESSION['user_id'];
});
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>จัดการสินค้าของฉัน - Doll Paradise</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Prompt:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        body { font-family: 'Sarabun', 'Outfit', sans-serif; }
        .glass-panel {
            background: rgba(255, 255, 255, 0.6);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.8);
            box-shadow: 0 20px 40px rgba(0,0,0,0.05);
            border-radius: 30px;
            padding: 60px 20px;
            max-width: 600px;
            margin: 40px auto;
            animation: fadeIn 0.8s ease-out;
            text-align: center;
        }
        .product-card {
            background: rgba(255, 255, 255, 0.85); /* Slightly more opaque for readability */
            backdrop-filter: blur(10px);
            border-radius: 20px;
            padding: 20px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 25px;
            border: 1px solid rgba(255, 255, 255, 0.6);
            box-shadow: 0 5px 15px rgba(0,0,0,0.03);
            transition: all 0.3s ease;
        }
        .product-card:hover {
            transform: translateY(-5px);
            background: rgba(255, 255, 255, 0.95);
            box-shadow: 0 15px 30px rgba(0,0,0,0.08);
        }
        .product-thumb {
            width: 120px;
            height: 120px;
            border-radius: 15px;
            object-fit: cover;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            background: #fff;
        }
        .action-btn {
            padding: 10px 20px;
            border-radius: 12px;
            text-decoration: none;
            font-size: 0.95rem;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: all 0.2s;
            font-weight: 500;
            border: none;
            cursor: pointer;
        }
        .btn-edit {
            background: linear-gradient(135deg, #fff3cd 0%, #ffecb5 100%);
            color: #856404;
            box-shadow: 0 3px 10px rgba(255, 236, 181, 0.4);
        }
        .btn-edit:hover { transform: translateY(-2px); box-shadow: 0 5px 15px rgba(255, 236, 181, 0.6); }
        .btn-delete {
            background: linear-gradient(135deg, #f8d7da 0%, #f5c6cb 100%);
            color: #721c24;
            box-shadow: 0 3px 10px rgba(245, 198, 203, 0.4);
        }
        .btn-delete:hover { transform: translateY(-2px); box-shadow: 0 5px 15px rgba(245, 198, 203, 0.6); }
        
        .badge {
            display: inline-block;
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 0.8rem;
            background: rgba(0,0,0,0.05);
            color: var(--text-muted);
        }
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        @keyframes float {
            0% { transform: translateY(0px); }
            50% { transform: translateY(-15px); }
            100% { transform: translateY(0px); }
        }
    </style>
</head>
<body>
    <?php include 'includes/header.php'; ?>

    <div class="container section" style="position: relative;">
        <!-- Background Blobs -->
        <div style="position: absolute; top: 0; left: -100px; width: 300px; height: 300px; background: var(--primary); opacity: 0.05; border-radius: 50%; filter: blur(80px); z-index: -1;"></div>
        <div style="position: absolute; bottom: 0; right: -100px; width: 250px; height: 250px; background: var(--accent); opacity: 0.05; border-radius: 50%; filter: blur(80px); z-index: -1;"></div>

        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 40px;">
            <h2 style="color: var(--primary); margin: 0; display: flex; align-items: center; gap: 15px;">
                <span style="font-size: 2.2rem; background: white; width: 60px; height: 60px; display: flex; align-items: center; justify-content: center; border-radius: 50%; box-shadow: 0 10px 25px rgba(0,0,0,0.08);">📦</span>
                <div>
                    <div>สินค้าของฉัน</div>
                    <div style="font-size: 1rem; color: var(--text-muted); font-weight: normal;">จัดการรายการสินค้าที่คุณลงขาย</div>
                </div>
            </h2>
            <a href="add_product.php" class="btn btn-primary" style="padding: 12px 25px; border-radius: 50px; box-shadow: 0 8px 20px rgba(214, 123, 179, 0.3);">
                ✨ ลงขายสินค้าใหม่
            </a>
        </div>

        <?php if (empty($myProducts)): ?>
            <div class="glass-panel">
                <div style="font-size: 6rem; margin-bottom: 25px; animation: float 3s ease-in-out infinite;">🧸</div>
                <h3 style="font-size: 1.8rem; color: var(--text-main); margin-bottom: 10px;">คุณยังไม่มีสินค้าที่ลงขาย</h3>
                <p style="color: var(--text-muted); margin-bottom: 30px; font-size: 1.1rem;">เริ่มลงขายสินค้าชิ้นแรกของคุณเพื่อสร้างรายได้เลย!</p>
                <a href="add_product.php" class="btn btn-primary" style="padding: 12px 35px; border-radius: 50px;">ลงขายสินค้าเลย 🚀</a>
            </div>
        <?php else: ?>
            <div style="display: flex; flex-direction: column; gap: 20px;">
                <?php foreach (array_reverse($myProducts) as $product): ?>
                    <div class="product-card" style="display: flex; flex-direction: row; align-items: center; text-align: left; justify-content: space-between;">
                        <div style="display: flex; align-items: center; gap: 25px; flex: 1;">
                            <div style="position: relative; flex-shrink: 0; width: 120px; height: 120px; border-radius: 15px; overflow: hidden; background: #f8f9fa; box-shadow: 0 5px 15px rgba(0,0,0,0.1);">
                                <?php 
                                    // Get first image from array or single image
                                    $images = $product['images'] ?? [$product['image'] ?? ''];
                                    $imgSrc = is_array($images) ? ($images[0] ?? '') : $images;
                                    $imgSrc = trim($imgSrc);
                                    // Check if it's a valid path (not empty and not just '-')
                                    $hasValidImage = !empty($imgSrc) && $imgSrc !== '-';
                                ?>
                                <?php if ($hasValidImage): ?>
                                    <?php 
                                        // Ensure path starts with /AKP/ for absolute path
                                        $displayPath = $imgSrc;
                                        if (!str_starts_with($imgSrc, 'http') && !str_starts_with($imgSrc, '/')) {
                                            $displayPath = '/AKP/' . $imgSrc;
                                        }
                                    ?>
                                    <img src="<?php echo htmlspecialchars($displayPath); ?>" 
                                         alt="<?php echo htmlspecialchars($product['name']); ?>"
                                         style="width: 100%; height: 100%; object-fit: cover; display: block;"
                                         onload="this.style.display='block'; this.nextElementSibling.style.display='none';"
                                         onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                                    <div style="width: 100%; height: 100%; display: none; align-items: center; justify-content: center; font-size: 3rem; color: #ccc;">📦</div>
                                <?php else: ?>
                                    <div style="width: 100%; height: 100%; display: flex; align-items: center; justify-content: center; font-size: 3rem; color: #ccc;">📦</div>
                                <?php endif; ?>
                            </div>
                            
                            <div class="product-info" style="flex: 1;">
                                <div style="display: flex; align-items: center; gap: 15px; margin-bottom: 5px;">
                                    <h3 style="margin: 0; font-size: 1.4rem; color: var(--text-main); font-weight: 600;"><?php echo htmlspecialchars($product['name']); ?></h3>
                                    <span style="color: var(--primary); font-weight: 800; font-size: 1.3rem; background: rgba(255,255,255,0.8); padding: 2px 10px; border-radius: 10px;">
                                        ฿<?php echo number_format($product['price']); ?>
                                    </span>
                                </div>
                                
                                <p style="color: var(--text-muted); font-size: 0.95rem; margin-bottom: 12px; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; line-height: 1.5;">
                                    <?php echo htmlspecialchars($product['description']); ?>
                                </p>

                                <div style="display: flex; gap: 10px;">
                                    <span class="badge">📂 <?php echo htmlspecialchars($product['category']); ?></span>
                                    <?php if(!empty($product['size'])): ?>
                                        <span class="badge">📏 <?php echo htmlspecialchars($product['size']); ?></span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>

                        <div style="display: flex; flex-direction: column; gap: 10px; margin-left: 20px; min-width: 120px;">
                            <a href="edit_product.php?id=<?php echo $product['id']; ?>" class="action-btn btn-edit" style="justify-content: center;">
                                ✏️ แก้ไข
                            </a>
                            <button onclick="deleteProduct(<?php echo $product['id']; ?>)" class="action-btn btn-delete" style="justify-content: center;">
                                🗑️ ลบ
                            </button>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <script>
        async function deleteProduct(id) {
            const confirmResult = await Swal.fire({
                title: 'ลบสินค้า?',
                text: "คุณต้องการลบสินค้านี้ใช่ไหม?",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#3085d6',
                confirmButtonText: 'ลบเลย!'
            });

            if (!confirmResult.isConfirmed) return;

            try {
                const formData = new FormData();
                formData.append('action', 'delete');
                formData.append('id', id);

                const response = await fetch('api/product.php', {
                    method: 'POST',
                    body: formData
                });
                
                const text = await response.text();
                try {
                    const result = JSON.parse(text);
                    if (result.success) {
                        Swal.fire('สำเร็จ', 'ลบสินค้าเรียบร้อยแล้ว', 'success').then(() => location.reload());
                    } else {
                        Swal.fire('ผิดพลาด', result.message || 'เกิดข้อผิดพลาด', 'error');
                    }
                } catch (e) {
                    console.error('Server Error:', text);
                    Swal.fire('Error', 'เกิดข้อผิดพลาดจากเซิร์ฟเวอร์', 'error');
                }
            } catch (error) {
                console.error(error);
                Swal.fire('Error', 'เกิดข้อผิดพลาดในการเชื่อมต่อ', 'error');
            }
        }
    </script>
</body>
</html>
