<?php
function setCorsHeaders() {
    // Load environment variables from .env file
    $envFile = dirname(__DIR__) . '/.env';
    if (file_exists($envFile)) {
        $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        foreach ($lines as $line) {
            if (strpos(trim($line), '#') === 0) continue;
            list($key, $value) = explode('=', $line, 2);
            putenv(trim("$key=$value"));
        }
    }
    
    // Get allowed origins from environment variable
    $allowedOrigins = getenv('ALLOWED_ORIGINS');
    
    // Default to localhost for development if not set
    if (!$allowedOrigins) {
        $allowedOrigins = 'http://localhost:3000,http://localhost:8080,http://localhost';
    }
    
    // Get the origin from the request
    $origin = isset($_SERVER['HTTP_ORIGIN']) ? $_SERVER['HTTP_ORIGIN'] : '';
    
    // Check if the origin is in the allowed list
    $allowedArray = array_map('trim', explode(',', $allowedOrigins));
    
    if (in_array($origin, $allowedArray) || in_array('*', $allowedArray)) {
        header("Access-Control-Allow-Origin: " . $origin);
    } else {
        // For requests without origin or non-matching origin, deny
        // In production, you might want to be more strict
        if (!empty($origin)) {
            http_response_code(403);
            echo json_encode(["error" => "CORS policy violation"]);
            exit();
        }
        // Allow requests without origin (same-origin requests)
        header("Access-Control-Allow-Origin: *");
    }
    
    header("Content-Type: application/json; charset=UTF-8");
    header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
    header("Access-Control-Max-Age: 3600");
    header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");
    header("Access-Control-Allow-Credentials: true");

    if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
        http_response_code(200);
        exit();
    }
}
?>
