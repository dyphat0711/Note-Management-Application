<?php
require_once '../../config/Cors.php';
require_once '../../config/Database.php';
require_once '../../config/AuthMiddleware.php';

setCorsHeaders();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(["message" => "Method not allowed"]);
    exit();
}

$user = authenticateAPI();
$userId = $user['id'];

$database = new Database();
$db = $database->getConnection();

$data = json_decode(file_get_contents("php://input"));

if (!empty($data->name)) {
    $color = !empty($data->color) ? $data->color : '#cccccc';

    $query = "INSERT INTO labels SET user_id=:user_id, name=:name, color=:color";
    $stmt = $db->prepare($query);

    $stmt->bindParam(":user_id", $userId);
    $stmt->bindParam(":name", $data->name);
    $stmt->bindParam(":color", $color);

    if ($stmt->execute()) {
        $labelId = $db->lastInsertId();
        http_response_code(201);
        echo json_encode([
            "message" => "Label created.",
            "label" => [
                "id" => $labelId,
                "name" => $data->name,
                "color" => $color
            ]
        ]);
    } else {
        http_response_code(503);
        echo json_encode(["error" => "Unable to create label."]);
    }
} else {
    http_response_code(400);
    echo json_encode(["error" => "Missing label name."]);
}
?>
