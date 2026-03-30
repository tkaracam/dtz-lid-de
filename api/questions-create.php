<?php
/**
 * Create new question - Admin only
 */

require_once __DIR__ . '/../src/Database/Database.php';
require_once __DIR__ . '/../src/Auth/JWT.php';

use DTZ\Database\Database;
use DTZ\Auth\JWT;

// CORS headers
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
header('Access-Control-Allow-Methods: POST, OPTIONS');

// Handle preflight
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

// Only allow POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Nur POST erlaubt']);
    exit;
}

// Get and verify token
$authHeader = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
$token = '';

if (preg_match('/Bearer\s+(\S+)/', $authHeader, $matches)) {
    $token = $matches[1];
}

if (empty($token)) {
    http_response_code(401);
    echo json_encode(['error' => 'Token erforderlich']);
    exit;
}

// Get JWT secret
$jwtSecret = $_ENV['JWT_SECRET'] ?? null;
if (!$jwtSecret) {
    $secretFile = __DIR__ . '/../.jwt_secret';
    if (file_exists($secretFile)) {
        $jwtSecret = trim(file_get_contents($secretFile));
    } else {
        $jwtSecret = 'dtz-learning-secret-key-change-in-production';
    }
}

try {
    // Verify token
    $jwt = new JWT($jwtSecret);
    $payload = $jwt->verify($token);
    
    if (!$payload) {
        http_response_code(401);
        echo json_encode(['error' => 'Ungültiger Token']);
        exit;
    }
    
    // Check if admin
    if (($payload['role'] ?? '') !== 'admin' && ($payload['role'] ?? '') !== 'owner') {
        http_response_code(403);
        echo json_encode(['error' => 'Nur Admin-Benutzer dürfen Fragen erstellen']);
        exit;
    }
    
    $adminId = $payload['sub'] ?? null;
    
    // Get POST data
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input) {
        http_response_code(400);
        echo json_encode(['error' => 'Ungültige JSON-Daten']);
        exit;
    }
    
    // Validate required fields
    $required = ['module', 'teil', 'level', 'question_type', 'content', 'correct_answer'];
    foreach ($required as $field) {
        if (empty($input[$field])) {
            http_response_code(400);
            echo json_encode(['error' => "Feld '$field' ist erforderlich"]);
            exit;
        }
    }
    
    // Validate module
    $validModules = ['lesen', 'hoeren', 'schreiben', 'sprechen', 'lid'];
    if (!in_array($input['module'], $validModules)) {
        http_response_code(400);
        echo json_encode(['error' => 'Ungültiges Modul. Erlaubt: ' . implode(', ', $validModules)]);
        exit;
    }
    
    // Validate teil
    $teil = intval($input['teil']);
    if ($teil < 1 || $teil > 5) {
        http_response_code(400);
        echo json_encode(['error' => 'Teil muss zwischen 1 und 5 liegen']);
        exit;
    }
    
    // Validate level
    $validLevels = ['A1', 'A2', 'B1', 'B2'];
    if (!in_array($input['level'], $validLevels)) {
        http_response_code(400);
        echo json_encode(['error' => 'Ungültiges Level. Erlaubt: ' . implode(', ', $validLevels)]);
        exit;
    }
    
    // Validate question_type
    $validTypes = ['multiple_choice', 'text_input', 'matching', 'ordering', 'audio'];
    if (!in_array($input['question_type'], $validTypes)) {
        http_response_code(400);
        echo json_encode(['error' => 'Ungültiger Fragetyp. Erlaubt: ' . implode(', ', $validTypes)]);
        exit;
    }
    
    // Prepare content
    $content = $input['content'];
    if (is_array($content)) {
        $content = json_encode($content, JSON_UNESCAPED_UNICODE);
    }
    
    // Prepare correct_answer
    $correctAnswer = $input['correct_answer'];
    if (is_array($correctAnswer)) {
        $correctAnswer = json_encode($correctAnswer, JSON_UNESCAPED_UNICODE);
    }
    
    // Connect to database
    $db = Database::getInstance();
    $pdo = $db->getPdo();
    
    // Insert question
    $stmt = $pdo->prepare("INSERT INTO question_pools 
        (module, teil, level, question_type, content, media_urls, correct_answer, explanation, hints, difficulty, points, is_premium_only, created_by) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    
    $result = $stmt->execute([
        $input['module'],
        $teil,
        $input['level'],
        $input['question_type'],
        $content,
        $input['media_urls'] ?? null,
        $correctAnswer,
        $input['explanation'] ?? null,
        $input['hints'] ?? null,
        $input['difficulty'] ?? 3,
        $input['points'] ?? 10,
        $input['is_premium_only'] ?? 0,
        $adminId
    ]);
    
    if (!$result) {
        http_response_code(500);
        echo json_encode(['error' => 'Fehler beim Speichern der Frage']);
        exit;
    }
    
    $questionId = $pdo->lastInsertId();
    
    // Get total count
    $totalCount = $pdo->query("SELECT COUNT(*) FROM question_pools WHERE is_active = 1")->fetchColumn();
    
    http_response_code(201);
    echo json_encode([
        'success' => true,
        'message' => 'Frage erfolgreich erstellt',
        'question_id' => $questionId,
        'total_questions' => intval($totalCount),
        'question' => [
            'id' => $questionId,
            'module' => $input['module'],
            'teil' => $teil,
            'level' => $input['level'],
            'question_type' => $input['question_type'],
            'difficulty' => $input['difficulty'] ?? 3,
            'points' => $input['points'] ?? 10
        ]
    ]);
    
} catch (Exception $e) {
    error_log('Question create error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Ein Fehler ist aufgetreten: ' . $e->getMessage()]);
}
