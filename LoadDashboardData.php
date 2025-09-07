<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once "CORS/CORS.php";
require "connection/connection.php";
require_once "jwt_middleware.php";

header('Content-Type: application/json');

$response = [
    "response" => false,
    "message" => "Failed to retrieve data",
    "today_sold" => 0,
    "today_sales" => 0.0,
    "total_products" => 0,
    "active_customers" => 0,
];

$admin = validateJWT();
if (!$admin) {
    echo json_encode(["response" => false, "message" => "Unauthorized"]);
    exit;
}

try {
    // Calculate today's date range
    $todayStart = date('Y-m-d 00:00:00');
    $todayEnd = date('Y-m-d 23:59:59');

    // Fetch today's total items sold
    $todaySoldQuery = "SELECT SUM(oi.order_item_qty) as total_items FROM `order_item` oi 
                      INNER JOIN `order` o ON oi.order_order_id = o.order_id 
                      WHERE o.order_date BETWEEN '$todayStart' AND '$todayEnd'";
    $resultset = Database::search($todaySoldQuery);
    if ($resultset->num_rows > 0) {
        $row = $resultset->fetch_assoc();
        $response["today_sold"] = $row["total_items"];
    }

    // Fetch today's total sales amount
    $todaySalesQuery = "SELECT SUM(o.order_amount) as total_sales FROM `order` o 
                         WHERE o.order_date BETWEEN '$todayStart' AND '$todayEnd'";
    $resultset = Database::search($todaySalesQuery);
    if ($resultset->num_rows > 0) {
        $row = $resultset->fetch_assoc();
        $response["today_sales"] = $row["total_sales"];
    }

    // Fetch total products
    $totalProductsQuery = "SELECT COUNT(*) as total FROM `product`";
    $resultset = Database::search($totalProductsQuery);
    if ($resultset->num_rows > 0) {
        $row = $resultset->fetch_assoc();
        $response["total_products"] = $row["total"];
    }

    // Fetch active customers (assuming active customers are those who made a purchase today)
    $activeCustomersQuery = "SELECT COUNT(DISTINCT c.customer_id) as active_count FROM `customer` c 
                               WHERE c.status='1'";
    $resultset = Database::search($activeCustomersQuery);
    if ($resultset->num_rows > 0) {
        $row = $resultset->fetch_assoc();
        $response["active_customers"] = $row["active_count"];
    }

    // Update the response message
    $response["response"] = true;
    $response["message"] = "Data retrieved successfully";
} catch (Exception $e) {
    $response = [
        "response" => false,
        "message" => "Error executing query: " . $e->getMessage(),
    ];
}

echo json_encode($response);