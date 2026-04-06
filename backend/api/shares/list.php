<?php
require_once '../../config/Cors.php';
require_once '../../config/Database.php';
require_once '../../config/AuthMiddleware.php';

setCorsHeaders();

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(["message" => "Method not allowed"]);
    exit();
}

$user = authenticateAPI();
$userId = $user['id'];

$noteId = isset($_GET['note_id']) ? intval($_GET['note_id']) : null;

if (empty($noteId)) {
    http_response_code(400);
    echo json_encode(["error" => "Missing note ID."]);
    exit();
}

$database = new Database();
$db = $database->getConnection();

// Check if user owns the note (only owners can see the full share list)
$checkQuery = "SELECT id FROM notes WHERE id = :id AND user_id = :user_id";
$cStmt = $db->prepare($checkQuery);
$cStmt->bindParam(':id', $noteId);
$cStmt->bindParam(':user_id', $userId);
$cStmt->execute();

if ($cStmt->rowCount() === 0) {
    http_response_code(403);
    echo json_encode(["error" => "Access denied. Only the owner can view share lists."]);
    exit();
}

$query = "SELECT id, shared_with_email, access_level FROM shares WHERE note_id = :note_id";
$stmt = $db->prepare($query);
$stmt->bindParam(":note_id", $noteId);
$stmt->execute();

$shares = [];
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $shares[] = $row;
}

http_response_code(200);
echo json_encode($shares);
?>
