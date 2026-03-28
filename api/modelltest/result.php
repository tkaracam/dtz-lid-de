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
    
    // Check if completed
    if ($attempt['status'] !== 'completed') {
        http_response_code(403);
        echo json_encode(['error' => 'Test noch nicht abgeschlossen']);
        exit;
    }
    
    $answers = json_decode($attempt['answers'], true);
    
    // Build detailed analysis
    $analysis = [
        'overall' => [
            'total_score' => $attempt['total_score'],
            'max_score' => $attempt['max_possible_score'],
            'percentage' => round(($attempt['total_score'] / $attempt['max_possible_score']) * 100, 1),
            'estimated_level' => $attempt['estimated_level'],
            'passed' => $attempt['passed'],
            'completed_at' => $attempt['completed_at']
        ],
        'modules' => [
            'hoeren' => [
                'score' => $attempt['hoeren_score'],
                'max' => 25,
                'percentage' => round(($attempt['hoeren_score'] / 25) * 100, 1),
                'strength' => ($attempt['hoeren_score'] / 25) >= 0.6 ? 'good' : 'weak'
            ],
            'lesen' => [
                'score' => $attempt['lesen_score'],
                'max' => 45,
                'percentage' => round(($attempt['lesen_score'] / 45) * 100, 1),
                'strength' => ($attempt['lesen_score'] / 45) >= 0.6 ? 'good' : 'weak'
            ],
            'schreiben' => [
                'score' => $attempt['schreiben_score'],
                'max' => 20,
                'percentage' => round(($attempt['schreiben_score'] / 20) * 100, 1),
                'strength' => ($attempt['schreiben_score'] / 20) >= 0.6 ? 'good' : 'weak'
            ],
            'sprechen' => [
                'score' => $attempt['sprechen_score'],
                'max' => 20,
                'percentage' => round(($attempt['sprechen_score'] / 20) * 100, 1),
                'strength' => ($attempt['sprechen_score'] / 20) >= 0.6 ? 'good' : 'weak'
            ]
        ],
        'recommendations' => generateRecommendations($attempt)
    ];
    
    echo json_encode([
        'success' => true,
        'result' => $analysis
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Fehler beim Laden']);
}

function generateRecommendations($attempt) {
    $recs = [];
    
    $modules = [
        'hoeren' => ['name' => 'Hören', 'score' => $attempt['hoeren_score'], 'max' => 25],
        'lesen' => ['name' => 'Lesen', 'score' => $attempt['lesen_score'], 'max' => 45],
        'schreiben' => ['name' => 'Schreiben', 'score' => $attempt['schreiben_score'], 'max' => 20],
        'sprechen' => ['name' => 'Sprechen', 'score' => $attempt['sprechen_score'], 'max' => 20]
    ];
    
    // Find weakest module
    $weakest = null;
    $lowestPct = 100;
    
    foreach ($modules as $key => $mod) {
        $pct = ($mod['score'] / $mod['max']) * 100;
        if ($pct < $lowestPct) {
            $lowestPct = $pct;
            $weakest = $key;
        }
    }
    
    if ($weakest && $lowestPct < 60) {
        $recs[] = [
            'priority' => 'high',
            'module' => $modules[$weakest]['name'],
            'message' => "Fokussieren Sie sich auf {$modules[$weakest]['name']}. Dies ist Ihr schwächster Bereich."
        ];
    }
    
    if ($attempt['passed']) {
        $recs[] = [
            'priority' => 'info',
            'message' => 'Gute Arbeit! Sie sind auf B1-Niveau. Üben Sie weiter für die echte Prüfung.'
        ];
    } else {
        $recs[] = [
            'priority' => 'high',
            'message' => 'Sie sind auf A2-Niveau. Empfohlen: Weiterer Kurs vor der Prüfung.'
        ];
    }
    
    return $recs;
}
