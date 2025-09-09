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
    "message" => "Order not found or verification failed",
    "order_details" => null
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
    $customerPhone = $input['customerPhone'] ?? null;
    $customerEmail = $input['customerEmail'] ?? null;

    if (!$orderNumber || (!$customerPhone && !$customerEmail)) {
        throw new Exception("Order number and customer phone/email are required for verification.");
    }

    // Build verification query - check phone OR email (matching your existing pattern)
    $verificationQuery = "SELECT 
        o.order_id,
        o.order_number,
        o.order_date,
        o.order_amount,
        o.order_status,
        c.customer_id,
        CONCAT(c.fname, ' ', c.lname) as customer_name,
        c.email,
        c.mobile
    FROM `order` o
    INNER JOIN customer c ON o.customer_customer_id = c.customer_id
    WHERE o.order_number = ?";

    $params = [$orderNumber];

    if ($customerPhone && $customerEmail) {
        $verificationQuery .= " AND (c.mobile = ? OR c.email = ?)";
        $params[] = $customerPhone;
        $params[] = $customerEmail;
    } elseif ($customerPhone) {
        $verificationQuery .= " AND c.mobile = ?";
        $params[] = $customerPhone;
    } elseif ($customerEmail) {
        $verificationQuery .= " AND c.email = ?";
        $params[] = $customerEmail;
    }

    $verificationResult = Database::search($verificationQuery, $params);

    if ($verificationResult->num_rows === 0) {
        throw new Exception("Order not found or customer verification failed. Please check the order number and customer details.");
    }

    $orderData = $verificationResult->fetch_assoc();

    // Check if order is in a valid state for returns (not cancelled/pending)
    if ($orderData['order_status'] == 0) {
        throw new Exception("Cannot create return for pending orders. Please wait until order is confirmed.");
    }

    // Check if return already exists
    $existingReturnQuery = "SELECT return_id, return_status FROM returns WHERE order_order_id = ?";
    $existingResult = Database::search($existingReturnQuery, [$orderData['order_id']]);
    
    if ($existingResult->num_rows > 0) {
        $existingReturn = $existingResult->fetch_assoc();
        $statusNames = [1 => 'Requested', 2 => 'Under Review', 3 => 'Approved', 4 => 'Replacement Sent', 5 => 'Completed', 6 => 'Rejected'];
        $statusName = $statusNames[$existingReturn['return_status']] ?? 'Unknown';
        throw new Exception("A return request already exists for this order (Return ID: " . $existingReturn['return_id'] . ", Status: " . $statusName . ")");
    }

    // Get order items with product details
    $itemsQuery = "SELECT 
        oi.order_item_id,
        oi.order_item_qty,
        oi.order_item_size,
        p.product_id,
        p.title as product_title,
        p.price as product_price,
        pi.image_url
    FROM order_item oi
    INNER JOIN product p ON oi.product_product_id = p.product_id
    LEFT JOIN (
        SELECT product_product_id, MIN(image_url) as image_url 
        FROM product_images 
        GROUP BY product_product_id
    ) pi ON p.product_id = pi.product_product_id
    WHERE oi.order_order_id = ?
    ORDER BY oi.order_item_id";

    $itemsResult = Database::search($itemsQuery, [$orderData['order_id']]);

    $items = [];
    while ($itemRow = $itemsResult->fetch_assoc()) {
        $items[] = [
            "order_item_id" => intval($itemRow["order_item_id"]),
            "product_id" => intval($itemRow["product_id"]),
            "product_title" => $itemRow["product_title"],
            "product_price" => floatval($itemRow["product_price"]),
            "quantity_ordered" => intval($itemRow["order_item_qty"]),
            "size" => $itemRow["order_item_size"],
            "image_url" => $itemRow["image_url"]
        ];
    }

    if (empty($items)) {
        throw new Exception("No items found for this order. Order may be incomplete.");
    }

    $response = [
        "response" => true,
        "message" => "Order verified successfully",
        "order_details" => [
            "order_id" => intval($orderData["order_id"]),
            "order_number" => $orderData["order_number"],
            "order_date" => $orderData["order_date"],
            "order_amount" => floatval($orderData["order_amount"]),
            "order_status" => intval($orderData["order_status"]),
            "customer" => [
                "customer_id" => intval($orderData["customer_id"]),
                "customer_name" => $orderData["customer_name"],
                "email" => $orderData["email"],
                "mobile" => $orderData["mobile"]
            ],
            "items" => $items
        ]
    ];

} catch (Exception $e) {
    $response = [
        "response" => false,
        "message" => $e->getMessage(),
        "order_details" => null
    ];
    error_log("Error in GetOrderForReturn: " . $e->getMessage());
}

echo json_encode($response);
?>