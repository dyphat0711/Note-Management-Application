<?php
require_once '../../config/Cors.php';
require_once '../../config/Database.php';
require_once '../../config/AuthMiddleware.php';

setCorsHeaders();

if ($_SERVER['REQUEST_METHOD'] !== 'PUT') {
    http_response_code(405);
    echo json_encode(["message" => "Method not allowed"]);
    exit();
}

$user = authenticateAPI();
$userId = $user['id'];

$database = new Database();
$db = $database->getConnection();

$data = json_decode(file_get_contents("php://input"));

if (!empty($data->id)) {
    // Check if note belongs to user first
    $checkQuery = "SELECT id FROM notes WHERE id = :id AND user_id = :user_id";
    $cStmt = $db->prepare($checkQuery);
    $cStmt->bindParam(':id', $data->id);
    $cStmt->bindParam(':user_id', $userId);
    $cStmt->execute();

    if ($cStmt->rowCount() === 0) {
        http_response_code(403);
        echo json_encode(["error" => "Access denied."]);
        exit();
    }

    $fields = [];
    $params = [":id" => $data->id];

    if (isset($data->title)) {
        $fields[] = "title = :title";
        $params[":title"] = $data->title;
    }
    if (isset($data->content)) {
        $fields[] = "content = :content";
        $params[":content"] = $data->content;
    }
    if (isset($data->is_pinned)) {
        $fields[] = "is_pinned = :is_pinned";
        $params[":is_pinned"] = $data->is_pinned ? 1 : 0;
    }

    if(empty($fields)) {
        http_response_code(400);
        echo json_encode(["error" => "No fields to update."]);
        exit();
    }

    $query = "UPDATE notes SET " . implode(", ", $fields) . " WHERE id = :id";
    $stmt = $db->prepare($query);

    foreach($params as $key => &$val) {
        $stmt->bindParam($key, $val);
    }

    if ($stmt->execute()) {
        http_response_code(200);
        echo json_encode(["message" => "Note updated successfully."]);
    } else {
        http_response_code(503);
        echo json_encode(["error" => "Unable to update note."]);
    }

} else {
    http_response_code(400);
    echo json_encode(["error" => "Missing note ID."]);
}
?>
