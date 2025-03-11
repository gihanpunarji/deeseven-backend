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
    "message" => "Failed to update status",
];

$admin = validateJWT();
if (!$admin) {
    echo json_encode(["response" => false, "message" => "Unauthorized"]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    $customerId = $input['id'];
    $newStatus = $input['status'];

    if ($customerId && $newStatus !== null) {
        $query = "UPDATE `customer` SET `status` = ? WHERE `customer_id` = ?";
        $params = [$newStatus, $customerId];
        $success = Database::iud($query, $params);

        if ($success) {
            $response = [
                "response" => true,
                "message" => "Status updated successfully",
            ];
        } else {
            $response = [
                "response" => false,
                "message" => "Failed to update status",
            ];
        }
    }
}

echo json_encode($response);
?>