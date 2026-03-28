<?php
declare(strict_types=1);

require_once __DIR__ . '/../../src/Auth/AuthController.php';
require_once __DIR__ . '/../../src/Database/Database.php';

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

// Auth check
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

$submissionId = $_GET['id'] ?? 0;

if (!$submissionId) {
    http_response_code(400);
    echo json_encode(['error' => 'ID erforderlich']);
    exit;
}

try {
    $db = Database::getInstance();
    
    // Check if admin - can view any submission
    // Regular user - can only view own submission
    $isAdmin = ($user['role'] === 'admin');
    
    if ($isAdmin) {
        $submission = $db->selectOne(
            "SELECT ws.*, u.display_name as user_name, u.email as user_email
             FROM writing_submissions ws
             JOIN users u ON ws.user_id = u.id
             WHERE ws.id = ?",
            [$submissionId]
        );
    } else {
        $submission = $db->selectOne(
            "SELECT * FROM writing_submissions WHERE id = ? AND user_id = ?",
            [$submissionId, $user['id']]
        );
    }
    
    if (!$submission) {
        http_response_code(404);
        echo json_encode(['error' => 'Einsendung nicht gefunden']);
        exit;
    }
    
    // Decode JSON fields
    if ($submission['ai_feedback']) {
        $submission['ai_feedback'] = json_decode($submission['ai_feedback'], true);
    }
    if ($submission['admin_feedback']) {
        $submission['admin_feedback'] = json_decode($submission['admin_feedback'], true);
    }
    if ($submission['final_feedback']) {
        $submission['final_feedback'] = json_decode($submission['final_feedback'], true);
    }
    
    echo json_encode([
        'success' => true,
        'submission' => $submission
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Fehler beim Laden']);
}
