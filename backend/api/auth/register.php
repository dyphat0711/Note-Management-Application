<?php
require_once '../../config/Cors.php';
require_once '../../config/Database.php';
require_once '../../config/JwtHandler.php';

setCorsHeaders();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(["message" => "Method not allowed"]);
    exit();
}

$database = new Database();
$db = $database->getConnection();

$data = json_decode(file_get_contents("php://input"));

if (!empty($data->email) && !empty($data->password) && !empty($data->display_name)) {
    // Check if email already exists
    $query = "SELECT id FROM users WHERE email = :email LIMIT 0,1";
    $stmt = $db->prepare($query);
    $stmt->bindParam(":email", $data->email);
    $stmt->execute();
    
    if ($stmt->rowCount() > 0) {
        http_response_code(400);
        echo json_encode(["error" => "Email already exists."]);
        exit();
    }

    $query = "INSERT INTO users SET email=:email, password_hash=:password_hash, display_name=:display_name, activation_token=:activation_token";
    $stmt = $db->prepare($query);

    $password_hash = password_hash($data->password, PASSWORD_BCRYPT);
    $activation_token = bin2hex(random_bytes(16)); // Simple token

    $stmt->bindParam(":email", $data->email);
    $stmt->bindParam(":password_hash", $password_hash);
    $stmt->bindParam(":display_name", $data->display_name);
    $stmt->bindParam(":activation_token", $activation_token);

    if ($stmt->execute()) {
        $userId = $db->lastInsertId();
        
        // Generate Token for auto-login
        $jwtHandler = new JwtHandler();
        $token = $jwtHandler->encode([
            "iss" => "noteflow",
            "aud" => "noteflow_users",
            "iat" => time(),
            "exp" => time() + (60 * 60 * 24), // 24 hours
            "data" => [
                "id" => $userId,
                "email" => $data->email,
                "display_name" => $data->display_name
            ]
        ]);

        http_response_code(201);
        echo json_encode([
            "message" => "User was registered.",
            "token" => $token,
            "user" => [
                "id" => $userId,
                "email" => $data->email,
                "display_name" => $data->display_name,
                "is_activated" => false
            ],
            // In a real app this should only be sent via email
            "activation_token" => $activation_token 
        ]);
    } else {
        http_response_code(503);
        echo json_encode(["error" => "Unable to register user."]);
    }
} else {
    http_response_code(400);
    echo json_encode(["error" => "Incomplete data."]);
}
?>
