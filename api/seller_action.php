<?php
session_start();
require_once '../includes/db.php';

// Disable error display to prevent JSON corruption
error_reporting(E_ALL);
ini_set('display_errors', 0);

header('Content-Type: application/json');

// Check authentication
if (!isset($_SESSION['user_id']) || empty($_SESSION['is_seller'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$action = $_POST['action'] ?? '';
$db = new DB();

    if ($action === 'update_status') {
    $orderId = $_POST['order_id'] ?? 0;
    // Status might be empty now, it's optional if tracking number is provided
    $newStatus = $_POST['status'] ?? '';
    $trackingNumber = $_POST['tracking_number'] ?? '';

    if (empty($orderId)) {
        echo json_encode(['success' => false, 'message' => 'Missing order ID']);
        exit;
    }
    
    // Auto-determine status if tracking number is provided
    if (!empty($trackingNumber)) {
        if (empty($newStatus)) {
            $newStatus = 'อยู่ระหว่างจัดส่ง';
        }
    }

    if (empty($newStatus)) {
        echo json_encode(['success' => false, 'message' => 'Status or Tracking Number required']);
        exit;
    }

    $order = $db->find('orders', 'id', $orderId);
    if (!$order) {
        echo json_encode(['success' => false, 'message' => 'Order not found']);
        exit;
    }

    // Verify seller owns at least one product in the order
    // (In a stricter system, we'd check if they own ALL, or split orders)
    $products = $db->read('products');
    $sellerProducts = [];
    foreach ($products as $p) {
        if ($p['seller_id'] == $_SESSION['user_id']) {
            $sellerProducts[] = $p['id'];
        }
    }

    $hasPermission = false;
    foreach ($order['items'] as $item) {
        if (in_array($item['product_id'], $sellerProducts)) {
            $hasPermission = true;
            break;
        }
    }

    if (!$hasPermission) {
        echo json_encode(['success' => false, 'message' => 'You do not have permission to manage this order']);
        exit;
    }

    // AI Verification for shipping proof (if receipt image uploaded)
    $aiVerificationResult = null;
    if (!empty($_FILES['receipt_image']) && $_FILES['receipt_image']['error'] === UPLOAD_ERR_OK) {
        // Upload receipt image
        $uploadDir = '../assets/uploads/shipping_proofs/';
        if (!file_exists($uploadDir)) mkdir($uploadDir, 0777, true);
        
        $ext = pathinfo($_FILES['receipt_image']['name'], PATHINFO_EXTENSION);
        $filename = 'proof_' . $orderId . '_' . time() . '.' . $ext;
        $targetPath = $uploadDir . $filename;
        
        if (move_uploaded_file($_FILES['receipt_image']['tmp_name'], $targetPath)) {
            // Perform AI verification
            require_once '../includes/AIVerifier.php';
            
            $orderData = [
                'customer_name' => $order['shipping_info']['fullname'] ?? 'Unknown',
                'tracking_number' => $trackingNumber
            ];
            
            $aiVerificationResult = AIVerifier::verifyShippingProof($targetPath, $orderData);
            
            // Store verification results
            $updates['shipping_proof'] = 'assets/uploads/shipping_proofs/' . $filename;
            $updates['verification_results'] = $aiVerificationResult;
            
            // If verification failed, don't auto-update to shipping status
            if (!$aiVerificationResult['success']) {
                $newStatus = 'ชำระเงินแล้ว'; // Keep as paid, don't mark as shipping
                $updates['status'] = $newStatus;
            }
        }
    }
    
    if (!empty($trackingNumber)) {
        $updates['tracking_number'] = $trackingNumber;
    }

    // Update Logic
    $updates['status'] = $newStatus;
    
    // Add timeline
    if (!isset($order['timeline'])) {
        $order['timeline'] = [];
    }
    
    $timelineText = $newStatus;
    if ($newStatus == 'อยู่ระหว่างจัดส่ง') {
        $timelineText = 'กำลังจัดส่ง';
    } elseif ($newStatus == 'จัดส่งสำเร็จ') {
        $timelineText = 'จัดส่งสำเร็จ';
    }
    
    
    if (!empty($trackingNumber)) {
        $timelineText .= " (เลขพัสดุ: $trackingNumber)";
        
        // Add AI verification results to timeline
        if ($aiVerificationResult) {
            if ($aiVerificationResult['success']) {
                $timelineText .= " - ✅ AI ตรวจสอบหลักฐานผ่าน";
            } else {
                $timelineText .= " - ❌ AI ตรวจพบข้อผิดพลาด";
            }
        }
    }

    $order['timeline'][] = [
        'status' => $timelineText,
        'time' => date('Y-m-d H:i:s'),
        'detail' => 'อัปเดตโดยผู้ขาย'
    ];
    $updates['timeline'] = $order['timeline'];

    $db->update('orders', $orderId, $updates);

    $response = ['success' => true];
    
    // Include AI verification results if available
    if ($aiVerificationResult) {
        $response['ai_verification'] = $aiVerificationResult;
    }

    echo json_encode($response);
    exit;
}

echo json_encode(['success' => false, 'message' => 'Invalid action']);
?>
