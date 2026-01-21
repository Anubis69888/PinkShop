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

// Thai status mapping
$statusMapThai = [
    'รอชำระเงิน' => 'pending',
    'ชำระเงินแล้ว' => 'paid',
    'กำลังจัดส่ง' => 'shipping',
    'อยู่ระหว่างจัดส่ง' => 'shipping',
    'จัดส่งแล้ว' => 'completed',
    'สำเร็จ' => 'completed',
    'ยกเลิก' => 'cancelled'
];

// English to Thai mapping
$statusMapEng = [
    'pending' => 'รอชำระเงิน',
    'paid' => 'ชำระเงินแล้ว',
    'shipping' => 'กำลังจัดส่ง',
    'completed' => 'จัดส่งแล้ว',
    'cancelled' => 'ยกเลิก'
];

// Handle GET request - Fetch orders
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $status = isset($_GET['status']) ? $_GET['status'] : '';
    $search = isset($_GET['search']) ? $_GET['search'] : '';
    
    try {
        $orders = $db->read('orders');
        $users = $db->read('users');
        
        // Create user lookup map
        $userMap = [];
        foreach ($users as $user) {
            $userMap[$user['id']] = $user;
        }
        
        // Calculate statistics
        $stats = [
            'pending' => 0,
            'paid' => 0,
            'shipping' => 0,
            'completed' => 0
        ];
        
        foreach ($orders as $order) {
            $orderStatus = $order['status'] ?? '';
            // Map Thai status to English
            $normalizedStatus = $statusMapThai[$orderStatus] ?? strtolower($orderStatus);
            if (isset($stats[$normalizedStatus])) {
                $stats[$normalizedStatus]++;
            }
        }
        
        // Filter orders
        $filteredOrders = [];
        foreach ($orders as $order) {
            $orderStatus = $order['status'] ?? '';
            $normalizedStatus = $statusMapThai[$orderStatus] ?? strtolower($orderStatus);
            
            // Status filter
            if ($status && $normalizedStatus !== $status) {
                continue;
            }
            
            // Search filter
            if ($search) {
                $user = $userMap[$order['user_id']] ?? null;
                $searchLower = mb_strtolower($search);
                $matchId = strpos(strtolower((string)$order['id']), $searchLower) !== false;
                $matchName = $user && mb_strpos(mb_strtolower($user['username']), $searchLower) !== false;
                $matchEmail = $user && mb_strpos(mb_strtolower($user['email'] ?? ''), $searchLower) !== false;
                $matchTracking = mb_strpos(mb_strtolower($order['tracking_number'] ?? ''), $searchLower) !== false;
                
                if (!$matchId && !$matchName && !$matchEmail && !$matchTracking) {
                    continue;
                }
            }
            
            // Add customer info to order
            $user = $userMap[$order['user_id']] ?? ['username' => 'Unknown', 'email' => ''];
            $order['customer_name'] = $user['username'];
            $order['customer_email'] = $user['email'] ?? '';
            
            // Normalize status for frontend
            $order['status_normalized'] = $normalizedStatus;
            $order['status_thai'] = $order['status'];
            
            // Fix amount field - use 'total' if 'total_amount' doesn't exist
            $order['total_amount'] = $order['total'] ?? $order['total_amount'] ?? 0;
            
            $filteredOrders[] = $order;
        }
        
        // Sort by created_at DESC
        usort($filteredOrders, function($a, $b) {
            return strcmp($b['created_at'] ?? '', $a['created_at'] ?? '');
        });
        
        // Limit to 100
        $filteredOrders = array_slice($filteredOrders, 0, 100);
        
        echo json_encode([
            'success' => true,
            'orders' => $filteredOrders,
            'stats' => $stats
        ]);
        
    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'message' => 'Error: ' . $e->getMessage()
        ]);
    }
}

// Handle POST request - Update order status or tracking
elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    $action = $input['action'] ?? '';
    
    try {
        if ($action === 'update_status') {
            $orderId = (int)($input['order_id'] ?? 0);
            $newStatus = $input['status'] ?? '';
            
            if (!$orderId || !$newStatus) {
                throw new Exception('Missing required fields');
            }
            
            // Validate and convert status to Thai if needed
            $validStatuses = ['pending', 'paid', 'shipping', 'completed', 'cancelled'];
            if (!in_array($newStatus, $validStatuses)) {
                throw new Exception('Invalid status');
            }
            
            // Convert to Thai status for storage
            $thaiStatus = $statusMapEng[$newStatus] ?? $newStatus;
            
            // Get current order for timeline
            $order = $db->find('orders', 'id', $orderId);
            $timeline = $order['timeline'] ?? [];
            $timeline[] = [
                'status' => $thaiStatus,
                'time' => date('Y-m-d H:i:s'),
                'detail' => 'อัปเดตโดยแอดมิน'
            ];
            
            // Update order status
            $result = $db->update('orders', $orderId, [
                'status' => $thaiStatus,
                'timeline' => $timeline
            ]);
            
            if ($result) {
                echo json_encode([
                    'success' => true,
                    'message' => 'อัปเดตสถานะเรียบร้อยแล้ว'
                ]);
            } else {
                throw new Exception('Failed to update order status');
            }
            
        } elseif ($action === 'update_tracking') {
            $orderId = (int)($input['order_id'] ?? 0);
            $trackingNumber = $input['tracking_number'] ?? '';
            
            if (!$orderId || !$trackingNumber) {
                throw new Exception('Missing required fields');
            }
            
            // Get current order for timeline
            $order = $db->find('orders', 'id', $orderId);
            $timeline = $order['timeline'] ?? [];
            $timeline[] = [
                'status' => 'กำลังจัดส่ง (เลขพัสดุ: ' . $trackingNumber . ')',
                'time' => date('Y-m-d H:i:s'),
                'detail' => 'อัปเดตโดยแอดมิน'
            ];
            
            // Update tracking number and set status to shipping
            $result = $db->update('orders', $orderId, [
                'tracking_number' => $trackingNumber,
                'status' => 'กำลังจัดส่ง',
                'timeline' => $timeline
            ]);
            
            if ($result) {
                echo json_encode([
                    'success' => true,
                    'message' => 'บันทึกเลขพัสดุเรียบร้อยแล้ว'
                ]);
            } else {
                throw new Exception('Failed to save tracking number');
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
