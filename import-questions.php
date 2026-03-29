<?php
/**
 * Import DTZ Questions - Secret based auth
 * POST { "secret": "dtz2024", "questions": [...] }
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Content-Type');
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

$input = json_decode(file_get_contents('php://input'), true);

// Secret check
if (($input['secret'] ?? '') !== 'dtz2024') {
    http_response_code(403);
    echo json_encode(['error' => 'Invalid secret']);
    exit;
}

if (empty($input['questions']) || !is_array($input['questions'])) {
    http_response_code(400);
    echo json_encode(['error' => 'questions array required']);
    exit;
}

// Database connection
try {
    $dbPath = '/var/www/html/database/dtz_production.db';
    if (!file_exists($dbPath)) {
        $dbPath = __DIR__ . '/database/dtz_learning.db';
    }
    
    $pdo = new PDO("sqlite:$dbPath", null, null, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    ]);
    
    $stmt = $pdo->prepare("INSERT OR IGNORE INTO question_pools 
        (module, teil, level, question_type, content, correct_answer, explanation, difficulty, points) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
    
    $added = 0;
    $skipped = 0;
    
    foreach ($input['questions'] as $q) {
        $content = is_array($q['content']) ? json_encode($q['content'], JSON_UNESCAPED_UNICODE) : $q['content'];
        $correct = is_array($q['correct_answer']) ? json_encode($q['correct_answer'], JSON_UNESCAPED_UNICODE) : $q['correct_answer'];
        
        $stmt->execute([
            $q['module'],
            intval($q['teil']),
            $q['level'],
            $q['question_type'],
            $content,
            $correct,
            $q['explanation'] ?? '',
            intval($q['difficulty'] ?? 3),
            intval($q['points'] ?? 10)
        ]);
        
        if ($stmt->rowCount() > 0) {
            $added++;
        } else {
            $skipped++;
        }
    }
    
    $total = $pdo->query("SELECT COUNT(*) FROM question_pools WHERE is_active = 1")->fetchColumn();
    
    echo json_encode([
        'success' => true,
        'added' => $added,
        'skipped' => $skipped,
        'total_questions' => intval($total)
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
