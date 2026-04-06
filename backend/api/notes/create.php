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

// Notes can be empty initially!
$title = !empty($data->title) ? $data->title : 'Untitled Note';
$content = !empty($data->content) ? $data->content : '';

$query = "INSERT INTO notes SET user_id=:user_id, title=:title, content=:content";
$stmt = $db->prepare($query);

$stmt->bindParam(":user_id", $userId);
$stmt->bindParam(":title", $title);
$stmt->bindParam(":content", $content);

if ($stmt->execute()) {
    $noteId = $db->lastInsertId();
    http_response_code(201);
    echo json_encode([
        "message" => "Note created.",
        "note" => [
            "id" => $noteId,
            "title" => $title,
            "content" => $content,
            "is_pinned" => 0,
            "is_locked" => 0,
            "labels" => [],
            "attachments" => []
        ]
    ]);
} else {
    http_response_code(503);
    echo json_encode(["error" => "Unable to create note."]);
}
?>
