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
    "message" => "No sizes found",
    "sizes" => []
];

try {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $data = json_decode(file_get_contents('php://input'), true);
        $subCategoryId = $data['sub_category_id'] ?? null;

        if ($subCategoryId === null) {
            $response["message"] = "Sub-category ID is missing";
            echo json_encode($response);
            exit;
        }

        // Fetch sizes based on sub-category
        $resultset = Database::search("SELECT s.size_id, s.size_name 
            FROM `size` s
            INNER JOIN sub_category sc ON s.size_type_size_type_id = sc.size_type_size_type_id
            WHERE sc.sub_category_id = ?
        ", [$subCategoryId]);

        if ($resultset->num_rows > 0) {
            $sizes = [];
            while ($row = $resultset->fetch_assoc()) {
                $sizes[] = [
                    "size_id" => $row["size_id"],
                    "size_name" => $row["size_name"],
                ];
            }
            $response["sizes"] = $sizes;
            $response["response"] = true;
            $response["message"] = "Success";
        } else {
            $response["message"] = "No sizes found for the given sub-category";
        }
    } else {
        $response["message"] = "Invalid request method";
    }
} catch (Exception $e) {
    $response = [
        "response" => false,
        "message" => "Error executing query: " . $e->getMessage(),
        "sizes" => []
    ];
}

echo json_encode($response);
?>