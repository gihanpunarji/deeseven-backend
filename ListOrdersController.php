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
    "message" => "No orders found",
    "orders" => []
];

$admin = validateJWT();
if (!$admin) {
    $response = [
        "response" => false,
        "message" => "Unauthorized"
    ];
    echo json_encode($response);
    exit;
}

// Fetch orders along with order items from the database
$result = Database::search("SELECT 
        o.order_id, 
        o.order_number, 
        o.tracking_number, 
        c.fname, 
        c.lname, 
        o.order_status, 
        o.order_amount, 
        o.order_date,
        oi.order_item_id,
        oi.order_item_qty,
        p.product_id,
        p.title AS product_title,
        p.price AS product_price
    FROM `order` o
    INNER JOIN `customer` c ON o.customer_customer_id = c.customer_id
    LEFT JOIN `order_item` oi ON o.order_id = oi.order_order_id
    LEFT JOIN `product` p ON oi.product_product_id = p.product_id
    ORDER BY o.order_date DESC");

if ($result->num_rows > 0) {
    $orders = [];
    $statusText = ["Paid", "Processing", "Shipped", "Delivered"];

    // Fetching all rows and grouping items per order
    while ($row = $result->fetch_assoc()) {
        $status = isset($statusText[$row["order_status"]]) ? $statusText[$row["order_status"]] : "Unknown";

        // Check if order already exists in the orders array
        $orderIndex = array_search($row['order_id'], array_column($orders, 'id'));

        if ($orderIndex === false) {
            // Create a new order entry
            $orders[] = [
                "id" => $row["order_id"],
                "number" => $row["order_number"],
                "customer" => $row["fname"] . " " . $row["lname"],
                "status" => $status,
                "tracking" => $row["tracking_number"],
                "total" => $row["order_amount"],
                "date" => $row["order_date"],
                "items" => [] // Empty array to store items
            ];
            $orderIndex = count($orders) - 1;
        }

        // Add order item to the corresponding order
        $orders[$orderIndex]['items'][] = [
            "order_item_id" => $row["order_item_id"],
            "qty" => $row["order_item_qty"],
            "product_id" => $row["product_id"],
            "product_title" => $row["product_title"],
            "product_price" => $row["product_price"]
        ];
    }

    $response = [
        "response" => true,
        "message" => "Success",
        "orders" => $orders
    ];
}

header('Content-Type: application/json');
echo json_encode($response);
?>
