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

$database = new Database();
$db = $database->getConnection();

// Fetch notes along with their labels and attachments
$query = "
    SELECT 
        n.id, n.title, n.content, n.is_pinned, n.created_at, n.updated_at,
        IF(n.password_hash IS NOT NULL, 1, 0) as is_locked
    FROM notes n
    WHERE n.user_id = :user_id
    ORDER BY n.is_pinned DESC, n.updated_at DESC
";

$stmt = $db->prepare($query);
$stmt->bindParam(":user_id", $userId);
$stmt->execute();

$notes = [];
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    // Note: To optimize, we could do this all in one query with GROUP_CONCAT,
    // but for simplicity and structure we fetch labels and attachments per note.
    $row['labels'] = [];
    $row['attachments'] = [];

    // Fetch labels
    $lblQuery = "SELECT l.id, l.name, l.color FROM labels l JOIN note_labels nl ON l.id = nl.label_id WHERE nl.note_id = :note_id";
    $lblStmt = $db->prepare($lblQuery);
    $lblStmt->bindParam(":note_id", $row['id']);
    $lblStmt->execute();
    while ($lblRow = $lblStmt->fetch(PDO::FETCH_ASSOC)) {
        $row['labels'][] = $lblRow;
    }

    // Fetch attachments
    $attQuery = "SELECT id, file_path, original_name FROM attachments WHERE note_id = :note_id";
    $attStmt = $db->prepare($attQuery);
    $attStmt->bindParam(":note_id", $row['id']);
    $attStmt->execute();
    while ($attRow = $attStmt->fetch(PDO::FETCH_ASSOC)) {
        $row['attachments'][] = $attRow;
    }

    if ($row['is_locked'] == 1) {
        $row['content'] = 'This note is locked.';
        $row['attachments'] = []; // Hidden
    }

    $notes[] = $row;
}

http_response_code(200);
echo json_encode($notes);
?>
