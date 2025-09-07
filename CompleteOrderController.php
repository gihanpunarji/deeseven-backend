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

$order_id = $data["order_id"] ?? null;
$customer_id = $data["customer_id"] ?? null;
$address_id = $data["address_id"] ?? null;
$totalAmount = $data["totalAmount"] ?? null;
$orderItems = $data["orderItems"] ?? null;


$result = Database::iud(
    "INSERT INTO `order` (`order_number`, `order_date`, `tracking_number`, `order_status`, `customer_customer_id`,
    `order_amount`, `address_address_id`, `courier_service_courier_service_id`) 
    VALUES (?, ?, ?, ?, ?, ?, ?, ?)",
    [$order_id, date("Y-m-d H:i:s"), "TN" . rand(100000, 999999), 1, $customer_id, $totalAmount, $address_id, 1]
);

if ($result) {
    $insertedID = Database::getLastInsertId();
    $response = ["status" => true, "message" => "success"];
    foreach ($orderItems as $item) {
        Database::iud(
            "INSERT INTO `order_item` (`order_item_qty`, `order_order_id`, `product_product_id`, `order_item_size`) 
            VALUES (?, ?, ?, ?)",
            [$item["qty"], $insertedID, $item["product_id"], $item["size"]]
        );
    }
    $response = ["status" => true, "message" => "success"];

    Database::iud("DELETE FROM `cart` WHERE `cart`.`customer_customer_id` = ? ", [$customer_id]);
} else {
    $response = ["status" => false, "message" => "fail"];
}

echo json_encode($response);
?>