<?php
session_start();
require_once '../includes/db.php';

header('Content-Type: application/json');

// Check admin permission
if (!isset($_SESSION['is_admin']) || !$_SESSION['is_admin']) {
    echo json_encode(['success' => false, 'message' => 'ไม่มีสิทธิ์เข้าถึง']);
    exit;
}

$db = new DB();
$input = json_decode(file_get_contents('php://input'), true);
$action = $input['action'] ?? $_POST['action'] ?? '';

// CREATE COUPON
if ($action === 'create') {
    $code = strtoupper(trim($input['code'] ?? ''));
    $discountType = $input['discount_type'] ?? 'percent';
    $discountValue = floatval($input['discount_value'] ?? 0);
    $minOrder = floatval($input['min_order'] ?? 0);
    $expiryDate = $input['expiry_date'] ?? '';
    $description = trim($input['description'] ?? '');
    $tag = trim($input['tag'] ?? 'โปรโมชั่น');
    $icon = trim($input['icon'] ?? '🎫');

    if (empty($code)) {
        echo json_encode(['success' => false, 'message' => 'กรุณากรอกรหัสคูปอง']);
        exit;
    }

    // Check if code already exists
    $coupons = $db->read('coupons');
    foreach ($coupons as $c) {
        if (strtoupper($c['code']) === $code) {
            echo json_encode(['success' => false, 'message' => 'รหัสคูปองนี้มีอยู่แล้ว']);
            exit;
        }
    }

    $newCoupon = [
        'code' => $code,
        'discount_type' => $discountType,
        'discount_value' => $discountValue,
        'min_order' => $minOrder,
        'expiry_date' => $expiryDate,
        'description' => $description,
        'tag' => $tag,
        'icon' => $icon
    ];

    $db->insert('coupons', $newCoupon);
    echo json_encode(['success' => true, 'message' => 'สร้างคูปองสำเร็จ']);
    exit;
}

// LIST COUPONS
if ($action === 'list') {
    $coupons = $db->read('coupons');
    echo json_encode(['success' => true, 'data' => $coupons]);
    exit;
}

// DELETE COUPON
if ($action === 'delete') {
    $id = intval($input['id'] ?? 0);
    if ($id > 0) {
        $db->delete('coupons', $id);
        echo json_encode(['success' => true, 'message' => 'ลบคูปองสำเร็จ']);
    } else {
        echo json_encode(['success' => false, 'message' => 'ID ไม่ถูกต้อง']);
    }
    exit;
}

echo json_encode(['success' => false, 'message' => 'การกระทำไม่ถูกต้อง']);
?>
