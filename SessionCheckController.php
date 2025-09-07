<?php
require_once "CORS/CORS.php";

session_start();

header('Content-Type: application/json');

$response = [
    "session_exists" => isset($_SESSION["admin"]),
    "session_data" => isset($_SESSION["admin"]) ? $_SESSION["admin"] : null,
    "session_id" => session_id(),
    "cookies_received" => $_COOKIE
];

echo json_encode($response);
?>