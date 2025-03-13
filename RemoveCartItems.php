<?php

require "connection/connection.php";
include "CORS/CORS.php";

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Read the JSON request body
$data = file_get_contents("php://input");
$cartItem = json_decode($data, true);

if ($cartItem == null) {
    echo json_encode(["status" => false, "message" => "No data received"]);
    return;
}

// Extract cart details
$product_id = $cartItem["product_id"];
$size = $cartItem["size"];
$user_id = $cartItem["user_id"];

// Retrieve updated cart items
$delete_query = Database::iud(
    "DELETE FROM cart WHERE product_product_id = ? AND size = ? AND customer_customer_id = ?", 
    [$product_id, $size, $user_id]
);

$updated_cart_query = Database::search(
    "SELECT * FROM cart WHERE customer_customer_id = ?", 
    [$user_id]
);

$cart_items = [];
while ($row = $updated_cart_query->fetch_assoc()) {
    $cart_items[] = $row;
}

// Send updated cart back to frontend
echo json_encode(["status" => true, "message" => "Cart updated", "cart" => $cart_items]);

?>
