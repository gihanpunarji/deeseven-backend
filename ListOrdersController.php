<?php

session_start();

include "CORS/CORS.php";


ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require "connection/connection.php";

$response = ["response" => false, "message" => "No orders found", "orders" => []];

// Check if admin is logged in
if (!isset($_SESSION["admin"])) {

    $response["message"] = "Unauthorized access";
    echo json_encode($response);
    exit;
}

// Fetch orders from the database


$result = Database::search("SELECT 
        o.order_id, 
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
    
    while ($row = $result->fetch_assoc()) {
        $statusText = ["Paid", "Processing", "Shipped", "Delivered"]; // Mapping status numbers to text

        $orders[] = [
            "id" => $row["order_id"],
            "customer" => $row["fname"] . " " . $row["lname"],
            "status" => $statusText[$row["order_status"]],
            "total" => $row["order_amount"],
            "date" => $row["order_date"]
        ];
    }

    $response = ["response" => true, "message" => "Success", "orders" => $orders];
}

header('Content-Type: application/json');
echo json_encode($response);

?>
