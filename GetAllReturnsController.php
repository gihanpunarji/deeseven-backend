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
    "message" => "No returns found",
    "returns" => []
];

$admin = validateJWT();
if (!$admin) {
    echo json_encode(["response" => false, "message" => "Unauthorized"]);
    exit;
}

try {
    $query = "SELECT 
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
        c.customer_id,
        CONCAT(c.fname, ' ', c.lname) as customer_name,
        c.email as customer_email,
        COUNT(ri.return_item_id) as items_count
    FROM returns r
    INNER JOIN `order` o ON r.order_order_id = o.order_id
    INNER JOIN customer c ON r.customer_customer_id = c.customer_id
    LEFT JOIN return_items ri ON r.return_id = ri.return_return_id
    GROUP BY r.return_id
    ORDER BY r.return_date DESC";

    $result = Database::search($query);

    if ($result->num_rows > 0) {
        $returns = [];
        while ($row = $result->fetch_assoc()) {
            $returns[] = [
                "return_id" => $row["return_id"],
                "return_date" => $row["return_date"],
                "return_reason" => $row["return_reason"],
                "return_status" => intval($row["return_status"]),
                "whatsapp_number" => $row["whatsapp_number"],
                "admin_notes" => $row["admin_notes"],
                "replacement_sent" => intval($row["replacement_sent"]),
                "replacement_tracking" => $row["replacement_tracking"],
                "order_id" => $row["order_id"],
                "order_number" => $row["order_number"],
                "customer_id" => $row["customer_id"],
                "customer_name" => $row["customer_name"],
                "customer_email" => $row["customer_email"],
                "items_count" => intval($row["items_count"])
            ];
        }

        $response = [
            "response" => true,
            "message" => "Returns fetched successfully",
            "returns" => $returns
        ];
    }
} catch (Exception $e) {
    $response = [
        "response" => false,
        "message" => "Error fetching returns: " . $e->getMessage(),
        "returns" => []
    ];
    error_log("Error in GetAllReturnsController: " . $e->getMessage());
}

echo json_encode($response);
?>