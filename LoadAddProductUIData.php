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
    "message" => "No items found",
    "categories" => [],
    "sub_categories" => [],
];

$admin = validateJWT();
if (!$admin) {
    echo json_encode(["response" => false, "message" => "Unauthorized"]);
    exit;
}

try {
    // Fetch categories
    $resultset = Database::search("SELECT * FROM category");
    if ($resultset->num_rows > 0) {
        $categories = [];
        while ($row = $resultset->fetch_assoc()) {
            $categories[] = [
                "category_id" => $row["category_id"],
                "category_name" => $row["category_name"],
            ];
        }
        $response["categories"] = $categories;
    }

    // Fetch sub-categories
    $resultset2 = Database::search("SELECT * FROM sub_category");
    if ($resultset2->num_rows > 0) {
        $sub_categories = [];
        while ($row = $resultset2->fetch_assoc()) {
            $sub_categories[] = [
                "sub_category_id" => $row["sub_category_id"],
                "sub_category_name" => $row["sub_category_name"],
            ];
        }
        $response["sub_categories"] = $sub_categories;
    }

    // Update the response message if categories or sub-categories are found
    if (!empty($response["categories"]) || !empty($response["sub_categories"])) {
        $response["response"] = true;
        $response["message"] = "Success";
    }
} catch (Exception $e) {
    $response = [
        "response" => false,
        "message" => "Error executing query: " . $e->getMessage(),
        "categories" => [],
        "sub_categories" => []
    ];
}

echo json_encode($response);
