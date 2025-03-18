<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

include "connection/connection.php";
require "CORS/CORS.php";
require_once "jwt_middleware.php";

header('Content-Type: application/json');

$response = [
    "response" => false,
    "message" => "No products found",
    "products" => []
];

$admin = validateJWT();
if (!$admin) {
    echo json_encode(["response" => false, "message" => "Unauthorized"]);
    exit;
}

$query = "SELECT *
    FROM product p
    INNER JOIN category c ON p.category_category_id = c.category_id
    INNER JOIN sub_category sc ON p.sub_category_sub_category_id = sc.sub_category_id
    LEFT JOIN product_size ps ON p.product_id = ps.product_product_id
    LEFT JOIN size s ON ps.size_size_id = s.size_id
    ORDER BY p.date_added DESC
";

$result = Database::search($query);

if ($result->num_rows > 0) {
    $products = [];
    $productMap = [];

    while ($row = $result->fetch_assoc()) {
        $productId = $row["product_id"];

        if (!isset($productMap[$productId])) {

            $status = $row["product_status"] == 1 ? "Active" : "Inactive";
            $productMap[$productId] = [
                "id" => $productId,
                "title" => $row["title"],
                "description" => $row["description"],
                "price" => $row["price"],
                "date_added" => $row["date_added"],
                "status" => $status,
                "category" => [
                    "id" => $row["category_id"],
                    "name" => $row["category_name"]
                ],
                "sub_category" => [
                    "id" => $row["sub_category_id"],
                    "name" => $row["sub_category_name"]
                ],
                "sizes" => [],
              
            ];
        }

        if (!empty($row["size_id"])) {
            $productMap[$productId]["sizes"][] = [
                "size_id" => $row["size_id"],
                "size_name" => $row["size_name"],
                "quantity" => $row["quantity"]
            ];
        }
    }

    $products = array_values($productMap);

    $response = [
        "response" => true,
        "message" => "Success",
        "products" => $products
    ];
} else {
    $response = [
        "response" => true,
        "message" => "No products found",
        "products" => []
    ];
}

echo json_encode($response);