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
    "message" => "Unauthorized"
];

$admin = validateJWT();
if (!$admin) {
    echo json_encode($response);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);

    $order_id = intval($input['orderId']);
    $statusText = $input['status'];
    $tracking_number = $input['trackingNumber'];
    $tracking_link = $input['trackingLink'];
    $courier_service_id = intval($input['courierServiceId']);

    $statusMap = [
        "Paid" => 0,
        "Processing" => 1,
        "Shipped" => 2,
        "Delivered" => 3
    ];

    if (!array_key_exists($statusText, $statusMap)) {
        echo json_encode([
            "response" => false,
            "message" => "Invalid status provided."
        ]);
        exit;
    }

    $status = $statusMap[$statusText];

    $updateOrderQuery = "UPDATE `order` SET 
        order_status = ?,
        tracking_number = ?,
        courier_service_courier_service_id = ?
        WHERE order_id = ?";

    $params = [$status, $tracking_number, $courier_service_id, $order_id];
    $success = Database::iud($updateOrderQuery, $params);

    if ($success) {
        if (!empty($tracking_link)) {
            $updateCourierLinkQuery = "UPDATE courier_service SET courier_service_link = ? WHERE courier_service_id = ?";
            $params = [$tracking_link, $courier_service_id];
            $success = Database::iud($updateCourierLinkQuery, $params);
        }

        if ($success) {
            $response = [
                "response" => true,
                "message" => "Order status and tracking information updated successfully."
            ];
        } else {
            $response = [
                "response" => false,
                "message" => "Failed to update courier service link."
            ];
        }
    } else {
        $response = [
            "response" => false,
            "message" => "Failed to update order status."
        ];
    }

    echo json_encode($response);
} else {
    echo json_encode(["response" => false, "message" => "Invalid request method"]);
}