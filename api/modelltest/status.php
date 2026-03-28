<?php
declare(strict_types=1);

require_once __DIR__ . '/../../src/Database/Database.php';
require_once __DIR__ . '/../../src/Auth/JWT.php';
require_once __DIR__ . '/../../src/Models/User.php';
require_once __DIR__ . '/../../src/Auth/AuthController.php';

use DTZ\Auth\AuthController;
use DTZ\Database\Database;

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

$attemptId = $_GET['id'] ?? '';

if (empty($attemptId)) {
    http_response_code(400);
    echo json_encode(['error' => 'ID erforderlich']);
    exit;
}

try {
    $db = Database::getInstance();
    
    $attempt = $db->selectOne(
        "SELECT * FROM modelltest_attempts WHERE id = ? AND user_id = ?",
        [$attemptId, $user['id']]
    );
    
    if (!$attempt) {
        http_response_code(404);
        echo json_encode(['error' => 'Test nicht gefunden']);
        exit;
    }
    
    $now = new DateTime();
    $currentModule = $attempt['current_module'];
    
    // Check time for current module
    $endTimeField = $currentModule . '_end_time';
    $moduleEnd = new DateTime($attempt[$endTimeField]);
    $timeRemaining = max(0, $moduleEnd->getTimestamp() - $now->getTimestamp());
    
    // If time expired and still in progress, auto-complete this module
    if ($timeRemaining <= 0 && $attempt['status'] === 'in_progress') {
        // Auto-advance or complete
        $moduleOrder = ['hoeren', 'lesen', 'schreiben', 'sprechen'];
        $currentIndex = array_search($currentModule, $moduleOrder);
        $nextModule = $moduleOrder[$currentIndex + 1] ?? null;
        
        if ($nextModule) {
            $db->update('modelltest_attempts', [
                'current_module' => $nextModule
            ], 'id = ?', [$attemptId]);
            $currentModule = $nextModule;
            $endTimeField = $currentModule . '_end_time';
            $moduleEnd = new DateTime($attempt[$endTimeField]);
            $timeRemaining = max(0, $moduleEnd->getTimestamp() - $now->getTimestamp());
        } else {
            // Complete test
            $totalScore = ($attempt['hoeren_score'] ?? 0) + 
                         ($attempt['lesen_score'] ?? 0) + 
                         ($attempt['schreiben_score'] ?? 0) + 
                         ($attempt['sprechen_score'] ?? 0);
            
            $passed = $totalScore >= ($attempt['max_possible_score'] * 0.6);
            $level = $passed ? 'B1' : 'A2';
            
            $db->update('modelltest_attempts', [
                'status' => 'completed',
                'completed_at' => $now->format('Y-m-d H:i:s'),
                'total_score' => $totalScore,
                'passed' => $passed,
                'estimated_level' => $level
            ], 'id = ?', [$attemptId]);
            
            $attempt['status'] = 'completed';
        }
    }
    
    $questions = json_decode($attempt['questions'], true);
    
    // Only return questions for current module
    $currentQuestions = [];
    if ($attempt['status'] === 'in_progress') {
        $currentQuestions = $questions[$currentModule] ?? [];
    }
    
    echo json_encode([
        'success' => true,
        'attempt' => [
            'id' => $attempt['id'],
            'status' => $attempt['status'],
            'current_module' => $currentModule,
            'time_remaining_seconds' => $timeRemaining,
            'module_end_time' => $attempt[$endTimeField],
            'started_at' => $attempt['started_at'],
            'completed_at' => $attempt['completed_at']
        ],
        'questions' => $currentQuestions,
        'progress' => [
            'hoeren' => [
                'completed' => $attempt['hoeren_score'] !== null,
                'score' => $attempt['hoeren_score']
            ],
            'lesen' => [
                'completed' => $attempt['lesen_score'] !== null,
                'score' => $attempt['lesen_score']
            ],
            'schreiben' => [
                'completed' => $attempt['schreiben_score'] !== null,
                'score' => $attempt['schreiben_score']
            ],
            'sprechen' => [
                'completed' => $attempt['sprechen_score'] !== null,
                'score' => $attempt['sprechen_score']
            ]
        ]
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Fehler beim Laden']);
}
