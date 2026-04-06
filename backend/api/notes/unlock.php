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

$query = "SELECT id, content, password_hash FROM notes WHERE id = :id AND user_id = :user_id";
$stmt = $db->prepare($query);
$stmt->bindParam(':id', $data->id);
$stmt->bindParam(':user_id', $userId);
$stmt->execute();

if ($stmt->rowCount() === 0) {
    http_response_code(404);
    echo json_encode(["error" => "Note not found or access denied."]);
    exit();
}

$row = $stmt->fetch(PDO::FETCH_ASSOC);

if (!empty($row['password_hash']) && password_verify($data->password, $row['password_hash'])) {
    
    // Fetch attachments since they were hidden
    $attachments = [];
    $attQuery = "SELECT id, file_path, original_name FROM attachments WHERE note_id = :note_id";
    $attStmt = $db->prepare($attQuery);
    $attStmt->bindParam(":note_id", $row['id']);
    $attStmt->execute();
    while ($attRow = $attStmt->fetch(PDO::FETCH_ASSOC)) {
        $attachments[] = $attRow;
    }

    http_response_code(200);
    echo json_encode([
        "message" => "Unlocked.",
        "content" => $row['content'],
        "attachments" => $attachments
    ]);
} else {
    http_response_code(401);
    echo json_encode(["error" => "Incorrect password."]);
}
?>
