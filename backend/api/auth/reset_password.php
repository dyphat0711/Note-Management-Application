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

if (!empty($data->action)) {
    if ($data->action === 'request' && !empty($data->email)) {
        // Generate Token
        $token = bin2hex(random_bytes(4)); // Simulate OTP for simple use case
        $expiry = date('Y-m-d H:i:s', time() + 900); // 15 mins

        $query = "UPDATE users SET reset_token = :token, reset_token_expiry = :expiry WHERE email = :email";
        $stmt = $db->prepare($query);
        $stmt->bindParam(":token", $token);
        $stmt->bindParam(":expiry", $expiry);
        $stmt->bindParam(":email", $data->email);
        $stmt->execute();
        
        if ($stmt->rowCount() > 0) {
            http_response_code(200);
            echo json_encode(["message" => "Reset token generated.", "otp" => $token]);
        } else {
            http_response_code(404);
            echo json_encode(["error" => "Email not found."]);
        }
    } else if ($data->action === 'reset' && !empty($data->otp) && !empty($data->new_password)) {
        $now = date('Y-m-d H:i:s');
        $query = "SELECT id FROM users WHERE reset_token = :token AND reset_token_expiry > :now LIMIT 0,1";
        $stmt = $db->prepare($query);
        $stmt->bindParam(":token", $data->otp);
        $stmt->bindParam(":now", $now);
        $stmt->execute();

        if ($stmt->rowCount() > 0) {
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            $userId = $row['id'];
            $newHash = password_hash($data->new_password, PASSWORD_BCRYPT);

            $update = "UPDATE users SET password_hash = :hash, reset_token = NULL, reset_token_expiry = NULL WHERE id = :id";
            $upStmt = $db->prepare($update);
            $upStmt->bindParam(":hash", $newHash);
            $upStmt->bindParam(":id", $userId);
            
            if ($upStmt->execute()) {
                http_response_code(200);
                echo json_encode(["message" => "Password was reset successfully."]);
            }
        } else {
            http_response_code(400);
            echo json_encode(["error" => "Invalid or expired OTP."]);
        }
    } else {
        http_response_code(400);
        echo json_encode(["error" => "Incomplete request."]);
    }
} else {
    http_response_code(400);
    echo json_encode(["error" => "Action not specified."]);
}
?>
