<?php
declare(strict_types=1);

namespace DTZ\Auth;

/**
 * Simple JWT Implementation
 * No external dependencies required
 */
class JWT
{
    private string $secret;
    private string $algorithm = 'HS256';
    
    public function __construct(string $secret)
    {
        if (strlen($secret) < 32) {
            throw new \InvalidArgumentException('JWT secret must be at least 32 characters');
        }
        $this->secret = $secret;
    }
    
    /**
     * Generate JWT token
     */
    public function generate(array $payload, int $ttl = 3600): string
    {
        $header = [
            'typ' => 'JWT',
            'alg' => $this->algorithm
        ];
        
        $time = time();
        $payload['iat'] = $time;
        $payload['exp'] = $time + $ttl;
        $payload['jti'] = bin2hex(random_bytes(16)); // Unique token ID
        
        $headerEncoded = $this->base64UrlEncode(json_encode($header));
        $payloadEncoded = $this->base64UrlEncode(json_encode($payload));
        
        $signature = hash_hmac('sha256', "$headerEncoded.$payloadEncoded", $this->secret, true);
        $signatureEncoded = $this->base64UrlEncode($signature);
        
        return "$headerEncoded.$payloadEncoded.$signatureEncoded";
    }
    
    /**
     * Validate and decode JWT token
     */
    public function decode(string $token): ?array
    {
        $parts = explode('.', $token);
        
        if (count($parts) !== 3) {
            return null;
        }
        
        [$headerEncoded, $payloadEncoded, $signatureEncoded] = $parts;
        
        // Verify signature
        $signature = hash_hmac('sha256', "$headerEncoded.$payloadEncoded", $this->secret, true);
        $expectedSignature = $this->base64UrlEncode($signature);
        
        if (!hash_equals($expectedSignature, $signatureEncoded)) {
            return null;
        }
        
        // Decode payload
        $payload = json_decode($this->base64UrlDecode($payloadEncoded), true);
        
        if (!$payload) {
            return null;
        }
        
        // Check expiration
        if (isset($payload['exp']) && $payload['exp'] < time()) {
            return null;
        }
        
        return $payload;
    }
    
    /**
     * Generate refresh token
     */
    public function generateRefreshToken(int $userId): string
    {
        return bin2hex(random_bytes(32));
    }
    
    private function base64UrlEncode(string $data): string
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }
    
    private function base64UrlDecode(string $data): string
    {
        return base64_decode(strtr($data, '-_', '+/') . str_repeat('=', 3 - (3 + strlen($data)) % 4));
    }
}
