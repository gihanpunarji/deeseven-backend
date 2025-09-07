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
    "orders_over_time" => [],
    "customer_growth" => [],
    "date_range" => "",
];

try {
    $endDate = new DateTime();
    $startDate = clone $endDate;
    $startDate->modify("-12 month");

    $response["date_range"] = $startDate->format('Y-m-d') . " to " . $endDate->format('Y-m-d');

    // Generate an array of all months in the past 12 months
    $months = [];
    for ($i = 11; $i >= 0; $i--) {
        $date = clone $endDate;
        $date->modify("-$i month");
        $months[] = $date->format('Y-m');
    }

    // Fetch orders over time
    $ordersQuery = "
        SELECT DATE_FORMAT(order_date, '%Y-%m') AS month, COUNT(*) AS total_orders
        FROM `order`
        WHERE order_date BETWEEN '{$startDate->format('Y-m-d')}' AND '{$endDate->format('Y-m-d')}'
        GROUP BY DATE_FORMAT(order_date, '%Y-%m')
        ORDER BY order_date ASC
    ";
    $resultset = Database::search($ordersQuery);
    $ordersData = [];
    while ($row = $resultset->fetch_assoc()) {
        $ordersData[$row["month"]] = (int)$row["total_orders"];
    }

    // Fetch customer growth trend
    $customerQuery = "
        SELECT DATE_FORMAT(registered_date, '%Y-%m') AS month, COUNT(*) AS total_customers
        FROM `customer`
        WHERE registered_date BETWEEN '{$startDate->format('Y-m-d')}' AND '{$endDate->format('Y-m-d')}'
        GROUP BY DATE_FORMAT(registered_date, '%Y-%m')
        ORDER BY registered_date ASC
    ";
    $resultset = Database::search($customerQuery);
    $customerData = [];
    while ($row = $resultset->fetch_assoc()) {
        $customerData[$row["month"]] = (int)$row["total_customers"];
    }

    // Prepare the final data structure
    foreach ($months as $month) {
        $currentOrders = isset($ordersData[$month]) ? $ordersData[$month] : 0;
        $currentCustomers = isset($customerData[$month]) ? $customerData[$month] : 0;

        $response["orders_over_time"][] = [
            "month" => $month,
            "total_orders" => $currentOrders,
        ];
        $response["customer_growth"][] = [
            "month" => $month,
            "total_customers" => $currentCustomers,
        ];
    }

    $response["response"] = true;
    $response["message"] = "Data retrieved successfully";
} catch (Exception $e) {
    $response = [
        "response" => false,
        "message" => "Error executing query: " . $e->getMessage(),
    ];
}

echo json_encode($response);