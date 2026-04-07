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
    $uploadErrors = [
        UPLOAD_ERR_INI_SIZE => 'File exceeds upload_max_filesize',
        UPLOAD_ERR_FORM_SIZE => 'File exceeds MAX_FILE_SIZE directive',
        UPLOAD_ERR_PARTIAL => 'File was only partially uploaded',
        UPLOAD_ERR_NO_FILE => 'No file was uploaded',
        UPLOAD_ERR_NO_TMP_DIR => 'Missing temporary folder',
        UPLOAD_ERR_CANT_WRITE => 'Failed to write file to disk',
        UPLOAD_ERR_EXTENSION => 'A PHP extension stopped the file upload'
    ];
    $errorMsg = isset($uploadErrors[$_FILES['image']['error']]) 
        ? $uploadErrors[$_FILES['image']['error']] 
        : "Upload error occurred";
    echo json_encode(["error" => "No file uploaded or an error occurred: " . $errorMsg]);
    exit();
}

// Load max file size from environment or default to 5MB
$maxFileSize = getenv('UPLOAD_MAX_SIZE') ?: 5242880; // 5MB default
if ($_FILES['image']['size'] > $maxFileSize) {
    http_response_code(400);
    echo json_encode(["error" => "File size exceeds maximum allowed size (" . ($maxFileSize / 1048576) . "MB)"]);
    exit();
}

// Secure upload directory - store outside web root if possible
$baseDir = dirname(__DIR__, 4);
$uploadDir = $baseDir . '/uploads/';

if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0755, true); // Use restrictive permissions
    // Add .htaccess to prevent script execution
    file_put_contents($uploadDir . '.htaccess', "Deny from all\nRemoveHandler .php .phtml .php3\nRemoveType .php .phtml .php3");
}

// Validate file type using multiple methods
$allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
$allowedExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];

$finfo = finfo_open(FILEINFO_MIME_TYPE);
$mimeType = finfo_file($finfo, $_FILES['image']['tmp_name']);
finfo_close($finfo);

if (!in_array($mimeType, $allowedTypes)) {
    http_response_code(400);
    echo json_encode(["error" => "Invalid file type. Only JPG, PNG, GIF, and WEBP are allowed."]);
    exit();
}

// Validate extension
$originalName = basename($_FILES['image']['name']);
$extension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));

if (!in_array($extension, $allowedExtensions)) {
    http_response_code(400);
    echo json_encode(["error" => "Invalid file extension."]);
    exit();
}

// Sanitize filename - use unique name to prevent overwrites and path traversal
$filename = uniqid('img_', true) . '.' . $extension;
$destination = $uploadDir . $filename;

// Additional check: verify the file is actually an image
$imageInfo = getimagesize($_FILES['image']['tmp_name']);
if ($imageInfo === false) {
    http_response_code(400);
    echo json_encode(["error" => "File is not a valid image."]);
    exit();
}

if (move_uploaded_file($_FILES['image']['tmp_name'], $destination)) {
    // Set proper permissions on uploaded file
    chmod($destination, 0644);
    
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
                "original_name" => $originalName,
                "mime_type" => $mimeType,
                "size" => $_FILES['image']['size']
            ]
        ]);
    } else {
        // Clean up file if database insert fails
        unlink($destination);
        http_response_code(503);
        echo json_encode(["error" => "Failed to save to database."]);
    }
} else {
    http_response_code(500);
    echo json_encode(["error" => "Failed to move uploaded file."]);
}
?>
