<?php

require "connection/connection.php";
require_once "CORS/CORS.php";

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

$data = file_get_contents("php://input");
$request = json_decode($data, true);

if (!isset($request['user_id'])) {
    echo json_encode(["status" => false, "message" => "User ID is required"]);
    return;
}

$user_id = $request['user_id'];

// Fetch cart items for the user
$cartQuery = Database::search("SELECT cart.*, product.title, product.price 
                               FROM cart 
                               INNER JOIN product ON cart.product_product_id = product.product_id
                               WHERE cart.customer_customer_id = ?", [$user_id]);

$cartItems = [];

while ($cartItem = $cartQuery->fetch_assoc()) {
    $product_id = $cartItem['product_product_id'];

    // Fetch the first image for the product
    $imageQuery = Database::search("SELECT image_url FROM product_images WHERE product_product_id = ? LIMIT 1", [$product_id]);
    $image = $imageQuery->fetch_assoc();

    $cartItems[] = [
        "id" => $cartItem['cart_id'],
        "product_id" => $cartItem['product_product_id'],
        "title" => $cartItem['title'],
        "price" => $cartItem['price'],
        "qty" => $cartItem['qty'],
        "size" => $cartItem['size'],
        "image" => $image ? $image['image_url'] : null // Assign the first image or null if not found
    ];
}

header("Content-Type: application/json");
echo json_encode(["status" => true, "cart" => $cartItems]);
