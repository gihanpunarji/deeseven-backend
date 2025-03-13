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
    "message" => "No orders found",
    "orders" => [],
    "couriers" => []
];

$admin = validateJWT();
if (!$admin) {
    echo json_encode(["response" => false, "message" => "Unauthorized"]);
    exit;
}

$order_id = intval($_GET['order_id']);

$courierResult = Database::search("SELECT courier_service_id, courier_service_name, courier_service_link 
FROM courier_service ORDER BY courier_service_name ASC");
$couriers = [];
if ($courierResult->num_rows > 0) {
    while ($row = $courierResult->fetch_assoc()) {
        $couriers[] = [
            "id" => $row["courier_service_id"],
            "name" => $row["courier_service_name"],
            "tracking_link" => $row["courier_service_link"]
        ];
    }
}

$orderResult = Database::search("SELECT 
o.order_id, 
o.tracking_number, 
o.order_status, 
c.courier_service_id, 
c.courier_service_name,
c.courier_service_link
FROM `order` o 
LEFT JOIN `courier_service` c ON o.courier_service_courier_service_id = c.courier_service_id 
WHERE `order_id` = $order_id");

if ($orderResult->num_rows > 0) {
    $orderDetails = $orderResult->fetch_assoc();
    $statusText = ["Paid", "Processing", "Shipped", "Delivered"];
    $status = isset($statusText[$orderDetails["order_status"]]) ? $statusText[$orderDetails["order_status"]] : "Unknown";

    $response = [
        "response" => true,
        "message" => "Success",
        "orderDetails" => [
            "id" => $orderDetails["order_id"],
            "status" => $status,
            "tracking_number" => $orderDetails["tracking_number"],
            "courier_service_id" => $orderDetails["courier_service_id"],
            "courier_service_name" => $orderDetails["courier_service_name"],
            "courier_service_link" => $orderDetails["courier_service_link"]
        ],
        "couriers" => $couriers
    ];
}

echo json_encode($response);