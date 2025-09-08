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
    "size_types" => [],
    "sizes" => []
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
    $resultset2 = Database::search("SELECT * FROM sub_category ORDER BY `sub_category_name` ASC");
    if ($resultset2->num_rows > 0) {
        $sub_categories = [];
        while ($row = $resultset2->fetch_assoc()) {
            $sub_categories[] = [
                "sub_category_id" => $row["sub_category_id"],
                "sub_category_name" => $row["sub_category_name"],
                "size_type_id" => $row["size_type_size_type_id"],
            ];
        }
        $response["sub_categories"] = $sub_categories;
    }

    // Fetch size_types
    $resultset3 = Database::search("SELECT * FROM `size_type`");
    if ($resultset3->num_rows > 0) {
        $size_types = [];
        while ($row = $resultset3->fetch_assoc()) {
            $size_types[] = [
                "size_type_id" => $row["size_type_id"],
                "size_type_name" => $row["size_type_name"],
            ];
        }
        $response["size_types"] = $size_types;
    }

    // Fetch sizes
    $resultset4 = Database::search("SELECT * FROM `size` ORDER BY `size_name` ASC");
    if ($resultset4->num_rows > 0) {
        $sizes = [];
        while ($row = $resultset4->fetch_assoc()) {
            $sizes[] = [
                "size_id" => $row["size_id"],
                "size_name" => $row["size_name"],
                "size_type_id" => $row["size_type_size_type_id"],
            ];
        }
        $response["sizes"] = $sizes;
    }

    // Update the response message if data is found
    if (!empty($response["categories"]) || !empty($response["sub_categories"]) || !empty($response["size_types"]) || !empty($response["sizes"])) {
        $response["response"] = true;
        $response["message"] = "Success";
    }
    
} catch (Exception $e) {
    $response = [
        "response" => false,
        "message" => "Error executing query: " . $e->getMessage(),
        "categories" => [],
        "sub_categories" => [],
        "size_types" => [],
        "sizes" => []
    ];
}

echo json_encode($response);
?>