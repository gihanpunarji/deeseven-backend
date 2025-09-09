<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

include "CORS/CORS.php";
require_once "jwt_middleware.php";

header('Content-Type: application/json');

$response = [
    "response" => false,
    "message" => "Logout failed"
];

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception("Invalid request method. Only POST allowed.");
    }

    // Validate JWT token
    $admin = validateJWT();
    if (!$admin) {
        throw new Exception("Invalid or expired token");
    }

    // Get the token from Authorization header
    $headers = getallheaders();
    $authHeader = $headers['Authorization'] ?? '';
    
    if (!$authHeader || !str_starts_with($authHeader, 'Bearer ')) {
        throw new Exception("No token provided");
    }

    $token = substr($authHeader, 7); // Remove 'Bearer ' prefix

    // Optional: Add token to blacklist (if you implement token blacklisting)
    // For now, we'll just validate that logout request came from valid admin
    
    // Log the logout activity (optional)
    $adminEmail = $admin['email'] ?? 'Unknown';
    $logMessage = "Admin logout: " . $adminEmail . " at " . date('Y-m-d H:i:s');
    error_log($logMessage);

    $response = [
        "response" => true,
        "message" => "Logged out successfully",
        "admin_email" => $adminEmail,
        "logout_time" => date('Y-m-d H:i:s')
    ];

} catch (Exception $e) {
    $response = [
        "response" => false,
        "message" => $e->getMessage()
    ];
    
    // Log the error
    error_log("Logout error: " . $e->getMessage());
}

echo json_encode($response);
?>