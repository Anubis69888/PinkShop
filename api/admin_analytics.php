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
$users = $db->read('users');
$products = $db->read('products'); // For product names
$statsFile = '../data/product_stats.json';
$viewStats = [];
if (file_exists($statsFile)) {
    $viewStats = json_decode(file_get_contents($statsFile), true) ?? [];
}

// 1. User Growth (Last 30 Days)
$userGrowth = [];
$now = time();
for ($i = 29; $i >= 0; $i--) {
    $date = date('Y-m-d', strtotime("-$i days"));
    $userGrowth[$date] = 0;
}

foreach ($users as $user) {
    if (isset($user['created_at'])) {
        $regDate = date('Y-m-d', strtotime($user['created_at']));
        if (isset($userGrowth[$regDate])) {
            $userGrowth[$regDate]++;
        }
    }
}

// 2. User Ranks Logic
$ranks = [
    'admin' => 0,
    'seller' => 0,
    'new_user' => 0,
    'user' => 0
];

foreach ($users as $user) {
    if (!empty($user['is_admin'])) {
        $ranks['admin']++;
        continue;
    }
    if (!empty($user['is_seller'])) {
        $ranks['seller']++;
        continue;
    }
    
    // Check if new user (<= 30 Days)
    $regTime = strtotime($user['created_at'] ?? 'now');
    $daysDiff = ($now - $regTime) / (60 * 60 * 24);
    
    if ($daysDiff <= 30) {
        $ranks['new_user']++;
    } else {
        $ranks['user']++;
    }
}

// 3. Top Viewed Products (Top 20)
$productViews = [];
foreach ($products as $p) {
    $pid = $p['id'];
    $views = 0;
    foreach ($viewStats as $vs) {
        if ($vs['product_id'] == $pid) {
            $views = $vs['views'];
            break;
        }
    }
    if ($views > 0) {
        $productViews[] = [
            'name' => $p['name'],
            'views' => $views
        ];
    }
}
usort($productViews, function($a, $b) {
    return $b['views'] - $a['views'];
});
$topProducts = array_slice($productViews, 0, 20);

echo json_encode([
    'success' => true,
    'user_growth' => $userGrowth,
    'user_ranks' => $ranks,
    'top_products' => $topProducts
]);
?>
