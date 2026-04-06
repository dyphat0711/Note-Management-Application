<?php
require_once __DIR__ . '/JwtHandler.php';

function authenticateAPI() {
    $headers = getallheaders();
    if (!isset($headers['Authorization'])) {
        http_response_code(401);
        echo json_encode(["error" => "No authorization token provided."]);
        exit();
    }

    $tokenParts = explode(" ", $headers['Authorization']);
    if (count($tokenParts) != 2 || strcasecmp($tokenParts[0], 'Bearer') != 0) {
        http_response_code(401);
        echo json_encode(["error" => "Invalid authorization header."]);
        exit();
    }

    $jwtHandler = new JwtHandler();
    $decoded = $jwtHandler->decode($tokenParts[1]);

    if (!$decoded) {
        http_response_code(401);
        echo json_encode(["error" => "Invalid or expired token."]);
        exit();
    }

    return $decoded['data'];
}
?>
