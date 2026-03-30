<?php
declare(strict_types=1);

/**
 * Speech Recording Upload Endpoint
 * Handles audio file uploads for speaking practice
 */

require_once __DIR__ . '/../../src/Auth/AuthController.php';
require_once __DIR__ . '/../../src/Database/Database.php';

use DTZ\Auth\AuthController;
use DTZ\Database\Database;

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

// Validate input
$taskId = $_POST['task_id'] ?? '';
$teil = $_POST['teil'] ?? '';
$duration = (int)($_POST['duration'] ?? 0);

if (empty($taskId) || empty($teil)) {
    http_response_code(400);
    echo json_encode(['error' => 'Aufgaben-ID und Teil erforderlich']);
    exit;
}

// Check file upload
if (!isset($_FILES['audio']) || $_FILES['audio']['error'] !== UPLOAD_ERR_OK) {
    http_response_code(400);
    echo json_encode(['error' => 'Audio-Datei erforderlich', 'upload_error' => $_FILES['audio']['error'] ?? 'Keine Datei']);
    exit;
}

$uploadedFile = $_FILES['audio'];

// Validate file type
$allowedTypes = ['audio/webm', 'audio/mp4', 'audio/mpeg', 'audio/wav', 'audio/ogg', 'audio/x-m4a'];
$fileInfo = finfo_open(FILEINFO_MIME_TYPE);
$mimeType = finfo_file($fileInfo, $uploadedFile['tmp_name']);
finfo_close($fileInfo);

if (!in_array($mimeType, $allowedTypes) && !str_starts_with($mimeType, 'audio/')) {
    http_response_code(400);
    echo json_encode(['error' => 'Ungültiges Dateiformat. Erlaubt: WebM, MP4, MP3, WAV, OGG, M4A', 'mime_type' => $mimeType]);
    exit;
}

// Validate file size (max 50MB)
$maxSize = 50 * 1024 * 1024;
if ($uploadedFile['size'] > $maxSize) {
    http_response_code(400);
    echo json_encode(['error' => 'Datei zu groß. Maximum: 50MB']);
    exit;
}

try {
    $db = Database::getInstance();
    
    // Generate unique filename
    $fileExt = pathinfo($uploadedFile['name'], PATHINFO_EXTENSION) ?: 'webm';
    $filename = sprintf(
        'user_%d_%s_teil%s_%s.%s',
        $user['id'],
        date('Y-m-d_H-i-s'),
        $teil,
        bin2hex(random_bytes(4)),
        $fileExt
    );
    
    $storagePath = __DIR__ . '/../../storage/speaking/' . $filename;
    $relativePath = 'storage/speaking/' . $filename;
    
    // Move uploaded file
    if (!move_uploaded_file($uploadedFile['tmp_name'], $storagePath)) {
        throw new Exception('Datei konnte nicht gespeichert werden');
    }
    
    // Save to database
    $submissionId = $db->insert('speaking_submissions', [
        'user_id' => $user['id'],
        'task_id' => $taskId,
        'teil' => $teil,
        'audio_path' => $relativePath,
        'original_filename' => $uploadedFile['name'],
        'mime_type' => $mimeType,
        'file_size' => $uploadedFile['size'],
        'duration_seconds' => $duration,
        'status' => 'uploaded',
        'created_at' => date('Y-m-d H:i:s')
    ]);
    
    echo json_encode([
        'success' => true,
        'submission_id' => (int)$submissionId,
        'message' => 'Aufnahme erfolgreich gespeichert',
        'status' => 'pending_transcription'
    ]);
    
} catch (Exception $e) {
    error_log('Speech upload error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Fehler beim Speichern: ' . $e->getMessage()]);
}
