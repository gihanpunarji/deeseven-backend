<?php
// Allow requests from any origin (replace * with your React app's origin in production)
header("Access-Control-Allow-Origin: http://localhost:3000");


// Handle preflight requests for OPTIONS method
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}
?>