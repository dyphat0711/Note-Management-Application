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

if (!isset($data->note_id) || !isset($data->email) || !isset($data->access_level)) {
    http_response_code(400);
    echo json_encode(["error" => "Missing note ID, collaborator email, or access level."]);
    exit();
}

$database = new Database();
$db = $database->getConnection();

// Check note access
$checkQuery = "SELECT id FROM notes WHERE id = :id AND user_id = :user_id";
$cStmt = $db->prepare($checkQuery);
$cStmt->bindParam(':id', $data->note_id);
$cStmt->bindParam(':user_id', $userId);
$cStmt->execute();

if ($cStmt->rowCount() === 0) {
    http_response_code(403);
    echo json_encode(["error" => "Access denied or note not found."]);
    exit();
}

// Check if user exists (Optional, if we want to allow sharing to non-registered emails we can skip, but let's check)
$uCheck = $db->prepare("SELECT id FROM users WHERE email = :email");
$uCheck->bindParam(":email", $data->email);
$uCheck->execute();
if ($uCheck->rowCount() === 0) {
    http_response_code(404);
    echo json_encode(["error" => "No user found with that email address."]);
    exit();
}

// Ensure not sharing with self
if ($data->email === $user['email']) {
    http_response_code(400);
    echo json_encode(["error" => "Cannot share note with yourself."]);
    exit();
}

// Check existing share
$sCheck = $db->prepare("SELECT id FROM shares WHERE note_id = :note_id AND shared_with_email = :email");
$sCheck->bindParam(":note_id", $data->note_id);
$sCheck->bindParam(":email", $data->email);
$sCheck->execute();

if ($sCheck->rowCount() > 0) {
    // Update existing share
    $query = "UPDATE shares SET access_level = :acc WHERE note_id = :note_id AND shared_with_email = :email";
} else {
    // Insert new share
    $query = "INSERT INTO shares SET note_id = :note_id, shared_by_user_id = :user_id, shared_with_email = :email, access_level = :acc";
}

$stmt = $db->prepare($query);
$stmt->bindParam(":acc", $data->access_level);
$stmt->bindParam(":note_id", $data->note_id);
$stmt->bindParam(":email", $data->email);
if ($sCheck->rowCount() === 0) {
    $stmt->bindParam(":user_id", $userId);
}

if ($stmt->execute()) {
    http_response_code(200);
    echo json_encode(["message" => "Note shared successfully."]);
} else {
    http_response_code(503);
    echo json_encode(["error" => "Unable to share note."]);
}
?>
