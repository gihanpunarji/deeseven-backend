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
    "message" => "Failed to fetch cities",
    "cities" => []
];

if (!isset($_GET["district_id"]) || !is_numeric($_GET["district_id"])) {
    echo json_encode(["response" => false, "message" => "Invalid district ID"]);
    exit;
}

$districtId = $_GET["district_id"];

$citiesQuery = "SELECT * FROM `city` WHERE `district_district_id` = ?";
$citiesResult = Database::search($citiesQuery, [$districtId]);

if ($citiesResult->num_rows > 0) {
    $cities = [];
    while ($row = $citiesResult->fetch_assoc()) {
        $cities[] = [
            "id" => $row["city_id"],
            "name" => $row["city_name"],
        ];
    }

    $response = [
        "response" => true,
        "message" => "Success",
        "cities" => $cities,
    ];
}

echo json_encode($response);
?>