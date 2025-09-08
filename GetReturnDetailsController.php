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
    "message" => "Return not found",
    "return_details" => null
];

$admin = validateJWT();
if (!$admin) {
    echo json_encode(["response" => false, "message" => "Unauthorized"]);
    exit;
}

try {
    if (!isset($_GET['return_id']) || empty($_GET['return_id'])) {
        throw new Exception("Return ID is required.");
    }

    $returnId = intval($_GET['return_id']);

    // Get return details
    $returnQuery = "SELECT 
        r.return_id,
        r.return_date,
        r.return_reason,
        r.return_status,
        r.whatsapp_number,
        r.admin_notes,
        r.replacement_sent,
        r.replacement_tracking,
        o.order_id,
        o.order_number,
        o.order_amount,
        o.order_date,
        c.customer_id,
        CONCAT(c.fname, ' ', c.lname) as customer_name,
        c.email as customer_email,
        c.mobile as customer_mobile
    FROM returns r
    INNER JOIN `order` o ON r.order_order_id = o.order_id
    INNER JOIN customer c ON r.customer_customer_id = c.customer_id
    WHERE r.return_id = ?";

    $returnResult = Database::search($returnQuery, [$returnId]);

    if ($returnResult->num_rows === 0) {
        throw new Exception("Return not found with ID: " . $returnId);
    }

    $returnData = $returnResult->fetch_assoc();

    // Get return items
    $itemsQuery = "SELECT 
        ri.return_item_id,
        ri.return_size,
        ri.quantity_returned,
        ri.item_condition,
        ri.replacement_product_id,
        ri.replacement_size,
        p.product_id,
        p.title as product_title,
        p.price as product_price,
        rp.title as replacement_product_title
    FROM return_items ri
    INNER JOIN product p ON ri.product_product_id = p.product_id
    LEFT JOIN product rp ON ri.replacement_product_id = rp.product_id
    WHERE ri.return_return_id = ?";

    $itemsResult = Database::search($itemsQuery, [$returnId]);

    $items = [];
    while ($itemRow = $itemsResult->fetch_assoc()) {
        $items[] = [
            "return_item_id" => $itemRow["return_item_id"],
            "product_id" => $itemRow["product_id"],
            "product_title" => $itemRow["product_title"],
            "product_price" => $itemRow["product_price"],
            "return_size" => $itemRow["return_size"],
            "quantity_returned" => intval($itemRow["quantity_returned"]),
            "item_condition" => $itemRow["item_condition"],
            "replacement_product_id" => $itemRow["replacement_product_id"],
            "replacement_product_title" => $itemRow["replacement_product_title"],
            "replacement_size" => $itemRow["replacement_size"]
        ];
    }

    $response = [
        "response" => true,
        "message" => "Return details fetched successfully",
        "return_details" => [
            "return_id" => $returnData["return_id"],
            "return_date" => $returnData["return_date"],
            "return_reason" => $returnData["return_reason"],
            "return_status" => intval($returnData["return_status"]),
            "whatsapp_number" => $returnData["whatsapp_number"],
            "admin_notes" => $returnData["admin_notes"],
            "replacement_sent" => intval($returnData["replacement_sent"]),
            "replacement_tracking" => $returnData["replacement_tracking"],
            "order" => [
                "order_id" => $returnData["order_id"],
                "order_number" => $returnData["order_number"],
                "order_amount" => $returnData["order_amount"],
                "order_date" => $returnData["order_date"]
            ],
            "customer" => [
                "customer_id" => $returnData["customer_id"],
                "customer_name" => $returnData["customer_name"],
                "customer_email" => $returnData["customer_email"],
                "customer_mobile" => $returnData["customer_mobile"]
            ],
            "items" => $items
        ]
    ];

} catch (Exception $e) {
    $response = [
        "response" => false,
        "message" => $e->getMessage(),
        "return_details" => null
    ];
    error_log("Error in GetReturnDetailsController: " . $e->getMessage());
}

echo json_encode($response);
?>