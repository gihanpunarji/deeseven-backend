<?php

require "connection/connection.php";
include "CORS/CORS.php";

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

header("Content-Type: application/json");


$response = ["status" => false, "message" => "fail"];
$request_body = file_get_contents('php://input');
$data = json_decode($request_body, true);

$user_id = $data["user_id"] ?? null;
$line1 = $data["line1"] ?? null;
$line2 = $data["line2"] ?? null;
$city_id = $data["city_id"] ?? null;
$postal_code = $data["postal_code"] ?? null;

$sql = Database::search(
    "SELECT `address_address_id` FROM customer WHERE customer_id = ?",
    [$user_id]
);

if ($sql->num_rows > 0) {
    $row = $sql->fetch_assoc();
    $address_id = $row["address_address_id"];

    if($address_id != null) {
        $response = ["status" => true, "message" => "Data already exists", "address_id" => $address_id];
    } else {
        $result = Database::iud("INSERT INTO `address` (`line1`, `line2`, `city_city_id`, `postal_code`) VALUES (?, ?, ?, ?)",
        [$line1, $line2, $city_id, $postal_code]);
    
        if ($result) {
            $insertedID = Database::getLastInsertId();
            Database::iud("UPDATE `customer` SET `address_address_id` = ? WHERE `customer`.`customer_id` = ?", [$insertedID, $user_id]);
            $response = ["status" => true, "message" => "Address added to the table", "address_id" => $insertedID];
        } else {
            echo json_encode("fail");
        }
    }

} 
echo json_encode($response);
