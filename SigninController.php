<?php
session_start();

require_once "CORS/CORS.php";
require_once "vendor/autoload.php";

use Firebase\JWT\JWT;

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require "connection/connection.php";

$requestBody = file_get_contents('php://input');
$data = json_decode($requestBody, true);

$email = $data["email"] ?? null;
$password = $data["password"] ?? null;

$response = ["response" => false, "message" => "No user found"];

$secret_key = "12345678901234567890123456789012";

// Validation checks with early return
if (empty($email)) {
    $response = ["response" => false, "message" => "Please enter the email"];
} else if (empty($password)) {
    $response = ["response" => false, "message" => "Please enter the password"];
} else {
    // Hardcoded admin check
    if ($email == "123@gmail.com") {
        $stmt = Database::search("SELECT * FROM `admin` WHERE `email` = ?", [$email]);

        if ($stmt->num_rows == 1) {
            $admin = $stmt->fetch_assoc();
            if (password_verify($password, $admin['password'])) {

                $payload = [
                    "iss" => "localhost",
                    "aud" => "localhost",
                    "iat" => time(),
                    "exp" => time() + (60 * 60),
                    "data" => [
                        "email" => $admin['email'],
                        "role" => "admin"
                    ]
                ];

                $jwt = JWT::encode($payload, $secret_key, 'HS256');

                $response = [
                    "response" => true, 
                    "message" => "Admin Success", 
                    "token" => $jwt, 
                    "admin" => $admin['email'],
                    "role" => "admin"
                ];
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
                
                $payload = [
                    "iss" => "localhost",
                    "aud" => "localhost",
                    "iat" => time(),
                    "exp" => time() + (60 * 60),
                    "data" => [
                        "email" => $user['email'],
                        "role" => "customer"
                    ]
                ];

                $jwt = JWT::encode($payload, $secret_key, 'HS256');

                $response = [
                    "response" => true, 
                    "message" => "Customer Success",
                    "token" => $jwt,
                    "user" => [
                        "id" => $user['customer_id'],
                        "email" => $user['email'],
                        "status" => $user['status'],
                        "name" => $user['fname'] . ' ' . $user['lname']
                    ],
                    "role" => "customer"
                ];
            } else {
                $response = ["response" => false, "message" => "Invalid Credentials"];
            }
        }
    }
}

header('Content-Type: application/json');
echo json_encode($response);
