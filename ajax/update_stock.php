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

$variantId = $_POST['variant_id'] ?? null;
$quantity = $_POST['quantity'] ?? null;

if (!$variantId || $quantity === null || $quantity < 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid parameters']);
    exit;
}

try {
    $productClass = new Product($db);
    $result = $productClass->updateVariantStock($variantId, $quantity);
    
    echo json_encode(['success' => $result]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}