<?php
/**
 * Get questions for learning/practice
 */

require_once __DIR__ . '/../src/Database/Database.php';

use DTZ\Database\Database;

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
    $db = Database::getInstance();
    
    // Build query
    $sql = "SELECT id, module, teil, level, question_type, content, correct_answer, explanation, difficulty, points 
            FROM question_pools 
            WHERE module = ? AND is_active = 1";
    $params = [$module];
    
    if (!empty($teil)) {
        $sql .= " AND teil = ?";
        $params[] = $teil;
    }
    
    $sql .= " ORDER BY RANDOM() LIMIT ?";
    $params[] = $limit;
    
    $questions = $db->select($sql, $params);
    
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
