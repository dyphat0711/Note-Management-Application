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
$userEmail = $user['email'];

$database = new Database();
$db = $database->getConnection();

$query = "
    SELECT 
        n.id, n.title, n.content, s.access_level, u.display_name as owner_name, u.email as owner_email,
        IF(n.password_hash IS NOT NULL, 1, 0) as is_locked
    FROM shares s
    JOIN notes n ON s.note_id = n.id
    JOIN users u ON n.user_id = u.id
    WHERE s.shared_with_email = :email
";

$stmt = $db->prepare($query);
$stmt->bindParam(":email", $userEmail);
$stmt->execute();

$sharedNotes = [];
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    if ($row['is_locked'] == 1) {
        $row['content'] = 'This note is locked.';
    }
    $sharedNotes[] = $row;
}

http_response_code(200);
echo json_encode($sharedNotes);
?>
