<?php
/**
 * Bulk Import DTZ Questions - Admin only
 * dtz_realistic_questions.sql dosyasındaki soruları canlı siteye ekler
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

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

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

try {
    $jwt = new JWT();
    $payload = $jwt->decode($token);
    
    if (!$payload || ($payload['role'] ?? '') !== 'admin') {
        http_response_code(403);
        echo json_encode(['error' => 'Admin yetkisi gerekli']);
        exit;
    }
    
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (empty($input['questions']) || !is_array($input['questions'])) {
        http_response_code(400);
        echo json_encode(['error' => 'Soru listesi gerekli']);
        exit;
    }
    
    $db = Database::getInstance();
    $pdo = $db->getPdo();
    
    $stmt = $pdo->prepare("INSERT OR IGNORE INTO question_pools 
        (module, teil, level, question_type, content, correct_answer, explanation, difficulty, points) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
    
    $added = 0;
    $skipped = 0;
    $errors = [];
    
    foreach ($input['questions'] as $index => $q) {
        try {
            $result = $stmt->execute([
                $q['module'],
                intval($q['teil']),
                $q['level'],
                $q['question_type'],
                is_array($q['content']) ? json_encode($q['content'], JSON_UNESCAPED_UNICODE) : $q['content'],
                is_array($q['correct_answer']) ? json_encode($q['correct_answer'], JSON_UNESCAPED_UNICODE) : $q['correct_answer'],
                $q['explanation'] ?? '',
                intval($q['difficulty'] ?? 3),
                intval($q['points'] ?? 10)
            ]);
            
            if ($stmt->rowCount() > 0) {
                $added++;
            } else {
                $skipped++;
            }
        } catch (Exception $e) {
            $errors[] = "Soru $index: " . $e->getMessage();
        }
    }
    
    $total = $pdo->query("SELECT COUNT(*) FROM question_pools WHERE is_active = 1")->fetchColumn();
    
    echo json_encode([
        'success' => true,
        'added' => $added,
        'skipped' => $skipped,
        'errors' => $errors,
        'total_questions' => intval($total),
        'message' => "$added soru eklendi, $skipped soru zaten vardı (atlandı)"
    ]);
    
} catch (Exception $e) {
    error_log('Bulk import error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Hata: ' . $e->getMessage()]);
}
