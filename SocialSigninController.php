<?php
require_once "CORS/CORS.php";
require "connection/connection.php";
require_once "vendor/autoload.php";

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

use Firebase\JWT\JWT;

$requestBody = file_get_contents('php://input');
$data = json_decode($requestBody, true);

$email = $data["email"] ?? null;
$name = $data["name"] ?? null;
$profilePicture = $data["profilePicture"] ?? null;

$response = ["response" => false, "message" => "Invalid login"];

if ($email) {
    $stmt = Database::search("SELECT * FROM `customer` WHERE `email` = ?", [$email]);

    if ($stmt->num_rows == 1) {
        $user = $stmt->fetch_assoc();
    } else {
        // Split Google full name into first/last
        $parts = explode(" ", $name, 2);
        $fname = $parts[0] ?? "";
        $lname = $parts[1] ?? "";

        Database::iud("INSERT INTO customer (fname, lname, email, status, registered_date) 
                       VALUES (?, ?, ?, 1, NOW())", [$fname, $lname, $email]);
        $userId = Database::$connection->insert_id;

        $user = [
            "customer_id" => $userId,
            "email" => $email,
            "fname" => $fname,
            "lname" => $lname,
            "status" => 1
        ];
    }

    // Create JWT
    $secret_key = "12345678901234567890123456789012";
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
            "name" => $user['fname'] . " " . $user['lname'],
            "profilePicture" => $profilePicture
        ],
        "role" => "customer"
    ];
}

header('Content-Type: application/json');
echo json_encode($response);