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
    "message" => "No customers found",
    "customers" => []
];

$admin = validateJWT();
if (!$admin) {
    echo json_encode(["response" => false, "message" => "Unauthorized"]);
    exit;
}

$result = Database::search("SELECT * FROM `customer`
INNER JOIN `address` ON `customer`.address_address_id=`address`.`address_id`
INNER JOIN `city` ON `city`.`city_id`=`address`.`city_city_id`
INNER JOIN `district` ON `district`.`district_id`=`city`.`district_district_id` 
ORDER BY customer.registered_date DESC");

if ($result->num_rows > 0) {
    $customers = [];
    $currentCustomerId = null;
    $currentCustomer = [];

    while ($row = $result->fetch_assoc()) {
        if ($currentCustomerId !== $row["customer_id"]) {
            if ($currentCustomerId !== null) {
                $customers[] = $currentCustomer;
            }
            $currentCustomerId = $row["customer_id"];
            $status = $row["status"] == 1 ? "Active" : "Inactive";

            $currentCustomer = [
                "id" => $row["customer_id"],
                "fname" => $row["fname"],
                "lname" => $row["lname"],
                "mobile" => $row["mobile"],
                "email" => $row["email"],
                "status" => $status,
                "date" => $row["registered_date"],
                "line1" => $row["line1"],
                "line2" => $row["line2"],
                "postal_code" => $row["postal_code"],
                "city" => $row["city_name"],
                "district" => $row["district_name"],

            ];
        }
    
    }
    if ($currentCustomerId !== null) {
        $customers[] = $currentCustomer;
    }

    echo json_encode([
        "response" => true,
        "message" => "Success",
        "customers" => $customers
    ]);
} else {
    echo json_encode([
        "response" => false,
        "message" => "No customers found",
        "customers" => []
    ]);
}
?>