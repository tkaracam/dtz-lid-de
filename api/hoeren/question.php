<?php
declare(strict_types=1);

require_once __DIR__ . '/../../src/Database/Database.php';
require_once __DIR__ . '/../../src/Auth/JWT.php';
require_once __DIR__ . '/../../src/Models/User.php';
require_once __DIR__ . '/../../src/Auth/AuthController.php';

use DTZ\Auth\AuthController;
use DTZ\Database\Database;

// CORS
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
header('Access-Control-Allow-Methods: GET, OPTIONS');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['error' => 'Nur GET erlaubt']);
    exit;
}

// Auth
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

// Get parameters
$teil = (int) ($_GET['teil'] ?? 1);
$level = $_GET['level'] ?? $user['level'];

// Validate teil (Hören has 4 parts)
if ($teil < 1 || $teil > 4) {
    http_response_code(400);
    echo json_encode(['error' => 'Ungültige Teil-Nummer (1-4)']);
    exit;
}

try {
    $db = Database::getInstance();
    
    // Get random question for this teil
    $question = $db->selectOne(
        "SELECT q.*, a.file_url, a.duration_seconds 
         FROM question_pools q
         LEFT JOIN audio_files a ON q.id = a.question_id
         WHERE q.module = 'hoeren' 
         AND q.teil = ?
         AND q.level = ?
         AND q.is_active = 1
         ORDER BY RANDOM()
         LIMIT 1",
        [$teil, $level]
    );
    
    if (!$question) {
        http_response_code(404);
        echo json_encode(['error' => 'Keine Frage verfügbar']);
        exit;
    }
    
    // Parse content
    $content = json_decode($question['content'], true);
    
    // Hören-specific settings by teil
    $teilConfig = [
        1 => ['maxPlays' => 1, 'timeLimit' => null],      // Teil 1: 1x play
        2 => ['maxPlays' => 2, 'timeLimit' => null],      // Teil 2: 2x play
        3 => ['maxPlays' => 1, 'timeLimit' => null],      // Teil 3: 1x play
        4 => ['maxPlays' => 1, 'timeLimit' => 180],       // Teil 4: 1x + 3min
    ];
    
    $config = $teilConfig[$teil] ?? ['maxPlays' => 2, 'timeLimit' => null];
    
    // Track audio play in session (for play limiting)
    $sessionId = $_GET['session_id'] ?? bin2hex(random_bytes(16));
    
    http_response_code(200);
    echo json_encode([
        'success' => true,
        'question' => [
            'id' => $question['id'],
            'teil' => $teil,
            'level' => $question['level'],
            'text' => $content['text'] ?? '',
            'question' => $content['question'] ?? '',
            'options' => $content['options'] ?? [],
            'points' => $question['points']
        ],
        'audio' => [
            'url' => $question['file_url'],
            'duration' => (int) ($question['duration_seconds'] ?? 60),
            'max_plays' => $config['maxPlays'],
            'plays_remaining' => $config['maxPlays']
        ],
        'config' => [
            'time_limit_seconds' => $config['timeLimit'],
            'session_id' => $sessionId
        ]
    ]);
    
} catch (Exception $e) {
    error_log('Hören question error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Fehler beim Laden']);
}
