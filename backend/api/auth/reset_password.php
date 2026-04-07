<?php
require_once '../../config/Cors.php';
require_once '../../config/Database.php';

setCorsHeaders();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(["message" => "Method not allowed"]);
    exit();
}

$database = new Database();
$db = $database->getConnection();
$data = json_decode(file_get_contents("php://input"));

// Validate input
if (empty($data)) {
    http_response_code(400);
    echo json_encode(["error" => "Invalid request data."]);
    exit();
}

if (!empty($data->action)) {
    if ($data->action === 'request' && !empty($data->email)) {
        // Validate email format
        if (!filter_var($data->email, FILTER_VALIDATE_EMAIL)) {
            http_response_code(400);
            echo json_encode(["error" => "Invalid email format."]);
            exit();
        }
        
        // Generate Token (6-digit numeric OTP)
        $token = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        $expiry = date('Y-m-d H:i:s', time() + 900); // 15 mins
        
        // Rate limiting: Check if too many recent requests from this email
        $rateLimitQuery = "SELECT id FROM users WHERE email = :email AND reset_token_expiry > DATE_SUB(NOW(), INTERVAL 5 MINUTE)";
        $rateStmt = $db->prepare($rateLimitQuery);
        $rateStmt->bindParam(":email", $data->email);
        $rateStmt->execute();
        
        if ($rateStmt->rowCount() > 0) {
            http_response_code(429);
            echo json_encode(["error" => "Too many requests. Please wait 5 minutes before requesting another reset token."]);
            exit();
        }
        
        $query = "UPDATE users SET reset_token = :token, reset_token_expiry = :expiry WHERE email = :email";
        $stmt = $db->prepare($query);
        $stmt->bindParam(":token", $token);
        $stmt->bindParam(":expiry", $expiry);
        $stmt->bindParam(":email", $data->email);
        $stmt->execute();
        
        if ($stmt->rowCount() > 0) {
            // In production, send OTP via email service (PHPMailer, SendGrid, etc.)
            // For now, log it and return success message without exposing the OTP
            error_log("Password reset OTP for {$data->email}: {$token}");
            
            http_response_code(200);
            echo json_encode([
                "message" => "If an account exists with that email, a reset code has been sent.",
                "debug_otp" => (getenv('APP_ENV') === 'development') ? $token : null
            ]);
        } else {
            // Don't reveal if email exists or not (security best practice)
            http_response_code(200);
            echo json_encode(["message" => "If an account exists with that email, a reset code has been sent."]);
        }
    } else if ($data->action === 'reset' && !empty($data->otp) && !empty($data->new_password)) {
        // Validate OTP format (should be 6 digits)
        if (!preg_match('/^\d{6}$/', $data->otp)) {
            http_response_code(400);
            echo json_encode(["error" => "Invalid OTP format."]);
            exit();
        }
        
        // Validate password strength
        if (strlen($data->new_password) < 8) {
            http_response_code(400);
            echo json_encode(["error" => "Password must be at least 8 characters long."]);
            exit();
        }
        
        // Check for password complexity (at least one letter and one number)
        if (!preg_match('/[A-Za-z]/', $data->new_password) || !preg_match('/[0-9]/', $data->new_password)) {
            http_response_code(400);
            echo json_encode(["error" => "Password must contain both letters and numbers."]);
            exit();
        }
        
        $now = date('Y-m-d H:i:s');
        $query = "SELECT id FROM users WHERE reset_token = :token AND reset_token_expiry > :now LIMIT 0,1";
        $stmt = $db->prepare($query);
        $stmt->bindParam(":token", $data->otp);
        $stmt->bindParam(":now", $now);
        $stmt->execute();
        
        if ($stmt->rowCount() > 0) {
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            $userId = $row['id'];
            $newHash = password_hash($data->new_password, PASSWORD_BCRYPT, ['cost' => 12]);
            
            $update = "UPDATE users SET password_hash = :hash, reset_token = NULL, reset_token_expiry = NULL, failed_login_attempts = 0 WHERE id = :id";
            $upStmt = $db->prepare($update);
            $upStmt->bindParam(":hash", $newHash);
            $upStmt->bindParam(":id", $userId);
            
            if ($upStmt->execute()) {
                // Log successful password reset
                error_log("Password reset successful for user ID: {$userId}");
                
                http_response_code(200);
                echo json_encode(["message" => "Password was reset successfully."]);
            } else {
                http_response_code(500);
                echo json_encode(["error" => "Failed to update password. Please try again."]);
            }
        } else {
            http_response_code(400);
            echo json_encode(["error" => "Invalid or expired OTP."]);
        }
    } else {
        http_response_code(400);
        echo json_encode(["error" => "Incomplete request. Email and action are required."]);
    }
} else {
    http_response_code(400);
    echo json_encode(["error" => "Action not specified."]);
}
?>
