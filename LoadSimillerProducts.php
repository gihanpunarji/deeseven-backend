<?php
include "CORS/CORS.php";

require "connection/connection.php";

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

if (empty($_GET['subCategory'])) {
    return;
} else {

    $sub_category_name = $_GET['subCategory'];
    $product_id = $_GET['id'];
    $sub_category_id;
    
    $response = ["status" => false, "message" => "fetching failed", "data" => null];

    $sub_category_table = Database::search("SELECT `sub_category_id` FROM `sub_category` 
    WHERE `sub_category_name` = '" . $sub_category_name . "' LIMIT 5");

    if ($sub_category_table->num_rows == 1) {
        $row = $sub_category_table->fetch_assoc();
        $sub_category_id = $row['sub_category_id'];
    }


    $table = Database::search("SELECT * FROM `product` WHERE 
    product.sub_category_sub_category_id = $sub_category_id AND product.product_id != $product_id ORDER BY `date_added` DESC");
    $table2 = Database::search("SELECT * FROM `product_images`");

    $product_images = [];

    while ($product_image = $table2->fetch_assoc()) {
        $productImageID = $product_image['product_product_id'];
        if (!isset($product_images[$productImageID])) {
            $product_images[$productImageID] = [];
        }
        $product_images[$productImageID][] = $product_image;
    }

    if ($table->num_rows > 0) {
        $products = [];
        while ($product = $table->fetch_assoc()) {
            $product_id = $product['product_id'];
            $product["images"] = $product_images[$product_id] ?? [];
            $products[] = $product;
        }
        $response = ["status" => true, "message" => "fetching success", "data" => $products];
    }

    header("Content-Type: application/json");
    echo json_encode($response);
}
