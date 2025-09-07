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
    "message" => "Failed to retrieve sales data",
    "sales_trend" => [],
    "top_products" => [],
    "category_sales" => [],
    "total_sales" => 0.0,
    "total_orders" => 0,
    "total_products" => 0,
    "total_customers" => 0,
    "sales_change_percent" => 0,
    "orders_change_percent" => 0,
    "products_change_percent" => 0,
    "customers_change_percent" => 0
];

// Validate JWT token
// $admin = validateJWT();
// if (!$admin) {
//     echo json_encode(["response" => false, "message" => "Unauthorized"]);
//     exit;
// }

try {
    $currentMonthStart = date('Y-m-01');
    $today = date('Y-m-d');
    $previousMonthStart = date('Y-m-01', strtotime('first day of last month'));
    $previousMonthEnd = date('Y-m-t', strtotime('last month'));
    $last365Days = date('Y-m-d', strtotime('-1 year'));
    $last7Days = date('Y-m-d', strtotime('-7 days'));

    // 1. SALES TREND - Last 365 Days
    $salesTrendQuery = "SELECT DATE(order_date) as date, 
        SUM(order_amount) as daily_sales,
        COUNT(order_id) as daily_orders
        FROM `order`
        WHERE order_date BETWEEN '$last365Days' AND '$today'
        GROUP BY DATE(order_date)
        ORDER BY date ASC";

    $resultset = Database::search($salesTrendQuery);
    while ($row = $resultset->fetch_assoc()) {
        $response["sales_trend"][] = [
            "date" => $row["date"],
            "sales" => $row["daily_sales"],
            "orders" => $row["daily_orders"]
        ];
    }

    // 2. TOP PRODUCTS
    $topProductsQuery = "SELECT p.product_id, p.title, 
        SUM(oi.order_item_qty) as total_sold,
        SUM(oi.order_item_qty * p.price) as total_revenue
        FROM `product` p
        INNER JOIN `order_item` oi ON p.product_id = oi.product_product_id
        INNER JOIN `order` o ON oi.order_order_id = o.order_id
        WHERE o.order_date BETWEEN '$currentMonthStart' AND '$today'
        GROUP BY p.product_id
        ORDER BY total_sold DESC
        LIMIT 5";

    $resultset = Database::search($topProductsQuery);
    while ($row = $resultset->fetch_assoc()) {
        $response["top_products"][] = [
            "product_id" => $row["product_id"],
            "product_title" => $row["title"],
            "total_sold" => $row["total_sold"],
            "total_revenue" => $row["total_revenue"]
        ];
    }

    // 3. CATEGORY SALES
    $categorySalesQuery = "SELECT c.category_id, c.category_name,
        SUM(oi.order_item_qty * p.price) as category_revenue,
        COUNT(DISTINCT o.order_id) as order_count
        FROM `category` c
        INNER JOIN `product` p ON c.category_id = p.category_category_id
        INNER JOIN `order_item` oi ON p.product_id = oi.product_product_id
        INNER JOIN `order` o ON oi.order_order_id = o.order_id
        WHERE o.order_date BETWEEN '$currentMonthStart' AND '$today'
        GROUP BY c.category_id
        ORDER BY category_revenue DESC";

    $resultset = Database::search($categorySalesQuery);
    while ($row = $resultset->fetch_assoc()) {
        $response["category_sales"][] = [
            "category_id" => $row["category_id"],
            "category_name" => $row["category_name"],
            "revenue" => $row["category_revenue"],
            "order_count" => $row["order_count"]
        ];
    }

    // 4. CURRENT TOTAL SALES AND ORDERS
    $currentSalesQuery = "SELECT SUM(order_amount) as total_sales, COUNT(order_id) as total_orders 
        FROM `order` 
        WHERE order_date BETWEEN '$currentMonthStart' AND '$today'";

    $resultset = Database::search($currentSalesQuery);
    if ($row = $resultset->fetch_assoc()) {
        $response["total_sales"] = (float)$row["total_sales"];
        $response["total_orders"] = (int)$row["total_orders"];
    }

    // 5. TOTAL PRODUCTS THIS MONTH
    $productsQuery = "SELECT COUNT(product.product_id) as total_products 
        FROM `product` 
        WHERE date_added BETWEEN '$currentMonthStart' AND '$today'";

    $resultset = Database::search($productsQuery);
    if ($row = $resultset->fetch_assoc()) {
        $response["total_products"] = (int)$row["total_products"];
    }

    // 6. TOTAL CUSTOMERS THIS MONTH
    $customersQuery = "SELECT COUNT(customer.customer_id) as total_customers 
        FROM `customer` 
        WHERE status = '1' AND registered_date BETWEEN '$currentMonthStart' AND '$today'";

    $resultset = Database::search($customersQuery);
    if ($row = $resultset->fetch_assoc()) {
        $response["total_customers"] = (int)$row["total_customers"];
    }

    // 7. PREVIOUS MONTH SALES AND ORDERS
    $prevSalesQuery = "SELECT SUM(order_amount) as prev_sales, COUNT(order_id) as prev_orders 
        FROM `order` 
        WHERE order_date BETWEEN '$previousMonthStart' AND '$previousMonthEnd'";

    $resultset = Database::search($prevSalesQuery);
    $prevSales = 0;
    $prevOrders = 0;
    if ($row = $resultset->fetch_assoc()) {
        $prevSales = (float)$row["prev_sales"];
        $prevOrders = (int)$row["prev_orders"];
    }

    // 8. PREVIOUS PRODUCTS AND CUSTOMERS
    $prevProductsQuery = "SELECT COUNT(product_id) as prev_products 
        FROM `product` 
        WHERE date_added BETWEEN '$previousMonthStart' AND '$previousMonthEnd'";

    $resultset = Database::search($prevProductsQuery);
    $prevProducts = ($row = $resultset->fetch_assoc()) ? (int)$row["prev_products"] : 0;

    $prevCustomersQuery = "SELECT COUNT(customer.customer_id) as prev_customers 
        FROM `customer` 
        WHERE status = '1' AND registered_date BETWEEN '$previousMonthStart' AND '$previousMonthEnd'";

    $resultset = Database::search($prevCustomersQuery);
    $prevCustomers = ($row = $resultset->fetch_assoc()) ? (int)$row["prev_customers"] : 0;

    // 9. CHANGE PERCENTAGES
    function calcPercent($current, $previous) {
        if ($previous == 0) return $current > 0 ? 100 : 0;
        return round((($current - $previous) / $previous) * 100, 2);
    }

    $response["sales_change_percent"] = calcPercent($response["total_sales"], $prevSales);
    $response["orders_change_percent"] = calcPercent($response["total_orders"], $prevOrders);
    $response["products_change_percent"] = calcPercent($response["total_products"], $prevProducts);
    $response["customers_change_percent"] = calcPercent($response["total_customers"], $prevCustomers);

    $response["response"] = true;
    $response["message"] = "Sales data retrieved successfully";
} catch (Exception $e) {
    $response = [
        "response" => false,
        "message" => "Error executing query: " . $e->getMessage(),
    ];
}

echo json_encode($response);
