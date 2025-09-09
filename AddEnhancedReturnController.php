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
    "message" => "Failed to create return"
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
    
    $orderId = $input['orderId'] ?? null;
    $customerId = $input['customerId'] ?? null;
    $whatsappNumber = $input['whatsappNumber'] ?? null;
    $returnReason = $input['returnReason'] ?? null;
    $selectedItems = $input['selectedItems'] ?? [];

    if (!$orderId || !$customerId || !$whatsappNumber || !$returnReason || empty($selectedItems)) {
        throw new Exception("All fields are required including selected items.");
    }

    // Validate WhatsApp number format (basic validation)
    $whatsappNumber = trim($whatsappNumber);
    if (empty($whatsappNumber)) {
        throw new Exception("WhatsApp number is required.");
    }

    // Validate return reason length
    if (strlen(trim($returnReason)) < 10) {
        throw new Exception("Return reason must be at least 10 characters long.");
    }

    // Verify order exists and belongs to customer
    $orderValidationQuery = "SELECT order_id, order_number, order_status 
                            FROM `order` 
                            WHERE order_id = ? AND customer_customer_id = ?";
    $orderResult = Database::search($orderValidationQuery, [$orderId, $customerId]);

    if ($orderResult->num_rows === 0) {
        throw new Exception("Order not found or does not belong to the specified customer.");
    }

    $orderData = $orderResult->fetch_assoc();

    // Check if return already exists (double-check)
    $existingReturnQuery = "SELECT return_id FROM returns WHERE order_order_id = ?";
    $existingResult = Database::search($existingReturnQuery, [$orderId]);

    if ($existingResult->num_rows > 0) {
        throw new Exception("A return request already exists for this order.");
    }

    // Validate each selected item
    $validatedItems = [];
    foreach ($selectedItems as $item) {
        $orderItemId = $item['order_item_id'] ?? null;
        $productId = $item['product_id'] ?? null;
        $quantityToReturn = $item['quantity_to_return'] ?? 1;
        $condition = $item['condition'] ?? 'Defective/Damaged';

        if (!$orderItemId || !$productId) {
            throw new Exception("Invalid item data provided.");
        }

        // Validate order item exists and belongs to this order
        $validateItemQuery = "SELECT 
            oi.order_item_id,
            oi.order_item_qty,
            oi.order_item_size,
            oi.product_product_id,
            p.title as product_title
        FROM order_item oi
        INNER JOIN product p ON oi.product_product_id = p.product_id
        WHERE oi.order_item_id = ? AND oi.order_order_id = ? AND oi.product_product_id = ?";

        $itemResult = Database::search($validateItemQuery, [$orderItemId, $orderId, $productId]);

        if ($itemResult->num_rows === 0) {
            throw new Exception("Invalid order item selected or item does not belong to this order.");
        }

        $orderItemData = $itemResult->fetch_assoc();

        // Validate quantity
        $quantityToReturn = intval($quantityToReturn);
        if ($quantityToReturn <= 0 || $quantityToReturn > intval($orderItemData['order_item_qty'])) {
            throw new Exception("Invalid return quantity for item: " . $orderItemData['product_title']);
        }

        $validatedItems[] = [
            'order_item_id' => $orderItemId,
            'product_id' => $productId,
            'quantity_to_return' => $quantityToReturn,
            'condition' => trim($condition),
            'size' => $orderItemData['order_item_size'],
            'product_title' => $orderItemData['product_title']
        ];
    }

    if (empty($validatedItems)) {
        throw new Exception("No valid items selected for return.");
    }

    // Create return record
    $returnDate = date('Y-m-d H:i:s');
    $adminNote = "Return created by admin for order #" . $orderData['order_number'] . " with " . count($validatedItems) . " item(s)";
    
    $insertReturnQuery = "INSERT INTO returns 
                        (order_order_id, customer_customer_id, return_date, return_reason, return_status, whatsapp_number, admin_notes)
                        VALUES (?, ?, ?, ?, 1, ?, ?)";

    $insertResult = Database::iud($insertReturnQuery, [
        $orderId,
        $customerId,
        $returnDate,
        trim($returnReason),
        $whatsappNumber,
        $adminNote
    ]);

    if (!$insertResult) {
        throw new Exception("Failed to create return record in database.");
    }

    $returnId = Database::$connection->insert_id;

    // Add return items
    $itemsAdded = 0;
    foreach ($validatedItems as $item) {
        $insertItemQuery = "INSERT INTO return_items 
                          (return_return_id, product_product_id, return_size, quantity_returned, item_condition)
                          VALUES (?, ?, ?, ?, ?)";
        
        $itemResult = Database::iud($insertItemQuery, [
            $returnId,
            $item['product_id'],
            $item['size'],
            $item['quantity_to_return'],
            $item['condition']
        ]);

        if ($itemResult) {
            $itemsAdded++;
        }
    }

    if ($itemsAdded === 0) {
        throw new Exception("Failed to add any return items to the database.");
    }

    $response = [
        "response" => true,
        "message" => "Return created successfully with " . $itemsAdded . " item(s)",
        "return_id" => $returnId,
        "order_number" => $orderData['order_number'],
        "items_count" => $itemsAdded
    ];

} catch (Exception $e) {
    $response = [
        "response" => false,
        "message" => $e->getMessage()
    ];
    error_log("Error in AddEnhancedReturnController: " . $e->getMessage());
}

echo json_encode($response);
?>