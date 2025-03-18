<?php
include "CORS/CORS.php";
require "connection/connection.php";

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

$response = ["status" => false, "message" => "Fetching failed", "data" => null];

$searchQuery = isset($_GET['search']) ? trim($_GET['search']) : "";
$page = isset($_GET['page']) ? intval($_GET['page']) : 1;
$limit = 10;
$offset = ($page - 1) * $limit;

$sql = "SELECT * FROM `product` 
        INNER JOIN `category` ON product.category_category_id = category.category_id";

if (!empty($searchQuery)) {
    $searchQuery = "%" . $searchQuery . "%";
    $sql .= " WHERE product.title LIKE '" . $searchQuery . "'";
}

$sql .= " ORDER BY `date_added` DESC LIMIT $limit OFFSET $offset";

$product_images = [];
$imageResults = Database::search("SELECT * FROM `product_images`");

while ($product_image = $imageResults->fetch_assoc()) {
    $productImageID = $product_image['product_product_id'];
    if (!isset($product_images[$productImageID])) {
        $product_images[$productImageID] = [];
    }
    $product_images[$productImageID][] = $product_image;
}

// Execute the paginated query
$result = Database::search($sql);

if ($result->num_rows > 0) {
    $products = [];
    while ($product = $result->fetch_assoc()) {
        $product_id = $product['product_id'];
        $product["images"] = $product_images[$product_id] ?? [];
        $products[] = $product;
    }

    $response = ["status" => true, "message" => "Fetching success", "data" => $products];
}

// Return JSON response
header("Content-Type: application/json");
echo json_encode($response);
