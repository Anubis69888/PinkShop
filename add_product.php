<?php
require_once 'includes/init.php';

// Allow admin or seller to access
$isAdmin = !empty($_SESSION['is_admin']);
$isSeller = !empty($_SESSION['is_seller']);

if (!isset($_SESSION['user_id']) || (!$isAdmin && !$isSeller)) {
    header('Location: index.php');
    exit;
}

// Redirect URL after success
$redirectUrl = $isAdmin ? 'admin_products.php' : 'my_products.php';
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ลงขายสินค้า - AKP Shop</title>
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
            box-shadow: 0 0 0 3px rgba(255, 105, 180, 0.1);
        }
        .input-group {
            display: flex;
            gap: 10px;
        }
        .input-group .form-control { flex: 1; }
        .input-group select { flex: 0 0 100px; }
        
        /* Tags */
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
        
        /* Image Upload */
        .upload-zone {
            border: 2px dashed #ddd;
            padding: 30px;
            text-align: center;
            border-radius: 15px;
            background: #fafafa;
            transition: all 0.3s;
        }
        .upload-zone:hover {
            border-color: var(--primary);
            background: #fff5f9;
        }
        .image-preview-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(100px, 1fr));
            gap: 15px;
            margin-top: 20px;
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
            transition: all 0.3s;
        }
        .add-image-btn:hover {
            background: rgba(255, 105, 180, 0.1);
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
        .btn-submit:disabled {
            opacity: 0.7;
            cursor: not-allowed;
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
        <form id="addProductForm" enctype="multipart/form-data">
            
            <!-- Section 1: Basic Info -->
            <div class="form-section">
                <h3 class="section-title">📦 ข้อมูลพื้นฐาน</h3>
                
                <div class="form-group">
                    <label>ชื่อสินค้า *</label>
                    <input type="text" name="name" class="form-control" required placeholder="เช่น ตุ๊กตาหมีน่ารัก...">
                </div>
                
                <div class="field-row">
                    <div class="form-group">
                        <label>หมวดหมู่ *</label>
                        <select name="category" class="form-control" required>
                            <option value="">-- เลือกหมวดหมู่ --</option>
                            <option value="food">🍜 อาหาร & ขนม</option>
                            <option value="drinks">🥤 เครื่องดื่ม</option>
                            <option value="cosmetics">💄 เครื่องสำอาง</option>
                            <option value="skincare">🧴 สกินแคร์</option>
                            <option value="fashion">👗 แฟชั่น & เสื้อผ้า</option>
                            <option value="bags">👜 กระเป๋า</option>
                            <option value="shoes">👟 รองเท้า</option>
                            <option value="jewelry">💍 เครื่องประดับ</option>
                            <option value="electronics">📱 อุปกรณ์อิเล็กทรอนิกส์</option>
                            <option value="home">🏠 ของใช้ในบ้าน</option>
                            <option value="kitchen">🍳 เครื่องครัว</option>
                            <option value="furniture">🛋️ เฟอร์นิเจอร์</option>
                            <option value="toys">🧸 ของเล่น & ตุ๊กตา</option>
                            <option value="sports">⚽ กีฬา & Outdoor</option>
                            <option value="health">💊 สุขภาพ & อาหารเสริม</option>
                            <option value="pets">🐾 สัตว์เลี้ยง</option>
                            <option value="books">📚 หนังสือ & เครื่องเขียน</option>
                            <option value="baby">👶 แม่และเด็ก</option>
                            <option value="automotive">🚗 ยานยนต์</option>
                            <option value="others">📦 อื่นๆ</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>รหัสสินค้า (SKU)</label>
                        <input type="text" name="sku" class="form-control" placeholder="เช่น DOLL-001">
                        <p class="help-text">รหัสสำหรับจัดการคลัง</p>
                    </div>
                </div>
                
                <div class="form-group">
                    <label>รายละเอียดสินค้า *</label>
                    <textarea name="description" class="form-control" rows="4" required placeholder="อธิบายความน่ารักของสินค้า..."></textarea>
                </div>
            </div>
            
            <!-- Section 2: Pricing & Stock -->
            <div class="form-section">
                <h3 class="section-title">💰 ราคา & สต็อก</h3>
                
                <div class="field-row">
                    <div class="form-group">
                        <label>ราคาปกติ (บาท) *</label>
                        <input type="number" name="price" class="form-control" required min="1" placeholder="0">
                    </div>
                    <div class="form-group">
                        <label>ราคาลด (บาท)</label>
                        <input type="number" name="sale_price" class="form-control" min="0" placeholder="ราคาโปรโมชั่น">
                        <p class="help-text">เว้นว่างถ้าไม่มีโปร</p>
                    </div>
                    <div class="form-group">
                        <label>จำนวนสต็อก *</label>
                        <input type="number" name="stock" class="form-control" required min="0" value="10" placeholder="0">
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
                            <input type="number" name="size_val" class="form-control" placeholder="0" step="0.1">
                            <select name="size_unit" class="form-control">
                                <option value="cm">ซม.</option>
                                <option value="mm">มม.</option>
                                <option value="inch">นิ้ว</option>
                                <option value="m">เมตร</option>
                            </select>
                        </div>
                    </div>
                    <div class="form-group">
                        <label>วัสดุ (Material)</label>
                        <input type="text" name="material" class="form-control" placeholder="เช่น ผ้าฝ้าย, เรซิ่น">
                    </div>
                    <div class="form-group">
                        <label>แหล่งผลิต (Origin)</label>
                        <input type="text" name="origin" class="form-control" placeholder="เช่น ญี่ปุ่น, ไทย">
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
                            <input type="number" name="weight_val" class="form-control" placeholder="0" step="0.1">
                            <select name="weight_unit" class="form-control">
                                <option value="g">กรัม</option>
                                <option value="kg">กิโลกรัม</option>
                            </select>
                        </div>
                    </div>
                    <div class="form-group">
                        <label>ขนาดพัสดุ (W x H x D)</label>
                        <div style="display: flex; gap: 8px;">
                            <input type="number" name="dim_w" class="form-control" placeholder="กว้าง" step="0.1">
                            <input type="number" name="dim_h" class="form-control" placeholder="สูง" step="0.1">
                            <input type="number" name="dim_d" class="form-control" placeholder="ลึก" step="0.1">
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
                            <option value="active">🟢 เปิดขาย (Active)</option>
                            <option value="hidden">🟡 ซ่อน (Hidden)</option>
                            <option value="draft">⚪ แบบร่าง (Draft)</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>ลิงก์วิดีโอ (YouTube/TikTok)</label>
                        <input type="url" name="video_url" class="form-control" placeholder="https://...">
                    </div>
                </div>
                
                <div class="form-group">
                    <label>ป้ายกำกับ (Tags)</label>
                    <div class="tag-options" id="tagOptions">
                        <div class="tag-option" data-tag="bestseller">🔥 ขายดี</div>
                        <div class="tag-option" data-tag="new">✨ สินค้าใหม่</div>
                        <div class="tag-option" data-tag="limited">💎 Limited Edition</div>
                        <div class="tag-option" data-tag="sale">🏷️ ลดราคา</div>
                        <div class="tag-option" data-tag="freeship">🚗 ส่งฟรี</div>
                    </div>
                    <input type="hidden" name="tags" id="tagsInput" value="">
                </div>
            </div>
            
            <!-- Section 6: Images -->
            <div class="form-section">
                <h3 class="section-title">📸 รูปภาพสินค้า</h3>
                
                <input type="file" name="images[]" id="imgInput" accept="image/*" multiple style="display: none;">
                
                <div class="image-preview-grid" id="previewGrid">
                    <label for="imgInput" class="add-image-btn">
                        <span style="font-size: 2rem;">📷</span>
                        <span style="font-size: 0.8rem; color: var(--primary);">เพิ่มรูปภาพ</span>
                    </label>
                </div>
                <p class="help-text">* รูปแรกจะเป็นรูปปกสินค้า (คลิกที่รูปเพื่อลบ)</p>
            </div>
            
            <button type="submit" class="btn-submit">ลงขายสินค้าเลย! 🚀</button>
        </form>
    </div>

    <script>
        const redirectUrl = '<?php echo $redirectUrl; ?>';
        const imgInput = document.getElementById('imgInput');
        const previewGrid = document.getElementById('previewGrid');
        let allFiles = [];
        let selectedTags = [];

        // Tag selection
        document.querySelectorAll('.tag-option').forEach(tag => {
            tag.addEventListener('click', () => {
                tag.classList.toggle('active');
                const tagValue = tag.dataset.tag;
                if (tag.classList.contains('active')) {
                    selectedTags.push(tagValue);
                } else {
                    selectedTags = selectedTags.filter(t => t !== tagValue);
                }
                document.getElementById('tagsInput').value = selectedTags.join(',');
            });
        });

        // Image handling
        imgInput.addEventListener('change', function() {
            const files = Array.from(this.files);
            allFiles = allFiles.concat(files);
            renderPreview();
            this.value = '';
        });

        function renderPreview() {
            const addBtn = previewGrid.firstElementChild;
            previewGrid.innerHTML = '';
            previewGrid.appendChild(addBtn);

            allFiles.forEach((file, index) => {
                const reader = new FileReader();
                reader.onload = function(e) {
                    const div = document.createElement('div');
                    div.className = 'preview-item';
                    div.innerHTML = `
                        <img src="${e.target.result}" alt="Preview">
                        <button type="button" class="delete-btn" onclick="removeFile(${index})">×</button>
                        ${index === 0 ? '<div style="position:absolute;bottom:5px;left:5px;background:var(--primary);color:white;padding:2px 8px;border-radius:10px;font-size:0.7rem;">ปก</div>' : ''}
                    `;
                    previewGrid.appendChild(div);
                }
                reader.readAsDataURL(file);
            });
        }

        function removeFile(index) {
            allFiles.splice(index, 1);
            renderPreview();
        }

        // Form Submit
        document.getElementById('addProductForm').addEventListener('submit', async (e) => {
            e.preventDefault();
            const btn = e.target.querySelector('.btn-submit');

            if (allFiles.length === 0) {
                Swal.fire({
                    icon: 'warning',
                    title: 'กรุณาแนบรูปภาพ',
                    text: 'ต้องมีรูปภาพสินค้าอย่างน้อย 1 รูป'
                });
                return;
            }

            btn.disabled = true;
            btn.innerText = 'กำลังบันทึก...';

            const formData = new FormData(e.target);
            formData.delete('images[]');
            allFiles.forEach(file => {
                formData.append('images[]', file);
            });
            formData.append('action', 'add');

            try {
                const response = await fetch('api/product.php', {
                    method: 'POST',
                    body: formData
                });
                const result = await response.json();

                if (result.success) {
                    Swal.fire({
                        icon: 'success',
                        title: 'ลงขายสำเร็จ! 🎉',
                        text: 'สินค้าของคุณออนไลน์แล้ว',
                        timer: 2000,
                        showConfirmButton: false
                    }).then(() => {
                        window.location.href = redirectUrl;
                    });
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'เกิดข้อผิดพลาด',
                        text: result.message
                    });
                    btn.disabled = false;
                    btn.innerText = 'ลงขายสินค้าเลย! 🚀';
                }
            } catch (error) {
                console.error(error);
                Swal.fire({
                    icon: 'error',
                    title: 'ข้อผิดพลาด',
                    text: 'ไม่สามารถติดต่อเซิร์ฟเวอร์ได้'
                });
                btn.disabled = false;
                btn.innerText = 'ลงขายสินค้าเลย! 🚀';
            }
        });
    </script>
</body>
</html>
