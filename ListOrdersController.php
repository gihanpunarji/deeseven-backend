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
    echo json_encode(["response" => false, "message" => "Unauthorized"]);
    exit;
}


$result = Database::search("SELECT 
        o.order_id, 
        o.order_number, 
        o.tracking_number, 
        c.fname, 
        c.lname, 
        o.order_status, 
        o.order_amount, 
        o.order_date
    FROM `order` o
    INNER JOIN `customer` c ON o.customer_customer_id = c.customer_id
    ORDER BY o.order_date DESC");

if ($result->num_rows > 0) {
    $orders = [];
    $statusText = ["Paid", "Processing", "Shipped", "Delivered"];

    while ($row = $result->fetch_assoc()) {
        $status = isset($statusText[$row["order_status"]]) ? $statusText[$row["order_status"]] : "Unknown";
        $orders[] = [
            "id" => $row["order_id"],
            "number" => $row["order_number"],
            "customer" => $row["fname"] . " " . $row["lname"],
            "status" => $status,
            "tracking" => $row["tracking_number"],
            "total" => $row["order_amount"],
            "date" => $row["order_date"]
        ];
    }

    $response = [
        "response" => true,
        "message" => "Success",
        "orders" => $orders
    ];
}

echo json_encode($response);
?>