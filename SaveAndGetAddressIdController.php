<?php

require "connection/connection.php";
require_once "CORS/CORS.php";

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

header("Content-Type: application/json");

$response = ["status" => false, "message" => "fail"];

$request_body = file_get_contents('php://input');
$data = json_decode($request_body, true);

$line1 = $data["line1"] ?? null;
$line2 = $data["line2"] ?? null;
$city_id = $data["city_id"] ?? null;
$postal_code = $data["postal_code"] ?? null;

$result = Database::iud(
    "INSERT INTO `address` (`line1`, `line2`, `city_city_id`, `postal_code`) 
    VALUES (?, ?, ?, ?)",
    [$line1, $line2, $city_id, $postal_code]
);

if ($result) {
    $insertedID = Database::getLastInsertId();
    $response = ["status" => true, "message" => "success", "address_id" => $insertedID];
    
} else {
    $response = ["status" => false, "message" => "fail"];
}

echo json_encode($response);
