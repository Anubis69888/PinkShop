<?php
session_start();
require_once '../includes/db.php';

// Disable error display to prevent JSON corruption
error_reporting(E_ALL);
ini_set('display_errors', 0);

header('Content-Type: application/json');

if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

$input = json_decode(file_get_contents('php://input'), true);
$action = $input['action'] ?? '';

if ($action === 'add') {
    $productId = $input['productId'] ?? 0;
    if (!$productId) {
        echo json_encode(['success' => false, 'message' => 'สินค้าไม่ถูกต้อง']);
        exit;
    }

    if (isset($_SESSION['cart'][$productId])) {
        $_SESSION['cart'][$productId]++;
    } else {
        $_SESSION['cart'][$productId] = 1;
    }

    echo json_encode(['success' => true, 'cart_count' => count($_SESSION['cart'])]);
    exit;
}

if ($action === 'remove') {
    $productId = $input['productId'] ?? 0;
    unset($_SESSION['cart'][$productId]);
    echo json_encode(['success' => true]);
    exit;
}

if ($action === 'apply_code') {
    $code = strtoupper(trim($input['code'] ?? ''));
    
    // Initialize DB to check products/prices
    $db = new DB();
    $products = $db->read('products');
    $coupons = $db->read('coupons');
    
    // Calculate Cart Totals
    $cart = $_SESSION['cart'] ?? [];
    $subtotal = 0;
    $totalQty = 0;
    
    foreach ($cart as $pid => $qty) {
        foreach ($products as $p) {
            if ($p['id'] == $pid) {
                $price = !empty($p['sale_price']) && $p['sale_price'] < $p['price'] ? $p['sale_price'] : $p['price'];
                $subtotal += $price * $qty;
                $totalQty += $qty;
                break;
            }
        }
    }

    // Find coupon in database
    $foundCoupon = null;
    foreach ($coupons as $c) {
        if (strtoupper($c['code']) === $code) {
            $foundCoupon = $c;
            break;
        }
    }

    if (!$foundCoupon) {
        unset($_SESSION['discount']);
        unset($_SESSION['discount_code']);
        echo json_encode(['success' => false, 'message' => 'โค้ดไม่ถูกต้องหรือหมดอายุ']);
        exit;
    }

    // Check expiry date
    if (!empty($foundCoupon['expiry_date']) && $foundCoupon['expiry_date'] < date('Y-m-d')) {
        echo json_encode(['success' => false, 'message' => 'โค้ดนี้หมดอายุแล้ว']);
        exit;
    }

    // Check minimum order
    $minOrder = floatval($foundCoupon['min_order'] ?? 0);
    if ($minOrder > 0 && $subtotal < $minOrder) {
        echo json_encode(['success' => false, 'message' => "ยอดซื้อไม่ถึงขั้นต่ำ ฿" . number_format($minOrder)]);
        exit;
    }

    // Check minimum quantity (for bundle deals)
    $minQty = intval($foundCoupon['min_qty'] ?? 0);
    if ($minQty > 0 && $totalQty < $minQty) {
        echo json_encode(['success' => false, 'message' => "ต้องซื้อสินค้าอย่างน้อย {$minQty} ชิ้น"]);
        exit;
    }

    // Apply discount based on type
    $discountType = $foundCoupon['discount_type'] ?? 'percent';
    $discountValue = floatval($foundCoupon['discount_value'] ?? 0);

    if ($discountType === 'percent') {
        $_SESSION['discount'] = $discountValue / 100;
        $_SESSION['discount_code'] = $code;
        echo json_encode(['success' => true, 'message' => "ใช้โค้ดสำเร็จ! ลด {$discountValue}%"]);
    } elseif ($discountType === 'fixed') {
        $_SESSION['discount_fixed'] = $discountValue;
        $_SESSION['discount_code'] = $code;
        echo json_encode(['success' => true, 'message' => "ใช้โค้ดสำเร็จ! ลด ฿" . number_format($discountValue)]);
    } elseif ($discountType === 'freeship') {
        $_SESSION['free_shipping'] = true;
        $_SESSION['discount_code'] = $code;
        echo json_encode(['success' => true, 'message' => 'ใช้โค้ดส่งฟรีสำเร็จ!']);
    } else {
        echo json_encode(['success' => false, 'message' => 'ประเภทส่วนลดไม่ถูกต้อง']);
    }
    exit;
}

echo json_encode(['success' => false, 'message' => 'การกระทำไม่ถูกต้อง']);
?>
