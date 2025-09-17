<?php
session_start();

require_once "CORS/CORS.php";
require_once "vendor/autoload.php";


require "SMTP.php";
require "PHPMailer.php";
require "Exception.php";

use PHPMailer\PHPMailer\PHPMailer;

use Firebase\JWT\JWT;

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require "connection/connection.php";

// Handle preflight OPTIONS request from the second script
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

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
    if ($email == "geniousgaming2212@gmail.com") {
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

                // Add email verification feature from the second script
                $verificationToken = hash('sha256', $email . 'admin_secret_key_2024');
                $emailSent = sendAdminVerificationEmail($email, $verificationToken);

                if ($emailSent) {
                    $response = [
                        "response" => true, 
                        "message" => "Admin verification email sent. Please check your email to complete login.", 
                        "token" => $jwt, 
                        "admin" => $admin['email'],
                        "role" => "admin",
                        "email_sent" => true
                    ];
                    // echo $response["email_sent"];    
                } else {
                    $response = [
                        "response" => false, 
                        "message" => "Failed to send verification email. Please try again."
                    ];
                }
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

echo json_encode($response);

function sendAdminVerificationEmail($email, $token) {
    $mail = new PHPMailer(true);
    
    try {
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = 'geniousgaming2212@gmail.com';
        $mail->Password   = 'pvkk gkit arzw aadd';
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587;

        $mail->setFrom('noreply@deezevenhingclothing.com', 'Deezeven');
        $mail->addAddress($email);

        $mail->isHTML(true);
        $mail->Subject = "Admin Login Verification - Deezeven";
        
        $verificationLink = "localhost/DeesevenBackend/AdminEmailVerification.php?token=" . $token . "&email=" . urlencode($email);
        
        $htmlMessage = "
        <!DOCTYPE html>
        <html>
        <head>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { background: #FFB700; color: white; padding: 20px; text-align: center; border-radius: 8px 8px 0 0; }
                .content { background: #f9f9f9; padding: 30px; border-radius: 0 0 8px 8px; }
                .button { display: inline-block; background: #FFB700; color: white; padding: 12px 30px; text-decoration: none; border-radius: 5px; font-weight: bold; margin: 20px 0; }
                .footer { text-align: center; margin-top: 30px; font-size: 12px; color: #666; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h1>Admin Login Verification</h1>
                </div>
                <div class='content'>
                    <h2>Hello Admin,</h2>
                    <p>Someone is attempting to log into the admin dashboard with your email address.</p>
                    <p>If this was you, please click the button below to complete your login:</p>
                    <p><a href='{$verificationLink}' class='button'>Access Admin Dashboard</a></p>
                    <p>If you didn't attempt to log in, please ignore this email.</p>
                    <p><strong>Note:</strong> This link will expire in 15 minutes for security purposes.</p>
                    <hr>
                    <p><small>If the button doesn't work, copy and paste this link in your browser:<br>
                    {$verificationLink}</small></p>
                </div>
                <div class='footer'>
                    <p>&copy; 2024 Deezeven. All rights reserved.</p>
                    <p>This is an automated email, please do not reply.</p>
                </div>
            </div>
        </body>
        </html>
        ";
        
        $mail->Body = $htmlMessage;
        
        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log("Mail Error: " . $mail->ErrorInfo);
        return false;
    }
}
?>