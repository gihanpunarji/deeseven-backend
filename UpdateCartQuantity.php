<?php

require "connection/connection.php";
include "CORS/CORS.php";

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

$data = file_get_contents("php://input");
$request = json_decode($data, true);

if ($request == null) {
    echo json_encode(["status" => false, "message" => "No data received"]);
    return;
}

$user_id = $request["user_id"];
$product_id = $request["product_id"];
$new_qty = $request["quantity"];
$size = $request["size"];

$result = Database::iud("UPDATE cart SET qty = ? WHERE product_product_id = ? AND size = ? AND customer_customer_id = ?", 
[$new_qty, $product_id, $size, $user_id]);

if ($result) {
    echo json_encode(["status" => true, "message" => "Quantity updated"]);
} else {
    echo json_encode(["status" => false, "message" => "Failed to update quantity"]);
}

?>
