<?php
require_once '../../config/Cors.php';
require_once '../../config/Database.php';
require_once '../../config/JwtHandler.php';

setCorsHeaders();

if ($_SERVER['REQUEST_METHOD'] !== 'PUT') {
    http_response_code(405);
    echo json_encode(["message" => "Method not allowed"]);
    exit();
}

$headers = getallheaders();
if (!isset($headers['Authorization'])) {
    http_response_code(401);
    echo json_encode(["error" => "No authorization token provided."]);
    exit();
}

$tokenParts = explode(" ", $headers['Authorization']);
if (count($tokenParts) != 2 || strcasecmp($tokenParts[0], 'Bearer') != 0) {
    http_response_code(401);
    echo json_encode(["error" => "Invalid authorization header."]);
    exit();
}

$jwtHandler = new JwtHandler();
$decoded = $jwtHandler->decode($tokenParts[1]);

if (!$decoded) {
    http_response_code(401);
    echo json_encode(["error" => "Invalid or expired token."]);
    exit();
}

$userId = $decoded['data']['id'];

$data = json_decode(file_get_contents("php://input"));

if (!empty($data->theme_preference)) {
    $database = new Database();
    $db = $database->getConnection();

    $query = "UPDATE users SET theme_preference = :theme WHERE id = :id";
    $stmt = $db->prepare($query);
    $stmt->bindParam(":theme", $data->theme_preference);
    $stmt->bindParam(":id", $userId);

    if ($stmt->execute()) {
        http_response_code(200);
        echo json_encode(["message" => "Preferences updated."]);
    } else {
        http_response_code(503);
        echo json_encode(["error" => "Unable to update preferences."]);
    }
} else {
    http_response_code(400);
    echo json_encode(["error" => "Missing theme preference."]);
}
?>
