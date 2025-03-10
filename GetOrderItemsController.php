<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

include "CORS/CORS.php";
require "connection/connection.php";
require_once "jwt_middleware.php";

header('Content-Type: application/json');

$response = [
    "response" => false,
    "message" => "No items found",
    "items" => []
];

$admin = validateJWT();
if (!$admin) {
    echo json_encode(["response" => false, "message" => "Unauthorized"]);
    exit;
}

if (!isset($_GET['order_id']) || empty($_GET['order_id'])) {
    echo json_encode(["response" => false, "message" => "Invalid order ID"]);
    exit;
}

$order_id = intval($_GET['order_id']);

$result = Database::search("SELECT * FROM `order_item` oi
    INNER JOIN `product` p ON oi.product_product_id = p.product_id
    INNER JOIN `sub_category` s ON p.product_id = s.product_product_id
    INNER JOIN `size` sz ON s.size_type_size_type_id = sz.size_type_size_type_id
    WHERE oi.order_order_id = '$order_id'");

if ($result->num_rows > 0) {
    $items = [];
    while ($row = $result->fetch_assoc()) {
        $items[] = [
            "order_item_id" => $row["order_item_id"],
            "qty" => $row["order_item_qty"],
            "size" => $row["size_name"],
            "product_id" => $row["product_id"],
            "product_title" => $row["product_title"],
            "product_price" => $row["product_price"]
        ];
    }

    $response = [
        "response" => true,
        "message" => "Success",
        "items" => $items
    ];
}

echo json_encode($response);
