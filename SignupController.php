<?php
require_once "CORS/CORS.php";

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require "connection/connection.php";

$requestBody = file_get_contents('php://input');
$data = json_decode($requestBody, true);

$fname = $data["firstName"] ?? null;
$lname = $data["lastName"] ?? null;
$email = $data["email"] ?? null;
$password = $data["password"] ?? null;

// Input Validation
if (empty($fname)) {
    $response = ["status" => false, "message" => "Please enter your first name !!!"];
} else if (strlen($fname) > 45) {
    $response = ["status" => false, "message" => "First name must have less than 45 characters"];
} else if (empty($lname)) {
    $response = ["status" => false, "message" => "Please enter your last name !!!"];
} else if (strlen($lname) > 45) {
    $response = ["status" => false, "message" => "Last name must have less than 45 characters"];
} else if (empty($email)) {
    $response = ["status" => false, "message" => "Please enter your Email"];
} else if (strlen($email) > 100) {
    $response = ["status" => false, "message" => "Email must have less than 100 characters"];
} else if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $response = ["status" => false, "message" => "Invalid Email !!!"];
} else if (empty($password)) {
    $response = ["status" => false, "message" => "Please enter your Password !!!"];
} else if (strlen($password) < 5 || strlen($password) > 20) {
    $response = ["status" => false, "message" => "Password must be between 5 - 20 characters"];
} else {
    
    $stmt = Database::search("SELECT * FROM `customer` WHERE `email` = ?", [$email]);

    if ($stmt->num_rows > 0) {
        $response = ["status" => false, "message" => "Email has been already registered"];
    } else {

        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

        $result = Database::iud(
            "INSERT INTO `customer` (`fname`, `lname`, `mobile`, `email`, `password`, `address_address_id`) 
             VALUES (?, ?, ?, ?, ?, ?)",
            [$fname, $lname, 0, $email, $hashedPassword, null]
        );

        if ($result) {

            $inserted_id = Database::getLastInsertId();
            $response = ["status" => true, "message" => "Registration successful!", "id" => $inserted_id];
        } else {
            $response = ["status" => false, "message" => "Failed to register user"];
        }
    }
}

// Send response
header('Content-Type: application/json');
echo json_encode($response);

?>
