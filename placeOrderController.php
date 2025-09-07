<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once "CORS/CORS.php";
require "connection/connection.php";

header('Content-Type: application/json');

$request_body = file_get_contents('php://input');
$data = json_decode($request_body, true);

$response = [
    "status" => false,
    "message" => "failed",
];

$merchant_id = "1221053";
$amount = $data['totalAmount'];
$mobile = $data['shipping_address']['mobile'];
$city_id = $data['shipping_address']['city_id'];
$district_id = $data['shipping_address']['district_id'];
$city = $data['shipping_address']['city'];
$district = $data['shipping_address']['district'];
$fname = $data['shipping_address']['fname'];
$lname = $data['shipping_address']['lname'];
$email = $data['shipping_address']['email'];
$line1 = $data['shipping_address']['line1'];
$line2 = $data['shipping_address']['line2'];
$postal_code = $data['shipping_address']['postal_code'];
$user_id = $data['user_id'];
$currency = "LKR";

$order_id = random_int(100000, 999999); 
$merchant_secret = "NDgyODA4MjQzMzM2NTM3NDI2OTIxNDA2MTU3NzcxODIxMjQ2NDI3";

$hash = strtoupper(
    md5(
        $merchant_id . 
        $order_id . 
        number_format($amount, 2, '.', '') . 
        $currency .  
        strtoupper(md5($merchant_secret)) 
    ) 
);

$response = [
    "status" => true,
    "message" => "success",
    "order_id" => $order_id,
    "hash" => $hash,
    "user_id" => $user_id,
    "mobile" => $mobile,
    "city_id" => $city_id,
    "district_id" => $district_id,
    "city" => $city,
    "district" => $district,
    "fname" => $fname,
    "lname" => $lname,
    "email" => $email,
    "line1" => $line1,
    "line2" => $line2,
    "postal_code" => $postal_code,
    "amount" => $amount,
    "currency" => $currency,
    "merchant_id" => $merchant_id,
    "merchant_secret" => $merchant_secret,
    "hash" => $hash,
];

echo json_encode($response);