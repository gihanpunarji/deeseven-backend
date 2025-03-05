<?php
// Allow requests from any origin (replace * with your React app's origin in production)
header("Access-Control-Allow-Origin: *");

// Allow specific HTTP methods (e.g., GET, POST, PUT, DELETE, OPTIONS)
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");

// Allow specific headers (e.g., Content-Type, Authorization)
header("Access-Control-Allow-Headers: Content-Type, Authorization");

// Allow credentials (if needed)
header("Access-Control-Allow-Credentials: true");

// Handle preflight requests for OPTIONS method
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    
    exit(0);
}
?>