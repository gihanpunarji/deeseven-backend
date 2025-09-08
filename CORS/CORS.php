<?php
// Allowed origins (add your production domain here)
$allowedOrigins = [
    "http://localhost:5173",  // Vite default
    "http://localhost:3000",  // React CRA
    "https://your-production-domain.com" // your live domain
];

$origin = $_SERVER['HTTP_ORIGIN'] ?? "";

if (in_array($origin, $allowedOrigins)) {
    header("Access-Control-Allow-Origin: $origin");
}

header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");
header("Access-Control-Allow-Credentials: true");

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit(0);
}
?>
