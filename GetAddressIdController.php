<?php

require "connection/connection.php";
include "CORS/CORS.php";

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

header("Content-Type: application/json");

$user_id = $_GET["user_id"];

$response = ["status" => false, "message" => "fail"];

$sql = Database::search(
    "SELECT `address_address_id` FROM customer WHERE customer_id = ?",
    [$user_id]
);

if ($sql->num_rows > 0) {
    $row = $sql->fetch_assoc();
    $address_id = $row["address_address_id"];

    $response = ["status" => true, "message" => "success", "address_id" => $address_id];
}

echo json_encode($response);
