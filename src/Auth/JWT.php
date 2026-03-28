<?php
declare(strict_types=1);

namespace DTZ\Auth;

class JWT {
    private string $secret;
    private int $expiration;
    
    public function __construct(string $secret, int $expiration = 86400) {
        $this->secret = $secret;
        $this->expiration = $expiration;
    }
    
    public function generate(array $payload): string {
        $header = json_encode(['typ' => 'JWT', 'alg' => 'HS256']);
        
        $time = time();
        $payload['iat'] = $time;
        $payload['exp'] = $time + $this->expiration;
        
        $payloadEncoded = json_encode($payload);
        
        $base64Header = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($header));
        $base64Payload = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($payloadEncoded));
        
        $signature = hash_hmac('sha256', "$base64Header.$base64Payload", $this->secret, true);
        $base64Signature = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($signature));
        
        return "$base64Header.$base64Payload.$base64Signature";
    }
    
    public function verify(string $token): ?array {
        $parts = explode('.', $token);
        
        if (count($parts) !== 3) {
            return null;
        }
        
        [$base64Header, $base64Payload, $base64Signature] = $parts;
        
        $signature = hash_hmac('sha256', "$base64Header.$base64Payload", $this->secret, true);
        $expectedSignature = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($signature));
        
        if (!hash_equals($expectedSignature, $base64Signature)) {
            return null;
        }
        
        $payload = json_decode(base64_decode(str_replace(['-', '_'], ['+', '/'], $base64Payload)), true);
        
        if (!$payload || ($payload['exp'] ?? 0) < time()) {
            return null;
        }
        
        return $payload;
    }
}
