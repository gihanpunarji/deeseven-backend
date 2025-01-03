<?php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require "connection/connection.php";

$requestBody = file_get_contents('php://input');
$data = json_decode($requestBody, true);

$fname = $data["fname"] ?? null;
$lname = $data["lname"] ?? null;
$email = $data["email"] ?? null;
$mobile = $data["mobile"] ?? null;
$password = $data["password"] ?? null;

// Input Validation
if (empty($fname)) {
    $response = ["success" => false, "message" => "Please enter your first name !!!"];
} else if (strlen($fname) > 45) {
    $response = ["success" => false, "message" => "First name must have less than 45 characters"];
} else if (empty($lname)) {
    $response = ["success" => false, "message" => "Please enter your last name !!!"];
} else if (strlen($lname) > 45) {
    $response = ["success" => false, "message" => "Last name must have less than 45 characters"];
} else if (empty($email)) {
    $response = ["success" => false, "message" => "Please enter your Email"];
} else if (strlen($email) > 100) {
    $response = ["success" => false, "message" => "Email must have less than 100 characters"];
} else if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $response = ["success" => false, "message" => "Invalid Email !!!"];
} else if (strlen($mobile) > 10) {
    $response = ["success" => false, "message" => "Mobile number must not exceed 10 characters"];
} else if (empty($password)) {
    $response = ["success" => false, "message" => "Please enter your Password !!!"];
} else if (strlen($password) < 5 || strlen($password) > 20) {
    $response = ["success" => false, "message" => "Password must be between 5 - 20 characters"];
} else {
    
    $stmt = Database::search("SELECT * FROM `customer` WHERE `email` = ?", [$email]);

    if ($stmt->num_rows > 0) {
        $response = ["success" => false, "message" => "Email has been already registered"];
    } else {

        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

        $result = Database::iud(
            "INSERT INTO `customer` (`fname`, `lname`, `mobile`, `email`, `password`, `address_address_id`) 
             VALUES (?, ?, ?, ?, ?, ?)",
            [$fname, $lname, $mobile, $email, $hashedPassword, null]
        );

        if ($result) {
            $response = ["success" => true, "message" => "Registration successful!"];
        } else {
            $response = ["success" => false, "message" => "Failed to register user"];
        }
    }
}

// Send response
header('Content-Type: application/json');
echo json_encode($response);

?>
