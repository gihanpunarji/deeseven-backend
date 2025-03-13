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
    "message" => "Failed to fetch districts",
    "districts" => []
];

$districtsQuery = "SELECT * FROM `district`";
$districtsResult = Database::search($districtsQuery);

if ($districtsResult->num_rows > 0) {
    $districts = [];
    while ($row = $districtsResult->fetch_assoc()) {
        $districts[] = [
            "id" => $row["district_id"],
            "name" => $row["district_name"],
        ];
    }

    $response = [
        "response" => true,
        "message" => "Success",
        "districts" => $districts,
    ];
}

echo json_encode($response);
?>