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
    "message" => "Failed to retrieve sales data",
    "overview_stats" => [],
    "sales_trend" => [],
    "top_products" => [],
    "category_breakdown" => [],
    "size_analytics" => [],
    "geographic_data" => [],
    "order_status_breakdown" => [],
    "customer_metrics" => [],
    "revenue_breakdown" => []
];

try {
    // Get time period from request (default to 'month')
    $period = $_GET['period'] ?? 'month';
    
    // Calculate date ranges based on period
    $dateRanges = calculateDateRanges($period);
    $currentStart = $dateRanges['current_start'];
    $currentEnd = $dateRanges['current_end'];
    $previousStart = $dateRanges['previous_start'];
    $previousEnd = $dateRanges['previous_end'];
    
    // 1. OVERVIEW STATISTICS
    $overviewQuery = "
        SELECT 
            COALESCE(SUM(order_amount), 0) as total_sales,
            COALESCE(COUNT(order_id), 0) as total_orders,
            COALESCE(AVG(order_amount), 0) as avg_order_value,
            COALESCE(COUNT(DISTINCT customer_customer_id), 0) as unique_customers
        FROM `order` 
        WHERE order_date BETWEEN '$currentStart' AND '$currentEnd'
    ";
    
    $result = Database::search($overviewQuery);
    $currentStats = $result->fetch_assoc();
    
    // Get previous period stats for comparison
    $prevOverviewQuery = "
        SELECT 
            COALESCE(SUM(order_amount), 0) as prev_sales,
            COALESCE(COUNT(order_id), 0) as prev_orders,
            COALESCE(AVG(order_amount), 0) as prev_avg_order,
            COALESCE(COUNT(DISTINCT customer_customer_id), 0) as prev_customers
        FROM `order` 
        WHERE order_date BETWEEN '$previousStart' AND '$previousEnd'
    ";
    
    $result = Database::search($prevOverviewQuery);
    $prevStats = $result->fetch_assoc();
    
    $response["overview_stats"] = [
        "total_sales" => (float)$currentStats["total_sales"],
        "total_orders" => (int)$currentStats["total_orders"],
        "avg_order_value" => (float)$currentStats["avg_order_value"],
        "unique_customers" => (int)$currentStats["unique_customers"],
        "sales_change" => calculatePercentChange($currentStats["total_sales"], $prevStats["prev_sales"]),
        "orders_change" => calculatePercentChange($currentStats["total_orders"], $prevStats["prev_orders"]),
        "avg_order_change" => calculatePercentChange($currentStats["avg_order_value"], $prevStats["prev_avg_order"]),
        "customers_change" => calculatePercentChange($currentStats["unique_customers"], $prevStats["prev_customers"])
    ];
    
    // 2. SALES TREND (Daily data for the period)
    $trendQuery = "
        SELECT 
            DATE(order_date) as date,
            SUM(order_amount) as daily_sales,
            COUNT(order_id) as daily_orders
        FROM `order`
        WHERE order_date BETWEEN '$currentStart' AND '$currentEnd'
        GROUP BY DATE(order_date)
        ORDER BY date ASC
    ";
    
    $result = Database::search($trendQuery);
    while ($row = $result->fetch_assoc()) {
        $response["sales_trend"][] = [
            "date" => $row["date"],
            "sales" => (float)$row["daily_sales"],
            "orders" => (int)$row["daily_orders"]
        ];
    }
    
    // 3. TOP 10 PRODUCTS
    $topProductsQuery = "
        SELECT 
            p.product_id,
            p.title,
            p.price,
            c.category_name,
            sc.sub_category_name,
            pi.image_url,
            SUM(oi.order_item_qty) as units_sold,
            SUM(oi.order_item_qty * p.price) as revenue
        FROM product p
        LEFT JOIN product_images pi ON p.product_id = pi.product_product_id
        LEFT JOIN category c ON p.category_category_id = c.category_id
        LEFT JOIN sub_category sc ON p.sub_category_sub_category_id = sc.sub_category_id
        INNER JOIN order_item oi ON p.product_id = oi.product_product_id
        INNER JOIN `order` o ON oi.order_order_id = o.order_id
        WHERE o.order_date BETWEEN '$currentStart' AND '$currentEnd'
        GROUP BY p.product_id
        ORDER BY units_sold DESC
        LIMIT 10
    ";
    
    $result = Database::search($topProductsQuery);
    while ($row = $result->fetch_assoc()) {
        $response["top_products"][] = [
            "product_id" => (int)$row["product_id"],
            "title" => $row["title"],
            "price" => (float)$row["price"],
            "category" => $row["category_name"],
            "subcategory" => $row["sub_category_name"],
            "image_url" => $row["image_url"],
            "units_sold" => (int)$row["units_sold"],
            "revenue" => (float)$row["revenue"]
        ];
    }
    
    // 4. CATEGORY BREAKDOWN
    $categoryQuery = "
        SELECT 
            c.category_name,
            COUNT(DISTINCT o.order_id) as order_count,
            SUM(oi.order_item_qty) as total_units,
            SUM(oi.order_item_qty * p.price) as revenue,
            AVG(p.price) as avg_price
        FROM category c
        INNER JOIN product p ON c.category_id = p.category_category_id
        INNER JOIN order_item oi ON p.product_id = oi.product_product_id
        INNER JOIN `order` o ON oi.order_order_id = o.order_id
        WHERE o.order_date BETWEEN '$currentStart' AND '$currentEnd'
        GROUP BY c.category_id, c.category_name
        ORDER BY revenue DESC
    ";
    
    $result = Database::search($categoryQuery);
    while ($row = $result->fetch_assoc()) {
        $response["category_breakdown"][] = [
            "name" => $row["category_name"],
            "order_count" => (int)$row["order_count"],
            "total_units" => (int)$row["total_units"],
            "revenue" => (float)$row["revenue"],
            "avg_price" => (float)$row["avg_price"]
        ];
    }
    
    // 5. SIZE ANALYTICS
    $sizeQuery = "
        SELECT 
            s.size_name,
            st.size_type_name,
            SUM(oi.order_item_qty) as total_sold
        FROM size s
        INNER JOIN size_type st ON s.size_type_size_type_id = st.size_type_id
        INNER JOIN product_size ps ON s.size_id = ps.size_size_id
        INNER JOIN order_item oi ON ps.product_product_id = oi.product_product_id AND s.size_name = oi.order_item_size
        INNER JOIN `order` o ON oi.order_order_id = o.order_id
        WHERE o.order_date BETWEEN '$currentStart' AND '$currentEnd'
        GROUP BY s.size_id, s.size_name, st.size_type_name
        ORDER BY total_sold DESC
    ";
    
    $result = Database::search($sizeQuery);
    while ($row = $result->fetch_assoc()) {
        $response["size_analytics"][] = [
            "size" => $row["size_name"],
            "type" => $row["size_type_name"],
            "sold" => (int)$row["total_sold"]
        ];
    }
    
    // 6. GEOGRAPHIC DATA
    $geoQuery = "
        SELECT 
            d.district_name,
            c.city_name,
            COUNT(o.order_id) as order_count,
            SUM(o.order_amount) as total_revenue
        FROM district d
        INNER JOIN city c ON d.district_id = c.district_district_id
        INNER JOIN address a ON c.city_id = a.city_city_id
        INNER JOIN `order` o ON a.address_id = o.address_address_id
        WHERE o.order_date BETWEEN '$currentStart' AND '$currentEnd'
        GROUP BY d.district_id, c.city_id
        ORDER BY order_count DESC
        LIMIT 20
    ";
    
    $result = Database::search($geoQuery);
    while ($row = $result->fetch_assoc()) {
        $response["geographic_data"][] = [
            "district" => $row["district_name"],
            "city" => $row["city_name"],
            "orders" => (int)$row["order_count"],
            "revenue" => (float)$row["total_revenue"]
        ];
    }
    
    // 7. ORDER STATUS BREAKDOWN
    $statusQuery = "
        SELECT 
            CASE 
                WHEN order_status = 1 THEN 'Pending'
                WHEN order_status = 2 THEN 'Processing'
                WHEN order_status = 3 THEN 'Completed'
                ELSE 'Other'
            END as status_name,
            COUNT(order_id) as count,
            SUM(order_amount) as revenue
        FROM `order`
        WHERE order_date BETWEEN '$currentStart' AND '$currentEnd'
        GROUP BY order_status
    ";
    
    $result = Database::search($statusQuery);
    while ($row = $result->fetch_assoc()) {
        $response["order_status_breakdown"][] = [
            "status" => $row["status_name"],
            "count" => (int)$row["count"],
            "revenue" => (float)$row["revenue"]
        ];
    }
    
    // 8. CUSTOMER METRICS
    $customerQuery = "
        SELECT 
            COUNT(DISTINCT c.customer_id) as total_customers,
            COUNT(DISTINCT CASE WHEN c.registered_date BETWEEN '$currentStart' AND '$currentEnd' THEN c.customer_id END) as new_customers,
            AVG(customer_orders.order_count) as avg_orders_per_customer
        FROM customer c
        LEFT JOIN (
            SELECT customer_customer_id, COUNT(order_id) as order_count
            FROM `order`
            WHERE order_date BETWEEN '$currentStart' AND '$currentEnd'
            GROUP BY customer_customer_id
        ) customer_orders ON c.customer_id = customer_orders.customer_customer_id
        WHERE c.status = 1
    ";
    
    $result = Database::search($customerQuery);
    if ($row = $result->fetch_assoc()) {
        $response["customer_metrics"] = [
            "total_customers" => (int)$row["total_customers"],
            "new_customers" => (int)$row["new_customers"],
            "avg_orders_per_customer" => (float)$row["avg_orders_per_customer"]
        ];
    }
    
    // 9. REVENUE BREAKDOWN
    $revenueQuery = "
        SELECT 
            COALESCE(SUM(o.order_amount), 0) as product_revenue,
            COALESCE((SELECT shipping_value FROM shipping LIMIT 1) * COUNT(o.order_id), 0) as shipping_revenue
        FROM `order` o
        WHERE o.order_date BETWEEN '$currentStart' AND '$currentEnd'
    ";
    
    $result = Database::search($revenueQuery);
    if ($row = $result->fetch_assoc()) {
        $response["revenue_breakdown"] = [
            "product_revenue" => (float)$row["product_revenue"],
            "shipping_revenue" => (float)$row["shipping_revenue"]
        ];
    }
    
    $response["response"] = true;
    $response["message"] = "Sales data retrieved successfully";
    
} catch (Exception $e) {
    $response["message"] = "Error: " . $e->getMessage();
}


