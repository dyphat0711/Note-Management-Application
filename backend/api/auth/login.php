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

if (!empty($data->email) && !empty($data->password)) {
    $query = "SELECT id, email, password_hash, display_name, theme_preference, is_activated FROM users WHERE email = :email LIMIT 0,1";
    $stmt = $db->prepare($query);
    $stmt->bindParam(":email", $data->email);
    $stmt->execute();

    if ($stmt->rowCount() > 0) {
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if (password_verify($data->password, $row['password_hash'])) {
            $jwtHandler = new JwtHandler();
            $token = $jwtHandler->encode([
                "iss" => "noteflow",
                "aud" => "noteflow_users",
                "iat" => time(),
                "exp" => time() + (60 * 60 * 24),
                "data" => [
                    "id" => $row['id'],
                    "email" => $row['email'],
                    "display_name" => $row['display_name']
                ]
            ]);

            http_response_code(200);
            echo json_encode([
                "message" => "Successful login.",
                "token" => $token,
                "user" => [
                    "id" => $row['id'],
                    "email" => $row['email'],
                    "display_name" => $row['display_name'],
                    "theme_preference" => $row['theme_preference'],
                    "is_activated" => (bool)$row['is_activated']
                ]
            ]);
            exit();
        }
    }
    
    http_response_code(401);
    echo json_encode(["error" => "Login failed. Incorrect credentials."]);
} else {
    http_response_code(400);
    echo json_encode(["error" => "Incomplete data."]);
}
?>
