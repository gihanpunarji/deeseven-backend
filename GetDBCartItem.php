<?php

require "connection/connection.php";
include "CORS/CORS.php";

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

header("Content-Type: application/json");

$user_id = $_GET["id"];

$response = ["status" => false, "message" => "No cart item", "data" => []];

$sql = Database::search("SELECT cart.qty, 
           cart.size, 
           product.title, 
           product.price, 
           MIN(product_images.image_url) AS image_url 
    FROM cart
    INNER JOIN product ON cart.product_product_id = product.product_id
    INNER JOIN product_images ON product.product_id = product_images.product_product_id 
    WHERE customer_customer_id = ?
    GROUP BY cart.qty, cart.size, product.title, product.price, product.product_id", 
    [$user_id]
);


$cart_item = [];

if($sql->num_rows > 0) {
    while($row = $sql->fetch_assoc()) {
        $row["image"] = $row["image_url"];
        $row["title"] = $row["title"];
        $row["price"] = $row["price"];
        $row["qty"] = $row["qty"];
        $row["size"] = $row["size"];
        $cart_item[] = $row;
    }
    $response = ["status" => true, "message" => "success", "data" => $cart_item];
}

echo json_encode($response);

