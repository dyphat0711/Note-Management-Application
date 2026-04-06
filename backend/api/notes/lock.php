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

$data = json_decode(file_get_contents("php://input"));

if (!isset($data->id) || !isset($data->password)) {
    http_response_code(400);
    echo json_encode(["error" => "Missing note ID or password."]);
    exit();
}

$database = new Database();
$db = $database->getConnection();

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

// Set password hash or Null to remove
$hash = null;
if (!empty($data->password)) {
    $hash = password_hash($data->password, PASSWORD_BCRYPT);
}

$query = "UPDATE notes SET password_hash = :hash WHERE id = :id";
$stmt = $db->prepare($query);
$stmt->bindParam(":hash", $hash);
$stmt->bindParam(":id", $data->id);

if ($stmt->execute()) {
    http_response_code(200);
    echo json_encode(["message" => empty($hash) ? "Note unlocked globally." : "Note is now locked."]);
} else {
    http_response_code(503);
    echo json_encode(["error" => "Unable to update lock state."]);
}
?>
