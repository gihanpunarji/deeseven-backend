<?php

require_once "CORS/CORS.php";
require_once "vendor/autoload.php";

use Firebase\JWT\JWT;

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

require "connection/connection.php";

if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['token']) && isset($_GET['email'])) {
    $token = $_GET['token'];
    $email = $_GET['email'];
    
    // Verify the token (you should implement proper token validation)
    // This is a simple example - in production, use proper JWT or database token validation
    $expectedToken = hash('sha256', $email . 'admin_secret_key_2024'); // Use your own secret
    
    if (hash_equals($expectedToken, $token) && $email === 'geniousgaming2212@gmail.com') {
        // Get admin data from database
        $stmt = Database::search("SELECT * FROM `admin` WHERE `email` = ?", [$email]);
        
        if ($stmt->num_rows == 1) {
            $admin = $stmt->fetch_assoc();
            
            // Generate proper JWT token
            $secret_key = "12345678901234567890123456789012";
            $payload = [
                "iss" => "localhost",
                "aud" => "localhost", 
                "iat" => time(),
                "exp" => time() + (60 * 60), // 1 hour
                "data" => [
                    "email" => $admin['email'],
                    "role" => "admin"
                ]
            ];
            
            $authToken = JWT::encode($payload, $secret_key, 'HS256');
            
            $adminData = [
                'id' => $admin['admin_id'],
                'email' => $admin['email'],
                'role' => 'admin',
                'name' => 'Admin User'
            ];
            
            // Redirect to frontend with token
            $frontendUrl = 'http://localhost:3000/admin-login-verify';
            $redirectUrl = $frontendUrl . '?token=' . $authToken . '&admin=' . urlencode(json_encode($adminData));
            
            header('Location: ' . $redirectUrl);
            exit();
        } else {
            http_response_code(403);
            echo json_encode(['error' => 'Admin not found in database']);
        }
    } else {
        http_response_code(403);
        echo json_encode(['error' => 'Invalid token or unauthorized access']);
    }
} else {
    http_response_code(400);
    echo json_encode(['error' => 'Missing required parameters']);
}
?>