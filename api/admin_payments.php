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

// Handle GET request - Fetch payments
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $dateFrom = isset($_GET['date_from']) ? $_GET['date_from'] : date('Y-m-01'); // First day of current month
    $dateTo = isset($_GET['date_to']) ? $_GET['date_to'] : date('Y-m-d'); // Today
    
    try {
        $orders = $db->read('orders');
        $users = $db->read('users');
        
        // Create user lookup map
        $userMap = [];
        foreach ($users as $user) {
            $userMap[$user['id']] = $user;
        }
        
        // Calculate statistics and filter by date
        $stats = [
            'total_revenue' => 0,
            'successful_count' => 0,
            'pending_count' => 0
        ];
        
        $payments = [];
        $dateToEnd = $dateTo . ' 23:59:59';
        
        foreach ($orders as $order) {
            $orderDate = $order['created_at'] ?? '';
            
            // Date range filter
            if ($orderDate < $dateFrom || $orderDate > $dateToEnd) {
                continue;
            }
            
            // Calculate stats
            if ($order['status'] === 'paid' || $order['status'] === 'completed') {
                $stats['total_revenue'] += (float)($order['total'] ?? 0);
                $stats['successful_count']++;
            }
            
            if ($order['status'] === 'pending') {
                $stats['pending_count']++;
            }
            
            // Add to payments list
            $user = $userMap[$order['user_id']] ?? ['username' => 'Unknown',  'email' => ''];
            $payments[] = [
                'id' => $order['id'],
                'order_id' => $order['id'],
                'user_id' => $order['user_id'],
                'status' => $order['status'],
                'amount' => $order['total'] ?? 0,
                'created_at' => $order['created_at'],
                'payment_slip' => $order['payment_slip'] ?? '',
                'shipping_proof' => $order['shipping_proof'] ?? '',
                'customer_name' => $user['username'],
                'customer_email' => $user['email']
            ];
        }
        
        // Sort by created_at DESC
        usort($payments, function($a, $b) {
            return strcmp($b['created_at'] ?? '', $a['created_at'] ?? '');
        });
        
        // Limit to 200
        $payments = array_slice($payments, 0, 200);
        
        echo json_encode([
            'success' => true,
            'payments' => $payments,
            'stats' => $stats
        ]);
        
    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'message' => 'Error: ' . $e->getMessage()
        ]);
    }
}
?>
