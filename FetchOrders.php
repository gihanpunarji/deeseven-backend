<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

include "CORS/CORS.php";
require "connection/connection.php";
require_once "jwt_middleware.php";

if(!isset($_GET['id'])) {
    http_response_code(400);
    echo json_encode([
        "status" => false,
        "message" => "Missing user ID"
    ]);
    exit;
}

$user = validateJWT();
if (!$user) {
    http_response_code(401);
    echo json_encode([
        "status" => false,
        "message" => "Unauthorized"
    ]);
    exit;
}

$user_id = $_GET['id'];

$response = [
    "status" => false,
    "message" => "No items found",
    "data" => []
];

$resultset = Database::search("SELECT 
    `order`.order_number, 
    `order`.order_date, 
    `order`.order_status, 
    `order`.order_amount, 
    `order`.tracking_number,
    billing_address.line1 AS billing_line1,
    billing_address.line2 AS billing_line2,
    billing_address.postal_code AS billing_postal_code,
    shipping_address.line1 AS shipping_line1,
    shipping_address.line2 AS shipping_line2,
    shipping_address.postal_code AS shipping_postal_code,
    customer.fname, 
    customer.lname, 
    customer.email, 
    customer.mobile,
    city_billing.city_name AS billing_city,
    city_shipping.city_name AS shipping_city,
    order_item.order_item_qty, 
    product.title,
    product.price
FROM `order`  
INNER JOIN order_item ON `order`.order_id = order_item.order_order_id  
INNER JOIN product ON order_item.product_product_id = product.product_id  
INNER JOIN customer ON `order`.customer_customer_id = customer.customer_id  
INNER JOIN address AS billing_address ON customer.address_address_id = billing_address.address_id  
INNER JOIN address AS shipping_address ON `order`.address_address_id = shipping_address.address_id
INNER JOIN city AS city_billing ON billing_address.city_city_id = city_billing.city_id  
INNER JOIN city AS city_shipping ON shipping_address.city_city_id = city_shipping.city_id  
WHERE `order`.customer_customer_id = ?
ORDER BY `order`.order_date DESC", [$user_id]);

$orders = [];
if ($resultset->num_rows > 0) {
    while ($row = $resultset->fetch_assoc()) {
        $orderNumber = $row["order_number"];
        if (!isset($orders[$orderNumber])) {
            $orders[$orderNumber] = [
                "order_number" => $row["order_number"],
                "order_date" => $row["order_date"],
                "order_status" => $row["order_status"],
                "tracking_number" => $row["tracking_number"],
                "order_amount" => $row["order_amount"],
                "shipping_address" => [
                    "line1" => $row["shipping_line1"],
                    "line2" => $row["shipping_line2"],
                    "postal_code" => $row["shipping_postal_code"],
                    "city" => $row["shipping_city"],
                ],
                "billing_address" => [
                    "line1" => $row["billing_line1"],
                    "line2" => $row["billing_line2"],
                    "postal_code" => $row["billing_postal_code"],
                    "city" => $row["billing_city"],
                ],
                
                "customer" => [
                    "fname" => $row["fname"],
                    "lname" => $row["lname"],
                    "email" => $row["email"],
                    "mobile" => $row["mobile"],
                ],
                "items" => []
            ];
        }
        $orders[$orderNumber]["items"][] = [
            "order_item_qty" => $row["order_item_qty"],
            "product_price" => $row["price"],
            "title" => $row["title"],
            "order_number" => $row["order_number"],
        ];
    }
    $response = [
        "status" => true,
        "message" => "Success",
        "data" => array_values($orders)
    ];
}

header('Content-Type: application/json');
echo json_encode($response);