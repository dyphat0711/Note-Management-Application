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

if (!empty($data->token)) {
    $query = "UPDATE users SET is_activated = 1, activation_token = NULL WHERE activation_token = :token AND is_activated = 0";
    $stmt = $db->prepare($query);
    $stmt->bindParam(":token", $data->token);
    $stmt->execute();

    if ($stmt->rowCount() > 0) {
        http_response_code(200);
        echo json_encode(["message" => "Account activated successfully."]);
    } else {
        http_response_code(400);
        echo json_encode(["error" => "Invalid or expired activation token."]);
    }
} else {
    http_response_code(400);
    echo json_encode(["error" => "Token is missing."]);
}
?>
