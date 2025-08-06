<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

header('Content-Type: application/json');
include "CORS/CORS.php";
require "connection/connection.php"; // Your DB class
require_once "jwt_middleware.php";

// Optional: Uncomment if token protection is needed
// $admin = validateJWT();
// if (!$admin) {
//     echo json_encode(["response" => false, "message" => "Unauthorized"]);
//     exit;
// }

$response = [
    "response" => false,
    "message" => "Failed to fetch report",
    "sales_data" => [],
    "inventory_data" => []
];

// --- 1. Get Filter ---
$filter = $_GET['filter'] ?? 'month';
$today = date('Y-m-d');
$startDate = match ($filter) {
    'today' => $today,
    'week'  => date('Y-m-d', strtotime('-7 days')),
    'month' => date('Y-m-01'),
    'year'  => date('Y-01-01'),
    default => date('Y-m-01'),
};

// Format type
$groupByFormat = match ($filter) {
    'today' => '%Y-%m-%d',
    'week', 'month' => '%Y-%m',
    'year' => '%Y-%m',
    default => '%Y-%m',
};

// --- 2. SALES DATA ---

$salesQuery = "
    SELECT 
        DATE_FORMAT(order_date, '$groupByFormat') AS period,
        SUM(order_amount) AS revenue,
        SUM(order_amount * 0.7) AS expenses, -- Assuming expenses = 70% of revenue
        SUM(order_amount * 0.3) AS profit     -- Assuming profit = 30% of revenue
    FROM `order`
    WHERE DATE(order_date) BETWEEN '$startDate' AND '$today'
    GROUP BY period
    ORDER BY period ASC
";

$salesResult = Database::search($salesQuery);
while ($row = $salesResult->fetch_assoc()) {
    $response['sales_data'][] = [
        "date" => $row['period'],
        "revenue" => (float)$row['revenue'],
        "expenses" => (float)$row['expenses'],
        "profit" => (float)$row['profit']
    ];
}

// --- 3. INVENTORY DATA ---

$inventoryQuery = "
    SELECT 
        DATE_FORMAT(o.order_date, '$groupByFormat') AS period,
        COUNT(oi.order_item_id) AS sold,
        SUM(ps.quantity) AS stock,
        SUM(ps.quantity) - COUNT(oi.order_item_id) AS remaining
    FROM product_size ps
    LEFT JOIN product p ON ps.product_product_id = p.product_id
    LEFT JOIN order_item oi ON ps.product_product_id = oi.product_product_id
    LEFT JOIN `order` o ON oi.order_order_id = o.order_id
        AND DATE(o.order_date) BETWEEN '$startDate' AND '$today'
    WHERE p.product_status = 1
    GROUP BY period
    ORDER BY period ASC
";

$inventoryResult = Database::search($inventoryQuery);
while ($row = $inventoryResult->fetch_assoc()) {
    $response['inventory_data'][] = [
        "date" => $row['period'],
        "stock" => (int)$row['stock'] ?? 0,
        "sold" => (int)$row['sold'] ?? 0,
        "remaining" => (int)$row['remaining'] ?? 0
    ];
}

$response["response"] = true;
$response["message"] = "Report fetched successfully";
echo json_encode($response);
