<?php
session_start();
require_once '../includes/db.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$action = $input['action'] ?? '';

if ($action === 'save') {
    $config = $input['config'] ?? null;
    if (!$config) {
        echo json_encode(['success' => false, 'message' => 'ไม่พบข้อมูลการตั้งค่า']);
        exit;
    }

    $db = new DB();
    $success = $db->update('users', $_SESSION['user_id'], ['avatar_config' => $config]);

    if ($success) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'message' => 'บันทึกไม่สำเร็จ']);
    }
    exit;
}
?>
