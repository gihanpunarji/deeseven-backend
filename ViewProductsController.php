<?php
include "CORS/CORS.php";

require "connection/connection.php";

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

$response = ["status" => false, "message" => "fetching failed", "data" => null];

$table = Database::search("SELECT * FROM `product` INNER JOIN `category` ON product.category_category_id = category.category_id ORDER BY `date_added` DESC LIMIT 12");
$table2 = Database::search("SELECT * FROM `product_images`");

$product_images = [];

while($product_image = $table2->fetch_assoc()){
    $productImageID = $product_image['product_product_id'];
    if(!isset($product_images[$productImageID])) {
        $product_images[$productImageID] = [];
    }
    $product_images[$productImageID][] = $product_image;
}


if($table->num_rows > 0) {
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

?>

