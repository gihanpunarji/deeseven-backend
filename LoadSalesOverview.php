<?php

use Firebase\JWT\JWT;

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

include "CORS/CORS.php";
require "connection/connection.php";
require_once "jwt_middleware.php";

header('Content-Type: application/json');

$response = [
    'response' => false,
    'message' => 'Failed to retrieve sales data',
    'total_sales' => 0.0,
    'average_order_value' => 0.0,
    'total_orders' => 0,
    'sales_growth' => 0.0
];

$admin = validateJWT();
if (!$admin) {
    echo json_encode(['response' => false, 'message' => 'Unauthorized']);
    exit;
}

$query = "SELECT COUNT(o.order_id) AS total_orders,
SUM(o.order_amount) AS total_sales,
AVG(o.order_amount) AS average_order_value
FROM `order` o WHERE o.order_status = 0";

$resultset = Database::search($query);
if ($resultset->num_rows > 0) {
    $row = $resultset->fetch_assoc();

    $response['response'] = true;
    $response['message'] = 'Sales data retrieved successfully';
    $response['total_sales'] = $row['total_sales'];
    $response['average_order_value'] = $row['average_order_value'];
    $response['total_orders'] = $row['total_orders'];
    $response['sales_growth'] = 0.0; // Optional or remove if not needed
} else {
    $response['message'] = 'No sales data found';
}

echo json_encode($response);
?>
