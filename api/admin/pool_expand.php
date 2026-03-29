<?php
declare(strict_types=1);

require_once __DIR__ . '/../../src/Auth/AuthController.php';
require_once __DIR__ . '/../../src/Database/Database.php';
require_once __DIR__ . '/../../src/Models/Question.php';

use DTZ\Auth\AuthController;
use DTZ\Models\Question;

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

$authHeader = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
$token = '';
if (preg_match('/Bearer\s+(\S+)/', $authHeader, $m)) {
    $token = $m[1];
}

if ($token === '') {
    http_response_code(401);
    echo json_encode(['error' => 'Nicht autorisiert']);
    exit;
}

$auth = new AuthController();
$user = $auth->me($token);
if (!$user || ($user['role'] ?? '') !== 'admin') {
    http_response_code(403);
    echo json_encode(['error' => 'Admin erforderlich']);
    exit;
}

$body = json_decode((string)file_get_contents('php://input'), true);
$minCount = (int)($body['min_count'] ?? 140);
$minCount = max(80, min(400, $minCount));

$modules = ['hoeren', 'lesen', 'schreiben', 'sprechen'];
if (is_array($body['modules'] ?? null) && count($body['modules']) > 0) {
    $allowed = array_flip(['hoeren', 'lesen', 'schreiben', 'sprechen', 'lid']);
    $modules = array_values(array_filter(array_map(static fn($v) => (string)$v, $body['modules']), static fn($m) => isset($allowed[$m])));
    if (!$modules) {
        $modules = ['hoeren', 'lesen', 'schreiben', 'sprechen'];
    }
}

try {
    $questionModel = new Question();
    $results = [];
    $insertedTotal = 0;
    foreach ($modules as $module) {
        $res = $questionModel->ensureModulePool($module, $minCount);
        $insertedTotal += (int)($res['inserted'] ?? 0);
        $results[] = $res;
    }

    echo json_encode([
        'success' => true,
        'inserted_total' => $insertedTotal,
        'min_count' => $minCount,
        'results' => $results,
    ], JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode([
        'error' => 'Pool-Erweiterung fehlgeschlagen',
        'detail' => $e->getMessage(),
    ], JSON_UNESCAPED_UNICODE);
}

