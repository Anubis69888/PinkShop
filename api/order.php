<?php
session_start();
require_once '../includes/db.php';

// Disable error display to prevent JSON corruption
error_reporting(E_ALL);
ini_set('display_errors', 0);

header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'กรุณาเข้าสู่ระบบ']);
    exit;
}

$action = $_POST['action'] ?? '';

// If not cancelling, require cart
if ($action !== 'cancel' && empty($_SESSION['cart'])) {
    echo json_encode(['success' => false, 'message' => 'เซสชั่นไม่ถูกต้อง (ไม่มีสินค้าในตะกร้า)']);
    exit;
}

$db = new DB();
$products = $db->read('products');
$cart = $_SESSION['cart'];
$discount = $_SESSION['discount'] ?? 0;
$discountFixed = $_SESSION['discount_fixed'] ?? 0;
$freeShipping = $_SESSION['free_shipping'] ?? false;

// Calculate total
$total = 0;
$orderItems = [];
foreach ($cart as $pid => $qty) {
    foreach ($products as $p) {
        if ($p['id'] == $pid) {
            $total += $p['price'] * $qty;
            $orderItems[] = [
                'product_id' => $p['id'],
                'seller_id' => $p['seller_id'] ?? null,
                'name' => $p['name'],
                'price' => $p['price'],
                'qty' => $qty
            ];
            break;
        }
    }
}

// Calculate discount amount
$discountAmount = 0;
if ($discount > 0) {
    $discountAmount = $total * $discount;
} elseif ($discountFixed > 0) {
    $discountAmount = $discountFixed;
}

// Calculate shipping
$shipping = $freeShipping ? 0 : 40;
$finalTotal = ($total - $discountAmount) + $shipping;
// Ensure total is not negative
if ($finalTotal < 0) $finalTotal = 0;

if (isset($_POST['action']) && $_POST['action'] === 'cancel') {
    $orderId = $_POST['order_id'] ?? 0;
    $reason = htmlspecialchars($_POST['reason'] ?? 'ไม่ระบุเหตุผล');

    $order = $db->find('orders', 'id', $orderId);
    if (!$order || $order['user_id'] != $_SESSION['user_id']) {
        echo json_encode(['success' => false, 'message' => 'ไม่พบคำสั่งซื้อหรือไม่มีสิทธิ์']);
        exit;
    }

    $cancellableStatuses = ['รอชำระเงิน', 'ชำระเงินแล้ว'];
    if (!in_array($order['status'], $cancellableStatuses)) {
        echo json_encode(['success' => false, 'message' => 'ไม่สามารถยกเลิกได้ในขณะนี้']);
        exit;
    }

    $updates = [
        'status' => 'ยกเลิก',
        'cancel_reason' => $reason
    ];
    
    // Add timeline event
    $order['timeline'][] = [
        'status' => 'ยกเลิกคำสั่งซื้อ',
        'detail' => $reason,
        'time' => date('Y-m-d H:i:s')
    ];
    $updates['timeline'] = $order['timeline'];

    $db->update('orders', $orderId, $updates);
    echo json_encode(['success' => true]);
    exit;
}

// Handle Slip Upload & Verification
if ($_POST['payment_method'] === 'promptpay') {
    if (empty($_FILES['slip']) || $_FILES['slip']['error'] !== UPLOAD_ERR_OK) {
        echo json_encode(['success' => false, 'message' => 'กรุณาอัพโหลดสลิปโอนเงิน']);
        exit;
    }

    $uploadDir = '../assets/uploads/slips/';
    if (!file_exists($uploadDir)) mkdir($uploadDir, 0777, true);
    
    $ext = pathinfo($_FILES['slip']['name'], PATHINFO_EXTENSION);
    $filename = 'slip_' . uniqid() . '.' . $ext;
    $targetPath = $uploadDir . $filename;

    if (!move_uploaded_file($_FILES['slip']['tmp_name'], $targetPath)) {
        echo json_encode(['success' => false, 'message' => 'เกิดข้อผิดพลาดในการอัพโหลดสลิป']);
        exit;
    }

    require_once '../includes/AIVerifier.php';
    $verification = AIVerifier::verifySlip($targetPath, $finalTotal);

    if (!$verification['success']) {
        // Build detailed error message with steps if available
        $errorMessage = $verification['message'];
        if (!empty($verification['steps'])) {
            $stepDetails = array_map(function($s) {
                return $s['name'] . ': ' . $s['status'];
            }, $verification['steps']);
            $errorMessage .= "\n\n" . implode("\n", $stepDetails);
        }
        
        echo json_encode([
            'success' => false, 
            'message' => $verification['message'],
            'steps' => $verification['steps'] ?? []
        ]);
        unlink($targetPath); // Delete invalid slip
        exit;
    }
}

// Generate Tracking Number (e.g., TH123456789)
$trackingNumber = 'TH' . strtoupper(substr(uniqid(), -9));

// Determine order status based on verification result
$orderStatus = 'ชำระเงินแล้ว';
$timelineStatus = 'ชำระเงินแล้ว';

if (!empty($verification['manual_review'])) {
    $orderStatus = 'รอตรวจสอบ';
    $timelineStatus = 'รอตรวจสอบการชำระเงิน (Slip)';
}

// Prepare order data
$orderData = [
    'user_id' => $_SESSION['user_id'],
    'items' => $orderItems,
    'total' => $finalTotal,
    'shipping_info' => [
        'fullname' => $_POST['fullname'],
        'address' => $_POST['address'],
        'phone' => $_POST['phone']
    ],
    'status' => $orderStatus,
    'tracking_number' => $trackingNumber,
    'created_at' => date('Y-m-d H:i:s'),
    'timeline' => [
        ['status' => 'สั่งซื้อสำเร็จ', 'time' => date('Y-m-d H:i:s')],
        ['status' => $timelineStatus, 'time' => date('Y-m-d H:i:s')]
    ]
];

// Add payment slip if uploaded
if (isset($filename)) {
    $orderData['payment_slip'] = 'assets/uploads/slips/' . $filename;
}

$orderId = $db->insert('orders', $orderData);

// Clear cart & session
unset($_SESSION['cart']);
unset($_SESSION['discount']);
unset($_SESSION['discount_fixed']);
unset($_SESSION['free_shipping']);
unset($_SESSION['discount_code']);

echo json_encode([
    'success' => true, 
    'order_id' => $orderId, 
    'tracking_number' => $trackingNumber,
    'status' => $orderStatus
]);
?>
