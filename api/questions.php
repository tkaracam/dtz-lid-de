<?php
/**
 * Get questions for learning/practice
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');

$module = $_GET['module'] ?? '';
$teil = $_GET['teil'] ?? '';
$level = $_GET['level'] ?? 'A2';
$limit = intval($_GET['limit'] ?? 10);

if (empty($module)) {
    http_response_code(400);
    echo json_encode(['error' => 'Module required']);
    exit;
}

try {
    // Direct database connection for reliability
    $dbPath = '/var/www/html/database/dtz_production.db';
    
    // Fallback to local path if not found
    if (!file_exists($dbPath)) {
        $dbPath = __DIR__ . '/../database/dtz_learning.db';
    }
    
    $pdo = new PDO("sqlite:$dbPath", null, null, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
    
    // Build query
    $sql = "SELECT id, module, teil, level, question_type, content, correct_answer, explanation, difficulty, points 
            FROM question_pools 
            WHERE module = ?";
    $params = [$module];
    
    if (!empty($teil)) {
        $sql .= " AND teil = ?";
        $params[] = $teil;
    }
    
    $sql .= " ORDER BY RANDOM() LIMIT ?";
    $params[] = $limit;
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $questions = $stmt->fetchAll();
    
    // Parse JSON content
    foreach ($questions as &$q) {
        $q['content'] = json_decode($q['content'], true);
        $q['correct_answer'] = json_decode($q['correct_answer'], true);
    }
    
    echo json_encode([
        'success' => true,
        'module' => $module,
        'teil' => $teil,
        'count' => count($questions),
        'questions' => $questions
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
