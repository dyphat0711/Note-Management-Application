<?php

class JwtHandler {
    private $secret;
    private $issuedAt;
    private $expirationTime;
    
    public function __construct() {
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
        
        // Get secret from environment variable with fallback
        $this->secret = getenv('JWT_SECRET');
        if (!$this->secret) {
            // In development only - never use in production
            $this->secret = 'noteflow_dev_secret_change_in_production_' . bin2hex(random_bytes(16));
            error_log("WARNING: Using auto-generated JWT secret. Set JWT_SECRET in .env file for production.");
        }
        
        $this->issuedAt = time();
        $this->expirationTime = $this->issuedAt + 3600; // 1 hour expiration
    }
    
    public function encode($data) {
        // Add standard JWT claims
        $payload = array_merge($data, [
            'iat' => $this->issuedAt,
            'exp' => $this->expirationTime
        ]);
        
        $header = json_encode(['typ' => 'JWT', 'alg' => 'HS256']);
        $payloadJson = json_encode($payload);
        
        $base64UrlHeader = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($header));
        $base64UrlPayload = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($payloadJson));
        
        $signature = hash_hmac('sha256', $base64UrlHeader . "." . $base64UrlPayload, $this->secret, true);
        $base64UrlSignature = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($signature));
        
        return $base64UrlHeader . "." . $base64UrlPayload . "." . $base64UrlSignature;
    }
    
    public function decode($jwt) {
        $tokenParts = explode('.', $jwt);
        if (count($tokenParts) !== 3) {
            return false;
        }
        
        $header = base64_decode(str_replace(['-', '_'], ['+', '/'], $tokenParts[0]));
        $payload = base64_decode(str_replace(['-', '_'], ['+', '/'], $tokenParts[1]));
        $signatureProvided = $tokenParts[2];
        
        // Check signature
        $base64UrlHeader = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($header));
        $base64UrlPayload = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($payload));
        $signature = hash_hmac('sha256', $base64UrlHeader . "." . $base64UrlPayload, $this->secret, true);
        $base64UrlSignature = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($signature));
        
        // Use hash_equals to prevent timing attacks
        if (!hash_equals($base64UrlSignature, $signatureProvided)) {
            return false;
        }
        
        $data = json_decode($payload, true);
        
        // Check expiration
        if (isset($data['exp']) && $data['exp'] < time()) {
            return false;
        }
        
        // Check issued at (not before)
        if (isset($data['iat']) && $data['iat'] > time()) {
            return false;
        }
        
        return $data;
    }
}
?>
