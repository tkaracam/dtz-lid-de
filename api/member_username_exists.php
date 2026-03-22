<?php
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['error' => 'Nur GET wird unterstützt.'], JSON_UNESCAPED_UNICODE);
    exit;
}

require_once __DIR__ . '/auth.php';

$username = mb_strtolower(trim((string)($_GET['username'] ?? '')));
if ($username === '') {
    http_response_code(400);
    echo json_encode(['error' => 'Benutzername ist erforderlich.'], JSON_UNESCAPED_UNICODE);
    exit;
}

if (!preg_match('/^[a-z0-9._-]{6,32}$/', $username)) {
    http_response_code(400);
    echo json_encode(['error' => 'Benutzername muss 6-32 Zeichen haben (a-z, 0-9, ., _, -).'], JSON_UNESCAPED_UNICODE);
    exit;
}

$exists = false;
$reason = '';

if ($username === 'admin') {
    $exists = true;
    $reason = 'reserviert';
}

if (!$exists && (find_member_user_by_username($username) || find_student_user_by_username($username))) {
    $exists = true;
    $reason = 'bereits_vergeben';
}

if (!$exists) {
    foreach (load_teacher_users() as $teacher) {
        if (!is_array($teacher)) {
            continue;
        }
        if (auth_lower_text((string)($teacher['username'] ?? '')) === $username) {
            $exists = true;
            $reason = 'bereits_vergeben';
            break;
        }
    }
}

echo json_encode([
    'ok' => true,
    'username' => $username,
    'exists' => $exists,
    'reason' => $reason,
], JSON_UNESCAPED_UNICODE);

