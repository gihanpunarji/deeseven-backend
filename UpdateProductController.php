<?php
// Enable error reporting for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once "CORS/CORS.php";
require "connection/connection.php";
require_once "jwt_middleware.php";

header('Content-Type: application/json');

$response = [
    "response" => false,
    "message" => "Failed to update product",
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

    $productId = $data['productId'] ?? null;
    if (!$productId) {
        throw new Exception("Missing product ID.");
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

    // Check if product exists
    $productExists = Database::search("SELECT product_id FROM product WHERE product_id = ?", [$productId]);
    if ($productExists->num_rows === 0) {
        throw new Exception("Product not found.");
    }

    // Update product basic information
    $updateProductQuery = "UPDATE product SET title = ?, description = ?, category_category_id = ?, 
                          sub_category_sub_category_id = ?, price = ? WHERE product_id = ?";
    $updateProductParams = [$title, $description, $category_id, $sub_category_id, $price, $productId];

    if (!Database::iud($updateProductQuery, $updateProductParams)) {
        throw new Exception("Failed to update product information.");
    }

    // Function to handle deleting and reinserting related data
    function updateRelatedData($table, $column, $values, $productId) {
        // Delete existing data
        Database::iud("DELETE FROM $table WHERE product_product_id = ?", [$productId]);
        
        // Insert new data
        foreach ($values as $value) {
            Database::iud("INSERT INTO $table (product_product_id, $column) VALUES (?, ?)", [$productId, $value]);
        }
    }

    // Update related data
    updateRelatedData("fabric", "about", $fabric_details, $productId);
    updateRelatedData("note", "note", $notes, $productId);
    updateRelatedData("fabric_care", "fabric_care", $fabric_care, $productId);
    updateRelatedData("add_on_features", "features", $add_on_features, $productId);

    // Update sizes
    Database::iud("DELETE FROM product_size WHERE product_product_id = ?", [$productId]);
    foreach ($sizes as $size) {
        Database::iud("INSERT INTO product_size (product_product_id, size_size_id, quantity) VALUES (?, ?, ?)", 
                      [$productId, $size['size_id'], $size['quantity']]);
    }

    // Handle image uploads if new images are provided
    $uploadDir = "uploads/";
    if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);
    $imageUrls = [];
    $hasNewImages = false;

    for ($i = 0; $i < 3; $i++) {
        if (isset($_FILES["image_$i"]) && $_FILES["image_$i"]['error'] === UPLOAD_ERR_OK) {
            $hasNewImages = true;
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
    }

    // Update images if new ones were uploaded
    if ($hasNewImages) {
        // Get existing images to delete them
        $existingImages = Database::search("SELECT image_url FROM product_images WHERE product_product_id = ?", [$productId]);
        while ($row = $existingImages->fetch_assoc()) {
            $imagePath = $row['image_url'];
            if (file_exists($imagePath)) {
                unlink($imagePath); // Delete the physical file
            }
        }

        // Delete existing image records
        Database::iud("DELETE FROM product_images WHERE product_product_id = ?", [$productId]);

        // Insert new image records
        foreach ($imageUrls as $imageUrl) {
            Database::iud("INSERT INTO product_images (product_product_id, image_url) VALUES (?, ?)", [$productId, $imageUrl]);
        }
    }

    $response = [
        "response" => true,
        "message" => "Product updated successfully",
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