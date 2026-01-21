<?php
// Prevent any output before JSON
error_reporting(0);
ini_set('display_errors', 0);
ob_start();

session_start();
header('Content-Type: application/json');

// Check admin permission
if (empty($_SESSION['user_id']) || empty($_SESSION['is_admin'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

require_once '../includes/db.php';
$db = new DB();

// Handle GET request - Fetch products
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $category = isset($_GET['category']) ? $_GET['category'] : '';
    $stock = isset($_GET['stock']) ? $_GET['stock'] : '';
    $search = isset($_GET['search']) ? $_GET['search'] : '';
    
    try {
        $products = $db->read('products');
        
        // Calculate statistics
        $stats = [
            'total' => count($products),
            'active' => 0,
            'low_stock' => 0,
            'out_stock' => 0
        ];
        
        foreach ($products as $product) {
            $stockAmount = (int)($product['stock'] ?? 0);
            if ($stockAmount > 0) {
                $stats['active']++;
            }
            if ($stockAmount > 0 && $stockAmount <= 10) {
                $stats['low_stock']++;
            }
            if ($stockAmount == 0) {
                $stats['out_stock']++;
            }
        }
        
        // Filter products
        $filteredProducts = [];
        foreach ($products as $product) {
            // Category filter
            if ($category && ($product['category'] ?? '') !== $category) {
                continue;
            }
            
            // Stock filter
            if ($stock) {
                $stockAmount = (int)($product['stock'] ?? 0);
                if ($stock === 'good' && $stockAmount <= 10) continue;
                if ($stock === 'low' && ($stockAmount == 0 || $stockAmount > 10)) continue;
                if ($stock === 'out' && $stockAmount != 0) continue;
            }
            
            // Search filter
            if ($search) {
                $searchLower = strtolower($search);
                $matchName = strpos(strtolower($product['name'] ?? ''), $searchLower) !== false;
                if (!$matchName) {
                    continue;
                }
            }
            
            // Decode images JSON if it's a string
            if (isset($product['images']) && is_string($product['images'])) {
                $decoded = json_decode($product['images'], true);
                if (is_array($decoded)) {
                    $product['images'] = $decoded;
                }
            }
            
            $filteredProducts[] = $product;
        }
        
        // Sort by name
        usort($filteredProducts, function($a, $b) {
            return strcmp($a['name'] ?? '', $b['name'] ?? '');
        });
        
        echo json_encode([
            'success' => true,
            'products' => $filteredProducts,
            'stats' => $stats
        ]);
        
    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'message' => 'Error: ' . $e->getMessage()
        ]);
    }
}

// Handle POST request - Delete product
elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    $action = $input['action'] ?? '';
    
    try {
        if ($action === 'delete') {
            $productId = (int)($input['product_id'] ?? 0);
            
            if (!$productId) {
                throw new Exception('Missing product ID');
            }
            
            // Check if product has active orders (not implemented in JSON DB)
            // For now, we'll allow deletion but you should implement order_items checking
            
            // Delete product by filtering it out
            $products = $db->read('products');
            $newProducts = [];
            $deleted = false;
            
            foreach ($products as $product) {
                if ($product['id'] != $productId) {
                    $newProducts[] = $product;
                } else {
                    $deleted = true;
                }
            }
            
            if ($deleted) {
                $db->write('products', $newProducts);
                echo json_encode([
                    'success' => true,
                    'message' => 'Product deleted successfully'
                ]);
            } else {
                throw new Exception('Product not found');
            }
            
        } else {
            throw new Exception('Invalid action');
        }
        
    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'message' => $e->getMessage()
        ]);
    }
}
?>
