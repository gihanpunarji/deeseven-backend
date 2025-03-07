<?php
session_start();

include "CORS/CORS.php";


ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require "connection/connection.php";

$requestBody = file_get_contents('php://input');
$data = json_decode($requestBody, true);

$email = $data["email"] ?? null;
$password = $data["password"] ?? null;

$response = ["response" => false, "message" => "No user found"];

// Validation checks with early return
if (empty($email)) {
    $response = ["response" => false, "message" => "Please enter the email"];
} else if (empty($password)) {
    $response = ["response" => false, "message" => "Please enter the password"];
} else {
    // Hardcoded admin check
    if ($email == "deesevenclothing@gmail.com" && $password == "DeezevenAdmin@2000") {
        $stmt = Database::search("SELECT * FROM `admin` WHERE `email` = ?", [$email]);

        if ($stmt->num_rows == 1) {
            $admin = $stmt->fetch_assoc();
            if (password_verify($password, $admin['password'])) {
                $_SESSION["admin"] = $admin;
                $response = ["response" => true, "message" => "Admin Success"];
            } else {
                $response = ["response" => false, "message" => "Invalid Credentials"];
            }
        }
    } else {
        // Customer login check
        $stmt = Database::search("SELECT * FROM `customer` WHERE `email` = ?", [$email]);

        if ($stmt->num_rows == 1) {
            $user = $stmt->fetch_assoc();
            if (password_verify($password, $user['password'])) {
                $_SESSION["user"] = $user;
                $response = ["response" => true, "message" => "Customer Success"];
            } else {
                $response = ["response" => false, "message" => "Invalid Credentials"];
            }
        }
    }
}

header('Content-Type: application/json');
echo json_encode($response);

?>