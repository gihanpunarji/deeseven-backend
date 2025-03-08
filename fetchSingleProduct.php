<?php
include "CORS/CORS.php";

require "connection/connection.php";

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

$id = $_GET['id'];

$response = ["status" => false, "message" => "fetching failed", "data" => null];

$table = Database::search("SELECT * FROM `product` 
INNER JOIN `category` 
ON product.category_category_id = category.category_id 
INNER JOIN `sub_category` 
ON category.category_id = sub_category.category_category_id 
INNER JOIN `size_type` ON sub_category.size_type_size_type_id = size_type.size_type_id
INNER JOIN `size` ON size_type.size_type_id = size.size_type_size_type_id
WHERE `product_id` = $id");

$table2 = Database::search("SELECT * FROM `product_images` WHERE `product_product_id` = $id");

$product_images = [];

while($product_image = $table2->fetch_assoc()){
    $productImageID = $product_image['product_product_id'];
    if(!isset($product_images[$productImageID])) {
        $product_images[$productImageID] = [];
    }
    $product_images[$productImageID][] = $product_image;
}


if($table->num_rows >0) {
    while($product = $table->fetch_assoc()) {
        $product['images'] = $product_images[$product['product_id']];
        $response = ["status" => true, "message" => "fetching successful", "data" => $product];
    }
} 

header("Content-Type: application/json");
echo json_encode($response);

?>

