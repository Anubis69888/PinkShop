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

// Handle GET request - Fetch customers
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $search = isset($_GET['search']) ? $_GET['search'] : '';
    
    try {
        $users = $db->read('users');
        $orders = $db->read('orders');
        
        // Create order data per user
        $userOrderData = [];
        foreach ($orders as $order) {
            $userId = $order['user_id'];
            if (!isset($userOrderData[$userId])) {
                $userOrderData[$userId] = [
                    'total_orders' => 0,
                    'total_spent' => 0
                ];
            }
            
            if ($order['status'] === 'paid' || $order['status'] === 'completed') {
                $userOrderData[$userId]['total_orders']++;
                $userOrderData[$userId]['total_spent'] += (float)($order['total_amount'] ?? 0);
            }
        }
        
        // Calculate statistics
        $totalCustomers = 0;
        $newThisMonth = 0;
        $vipCustomers = 0;
        $customersWithOrders = 0;
        
        $currentMonth = date('Y-m');
        
        foreach ($users as $user) {
            if (isset($user['is_admin']) && $user['is_admin']) {
                continue;
            }
            
            $totalCustomers++;
            
            // New this month
            if (isset($user['created_at']) && strpos($user['created_at'], $currentMonth) === 0) {
                $newThisMonth++;
            }
            
            // VIP (spent > 5000)
            if (isset($userOrderData[$user['id']]) && $userOrderData[$user['id']]['total_spent'] > 5000) {
                $vipCustomers++;
            }
            
            // Has orders
            if (isset($userOrderData[$user['id']]) && $userOrderData[$user['id']]['total_orders'] > 0) {
                $customersWithOrders++;
            }
        }
        
        $conversionRate = $totalCustomers > 0 ? round(($customersWithOrders / $totalCustomers) * 100, 1) : 0;
        
        $stats = [
            'total' => $totalCustomers,
            'new_this_month' => $newThisMonth,
            'vip' => $vipCustomers,
            'conversion_rate' => $conversionRate
        ];
        
        // Build customers list
        $customers = [];
        foreach ($users as $user) {
            if (isset($user['is_admin']) && $user['is_admin']) {
                continue;
            }
            
            // Search filter
            if ($search) {
                $searchLower = strtolower($search);
                $matchName = strpos(strtolower($user['username'] ?? ''), $searchLower) !== false;
                $matchEmail = strpos(strtolower($user['email'] ?? ''), $searchLower) !== false;
                $matchPhone = isset($user['phone']) && strpos(strtolower($user['phone']), $searchLower) !== false;
                
                if (!$matchName && !$matchEmail && !$matchPhone) {
                    continue;
                }
            }
            
            $orderData = $userOrderData[$user['id']] ?? ['total_orders' => 0, 'total_spent' => 0];
            
            // Get avatar from avatar_config or avatar field
            $avatar = '';
            if (!empty($user['avatar_config']) && isset($user['avatar_config']['src'])) {
                $avatar = $user['avatar_config']['src'];
            } elseif (!empty($user['avatar'])) {
                $avatar = $user['avatar'];
            }
            
            // Get email or fallback to '-'
            $email = !empty($user['email']) ? $user['email'] : '-';
            
            $customers[] = [
                'id' => $user['id'],
                'username' => $user['username'] ?? 'ไม่ระบุ',
                'email' => $email,
                'phone' => $user['phone'] ?? '-',
                'avatar' => $avatar,
                'created_at' => $user['created_at'] ?? date('Y-m-d H:i:s'),
                'total_orders' => $orderData['total_orders'],
                'total_spent' => $orderData['total_spent']
            ];
        }
        
        // Sort by total_spent DESC
        usort($customers, function($a, $b) {
            return $b['total_spent'] - $a['total_spent'];
        });
        
        // Limit to 100
        $customers = array_slice($customers, 0, 100);
        
        echo json_encode([
            'success' => true,
            'customers' => $customers,
            'stats' => $stats
        ]);
        
    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'message' => 'Error: ' . $e->getMessage()
        ]);
    }
}

// Handle POST request - Create coupon
elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    $action = $input['action'] ?? '';
    
    try {
        if ($action === 'create_coupon') {
            $code = strtoupper($input['code'] ?? '');
            $type = $input['type'] ?? 'percent';
            $value = (float)($input['value'] ?? 0);
            $minPurchase = (float)($input['min_purchase'] ?? 0);
            $startDate = $input['start_date'] ?? '';
            $expiryDate = $input['expiry_date'] ?? '';
            
            if (!$code || !$value) {
                throw new Exception('กรุณากรอกรหัสคูปองและมูลค่าส่วนลด');
            }
            
            // Read existing coupons
            $coupons = $db->read('coupons');
            
            // Check if code already exists
            foreach ($coupons as $coupon) {
                if ($coupon['code'] === $code) {
                    throw new Exception('รหัสคูปองนี้มีอยู่แล้ว');
                }
            }
            
            // Create new coupon
            $newCoupon = [
                'code' => $code,
                'discount_type' => $type,
                'discount_value' => $value,
                'min_order' => $minPurchase,
                'start_date' => $startDate ?: null,
                'expiry_date' => $expiryDate ?: null,
                'is_active' => true
            ];
            
            $couponId = $db->insert('coupons', $newCoupon);
            
            if ($couponId) {
                echo json_encode([
                    'success' => true,
                    'message' => 'Coupon created successfully',
                    'code' => $code
                ]);
            } else {
                throw new Exception('Failed to create coupon');
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