echo json_encode($response);

// Helper Functions
function calculateDateRanges($period) {
    $today = date('Y-m-d');
    $ranges = [];
    
    switch ($period) {
        case 'today':
            $ranges['current_start'] = $today;
            $ranges['current_end'] = $today . ' 23:59:59';
            $ranges['previous_start'] = date('Y-m-d', strtotime('-1 day'));
            $ranges['previous_end'] = date('Y-m-d', strtotime('-1 day')) . ' 23:59:59';
            break;
            
        case 'week':
            $ranges['current_start'] = date('Y-m-d', strtotime('monday this week'));
            $ranges['current_end'] = $today . ' 23:59:59';
            $ranges['previous_start'] = date('Y-m-d', strtotime('monday last week'));
            $ranges['previous_end'] = date('Y-m-d', strtotime('sunday last week')) . ' 23:59:59';
            break;
            
        case 'month':
            $ranges['current_start'] = date('Y-m-01');
            $ranges['current_end'] = $today . ' 23:59:59';
            $ranges['previous_start'] = date('Y-m-01', strtotime('first day of last month'));
            $ranges['previous_end'] = date('Y-m-t', strtotime('last month')) . ' 23:59:59';
            break;
            
        case 'year':
            $ranges['current_start'] = date('Y-01-01');
            $ranges['current_end'] = $today . ' 23:59:59';
            $ranges['previous_start'] = date('Y-01-01', strtotime('-1 year'));
            $ranges['previous_end'] = date('Y-12-31', strtotime('-1 year')) . ' 23:59:59';
            break;
            
        default:
            $ranges['current_start'] = date('Y-m-01');
            $ranges['current_end'] = $today . ' 23:59:59';
            $ranges['previous_start'] = date('Y-m-01', strtotime('first day of last month'));
            $ranges['previous_end'] = date('Y-m-t', strtotime('last month')) . ' 23:59:59';
    }
    
    return $ranges;
}

function calculatePercentChange($current, $previous) {
    if ($previous == 0) {
        return $current > 0 ? 100 : 0;
    }
    return round((($current - $previous) / $previous) * 100, 2);
}
?>