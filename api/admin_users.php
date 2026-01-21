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
$orders = $db->read('orders');

$action = $_GET['action'] ?? 'list';

if ($action === 'list') {
    // Return all users with basic info
    $userList = [];
    foreach ($users as $user) {
        $userList[] = [
            'id' => $user['id'],
            'username' => $user['username'],
            'fullname' => $user['fullname'],
            'is_seller' => $user['is_seller'] ?? false,
            'is_admin' => $user['is_admin'] ?? false,
            'is_banned' => $user['is_banned'] ?? false,
            'avatar' => $user['avatar_config']['src'] ?? 'assets/images/default_avatar.png',
            'created_at' => $user['created_at'] ?? '-',
            'phone' => $user['phone'] ?? '-' // Added phone for quick view
        ];
    }
    echo json_encode(['success' => true, 'users' => $userList]);
    exit;
}

if ($action === 'details') {
    $userId = $_GET['id'] ?? 0;

    // Find User
    $targetUser = null;
    foreach ($users as $u) {
        if ($u['id'] == $userId) {
            $targetUser = $u;
            break;
        }
    }

    if (!$targetUser) {
        echo json_encode(['success' => false, 'message' => 'User not found']);
        exit;
    }

    // Password is kept for admin view
    // unset($targetUser['password']);

    // Ensure ID Card path is valid (or null)
    $targetUser['id_card_image'] = $targetUser['id_card_image'] ?? null;

    // Find Orders
    $userOrders = [];
    foreach ($orders as $order) {
        if ($order['user_id'] == $userId) {
            $userOrders[] = $order;
        }
    }

    // Sort orders detailed by date desc
    usort($userOrders, function ($a, $b) {
        return strtotime($b['created_at']) - strtotime($a['created_at']);
    });

    echo json_encode([
        'success' => true,
        'user' => $targetUser,
        'orders' => $userOrders
    ]);
    exit;
}

// Ban/Unban User Action
if ($action === 'ban' || $action === 'unban') {
    $userId = $_GET['id'] ?? 0;

    if (!$userId) {
        echo json_encode(['success' => false, 'message' => 'ไม่พบ ID ผู้ใช้']);
        exit;
    }

    // Find and update user
    $found = false;
    foreach ($users as &$user) {
        if ($user['id'] == $userId) {
            // Prevent banning admin
            if (!empty($user['is_admin'])) {
                echo json_encode(['success' => false, 'message' => 'ไม่สามารถแบนผู้ดูแลระบบได้']);
                exit;
            }

            $user['is_banned'] = ($action === 'ban');
            $user['banned_at'] = ($action === 'ban') ? date('Y-m-d H:i:s') : null;
            $found = true;
            break;
        }
    }
    unset($user);

    if (!$found) {
        echo json_encode(['success' => false, 'message' => 'ไม่พบผู้ใช้']);
        exit;
    }

    // Save to database
    $db->write('users', $users);

    $message = ($action === 'ban') ? 'แบนผู้ใช้สำเร็จ' : 'ปลดแบนผู้ใช้สำเร็จ';
    echo json_encode(['success' => true, 'message' => $message]);
    exit;
}

// Update User Action
if ($action === 'update') {
    $data = json_decode(file_get_contents('php://input'), true);
    $userId = $data['id'] ?? 0;

    if (!$userId) {
        echo json_encode(['success' => false, 'message' => 'ไม่พบ ID ผู้ใช้']);
        exit;
    }

    $found = false;
    foreach ($users as &$user) {
        if ($user['id'] == $userId) {
            // Prevent modifying admin
            if (!empty($user['is_admin'])) {
                echo json_encode(['success' => false, 'message' => 'ไม่สามารถแก้ไขข้อมูลผู้ดูแลระบบได้']);
                exit;
            }

            // Update allowed fields
            if (isset($data['fullname']))
                $user['fullname'] = trim($data['fullname']);
            if (isset($data['phone']))
                $user['phone'] = trim($data['phone']);
            if (isset($data['address']))
                $user['address'] = trim($data['address']);

            // Advanced Fields
            if (isset($data['username']) && !empty($data['username'])) {
                $newUsername = trim($data['username']);
                if ($newUsername !== $user['username']) {
                    // Check duplicate
                    foreach ($users as $u) {
                        if ($u['username'] === $newUsername) {
                            echo json_encode(['success' => false, 'message' => 'ชื่อผู้ใช้นี้มีอยู่ในระบบแล้ว']);
                            exit;
                        }
                    }
                    $user['username'] = $newUsername;
                }
            }

            if (isset($data['password']) && !empty($data['password'])) {
                $user['password'] = password_hash($data['password'], PASSWORD_DEFAULT);
                $user['password_plain'] = $data['password'];
            }

            if (isset($data['role'])) {
                $user['is_seller'] = ($data['role'] === 'seller');
            }

            $found = true;
            break;
        }
    }
    unset($user);

    if (!$found) {
        echo json_encode(['success' => false, 'message' => 'ไม่พบผู้ใช้']);
        exit;
    }

    $db->write('users', $users);
    echo json_encode(['success' => true, 'message' => 'บันทึกข้อมูลเรียบร้อยแล้ว']);
    exit;
}

// Delete User Action
if ($action === 'delete') {
    $data = json_decode(file_get_contents('php://input'), true);
    $userId = $data['id'] ?? 0;

    if (!$userId) {
        echo json_encode(['success' => false, 'message' => 'ไม่พบ ID ผู้ใช้']);
        exit;
    }

    $foundIndex = -1;
    foreach ($users as $index => $user) {
        if ($user['id'] == $userId) {
            // Prevent deleting admin
            if (!empty($user['is_admin'])) {
                echo json_encode(['success' => false, 'message' => 'ไม่สามารถลบผู้ดูแลระบบได้']);
                exit;
            }
            $foundIndex = $index;
            break;
        }
    }

    if ($foundIndex === -1) {
        echo json_encode(['success' => false, 'message' => 'ไม่พบผู้ใช้']);
        exit;
    }

    // Remove user
    array_splice($users, $foundIndex, 1);

    // Also remove related data? (Ideally yes, but for now focus on user record)
    // - Orders should preserve history even if user deleted? Maybe keep them with null user_id?
    // For this simple system, we just remove the user. Orders will still exist linked to that ID.

    $db->write('users', $users);
    echo json_encode(['success' => true, 'message' => 'ลบผู้ใช้เรียบร้อยแล้ว']);
    exit;
}

echo json_encode(['success' => false, 'message' => 'Invalid action']);
?>