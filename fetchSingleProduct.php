<?php
include "CORS/CORS.php";
require "connection/connection.php";

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

$id = $_GET['id'];

$response = ["status" => false, "message" => "fetching failed", "data" => null];

// Fetch product details
$productQuery = Database::search("SELECT * FROM `product` 
    INNER JOIN `category` ON product.category_category_id = category.category_id 
    INNER JOIN `sub_category` ON category.category_id = sub_category.category_category_id 
    INNER JOIN `size_type` ON sub_category.size_type_size_type_id = size_type.size_type_id 
    INNER JOIN `size` ON size_type.size_type_id = size.size_type_size_type_id 
    INNER JOIN `note` ON product.product_id = note.product_product_id
    WHERE `product_id` = $id");

if ($productQuery->num_rows > 0) {
    $product = $productQuery->fetch_assoc();

    // Fetch product images
    $imagesQuery = Database::search("SELECT * FROM `product_images` WHERE `product_product_id` = $id");
    $product['images'] = [];
    while ($image = $imagesQuery->fetch_assoc()) {
        $product['images'][] = $image;
    }

    // Fetch add_on_features
    $featuresQuery = Database::search("SELECT * FROM `add_on_features` WHERE `product_product_id` = $id");
    $product['add_on_features'] = [];
    while ($feature = $featuresQuery->fetch_assoc()) {
        $product['add_on_features'][] = $feature;
    }

    // Fetch fabric details
    $fabricQuery = Database::search("SELECT * FROM `fabric` WHERE `product_product_id` = $id");
    $product['fabric'] = [];
    while ($fabric = $fabricQuery->fetch_assoc()) {
        $product['fabric'][] = $fabric;
    }

    // Fetch fabric care instructions
    $fabricCareQuery = Database::search("SELECT * FROM `fabric_care` WHERE `product_product_id` = $id");
    $product['fabric_care'] = [];
    while ($care = $fabricCareQuery->fetch_assoc()) {
        $product['fabric_care'][] = $care;
    }

    $response = [
        "status" => true,
        "message" => "fetching successful",
        "data" => $product
    ];
}

header("Content-Type: application/json");
echo json_encode($response);
