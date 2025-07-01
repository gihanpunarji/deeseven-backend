<?php
header("Access-Control-Allow-Origin: https://deezevenclothing.com"); // Use your frontend URL
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Access-Control-Allow-Credentials: true"); // 🔥 Make sure this is present

// Handle preflight requests for OPTIONS method
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit(0);
}

?>