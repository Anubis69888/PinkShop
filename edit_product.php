<?php
session_start();
require_once 'includes/db.php';

// Allow admin or seller to access
$isAdmin = !empty($_SESSION['is_admin']);
$isSeller = !empty($_SESSION['is_seller']);

if (!isset($_SESSION['user_id']) || (!$isAdmin && !$isSeller)) {
    header('Location: index.php');
    exit;
}

$id = $_GET['id'] ?? 0;
$db = new DB();
$product = $db->find('products', 'id', $id);

if (!$product) {
    die("Product not found.");
}
if (!$isAdmin && $product['seller_id'] != $_SESSION['user_id']) {
    die("Access denied.");
}

// Redirect URL
$redirectUrl = $isAdmin ? 'admin_products.php' : 'my_products.php';

// Parse Size
$sizeVal = '';
$sizeUnit = 'cm';
if (!empty($product['size'])) {
    $parts = explode(' ', $product['size']);
    if (count($parts) >= 2) {
        $sizeVal = $parts[0];
        $sizeUnit = $parts[1];
    } else {
        $sizeVal = $product['size'];
    }
}
if ($sizeVal == '-') $sizeVal = '';

// Parse Weight
$weightVal = '';
$weightUnit = 'g';
if (!empty($product['weight'])) {
    $parts = explode(' ', $product['weight']);
    if (count($parts) >= 2) {
        $weightVal = $parts[0];
        $weightUnit = $parts[1];
    }
}

// Parse Dimensions
$dimW = $dimH = $dimD = '';
if (!empty($product['dimensions'])) {
    if (preg_match('/(\d+(?:\.\d+)?)\s*x\s*(\d+(?:\.\d+)?)\s*x\s*(\d+(?:\.\d+)?)/', $product['dimensions'], $matches)) {
        $dimW = $matches[1];
        $dimH = $matches[2];
        $dimD = $matches[3];
    }
}

