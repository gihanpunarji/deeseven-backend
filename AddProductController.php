<?php
// Enable error reporting for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

include "CORS/CORS.php";
require "connection/connection.php";
require_once "jwt_middleware.php";

header('Content-Type: application/json');

$response = [
    "response" => false,
    "message" => "Failed to add product",
];

$admin = validateJWT();
if (!$admin) {
    echo json_encode(["response" => false, "message" => "Unauthorized"]);
    exit;
}

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception("Invalid request method. Only POST allowed.");
    }

    $productDataJson = $_POST['productData'] ?? null;
    if (!$productDataJson) {
        throw new Exception("Missing product data.");
    }

    $data = json_decode($productDataJson, true);
    if (!$data) {
        throw new Exception("Invalid JSON data.");
    }

    $title = $data['title'] ?? null;
    $description = $data['description'] ?? null;
    $category_id = $data['category_id'] ?? null;
    $sub_category_id = $data['sub_category_id'] ?? null;
    $price = isset($data['price']) ? (double) $data['price'] : null;
    $fabric_details = $data['fabric_details'] ?? [];
    $notes = $data['notes'] ?? [];
    $fabric_care = $data['fabric_care'] ?? [];
    $add_on_features = $data['add_on_features'] ?? [];
    $sizes = $data['sizes'] ?? [];

    if (!$title || !$description || !$category_id || !$sub_category_id || $price === null || 
        empty($fabric_details) || empty($notes) || empty($fabric_care) || empty($add_on_features) || empty($sizes)) {
        throw new Exception("Missing required fields.");
    }

    $date_added = date('Y-m-d');
    $insertProductQuery = "INSERT INTO product (title, description, category_category_id, sub_category_sub_category_id, price, date_added, product_status) 
                           VALUES (?, ?, ?, ?, ?, ?, ?)";
    $insertProductParams = [$title, $description, $category_id, $sub_category_id, $price, $date_added, 1];

    if (!Database::iud($insertProductQuery, $insertProductParams)) {
        throw new Exception("Failed to insert product.");
    }

    $productId = Database::$connection->insert_id;

    function insertData($table, $column, $values, $productId) {
        foreach ($values as $value) {
            Database::iud("INSERT INTO $table (product_product_id, $column) VALUES (?, ?)", [$productId, $value]);
        }
    }

    insertData("fabric", "about", $fabric_details, $productId);
    insertData("note", "note", $notes, $productId);
    insertData("fabric_care", "fabric_care", $fabric_care, $productId);
    insertData("add_on_features", "features", $add_on_features, $productId);

    foreach ($sizes as $size) {
        Database::iud("INSERT INTO product_size (product_product_id, size_size_id, quantity) VALUES (?, ?, ?)", 
                      [$productId, $size['size_id'], $size['quantity']]);
    }

    $uploadDir = "uploads/";
    if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);
    $imageUrls = [];

    for ($i = 0; $i < 3; $i++) {
        if (!isset($_FILES["image_$i"]) || $_FILES["image_$i"]['error'] !== UPLOAD_ERR_OK) continue;
        
        $file = $_FILES["image_$i"];
        $fileExt = pathinfo($file['name'], PATHINFO_EXTENSION);
        $allowedTypes = ['jpg', 'jpeg', 'png', 'webp'];

        if (!in_array(strtolower($fileExt), $allowedTypes)) {
            throw new Exception("Invalid file type for image_$i.");
        }

        $fileName = uniqid("product_", true) . ".$fileExt";
        $uploadPath = $uploadDir . $fileName;

        if (!move_uploaded_file($file['tmp_name'], $uploadPath)) {
            throw new Exception("Failed to upload image_$i.");
        }

        $imageUrls[] = $uploadPath;
    }

    foreach ($imageUrls as $imageUrl) {
        Database::iud("INSERT INTO product_images (product_product_id, image_url) VALUES (?, ?)", [$productId, $imageUrl]);
    }

    $response = [
        "response" => true,
        "message" => "Product added successfully",
        "product_id" => $productId
    ];
} catch (Exception $e) {
    $response = [
        "response" => false,
        "message" => $e->getMessage(),
    ];
    error_log("Error: " . $e->getMessage());
}

echo json_encode($response);
?>
