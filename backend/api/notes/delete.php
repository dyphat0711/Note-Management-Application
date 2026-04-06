<?php
require_once '../../config/Cors.php';
require_once '../../config/Database.php';
require_once '../../config/AuthMiddleware.php';

setCorsHeaders();

if ($_SERVER['REQUEST_METHOD'] !== 'DELETE') {
    http_response_code(405);
    echo json_encode(["message" => "Method not allowed"]);
    exit();
}

$user = authenticateAPI();
$userId = $user['id'];

// Get ID from query param e.g. delete.php?id=1
$noteId = isset($_GET['id']) ? intval($_GET['id']) : null;

if (empty($noteId)) {
    http_response_code(400);
    echo json_encode(["error" => "Missing note ID."]);
    exit();
}

$database = new Database();
$db = $database->getConnection();

$query = "DELETE FROM notes WHERE id = :id AND user_id = :user_id";
$stmt = $db->prepare($query);
$stmt->bindParam(":id", $noteId);
$stmt->bindParam(":user_id", $userId);

if ($stmt->execute()) {
    if ($stmt->rowCount() > 0) {
        http_response_code(200);
        echo json_encode(["message" => "Note deleted successfully."]);
    } else {
        http_response_code(404);
        echo json_encode(["error" => "Note not found or access denied."]);
    }
} else {
    http_response_code(503);
    echo json_encode(["error" => "Unable to delete note."]);
}
?>
