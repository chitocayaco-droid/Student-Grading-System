<?php
require_once 'config/database.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = isset($_POST['name']) ? trim($_POST['name']) : '';
    $email = isset($_POST['email']) ? trim($_POST['email']) : '';
    $message = isset($_POST['message']) ? trim($_POST['message']) : '';
    $ip = $_SERVER['REMOTE_ADDR'];
    
    // Validate
    if (empty($name) || empty($email) || empty($message)) {
        echo json_encode(['success' => false, 'message' => 'All fields are required']);
        exit();
    }
    
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo json_encode(['success' => false, 'message' => 'Invalid email format']);
        exit();
    }
    
    try {
        $stmt = $pdo->prepare("INSERT INTO help_requests (name, email, message, ip_address) VALUES (?, ?, ?, ?)");
        $stmt->execute([$name, $email, $message, $ip]);
        
        echo json_encode(['success' => true, 'message' => 'Your request has been saved. An administrator will respond soon.']);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Database error. Please try again.']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
}
?>