// Parse Tags
$currentTags = array_filter(explode(',', $product['tags'] ?? ''));
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>แก้ไขสินค้า - <?php echo htmlspecialchars($product['name']); ?></title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Prompt:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        body { font-family: 'Prompt', 'Sarabun', sans-serif; }
        .product-form-container {
            max-width: 900px;
            margin: 40px auto;
            padding: 20px;
        }
        .form-section {
            background: white;
            border-radius: 20px;
            padding: 30px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.08);
            margin-bottom: 25px;
        }
        .section-title {
            color: var(--primary);
            font-size: 1.2rem;
            font-weight: 600;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 2px solid #f0f0f0;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .field-row {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 20px;
        }
        .form-group label {
            font-weight: 500;
            color: #333;
            margin-bottom: 8px;
            display: block;
        }
        .form-control {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid #e0e0e0;
            border-radius: 12px;
            font-size: 1rem;
            transition: all 0.3s;
        }
        .form-control:focus {
            border-color: var(--primary);
            outline: none;
        }
        .input-group {
            display: flex;
            gap: 10px;
        }
        .input-group .form-control { flex: 1; }
        .input-group select { flex: 0 0 100px; }
        
        .tag-options {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
        }
        .tag-option {
            padding: 8px 16px;
            border: 2px solid #e0e0e0;
            border-radius: 20px;
            cursor: pointer;
            transition: all 0.3s;
            font-size: 0.9rem;
        }
        .tag-option:hover { border-color: var(--primary); }
        .tag-option.active {
            background: var(--primary);
            border-color: var(--primary);
            color: white;
        }
        
        .image-preview-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(100px, 1fr));
            gap: 15px;
        }
        .preview-item {
            position: relative;
            height: 100px;
            border-radius: 10px;
            overflow: hidden;
        }
        .preview-item img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        .preview-item .delete-btn {
            position: absolute;
            top: 5px;
            right: 5px;
            background: red;
            color: white;
            border: none;
            border-radius: 50%;
            width: 24px;
            height: 24px;
            cursor: pointer;
            font-size: 12px;
        }
        .add-image-btn {
            height: 100px;
            border: 2px dashed var(--primary);
            border-radius: 10px;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            background: rgba(255, 105, 180, 0.05);
        }
        
        .btn-submit {
            width: 100%;
            padding: 18px;
            font-size: 1.2rem;
            border-radius: 15px;
            background: #ffffff;
            color: #ff6b81;
            border: 2px solid white;
            cursor: pointer;
            font-weight: 700;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.08);
            transition: all 0.3s;
        }
        .btn-submit:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 25px rgba(255, 107, 129, 0.25);
            color: #ff4757;
            background: #fff0f3;
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
            display: flex;
            justify-content: space-between;
            align-items: center;
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
            color: var(--primary);
            margin: 0;
            font-size: 1.8rem;
            font-weight: 700;
            background: linear-gradient(135deg, #722ed1, #eb2f96);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .back-button {
            background: rgba(114, 46, 209, 0.1);
            border: 2px solid rgba(114, 46, 209, 0.3);
            color: var(--primary);
            padding: 10px 20px;
            border-radius: 15px;
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

        .help-text {
            font-size: 0.85rem;
            color: #888;
            margin-top: 5px;
        }
    </style>
</head>
<body>
    <?php include 'includes/header.php'; ?>

    <div class="product-form-container">
        <div class="page-header">
            <h1><i class="fas fa-edit"></i> แก้ไขสินค้า</h1>
            <a href="<?php echo $redirectUrl; ?>" class="back-button">
                <i class="fas fa-arrow-left"></i>
                <span>ย้อนกลับ</span>
            </a>
        </div>
        
        <form id="editProductForm" enctype="multipart/form-data">
            <input type="hidden" name="action" value="edit">
            <input type="hidden" name="id" value="<?php echo $product['id']; ?>">
            
            <!-- Section 1: Basic Info -->
            <div class="form-section">
                <h3 class="section-title">📦 ข้อมูลพื้นฐาน</h3>
                
                <div class="form-group">
                    <label>ชื่อสินค้า *</label>
                    <input type="text" name="name" class="form-control" required value="<?php echo htmlspecialchars($product['name']); ?>">
                </div>
                
                <div class="field-row">
                    <div class="form-group">
                        <label>หมวดหมู่ *</label>
                        <select name="category" class="form-control" required>
                            <?php 
                            $cats = [
                                'food' => '🍜 อาหาร & ขนม',
                                'drinks' => '🥤 เครื่องดื่ม',
                                'cosmetics' => '💄 เครื่องสำอาง',
                                'skincare' => '🧴 สกินแคร์',
                                'fashion' => '👗 แฟชั่น & เสื้อผ้า',
                                'bags' => '👜 กระเป๋า',
                                'shoes' => '👟 รองเท้า',
                                'jewelry' => '💍 เครื่องประดับ',
                                'electronics' => '📱 อุปกรณ์อิเล็กทรอนิกส์',
                                'home' => '🏠 ของใช้ในบ้าน',
                                'kitchen' => '🍳 เครื่องครัว',
                                'furniture' => '🛋️ เฟอร์นิเจอร์',
                                'toys' => '🧸 ของเล่น & ตุ๊กตา',
                                'sports' => '⚽ กีฬา & Outdoor',
                                'health' => '💊 สุขภาพ & อาหารเสริม',
                                'pets' => '🐾 สัตว์เลี้ยง',
                                'books' => '📚 หนังสือ & เครื่องเขียน',
                                'baby' => '👶 แม่และเด็ก',
                                'automotive' => '🚗 ยานยนต์',
                                'others' => '📦 อื่นๆ'
                            ];
                            foreach($cats as $k => $v) {
                                $selected = ($product['category'] ?? '') == $k ? 'selected' : '';
                                echo "<option value='$k' $selected>$v</option>";
                            }
                            ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>รหัสสินค้า (SKU)</label>
                        <input type="text" name="sku" class="form-control" value="<?php echo htmlspecialchars($product['sku'] ?? ''); ?>" placeholder="เช่น DOLL-001">
                    </div>
                </div>
                
                <div class="form-group">
                    <label>รายละเอียดสินค้า *</label>
                    <textarea name="description" class="form-control" rows="4" required><?php echo htmlspecialchars($product['description']); ?></textarea>
                </div>
            </div>
            
            <!-- Section 2: Pricing & Stock -->
            <div class="form-section">
                <h3 class="section-title">💰 ราคา & สต็อก</h3>
                
                <div class="field-row">
                    <div class="form-group">
                        <label>ราคาปกติ (บาท) *</label>
                        <input type="number" name="price" class="form-control" required min="1" value="<?php echo $product['price']; ?>">
                    </div>
                    <div class="form-group">
                        <label>ราคาลด (บาท)</label>
                        <input type="number" name="sale_price" class="form-control" min="0" value="<?php echo $product['sale_price'] ?? ''; ?>" placeholder="ราคาโปรโมชั่น">
                        <p class="help-text">เว้นว่างถ้าไม่มีโปร</p>
                    </div>
                    <div class="form-group">
                        <label>จำนวนสต็อก *</label>
                        <input type="number" name="stock" class="form-control" required min="0" value="<?php echo $product['stock'] ?? 10; ?>">
                    </div>
                </div>
            </div>
            
            <!-- Section 3: Specifications -->
            <div class="form-section">
                <h3 class="section-title">📐 รายละเอียดสินค้า</h3>
                
                <div class="field-row">
                    <div class="form-group">
                        <label>ขนาด (Size)</label>
                        <div class="input-group">
                            <input type="number" name="size_val" class="form-control" step="0.1" value="<?php echo $sizeVal; ?>">
                            <select name="size_unit" class="form-control">
                                <option value="cm" <?php echo $sizeUnit=='cm'?'selected':''; ?>>ซม.</option>
                                <option value="mm" <?php echo $sizeUnit=='mm'?'selected':''; ?>>มม.</option>
                                <option value="inch" <?php echo $sizeUnit=='inch'?'selected':''; ?>>นิ้ว</option>
                                <option value="m" <?php echo $sizeUnit=='m'?'selected':''; ?>>เมตร</option>
                            </select>
                        </div>
                    </div>
                    <div class="form-group">
                        <label>วัสดุ (Material)</label>
                        <input type="text" name="material" class="form-control" value="<?php echo htmlspecialchars($product['material'] ?? ''); ?>">
                    </div>
                    <div class="form-group">
                        <label>แหล่งผลิต (Origin)</label>
                        <input type="text" name="origin" class="form-control" value="<?php echo htmlspecialchars($product['origin'] ?? ''); ?>">
                    </div>
                </div>
            </div>
            
            <!-- Section 4: Shipping -->
            <div class="form-section">
                <h3 class="section-title">🚚 ข้อมูลจัดส่ง</h3>
                
                <div class="field-row">
                    <div class="form-group">
                        <label>น้ำหนัก</label>
                        <div class="input-group">
                            <input type="number" name="weight_val" class="form-control" step="0.1" value="<?php echo $weightVal; ?>">
                            <select name="weight_unit" class="form-control">
                                <option value="g" <?php echo $weightUnit=='g'?'selected':''; ?>>กรัม</option>
                                <option value="kg" <?php echo $weightUnit=='kg'?'selected':''; ?>>กิโลกรัม</option>
                            </select>
                        </div>
                    </div>
                    <div class="form-group">
                        <label>ขนาดพัสดุ (W x H x D)</label>
                        <div style="display: flex; gap: 8px;">
                            <input type="number" name="dim_w" class="form-control" placeholder="กว้าง" step="0.1" value="<?php echo $dimW; ?>">
                            <input type="number" name="dim_h" class="form-control" placeholder="สูง" step="0.1" value="<?php echo $dimH; ?>">
                            <input type="number" name="dim_d" class="form-control" placeholder="ลึก" step="0.1" value="<?php echo $dimD; ?>">
                        </div>
                        <p class="help-text">หน่วย: ซม.</p>
                    </div>
                </div>
            </div>
            
            <!-- Section 5: Marketing -->
            <div class="form-section">
                <h3 class="section-title">🏷️ การตลาด & สถานะ</h3>
                
                <div class="field-row">
                    <div class="form-group">
                        <label>สถานะสินค้า</label>
                        <select name="status" class="form-control">
                            <option value="active" <?php echo ($product['status'] ?? 'active')=='active'?'selected':''; ?>>🟢 เปิดขาย (Active)</option>
                            <option value="hidden" <?php echo ($product['status'] ?? '')=='hidden'?'selected':''; ?>>🟡 ซ่อน (Hidden)</option>
                            <option value="draft" <?php echo ($product['status'] ?? '')=='draft'?'selected':''; ?>>⚪ แบบร่าง (Draft)</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>ลิงก์วิดีโอ (YouTube/TikTok)</label>
                        <input type="url" name="video_url" class="form-control" value="<?php echo htmlspecialchars($product['video_url'] ?? ''); ?>" placeholder="https://...">
                    </div>
                </div>
                
                <div class="form-group">
                    <label>ป้ายกำกับ (Tags)</label>
                    <div class="tag-options" id="tagOptions">
                        <div class="tag-option <?php echo in_array('bestseller', $currentTags)?'active':''; ?>" data-tag="bestseller">🔥 ขายดี</div>
                        <div class="tag-option <?php echo in_array('new', $currentTags)?'active':''; ?>" data-tag="new">✨ สินค้าใหม่</div>
                        <div class="tag-option <?php echo in_array('limited', $currentTags)?'active':''; ?>" data-tag="limited">💎 Limited Edition</div>
                        <div class="tag-option <?php echo in_array('sale', $currentTags)?'active':''; ?>" data-tag="sale">🏷️ ลดราคา</div>
                        <div class="tag-option <?php echo in_array('freeship', $currentTags)?'active':''; ?>" data-tag="freeship">🚗 ส่งฟรี</div>
                    </div>
                    <input type="hidden" name="tags" id="tagsInput" value="<?php echo implode(',', $currentTags); ?>">
                </div>
            </div>
            
            <!-- Section 6: Images -->
            <div class="form-section">
                <h3 class="section-title">📸 รูปภาพสินค้า</h3>
                
                <!-- Existing Images -->
                <div style="margin-bottom: 20px;">
                    <p style="font-weight: 500; margin-bottom: 10px;">รูปภาพปัจจุบัน:</p>
                    <div class="image-preview-grid" id="existingImages">
                        <?php 
                        $images = $product['images'] ?? [$product['image']];
                        foreach($images as $idx => $img): ?>
                            <div class="preview-item" id="existing-<?php echo $idx; ?>">
                                <img src="<?php echo $img; ?>" alt="Product">
                                <button type="button" class="delete-btn" onclick="deleteExistingImage(<?php echo $product['id']; ?>, '<?php echo $img; ?>', <?php echo $idx; ?>)">×</button>
                                <?php if($idx === 0): ?>
                                    <div style="position:absolute;bottom:5px;left:5px;background:var(--primary);color:white;padding:2px 8px;border-radius:10px;font-size:0.7rem;">ปก</div>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                
                <!-- Add New Images -->
                <p style="font-weight: 500; margin-bottom: 10px;">เพิ่มรูปภาพใหม่:</p>
                <input type="file" name="images[]" id="imgInput" accept="image/*" multiple style="display: none;">
                
                <div class="image-preview-grid" id="newPreviewGrid">
                    <label for="imgInput" class="add-image-btn">
                        <span style="font-size: 2rem;">➕</span>
                        <span style="font-size: 0.8rem; color: var(--primary);">เพิ่มรูป</span>
                    </label>
                </div>
            </div>
            
            <button type="submit" class="btn-submit">บันทึกการแก้ไข 💾</button>
        </form>
    </div>

    <script>
        const isAdmin = <?php echo $isAdmin ? 'true' : 'false'; ?>;
        const redirectUrl = '<?php echo $redirectUrl; ?>';
        const imgInput = document.getElementById('imgInput');
        const newPreviewGrid = document.getElementById('newPreviewGrid');
        let newFiles = [];
        let selectedTags = <?php echo json_encode($currentTags); ?>;

        // Tag selection
        document.querySelectorAll('.tag-option').forEach(tag => {
            tag.addEventListener('click', () => {
                tag.classList.toggle('active');
                const tagValue = tag.dataset.tag;
                if (tag.classList.contains('active')) {
                    if (!selectedTags.includes(tagValue)) selectedTags.push(tagValue);
                } else {
                    selectedTags = selectedTags.filter(t => t !== tagValue);
                }
                document.getElementById('tagsInput').value = selectedTags.join(',');
            });
        });

        // Delete existing image
        async function deleteExistingImage(productId, imagePath, idx) {
            const result = await Swal.fire({
                title: 'ยืนยันการลบ?',
                text: "ต้องการลบรูปนี้?",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#f5222d',
                confirmButtonText: 'ลบ',
                cancelButtonText: 'ยกเลิก'
            });

            if (!result.isConfirmed) return;

            const formData = new FormData();
            formData.append('action', 'delete_image');
            formData.append('id', productId);
            formData.append('image_path', imagePath);

            try {
                const response = await fetch('api/product.php', { method: 'POST', body: formData });
                const data = await response.json();
                if (data.success) {
                    document.getElementById('existing-' + idx).remove();
                    Swal.fire({ icon: 'success', title: 'ลบแล้ว', timer: 1000, showConfirmButton: false });
                } else {
                    Swal.fire({ icon: 'error', title: 'เกิดข้อผิดพลาด', text: data.message });
                }
            } catch (e) { console.error(e); }
        }

        // New image handling
        imgInput.addEventListener('change', function() {
            newFiles = newFiles.concat(Array.from(this.files));
            renderNewPreview();
            this.value = '';
        });

        function renderNewPreview() {
            const addBtn = newPreviewGrid.firstElementChild;
            newPreviewGrid.innerHTML = '';
            newPreviewGrid.appendChild(addBtn);

            newFiles.forEach((file, index) => {
                const reader = new FileReader();
                reader.onload = function(e) {
                    const div = document.createElement('div');
                    div.className = 'preview-item';
                    div.innerHTML = `
                        <img src="${e.target.result}" alt="Preview">
                        <button type="button" class="delete-btn" onclick="removeNewFile(${index})">×</button>
                    `;
                    newPreviewGrid.appendChild(div);
                }
                reader.readAsDataURL(file);
            });
        }

        function removeNewFile(index) {
            newFiles.splice(index, 1);
            renderNewPreview();
        }

        // Form Submit
        document.getElementById('editProductForm').addEventListener('submit', async (e) => {
            e.preventDefault();
            const btn = e.target.querySelector('.btn-submit');
            btn.disabled = true;
            btn.innerText = 'กำลังบันทึก...';

            const formData = new FormData(e.target);
            formData.delete('images[]');
            newFiles.forEach(file => {
                formData.append('images[]', file);
            });

            try {
                const response = await fetch('api/product.php', { method: 'POST', body: formData });
                const result = await response.json();

                if (result.success) {
                    Swal.fire({
                        icon: 'success',
                        title: 'บันทึกสำเร็จ! 🎉',
                        timer: 2000,
                        showConfirmButton: false
                    }).then(() => {
                        window.location.href = redirectUrl;
                    });
                } else {
                    Swal.fire({ icon: 'error', title: 'เกิดข้อผิดพลาด', text: result.message });
                    btn.disabled = false;
                    btn.innerText = 'บันทึกการแก้ไข 💾';
                }
            } catch (error) {
                console.error(error);
                Swal.fire({ icon: 'error', title: 'ข้อผิดพลาด', text: 'ไม่สามารถติดต่อเซิร์ฟเวอร์ได้' });
                btn.disabled = false;
                btn.innerText = 'บันทึกการแก้ไข 💾';
            }
        });
    </script>
</body>
</html>
