<?php
/**
 * Delete (soft delete) question - Admin only
 */

require_once __DIR__ . '/../src/Database/Database.php';
require_once __DIR__ . '/../src/Auth/JWT.php';

use DTZ\Database\Database;
use DTZ\Auth\JWT;

// CORS headers
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
header('Access-Control-Allow-Methods: POST, DELETE, OPTIONS');

// Handle preflight
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

// Only allow POST or DELETE
if ($_SERVER['REQUEST_METHOD'] !== 'POST' && $_SERVER['REQUEST_METHOD'] !== 'DELETE') {
    http_response_code(405);
    echo json_encode(['error' => 'Nur POST oder DELETE erlaubt']);
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
        echo json_encode(['error' => 'Nur Admin-Benutzer dürfen Fragen löschen']);
        exit;
    }
    
    // Get question ID
    $input = json_decode(file_get_contents('php://input'), true);
    $questionId = $input['id'] ?? $_GET['id'] ?? '';
    
    if (empty($questionId)) {
        http_response_code(400);
        echo json_encode(['error' => 'Frage-ID ist erforderlich']);
        exit;
    }
    
    // Connect to database
    $db = Database::getInstance();
    $pdo = $db->getPdo();
    
    // Check if question exists
    $checkStmt = $pdo->prepare("SELECT id FROM question_pools WHERE id = ? AND is_active = 1");
    $checkStmt->execute([$questionId]);
    
    if (!$checkStmt->fetch()) {
        http_response_code(404);
        echo json_encode(['error' => 'Frage nicht gefunden']);
        exit;
    }
    
    // Soft delete (set is_active = 0)
    $stmt = $pdo->prepare("UPDATE question_pools SET is_active = 0, updated_at = CURRENT_TIMESTAMP WHERE id = ?");
    $result = $stmt->execute([$questionId]);
    
    if (!$result) {
        http_response_code(500);
        echo json_encode(['error' => 'Fehler beim Löschen der Frage']);
        exit;
    }
    
    // Get new total count
    $totalCount = $pdo->query("SELECT COUNT(*) FROM question_pools WHERE is_active = 1")->fetchColumn();
    
    echo json_encode([
        'success' => true,
        'message' => 'Frage erfolgreich gelöscht',
        'question_id' => intval($questionId),
        'total_questions' => intval($totalCount)
    ]);
    
} catch (Exception $e) {
    error_log('Question delete error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Ein Fehler ist aufgetreten: ' . $e->getMessage()]);
}
