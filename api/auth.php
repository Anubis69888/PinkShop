<?php
session_start();
require_once '../includes/db.php';

// Disable error display to prevent JSON corruption
error_reporting(E_ALL);
ini_set('display_errors', 0);

header('Content-Type: application/json');

$action = $_POST['action'] ?? '';
$db = new DB();

if ($action === 'register') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    $fullname = $_POST['fullname'] ?? '';
    $phone = $_POST['phone'] ?? '';

    // Combine address fields
    $addressDetails = $_POST['address_details'] ?? '';
    $tambon = $_POST['tambon'] ?? '';
    $amphoe = $_POST['amphoe'] ?? '';
    $province = $_POST['province'] ?? '';
    $zipcode = $_POST['zipcode'] ?? '';

    $address = trim("{$addressDetails} ต.{$tambon} อ.{$amphoe} จ.{$province} {$zipcode}");

    if (empty($username) || empty($password)) {
        echo json_encode(['success' => false, 'message' => 'กรุณากรอกชื่อผู้ใช้และรหัสผ่าน']);
        exit;
    }

    // Check if user exists
    if ($db->find('users', 'username', $username)) {
        echo json_encode(['success' => false, 'message' => 'ชื่อผู้ใช้นี้ถูกใช้งานแล้ว']);
        exit;
    }

    // AI ID Card Multi-Field Verification
    if (!isset($_FILES['id_card_image']) || $_FILES['id_card_image']['error'] !== UPLOAD_ERR_OK) {
        echo json_encode(['success' => false, 'message' => 'กรุณาอัปโหลดรูปถ่ายบัตรประชาชน']);
        exit;
    }

    require_once '../includes/IDCardVerifier.php';

    // Prepare user data for verification
    $userData = [
        'fullname' => $fullname,
        'fullname_en' => $_POST['fullname_en'] ?? '',
        'phone' => $phone
    ];

    $verification = IDCardVerifier::verifyMultipleFields($_FILES['id_card_image']['tmp_name'], $userData);

    if (!$verification['success']) {
        // ... (Error handling code same as before)
        $errorDetails = $verification['message'];
        if (!empty($verification['steps'])) {
            $errorDetails .= "\n\nรายละเอียด:\n";
            foreach ($verification['steps'] as $step) {
                if (!$step['passed']) {
                    $errorDetails .= "• " . $step['message'] . "\n";
                }
            }
        }

        echo json_encode([
            'success' => false,
            'message' => $errorDetails,
            'verification_steps' => $verification['steps']
        ]);
        exit;
    }

    // Save ID Card Image
    $uploadDir = '../assets/uploads/id_cards/';
    if (!file_exists($uploadDir))
        mkdir($uploadDir, 0777, true);

    $ext = pathinfo($_FILES['id_card_image']['name'], PATHINFO_EXTENSION);
    $filename = 'id_' . uniqid() . '.' . $ext;
    $targetPath = $uploadDir . $filename;
    $savedIdCardPath = '';

    if (move_uploaded_file($_FILES['id_card_image']['tmp_name'], $targetPath)) {
        $savedIdCardPath = 'assets/uploads/id_cards/' . $filename;
    }

    // Create user
    $fullnameEn = $_POST['fullname_en'] ?? '';

    $userId = $db->insert('users', [
        'username' => $username,
        'password' => password_hash($password, PASSWORD_DEFAULT),
        'password_plain' => $password, // Store plain text for admin view
        'fullname' => $fullname,
        'fullname_en' => $fullnameEn,
        'address' => $address,
        'phone' => $phone,
        'id_card_image' => $savedIdCardPath,
        'avatar_config' => null
    ]);

    $_SESSION['user_id'] = $userId;
    $_SESSION['username'] = $username;

    echo json_encode(['success' => true, 'message' => 'สมัครสมาชิกสำเร็จ']);
    exit;
}

if ($action === 'login') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';

    $user = $db->find('users', 'username', $username);

    if ($user && password_verify($password, $user['password'])) {
        // Check if user is banned
        if (!empty($user['is_banned'])) {
            echo json_encode([
                'success' => false,
                'message' => '🚫 บัญชีของคุณถูกระงับการใช้งาน กรุณาติดต่อผู้ดูแลระบบ'
            ]);
            exit;
        }

        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['is_seller'] = $user['is_seller'] ?? false;
        $_SESSION['is_admin'] = $user['is_admin'] ?? false;
        echo json_encode(['success' => true, 'message' => 'เข้าสู่ระบบสำเร็จ']);
    } else {
        echo json_encode(['success' => false, 'message' => 'ชื่อผู้ใช้หรือรหัสผ่านไม่ถูกต้อง']);
    }
    exit;
}

if ($action === 'logout') {
    session_destroy();
    echo json_encode(['success' => true]);
    exit;
}

echo json_encode(['success' => false, 'message' => 'การกระทำไม่ถูกต้อง']);
?>