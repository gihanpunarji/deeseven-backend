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
$quantity = $cartItem["qty"];
$size = $cartItem["size"];
$user_id = $cartItem["user_id"];

// Check if the item is already in the cart
$cart_query = Database::search(
    "SELECT * FROM cart WHERE product_product_id = ? AND size = ? AND customer_customer_id = ?", 
    [$product_id, $size, $user_id]
);

if ($cart_query->num_rows > 0) {
    // Item exists, update the quantity
    $existing_cart = $cart_query->fetch_assoc();
    $new_qty = $existing_cart["qty"] + $quantity;

    Database::iud(
        "UPDATE cart SET qty = ? WHERE product_product_id = ? AND size = ? AND customer_customer_id = ?", 
        [$new_qty, $product_id, $size, $user_id]
    );
} else {
    // Item does not exist, insert a new row
    Database::iud(
        "INSERT INTO cart (qty, size, product_product_id, customer_customer_id) VALUES (?, ?, ?, ?)", 
        [$quantity, $size, $product_id, $user_id]
    );
}

// Retrieve updated cart items
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
