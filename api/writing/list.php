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

try {
    $db = Database::getInstance();
    
    $submissions = $db->select(
        "SELECT 
            id, task_type, task_prompt, original_text, word_count,
            status, submitted_at, approved_at,
            CASE 
                WHEN status = 'approved' THEN final_score 
                ELSE NULL 
            END as score
         FROM writing_submissions 
         WHERE user_id = ?
         ORDER BY submitted_at DESC",
        [$user['id']]
    );
    
    // Map status to user-friendly text
    $statusMap = [
        'pending' => ['text' => 'Warte auf Analyse', 'color' => 'yellow'],
        'ai_processing' => ['text' => 'KI analysiert...', 'color' => 'blue'],
        'ai_reviewed' => ['text' => 'Warte auf Prüfer', 'color' => 'orange'],
        'admin_reviewing' => ['text' => 'Wird geprüft', 'color' => 'purple'],
        'approved' => ['text' => 'Abgeschlossen', 'color' => 'green'],
        'rejected' => ['text' => 'Abgelehnt', 'color' => 'red']
    ];
    
    foreach ($submissions as &$sub) {
        $status = $sub['status'];
        $sub['status_text'] = $statusMap[$status]['text'] ?? $status;
        $sub['status_color'] = $statusMap[$status]['color'] ?? 'gray';
        $sub['can_view_feedback'] = ($status === 'approved');
    }
    
    http_response_code(200);
    echo json_encode([
        'success' => true,
        'submissions' => $submissions,
        'total' => count($submissions)
    ]);
    
} catch (Exception $e) {
    error_log('Writing list error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Fehler beim Laden']);
}
