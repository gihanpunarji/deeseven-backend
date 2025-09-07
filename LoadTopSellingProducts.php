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
    "products" => [],
];

// $admin = validateJWT();
// if (!$admin) {
//     echo json_encode(["response" => false, "message" => "Unauthorized"]);
//     exit;
// }

try {
    // Fetch the top 5 most sold products
    $query = "SELECT p.product_id, p.title, p.price, SUM(oi.order_item_qty) as total_sold
                 FROM product p
                 INNER JOIN order_item oi ON p.product_id = oi.product_product_id
                 GROUP BY p.product_id
                 ORDER BY total_sold DESC
                 LIMIT 5";
    $resultset = Database::search($query);
    if ($resultset->num_rows > 0) {
        $products = [];
        while ($row = $resultset->fetch_assoc()) {
            $product_image_query = "SELECT image_url FROM product_images WHERE product_product_id = '{$row['product_id']}' LIMIT 1";
            $product_image_result = Database::search($product_image_query);
            $product_image = $product_image_result->num_rows > 0 ? $product_image_result->fetch_assoc()['image_url'] : 'default.jpg';

            $products[] = [
                "image" => $product_image,
                "title" => $row["title"],
                "unitPrice" => $row["price"],
                "sales" => $row["total_sold"],
            ];
        }
        $response["products"] = $products;
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