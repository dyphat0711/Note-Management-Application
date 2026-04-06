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

$query = "SELECT id, name, color FROM labels WHERE user_id = :user_id";
$stmt = $db->prepare($query);
$stmt->bindParam(":user_id", $userId);
$stmt->execute();

$labels = [];
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $labels[] = $row;
}

http_response_code(200);
echo json_encode($labels);
?>
