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

$input = json_decode(file_get_contents('php://input'), true);
$attemptId = $input['attempt_id'] ?? '';
$module = $input['module'] ?? ''; // hoeren, lesen, schreiben, sprechen
$answers = $input['answers'] ?? [];

if (empty($attemptId) || empty($module)) {
    http_response_code(400);
    echo json_encode(['error' => 'Fehlende Daten']);
    exit;
}

try {
    $db = Database::getInstance();
    
    // Get current attempt
    $attempt = $db->selectOne(
        "SELECT * FROM modelltest_attempts WHERE id = ? AND user_id = ? AND status = 'in_progress'",
        [$attemptId, $user['id']]
    );
    
    if (!$attempt) {
        http_response_code(404);
        echo json_encode(['error' => 'Test nicht gefunden']);
        exit;
    }
    
    // Check time limit
    $endTimeField = $module . '_end_time';
    if (strtotime($attempt[$endTimeField]) < time()) {
        http_response_code(403);
        echo json_encode(['error' => 'Zeit abgelaufen für diesen Teil']);
        exit;
    }
    
    // Save answers
    $currentAnswers = json_decode($attempt['answers'], true);
    $currentAnswers[$module] = $answers;
    
    // Calculate score for this module
    $score = calculateModuleScore($db, $module, $answers);
    
    // Determine next module
    $moduleOrder = ['hoeren', 'lesen', 'schreiben', 'sprechen'];
    $currentIndex = array_search($module, $moduleOrder);
    $nextModule = $moduleOrder[$currentIndex + 1] ?? null;
    
    $updateData = [
        'answers' => json_encode($currentAnswers),
        $module . '_score' => $score
    ];
    
    if ($nextModule) {
        $updateData['current_module'] = $nextModule;
    } else {
        // Last module completed - finish test
        $updateData['status'] = 'completed';
        $updateData['completed_at'] = date('Y-m-d H:i:s');
        
        // Calculate total
        $totalScore = ($attempt['hoeren_score'] ?? 0) + 
                      ($attempt['lesen_score'] ?? 0) + 
                      ($attempt['schreiben_score'] ?? 0) + 
                      ($attempt['sprechen_score'] ?? 0) + 
                      $score;
        
        $updateData['total_score'] = $totalScore;
        
        // Estimate level
        $maxScore = 100; // Adjust based on your scoring
        $percentage = ($totalScore / $maxScore) * 100;
        
        if ($percentage >= 60) {
            $updateData['estimated_level'] = 'B1';
            $updateData['passed'] = true;
        } else {
            $updateData['estimated_level'] = 'A2';
            $updateData['passed'] = false;
        }
    }
    
    $db->update('modelltest_attempts', $updateData, 'id = ?', [$attemptId]);
    
    echo json_encode([
        'success' => true,
        'module_score' => $score,
        'next_module' => $nextModule,
        'completed' => $nextModule === null
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Fehler beim Speichern']);
}

function calculateModuleScore($db, $module, $answers) {
    if (!is_array($answers)) return 0;
    
    $score = 0;
    
    if ($module === 'hoeren' || $module === 'lesen') {
        // Check answers against correct answers
        foreach ($answers as $questionId => $userAnswer) {
            $question = $db->selectOne(
                "SELECT points, correct_answer FROM question_pools WHERE id = ?",
                [$questionId]
            );
            
            if ($question) {
                $correct = json_decode($question['correct_answer'], true);
                if (strtoupper($userAnswer) === strtoupper($correct['answer'] ?? '')) {
                    $score += ($question['points'] ?? 5);
                }
            }
        }
    } elseif ($module === 'schreiben') {
        // Writing is manually graded or AI scored
        // Return 0 for now, will be updated later
        $score = 0;
    }
    
    return $score;
}
