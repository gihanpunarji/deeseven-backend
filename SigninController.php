<?php

session_start();

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require "connection/connection.php";

$requestBody = file_get_contents('php://input');
$data = json_decode($requestBody, true);

$email = $data["email"] ?? null;
$password = $data["password"] ?? null;


if (empty($email)) {
    $response = ["response" => false, "message" => "Please enter the email"];
}
if (empty($password)) {
    $response = ["response" => false, "message" => "Please enter the password"];
} else {

    $stmt = Database::search("SELECT * FROM `customer` WHERE `email` = ?", [$email]);

    if ($stmt->num_rows == 1) {
        $user = $stmt->fetch_assoc();
        if (password_verify($password, $user['password'])) {

            $_SESSION["user"] = $user;
            $response = ["response" => true, "message" => "Success"];
        } else {
            $response = ["response" => false, "message" => "Invalid Credentials"];
        }
    } 
}

header('Content-Type: application/json');
echo json_encode($response);

?>
