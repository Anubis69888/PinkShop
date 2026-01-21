<?php
session_start();
require_once '../includes/db.php';

header('Content-Type: application/json');

// Check Admin Access
if (empty($_SESSION['is_admin'])) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$db = new DB();
$orders = $db->read('orders');
$products = $db->read('products');
$users = $db->read('users');
$viewStats = [];
$statsFile = '../data/product_stats.json';

if (file_exists($statsFile)) {
    $viewStats = json_decode(file_get_contents($statsFile), true) ?? [];
}

// 1. Overview Stats
$totalSales = 0;
$totalOrders = count($orders);
$totalMembers = count($users);

// Calculate Sales and prepare chart data
$salesByDate = [];
$recentOrders = [];

// Sort orders by date desc for recent list
usort($orders, function($a, $b) {
    return strtotime($b['created_at']) - strtotime($a['created_at']);
});

foreach ($orders as $order) {
    // Only count paid/completed orders for revenue (checking status)
    if ($order['status'] !== 'ยกเลิก' && $order['status'] !== 'รอชำระเงิน') {
        $totalSales += $order['total'];
        
        $date = date('Y-m-d', strtotime($order['created_at']));
        if (!isset($salesByDate[$date])) $salesByDate[$date] = 0;
        $salesByDate[$date] += $order['total'];
    }
}

// Get recent 5 orders
$recentOrders = array_slice($orders, 0, 5);

// 2. Product Stats (Views vs Sales)
$productPerformance = [];
foreach ($products as $p) {
    $pid = $p['id'];
    $views = 0;
    
    // Find views
    foreach ($viewStats as $vs) {
        if ($vs['product_id'] == $pid) {
            $views = $vs['views'];
            break;
        }
    }
    
    // Count sales quantity
    $salesQty = 0;
    foreach ($orders as $o) {
        if ($o['status'] !== 'ยกเลิก') {
            foreach ($o['items'] as $item) {
                if ($item['product_id'] == $pid) {
                    $salesQty += $item['qty'];
                }
            }
        }
    }
    
    $productPerformance[] = [
        'name' => $p['name'],
        'views' => $views,
        'sales' => $salesQty
    ];
}

// Sort by views desc
usort($productPerformance, function($a, $b) {
    return $b['views'] - $a['views'];
});

// Take top 10
$productPerformance = array_slice($productPerformance, 0, 10);

echo json_encode([
    'success' => true,
    'overview' => [
        'total_sales' => $totalSales,
        'total_orders' => $totalOrders,
        'total_members' => $totalMembers
    ],
    'sales_chart' => $salesByDate, // Frontend will need to sort keys or format
    'product_stats' => $productPerformance,
    'recent_orders' => $recentOrders
]);
?>
