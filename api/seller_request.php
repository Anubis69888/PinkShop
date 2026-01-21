<?php
session_start();
require_once '../includes/db.php';

// Disable error display to prevent JSON corruption
error_reporting(E_ALL);
ini_set('display_errors', 0);

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'กรุณาเข้าสู่ระบบ']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

$db = new DB();
$userId = $_SESSION['user_id'];

// Check if already submitted
$existingRequest = $db->find('seller_requests', 'user_id', $userId);
if ($existingRequest && $existingRequest['status'] === 'pending') {
    echo json_encode(['success' => false, 'message' => 'คุณได้ส่งคำขอไปแล้ว กรุณารอการตรวจสอบ']);
    exit;
}

// Upload Directory
$uploadDir = '../assets/uploads/documents/';
if (!file_exists($uploadDir)) {
    mkdir($uploadDir, 0777, true);
}

function uploadFile($fileKey, $prefix, $dir) {
    if (!isset($_FILES[$fileKey]) || $_FILES[$fileKey]['error'] !== UPLOAD_ERR_OK) {
        throw new Exception("กรุณาอัพโหลดไฟล์ " . $fileKey);
    }

    $allowedTypes = ['image/jpeg', 'image/png', 'image/jpg'];
    if (!in_array($_FILES[$fileKey]['type'], $allowedTypes)) {
        throw new Exception("ไฟล์ " . $fileKey . " ต้องเป็นรูปภาพ (JPG, PNG) เท่านั้น");
    }

    $ext = pathinfo($_FILES[$fileKey]['name'], PATHINFO_EXTENSION);
    $filename = $prefix . '_' . uniqid() . '.' . $ext;
    $targetPath = $dir . $filename;

    if (!move_uploaded_file($_FILES[$fileKey]['tmp_name'], $targetPath)) {
        throw new Exception("เกิดข้อผิดพลาดในการอัพโหลดไฟล์ " . $fileKey);
    }

    return 'assets/uploads/documents/' . $filename;
}

try {
    // Validate inputs
    if (empty($_POST['real_name']) || empty($_POST['id_card_number']) || empty($_POST['address']) || 
        empty($_POST['bank_name']) || empty($_POST['bank_account'])) {
        throw new Exception("กรุณากรอกข้อมูลให้ครบถ้วน");
    }

    // Upload Files
    $idFrontPath = uploadFile('id_card_front', 'id_front', $uploadDir);
    $idBackPath = uploadFile('id_card_back', 'id_back', $uploadDir);
    $bankBookPath = uploadFile('bank_book', 'bank_book', $uploadDir);

    // AI Verification Step
    require_once '../includes/AIVerifier.php';
    $aiResult = AIVerifier::verify($_POST['real_name'], $idFrontPath);

    if (!$aiResult['success']) {
        // If verification fails, delete uploaded files to save space (cleanup)
        @unlink($idFrontPath);
        @unlink($idBackPath);
        @unlink($bankBookPath);
        
        echo json_encode(['success' => false, 'message' => $aiResult['message']]);
        exit;
    }

    // Save Data
    $requestData = [
        'user_id' => $userId,
        'real_name' => htmlspecialchars($_POST['real_name']),
        'id_card_number' => htmlspecialchars($_POST['id_card_number']),
        'address' => htmlspecialchars($_POST['address']),
        'bank_name' => htmlspecialchars($_POST['bank_name']),
        'bank_account' => htmlspecialchars($_POST['bank_account']),
        'files' => [
            'id_front' => $idFrontPath,
            'id_back' => $idBackPath,
            'bank_book' => $bankBookPath
        ],
        'status' => 'pending'
    ];

    $db->insert('seller_requests', $requestData);

    // Auto-Approve: Set user as seller immediately
    $db->update('users', $userId, ['is_seller' => true]);
    $_SESSION['is_seller'] = true; // Update session immediately

    echo json_encode(['success' => true, 'message' => 'ส่งคำขอสำเร็จและได้รับการอนุมัติทันที! คุณสามารถลงขายสินค้าได้แล้ว']);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
