<?php
require_once '../config/app.php';
requireAuth();

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

if (!verifyCSRFToken($_POST['_token'] ?? '')) {
    echo json_encode(['success' => false, 'message' => 'Invalid security token']);
    exit;
}

$type = $_POST['type'] ?? null;
$id = $_POST['id'] ?? null;
$status = $_POST['status'] ?? null;

if (!$type || !$id || !$status) {
    echo json_encode(['success' => false, 'message' => 'Invalid parameters']);
    exit;
}

try {
    $result = false;
    
    switch ($type) {
        case 'order':
            $orderClass = new Order($db);
            $updateResult = $orderClass->updateOrderStatus($id, $status);
            $result = $updateResult['success'];
            break;
            
        case 'category':
            $categoryClass = new Category($db);
            $result = $categoryClass->toggleStatus($id);
            break;
            
        default:
            echo json_encode(['success' => false, 'message' => 'Invalid type']);
            exit;
    }
    
    echo json_encode(['success' => $result]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}