<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once "CORS/CORS.php";
require "connection/connection.php";
require_once "jwt_middleware.php";

header('Content-Type: application/json');

$response = [
    "response" => false,
    "message" => "Failed to add sub category",
];

try {
    // Validate JWT token
    $admin = validateJWT();
    if (!$admin) {
        echo json_encode(["response" => false, "message" => "Unauthorized"]);
        exit;
    }

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception("Invalid request method. Only POST allowed.");
    }

    // Read raw POST data
    $rawData = file_get_contents('php://input');
    if (!$rawData) {
        throw new Exception("No data provided.");
    }

    // Decode JSON data
    $data = json_decode($rawData, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new Exception("Invalid JSON data.");
    }

    // Extract subcategory name
    $sub_category_name = $data['sub_category_name'] ?? null;

    if (!$sub_category_name) {
        throw new Exception("Missing required fields.");
    }

    $size_type_id = $data['size_type_id'] ?? null;
    
    if (!$size_type_id) {
        throw new Exception("Missing required fields.");
    }

    // Prepare and execute the SQL query
    $query = "INSERT INTO sub_category (sub_category_name,size_type_size_type_id) VALUES (?,?)";
    $params = [$sub_category_name,$size_type_id];

    if (!Database::iud($query, $params)) {
        throw new Exception("Failed to add new sub category.");
    }

    // Success response
    $response = [
        "response" => true,
        "message" => "Sub Category added successfully",
    ];
} catch (Exception $e) {
    $response = [
        "response" => false,
        "message" => $e->getMessage(),
    ];
    error_log("Error: " . $e->getMessage());
}

echo json_encode($response);