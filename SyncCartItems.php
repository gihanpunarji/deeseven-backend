<?php

require "connection/connection.php";
require_once "CORS/CORS.php";

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

$data = file_get_contents("php://input");

$cartItems = json_decode($data, true);

if($cartItems == null) {
    echo json_encode(["status" => false, "message" => "No data received"]);
    return;
}

$response = ["status" => false, "message" => "Item already in cart"]; // Default

foreach($cartItems as $item) {
    $product_id = $item["id"];
    $quantity = $item["qty"];
    $size = $item["size"];
    $email = $item["emailID"];

    // Check if item already exists in cart
    $sql = Database::search("SELECT * FROM cart WHERE product_product_id = ? AND size = ?", [$product_id, $size]);

    if($sql->num_rows == 0) {  
        $result = Database::iud("INSERT INTO `cart` (`qty`, `size`, `product_product_id`, `customer_customer_id`)
        VALUES (?, ?, ?, ?)", [$quantity, $size, $product_id, $email]);

        if ($result) {
            $response = ["status" => true, "message" => "Added to the cart"];
        } else {
            $response = ["status" => false, "message" => "Failed to add to the cart"];
        }
    }
}

header("Content-Type: application/json");
echo json_encode($response);
?>