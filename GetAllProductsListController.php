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

$result = Database::search("SELECT * FROM `product` 
    INNER JOIN `category` ON product.category_category_id = category.category_id 
    INNER JOIN `sub_category` ON product.product_id = sub_category.product_product_id 
    INNER JOIN `size` ON sub_category.size_type_size_type_id = size.size_type_size_type_id 
    ORDER BY product.date_added DESC
");

if ($result->num_rows > 0) {
    $products = [];
    $currentProductId = null;
    $currentProduct = [];

    while ($row = $result->fetch_assoc()) {
        if ($currentProductId !== $row["product_id"]) {
            if ($currentProductId !== null) {
                $products[] = $currentProduct;
            }
            $currentProductId = $row["product_id"];
            $status = $row["product_status"] == 1 ? "Active" : "Inactive";

            $currentProduct = [
                "id" => $row["product_id"],
                "title" => $row["title"],
                "price" => $row["price"],
                "category" => $row["category_name"],
                "date" => $row["date_added"],
                "status" => $status,
                "sizes" => []
            ];
        }
        if (!empty($row["size_name"])) {
            $currentProduct["sizes"][] = [
                "size_name" => $row["size_name"],
                "size_qty" => $row["qty"]
            ];
        }
    }
    if ($currentProductId !== null) {
        $products[] = $currentProduct;
    }

    echo json_encode([
        "response" => true,
        "message" => "Success",
        "products" => $products
    ]);
} else {
    echo json_encode([
        "response" => false,
        "message" => "No products found",
        "products" => []
    ]);
}
?>