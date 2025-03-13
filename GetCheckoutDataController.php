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
    "message" => "No customer found",
    "customer" => []
];

if (!isset($_GET["id"]) || !is_numeric($_GET["id"])) {
    echo json_encode(["response" => false, "message" => "Invalid customer ID"]);
    exit;
}

$customerId = $_GET["id"];

$customerQuery = "SELECT 
    c.customer_id,
    c.fname,
    c.lname,
    c.email,
    c.mobile,
    a.line1,
    a.line2,
    a.postal_code,
    a.city_city_id,
    ci.city_name,
    ci.district_district_id,
    d.district_name
FROM 
    customer c
INNER JOIN 
    address a ON c.address_address_id = a.address_id
INNER JOIN 
    city ci ON a.city_city_id = ci.city_id
INNER JOIN 
    district d ON ci.district_district_id = d.district_id
WHERE 
    c.customer_id = ?";
$customerResult = Database::search($customerQuery, [$customerId]);

if ($customerResult->num_rows > 0) {
    $customer = [];
    while ($row = $customerResult->fetch_assoc()) {
        $customer[] = [
            "id" => $row["customer_id"],
            "fname" => $row["fname"],
            "lname" => $row["lname"],
            "email" => $row["email"],
            "mobile" => $row["mobile"],
            "line1" => $row["line1"],
            "line2" => $row["line2"],
            "postal_code" => $row["postal_code"],
            "city_id" => $row["city_city_id"],
            "city" => $row["city_name"],
            "district_id" => $row["district_district_id"],
            "district" => $row["district_name"],
        ];
    }

    $response = [
        "response" => true,
        "message" => "Success",
        "customer" => $customer,
    ];
} else {
    $response["message"] = "No customer";
}

echo json_encode($response);
?>