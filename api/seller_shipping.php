<?php
session_start();
require_once '../includes/db.php';
require_once '../includes/AIVerifier.php';

header('Content-Type: application/json');

// Check if user is seller
if (!isset($_SESSION['user_id']) || !isset($_SESSION['is_seller']) || !$_SESSION['is_seller']) {
    echo json_encode(['success' => false, 'message' => 'ไม่มีสิทธิ์เข้าถึง']);
    exit;
}

$action = $_POST['action'] ?? '';
$db = new DB();

if ($action === 'upload_proof') {
    $orderId = $_POST['order_id'] ?? 0;
    
    // Get order
    $order = $db->find('orders', 'id', $orderId);
    if (!$order) {
        echo json_encode(['success' => false, 'message' => 'ไม่พบคำสั่งซื้อ']);
        exit;
    }
    
    // Check if order needs shipping proof
    if ($order['status'] !== 'ชำระเงินแล้ว') {
        echo json_encode(['success' => false, 'message' => 'สถานะคำสั่งซื้อไม่ถูกต้อง']);
        exit;
    }
    
    // Handle image upload
    if (empty($_FILES['proof']) || $_FILES['proof']['error'] !== UPLOAD_ERR_OK) {
        echo json_encode(['success' => false, 'message' => 'กรุณาอัพโหลดรูปหลักฐานการจัดส่ง']);
        exit;
    }
    
    $uploadDir = '../assets/uploads/shipping_proofs/';
    if (!file_exists($uploadDir)) mkdir($uploadDir, 0777, true);
    
    $ext = pathinfo($_FILES['proof']['name'], PATHINFO_EXTENSION);
    $filename = 'proof_' . $orderId . '_' . time() . '.' . $ext;
    $targetPath = $uploadDir . $filename;
    
    if (!move_uploaded_file($_FILES['proof']['tmp_name'], $targetPath)) {
        echo json_encode(['success' => false, 'message' => 'เกิดข้อผิดพลาดในการอัพโหลด']);
        exit;
    }
    
    // Prepare order data for verification
    $orderData = [
        'customer_name' => $order['shipping_info']['fullname'] ?? 'Unknown',
        'tracking_number' => $order['tracking_number'] ?? ''
    ];
    
    // AI Verification
    $verification = AIVerifier::verifyShippingProof($targetPath, $orderData);
    
    // Update order with verification results
    $updates = [
        'shipping_proof' => 'assets/uploads/shipping_proofs/' . $filename,
        'verification_results' => $verification,
        'status' => $verification['success'] ? 'กำลังจัดส่ง' : 'ชำระเงินแล้ว'
    ];
    
    // Add timeline event
    $order['timeline'][] = [
        'status' => $verification['success'] ? 'กำลังจัดส่ง' : 'หลักฐานการจัดส่งไม่ถูกต้อง',
        'detail' => $verification['overall_message'],
        'time' => date('Y-m-d H:i:s')
    ];
    $updates['timeline'] = $order['timeline'];
    
    $db->update('orders', $orderId, $updates);
    
    echo json_encode([
        'success' => true,
        'verification' => $verification,
        'message' => $verification['overall_message']
    ]);
    exit;
}

// Get seller's orders that need shipping
if ($action === 'get_orders') {
    $orders = $db->read('orders');
    
    // Filter orders that are paid and waiting for shipping
    $sellerOrders = array_filter($orders, function($order) {
        return $order['status'] === 'ชำระเงินแล้ว' || $order['status'] === 'กำลังจัดส่ง';
    });
    
    echo json_encode(['success' => true, 'orders' => array_values($sellerOrders)]);
    exit;
}

echo json_encode(['success' => false, 'message' => 'Invalid action']);
?>
