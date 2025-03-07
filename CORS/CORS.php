<?php
header("Access-Control-Allow-Origin: http://localhost:3000");  // Allow React app
header("Access-Control-Allow-Credentials: true");              // Allow credentials (session cookies)
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");    // Allow GET, POST, etc.
header("Access-Control-Allow-Headers: Content-Type, Authorization, Session-Id"); // Allow headers


// Handle preflight requests for OPTIONS method
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}
?>