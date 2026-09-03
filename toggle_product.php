<?php
session_start();
require 'includes/db.php';

// Security Check: Must be Admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

// Set content type to JSON
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['product_id'])) {
    $product_id = intval($_POST['product_id']);
    
    try {
        // Toggle the is_available status
        $stmt = $pdo->prepare("UPDATE products SET is_available = NOT is_available WHERE id = ?");
        $stmt->execute([$product_id]);
        
        if ($stmt->rowCount() > 0) {
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Product not found or no change made']);
        }
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
    }
} else {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
}
?>