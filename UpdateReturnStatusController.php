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
    "message" => "Failed to update return status"
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
    
    $returnId = $input['returnId'] ?? null;
    $status = $input['status'] ?? null;
    $adminNotes = $input['adminNotes'] ?? '';
    $trackingNumber = $input['trackingNumber'] ?? '';

    if (!$returnId || !$status) {
        throw new Exception("Return ID and status are required.");
    }

    // Validate status values (1-6)
    if (!in_array($status, [1, 2, 3, 4, 5, 6])) {
        throw new Exception("Invalid status value.");
    }

    // Check if return exists
    $checkQuery = "SELECT return_id FROM returns WHERE return_id = ?";
    $checkResult = Database::search($checkQuery, [$returnId]);
    
    if ($checkResult->num_rows === 0) {
        throw new Exception("Return not found.");
    }

    // Update return status
    $updateQuery = "UPDATE returns SET 
                    return_status = ?, 
                    admin_notes = ?";
    $params = [$status, $adminNotes];
    
    // Add replacement tracking if status is 4 (Replacement Sent)
    if ($status == 4 && !empty($trackingNumber)) {
        $updateQuery .= ", replacement_sent = 1, replacement_tracking = ?";
        $params[] = $trackingNumber;
    }
    
    $updateQuery .= " WHERE return_id = ?";
    $params[] = $returnId;

    $updateResult = Database::iud($updateQuery, $params);

    if ($updateResult) {
        $response = [
            "response" => true,
            "message" => "Return status updated successfully",
            "return_id" => $returnId,
            "new_status" => $status
        ];
    } else {
        throw new Exception("Failed to update return status in database.");
    }

} catch (Exception $e) {
    $response = [
        "response" => false,
        "message" => $e->getMessage()
    ];
    error_log("Error in UpdateReturnStatusController: " . $e->getMessage());
}

echo json_encode($response);
?>