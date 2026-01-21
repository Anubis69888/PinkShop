<?php
// Prevent any unwanted output
ob_start();
error_reporting(E_ALL);
ini_set('display_errors', 0);

session_start();
require_once '../includes/db.php';

ob_clean();
header('Content-Type: application/json');

// Allow admin or seller
$isAdmin = !empty($_SESSION['is_admin']);
$isSeller = !empty($_SESSION['is_seller']);

if (!isset($_SESSION['user_id']) || (!$isAdmin && !$isSeller)) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid method']);
    exit;
}

$action = $_POST['action'] ?? 'add';
$db = new DB();

try {
    if ($action === 'delete') {
        $id = $_POST['id'] ?? 0;
        $product = $db->find('products', 'id', $id);
        
        // Admin can delete any, seller only own
        if (!$product) {
            throw new Exception("Product not found");
        }
        if (!$isAdmin && $product['seller_id'] != $_SESSION['user_id']) {
            throw new Exception("Unauthorized");
        }
        
        $products = $db->read('products');
        $newProducts = array_filter($products, function($p) use ($id) {
            return $p['id'] != $id;
        });
        
        $db->write('products', array_values($newProducts));
        echo json_encode(['success' => true]);
        exit;
    }

    if ($action === 'delete_image') {
        $id = $_POST['id'] ?? 0;
        $imagePath = $_POST['image_path'] ?? '';
        
        $product = $db->find('products', 'id', $id);
        if (!$product) {
            throw new Exception("Product not found");
        }
        if (!$isAdmin && $product['seller_id'] != $_SESSION['user_id']) {
            throw new Exception("Unauthorized");
        }

        $images = $product['images'] ?? [$product['image']];
        $newImages = array_values(array_diff($images, [$imagePath]));
        
        $db->update('products', $id, ['images' => $newImages, 'image' => $newImages[0] ?? '']);
        
        echo json_encode(['success' => true]);
        exit;
    }

    if ($action === 'add' || $action === 'edit') {
        if (empty($_POST['name']) || empty($_POST['price'])) {
            throw new Exception("กรุณากรอกข้อมูลให้ครบถ้วน");
        }

        // Multiple Image Upload
        $uploadDir = '../assets/uploads/products/';
        if (!file_exists($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }

        $newImagePaths = [];
        if (isset($_FILES['images'])) {
            $files = $_FILES['images'];
            $count = count($files['name']);
            
            for ($i = 0; $i < $count; $i++) {
                if ($files['error'][$i] === UPLOAD_ERR_OK) {
                    $ext = pathinfo($files['name'][$i], PATHINFO_EXTENSION);
                    $filename = 'prod_' . uniqid() . '_' . $i . '.' . $ext;
                    $targetPath = $uploadDir . $filename;
                    
                    if (move_uploaded_file($files['tmp_name'][$i], $targetPath)) {
                        $newImagePaths[] = 'assets/uploads/products/' . $filename;
                    }
                }
            }
        }

        // Process Size
        $size = '';
        if (!empty($_POST['size_val'])) {
            $unit = $_POST['size_unit'] ?? 'cm';
            $size = $_POST['size_val'] . ' ' . $unit;
        }
        
        // Process Weight
        $weight = '';
        if (!empty($_POST['weight_val'])) {
            $unit = $_POST['weight_unit'] ?? 'g';
            $weight = $_POST['weight_val'] . ' ' . $unit;
        }
        
        // Process Dimensions
        $dimensions = '';
        if (!empty($_POST['dim_w']) || !empty($_POST['dim_h']) || !empty($_POST['dim_d'])) {
            $dimensions = ($_POST['dim_w'] ?? 0) . 'x' . ($_POST['dim_h'] ?? 0) . 'x' . ($_POST['dim_d'] ?? 0) . ' cm';
        }

        if ($action === 'add') {
            if (empty($newImagePaths)) {
                throw new Exception("กรุณาอัพโหลดรูปภาพอย่างน้อย 1 รูป");
            }
            
            $productData = [
                // Basic Info
                'name' => htmlspecialchars($_POST['name']),
                'description' => htmlspecialchars($_POST['description'] ?? ''),
                'category' => htmlspecialchars($_POST['category'] ?? 'others'),
                'sku' => htmlspecialchars($_POST['sku'] ?? ''),
                
                // Pricing & Stock
                'price' => (float)$_POST['price'],
                'sale_price' => !empty($_POST['sale_price']) ? (float)$_POST['sale_price'] : null,
                'stock' => (int)($_POST['stock'] ?? 10),
                
                // Specifications
                'size' => htmlspecialchars($size),
                'material' => htmlspecialchars($_POST['material'] ?? ''),
                'origin' => htmlspecialchars($_POST['origin'] ?? ''),
                
                // Shipping
                'weight' => htmlspecialchars($weight),
                'dimensions' => htmlspecialchars($dimensions),
                
                // Marketing
                'status' => htmlspecialchars($_POST['status'] ?? 'active'),
                'tags' => htmlspecialchars($_POST['tags'] ?? ''),
                'video_url' => filter_var($_POST['video_url'] ?? '', FILTER_SANITIZE_URL),
                
                // Images
                'image' => $newImagePaths[0],
                'images' => $newImagePaths,
                
                // Metadata
                'seller_id' => $_SESSION['user_id'],
                'created_at' => date('Y-m-d H:i:s')
            ];

            $db->insert('products', $productData);
            echo json_encode(['success' => true, 'message' => 'Product added']);
        } 
        else if ($action === 'edit') {
            $id = $_POST['id'] ?? 0;
            $product = $db->find('products', 'id', $id);
            
            if (!$product) {
                throw new Exception("Product not found");
            }
            if (!$isAdmin && $product['seller_id'] != $_SESSION['user_id']) {
                throw new Exception("Unauthorized");
            }

            // Merge new images with old ones
            $currentImages = $product['images'] ?? [$product['image']];
            $finalImages = array_merge($currentImages, $newImagePaths);

            $updateData = [
                // Basic Info
                'name' => htmlspecialchars($_POST['name']),
                'description' => htmlspecialchars($_POST['description'] ?? ''),
                'category' => htmlspecialchars($_POST['category'] ?? 'others'),
                'sku' => htmlspecialchars($_POST['sku'] ?? $product['sku'] ?? ''),
                
                // Pricing & Stock
                'price' => (float)$_POST['price'],
                'sale_price' => !empty($_POST['sale_price']) ? (float)$_POST['sale_price'] : null,
                'stock' => isset($_POST['stock']) ? (int)$_POST['stock'] : ($product['stock'] ?? 10),
                
                // Specifications
                'size' => htmlspecialchars($size),
                'material' => htmlspecialchars($_POST['material'] ?? ''),
                'origin' => htmlspecialchars($_POST['origin'] ?? ''),
                
                // Shipping
                'weight' => htmlspecialchars($weight ?: ($product['weight'] ?? '')),
                'dimensions' => htmlspecialchars($dimensions ?: ($product['dimensions'] ?? '')),
                
                // Marketing
                'status' => htmlspecialchars($_POST['status'] ?? $product['status'] ?? 'active'),
                'tags' => htmlspecialchars($_POST['tags'] ?? $product['tags'] ?? ''),
                'video_url' => filter_var($_POST['video_url'] ?? $product['video_url'] ?? '', FILTER_SANITIZE_URL),
                
                // Images
                'images' => $finalImages,
                'image' => $finalImages[0] ?? ''
            ];

            $db->update('products', $id, $updateData);
            echo json_encode(['success' => true, 'message' => 'Product updated']);
        }
        exit;
    }

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
