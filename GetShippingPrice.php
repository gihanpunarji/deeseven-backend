<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once "CORS/CORS.php";
require "connection/connection.php";
require_once "jwt_middleware.php";

$response = [
    "status" => false,
    "message" => "Shipping price unavailable",
    "price" => []
];

$resultset = Database::search("SELECT `shipping_value` FROM `shipping`");
if ($resultset->num_rows > 0) {
    $row = $resultset->fetch_assoc();
    $response = [
        "status" => true,
        "message" => "success",
        "price" => $row["shipping_value"]
    ];
}

header('Content-Type: application/json');
echo json_encode($response);
?>