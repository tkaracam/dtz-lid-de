<?php
declare(strict_types=1);

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../src/Auth/JWT.php';
require_once __DIR__ . '/../src/Security/InputValidator.php';

use DTZ\Auth\JWT;
use DTZ\Security\InputValidator;

class AuthTest extends TestCase
{
    private string $secret = 'test-secret-key-for-testing-only';
    
    public function testJwtGeneration(): void
    {
        $jwt = new JWT($this->secret);
        $payload = ['sub' => '123', 'email' => 'test@example.com'];
        
        $token = $jwt->generate($payload);
        
        $this->assertNotEmpty($token);
        $this->assertStringContainsString('.', $token);
        $this->assertCount(3, explode('.', $token));
    }
    
    public function testJwtVerification(): void
    {
        $jwt = new JWT($this->secret);
        $payload = ['sub' => '123', 'email' => 'test@example.com'];
        
        $token = $jwt->generate($payload);
        $verified = $jwt->verify($token);
        
        $this->assertNotNull($verified);
        $this->assertEquals('123', $verified['sub']);
        $this->assertEquals('test@example.com', $verified['email']);
    }
    
    public function testJwtInvalidSignature(): void
    {
        $jwt1 = new JWT($this->secret);
        $jwt2 = new JWT('different-secret');
        
        $token = $jwt1->generate(['sub' => '123']);
        $verified = $jwt2->verify($token);
        
        $this->assertNull($verified);
    }
    
    public function testJwtExpired(): void
    {
        $jwt = new JWT($this->secret, -1); // Already expired
        $token = $jwt->generate(['sub' => '123']);
        $verified = $jwt->verify($token);
        
        $this->assertNull($verified);
    }
    
    public function testEmailValidation(): void
    {
        $this->assertEquals('test@example.com', InputValidator::email('test@example.com'));
        $this->assertEquals('test@example.com', InputValidator::email('  test@example.com  '));
        $this->assertNull(InputValidator::email('invalid'));
        $this->assertNull(InputValidator::email(''));
        $this->assertNull(InputValidator::email('test@'));
    }
    
    public function testPasswordValidation(): void
    {
        // Valid password
        $result = InputValidator::password('Secure123!');
        $this->assertTrue($result['valid']);
        
        // Too short
        $result = InputValidator::password('Short1!');
        $this->assertFalse($result['valid']);
        $this->assertContains('Mindestens 8 Zeichen', $result['errors']);
        
        // No uppercase
        $result = InputValidator::password('secure123!');
        $this->assertFalse($result['valid']);
        
        // No number
        $result = InputValidator::password('SecurePass!');
        $this->assertFalse($result['valid']);
        
        // No special char
        $result = InputValidator::password('SecurePass123');
        $this->assertFalse($result['valid']);
    }
    
    public function testXssDetection(): void
    {
        $this->assertTrue(InputValidator::hasXss('<script>alert(1)</script>'));
        $this->assertTrue(InputValidator::hasXss('javascript:void(0)'));
        $this->assertTrue(InputValidator::hasXss('onclick="alert(1)"'));
        $this->assertFalse(InputValidator::hasXss('Normal text'));
    }
    
    public function testSqlInjectionDetection(): void
    {
        $this->assertTrue(InputValidator::hasSqlInjection("'; DROP TABLE users;"));
        $this->assertTrue(InputValidator::hasSqlInjection('SELECT * FROM users'));
        $this->assertTrue(InputValidator::hasSqlInjection('UNION SELECT password'));
        $this->assertFalse(InputValidator::hasSqlInjection('Normal text'));
    }
    
    public function testStringSanitization(): void
    {
        $this->assertEquals('Hello World', InputValidator::string('Hello World'));
        $this->assertEquals('Hello', InputValidator::string('Hello<script>'));
        $this->assertEquals('Hello', InputValidator::string("Hello\0"));
        
        // Max length
        $long = str_repeat('a', 300);
        $this->assertEquals(255, strlen(InputValidator::string($long)));
    }
}
