<?php
declare(strict_types=1);

require_once __DIR__ . '/../../src/Auth/AuthController.php';
require_once __DIR__ . '/../../src/Database/Database.php';
require_once __DIR__ . '/../../src/Services/AzureTTSService.php';

use DTZ\Auth\AuthController;
use DTZ\Database\Database;
use DTZ\Services\AzureTTSService;

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
header('Access-Control-Allow-Methods: POST, OPTIONS');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Nur POST erlaubt']);
    exit;
}

// Auth check
$authHeader = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
$token = '';
if (preg_match('/Bearer\s+(\S+)/', $authHeader, $matches)) {
    $token = $matches[1];
}

if (empty($token)) {
    http_response_code(401);
    echo json_encode(['error' => 'Nicht autorisiert']);
    exit;
}

$auth = new AuthController();
$user = $auth->me($token);

if (!$user) {
    http_response_code(401);
    echo json_encode(['error' => 'Token ungültig']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$text = $input['text'] ?? '';
$scenario = $input['scenario'] ?? 'default';
$teil = $input['teil'] ?? null;

if (empty($text)) {
    http_response_code(400);
    echo json_encode(['error' => 'Text erforderlich']);
    exit;
}

// Check if audio already exists (simple caching)
try {
    $db = Database::getInstance();
    
    $textHash = md5($text . $scenario);
    
    // Check cache
    $existing = $db->selectOne(
        "SELECT id, file_path FROM audio_files WHERE text_hash = ?",
        [$textHash]
    );
    
    if ($existing) {
        // Return cached audio
        $audioPath = __DIR__ . '/../../storage/audio/' . $existing['file_path'];
        if (file_exists($audioPath)) {
            $audioData = file_get_contents($audioPath);
            echo json_encode([
                'success' => true,
                'audio' => base64_encode($audioData),
                'format' => 'audio/mp3',
                'cached' => true
            ]);
            exit;
        }
    }
    
    // Generate new audio
    $apiKey = $_ENV['AZURE_TTS_KEY'] ?? '';
    $region = $_ENV['AZURE_TTS_REGION'] ?? 'westeurope';
    
    // Demo mode if no key
    $demoMode = empty($apiKey) || $apiKey === 'your-azure-key';
    
    if ($demoMode) {
        // Return demo audio URL (could be a placeholder or pre-generated sample)
        echo json_encode([
            'success' => true,
            'demo' => true,
            'message' => 'Demo mode - Azure key not configured',
            'text' => $text,
            'scenario' => $scenario
        ]);
        exit;
    }
    
    $tts = new AzureTTSService($apiKey, $region);
    
    if ($teil !== null) {
        $result = $tts->generateForHoeren((int)$teil, $text);
    } else {
        $result = $tts->generateSpeech($text, $scenario);
    }
    
    if (!$result['success']) {
        http_response_code(500);
        echo json_encode(['error' => $result['error'] ?? 'TTS failed']);
        exit;
    }
    
    // Save to storage
    $storageDir = __DIR__ . '/../../storage/audio';
    if (!is_dir($storageDir)) {
        mkdir($storageDir, 0755, true);
    }
    
    $filename = uniqid('audio_') . '.mp3';
    $filepath = $storageDir . '/' . $filename;
    file_put_contents($filepath, base64_decode($result['audio']));
    
    // Save to database
    $db->insert('audio_files', [
        'text_hash' => $textHash,
        'text_content' => $text,
        'voice_id' => $result['voice'],
        'scenario' => $result['scenario'],
        'file_path' => $filename,
        'file_size_bytes' => filesize($filepath)
    ]);
    
    echo json_encode([
        'success' => true,
        'audio' => $result['audio'],
        'format' => $result['format'],
        'voice' => $result['voice'],
        'cached' => false
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Fehler: ' . $e->getMessage()]);
}
