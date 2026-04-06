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

if (!isset($_POST['note_id'])) {
    http_response_code(400);
    echo json_encode(["error" => "Missing note ID."]);
    exit();
}

$noteId = intval($_POST['note_id']);

$database = new Database();
$db = $database->getConnection();

// Verify note access
$checkQuery = "SELECT id FROM notes WHERE id = :id AND user_id = :user_id";
$cStmt = $db->prepare($checkQuery);
$cStmt->bindParam(':id', $noteId);
$cStmt->bindParam(':user_id', $userId);
$cStmt->execute();

if ($cStmt->rowCount() === 0) {
    http_response_code(403);
    echo json_encode(["error" => "Access denied."]);
    exit();
}

if (!isset($_FILES['image']) || $_FILES['image']['error'] !== UPLOAD_ERR_OK) {
    http_response_code(400);
    echo json_encode(["error" => "No file uploaded or an error occurred."]);
    exit();
}

$uploadDir = '../../uploads/';
if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0777, true);
}

// Basic validation for image
$allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];

$finfo = finfo_open(FILEINFO_MIME_TYPE);
$mimeType = finfo_file($finfo, $_FILES['image']['tmp_name']);
finfo_close($finfo);

if (!in_array($mimeType, $allowedTypes)) {
    http_response_code(400);
    echo json_encode(["error" => "Invalid file type. Only JPG, PNG, GIF, and WEBP are allowed."]);
    exit();
}

$originalName = basename($_FILES['image']['name']);
$extension = pathinfo($originalName, PATHINFO_EXTENSION);
$filename = uniqid('img_', true) . '.' . $extension;
$destination = $uploadDir . $filename;

if (move_uploaded_file($_FILES['image']['tmp_name'], $destination)) {
    $dbPath = 'uploads/' . $filename;
    
    $query = "INSERT INTO attachments SET note_id = :note_id, file_path = :file_path, original_name = :original_name";
    $stmt = $db->prepare($query);
    $stmt->bindParam(":note_id", $noteId);
    $stmt->bindParam(":file_path", $dbPath);
    $stmt->bindParam(":original_name", $originalName);
    
    if ($stmt->execute()) {
        http_response_code(201);
        echo json_encode([
            "message" => "File uploaded successfully.",
            "attachment" => [
                "id" => $db->lastInsertId(),
                "file_path" => $dbPath,
                "original_name" => $originalName
            ]
        ]);
    } else {
        http_response_code(503);
        echo json_encode(["error" => "Failed to save to database."]);
    }
} else {
    http_response_code(500);
    echo json_encode(["error" => "Failed to move uploaded file."]);
}
?>
