<?php
session_start();
require_once '../includes/db.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

$shopName = trim($_POST['shop_name'] ?? '');
$shopDesc = trim($_POST['shop_description'] ?? '');
$shopContact = [
    'line' => trim($_POST['line_id'] ?? ''),
    'facebook' => trim($_POST['facebook'] ?? ''),
    'phone' => trim($_POST['phone'] ?? '')
];

if (empty($shopName)) {
    echo json_encode(['success' => false, 'message' => 'Shop name is required']);
    exit;
}

// Handle Cover Image Upload
$shopCoverPath = null;
if (isset($_FILES['shop_cover']) && $_FILES['shop_cover']['error'] === UPLOAD_ERR_OK) {
    $uploadDir = '../assets/uploads/shop_covers/';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0777, true);
    }
    
    $fileExt = strtolower(pathinfo($_FILES['shop_cover']['name'], PATHINFO_EXTENSION));
    $allowed = ['jpg', 'jpeg', 'png', 'gif'];
    
    if (in_array($fileExt, $allowed)) {
        $fileName = 'cover_' . $_SESSION['user_id'] . '_' . time() . '.' . $fileExt;
        $destPath = $uploadDir . $fileName;
        
        if (move_uploaded_file($_FILES['shop_cover']['tmp_name'], $destPath)) {
            $shopCoverPath = 'assets/uploads/shop_covers/' . $fileName;
        }
    }
}

$updateData = [
    'shop_name' => $shopName,
    'shop_description' => $shopDesc,
    'shop_contact' => $shopContact
];

if ($shopCoverPath) {
    $updateData['shop_cover'] = $shopCoverPath;
}

$db = new DB();
$success = $db->update('users', $_SESSION['user_id'], $updateData);

if ($success) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to update database']);
}
?>
