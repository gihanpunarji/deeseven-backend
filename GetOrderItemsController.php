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

$query = "
    SELECT 
     order_item_id,
     order_item_size,
     order_item_qty,
     product_id,
     title,
     price
    FROM order_item oi
    INNER JOIN product p ON oi.product_product_id = p.product_id
    WHERE oi.order_order_id = ?";

try {
    $resultset = Database::search($query, [$order_id]);

    if ($resultset->num_rows > 0) {
        $items = [];
        while ($row = $resultset->fetch_assoc()) {
            $items[] = [
                "order_item_id" => $row["order_item_id"],
                "qty" => $row["order_item_qty"],
                "size" => $row["order_item_size"],
                "product_id" => $row["product_id"],
                "product_title" => $row["title"],
                "product_price" => $row["price"]
            ];
        }

        $response = [
            "response" => true,
            "message" => "Success",
            "items" => $items
        ];
    } else {
        $response = [
            "response" => false,
            "message" => "No items found",
            "items" => []
        ];
    }
} catch (Exception $e) {
    $response = [
        "response" => false,
        "message" => "Error executing query: " . $e->getMessage(),
        "items" => []
    ];
}

echo json_encode($response);
?>