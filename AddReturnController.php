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
    "message" => "Failed to add return"
];

$admin = validateJWT();
if (!$admin) {
    echo json_encode(["response" => false, "message" => "Unauthorized"]);
    exit;
}

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception("Invalid request method. Only POST allowed.");
    }

    $input = json_decode(file_get_contents('php://input'), true);
    
    $orderNumber = $input['orderNumber'] ?? null;
    $whatsappNumber = $input['whatsappNumber'] ?? null;
    $returnReason = $input['returnReason'] ?? null;
    $items = $input['items'] ?? [];

    if (!$orderNumber || !$whatsappNumber || !$returnReason) {
        throw new Exception("Order number, WhatsApp number, and return reason are required.");
    }

    // Find the order and customer
    $orderQuery = "SELECT o.order_id, o.customer_customer_id 
                   FROM `order` o 
                   WHERE o.order_number = ?";
    $orderResult = Database::search($orderQuery, [$orderNumber]);

    if ($orderResult->num_rows === 0) {
        throw new Exception("Order not found with number: " . $orderNumber);
    }

    $orderData = $orderResult->fetch_assoc();
    $orderId = $orderData['order_id'];
    $customerId = $orderData['customer_customer_id'];

    // Check if return already exists for this order
    $existingReturnQuery = "SELECT return_id FROM returns WHERE order_order_id = ?";
    $existingResult = Database::search($existingReturnQuery, [$orderId]);

    if ($existingResult->num_rows > 0) {
        throw new Exception("A return request already exists for this order.");
    }

    // Insert return record
    $returnDate = date('Y-m-d H:i:s');
    $insertReturnQuery = "INSERT INTO returns 
                          (order_order_id, customer_customer_id, return_date, return_reason, return_status, whatsapp_number) 
                          VALUES (?, ?, ?, ?, 1, ?)";
    
    $insertResult = Database::iud($insertReturnQuery, [
        $orderId, 
        $customerId, 
        $returnDate, 
        $returnReason, 
        $whatsappNumber
    ]);

    if (!$insertResult) {
        throw new Exception("Failed to create return record.");
    }

    $returnId = Database::$connection->insert_id;

    // If items are provided, insert them
    if (!empty($items)) {
        foreach ($items as $item) {
            $productId = $item['productId'] ?? null;
            $size = $item['size'] ?? null;
            $quantity = $item['quantity'] ?? 1;
            $condition = $item['condition'] ?? 'Defective/Damaged';

            if ($productId && $size) {
                $insertItemQuery = "INSERT INTO return_items 
                                   (return_return_id, product_product_id, return_size, quantity_returned, item_condition) 
                                   VALUES (?, ?, ?, ?, ?)";
                
                Database::iud($insertItemQuery, [
                    $returnId,
                    $productId,
                    $size,
                    $quantity,
                    $condition
                ]);
            }
        }
    }

    $response = [
        "response" => true,
        "message" => "Return added successfully",
        "return_id" => $returnId
    ];

} catch (Exception $e) {
    $response = [
        "response" => false,
        "message" => $e->getMessage()
    ];
    error_log("Error in AddReturnController: " . $e->getMessage());
}

echo json_encode($response);
?>