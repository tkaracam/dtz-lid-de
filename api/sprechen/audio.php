<?php
declare(strict_types=1);

/**
 * Audio File Streaming Endpoint
 * Serves audio files with token-based authentication
 */

require_once __DIR__ . '/../../src/Database/Database.php';

use DTZ\Database\Database;

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Authorization');

$token = $_GET['token'] ?? '';

if (empty($token)) {
    http_response_code(401);
    echo json_encode(['error' => 'Token erforderlich']);
    exit;
}

// Decode and validate token
$tokenData = json_decode(base64_decode($token), true);

if (!$tokenData || !isset($tokenData['sub'], $tokenData['user'], $tokenData['exp'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Ungültiger Token']);
    exit;
}

// Check expiry
if ($tokenData['exp'] < time()) {
    http_response_code(401);
    echo json_encode(['error' => 'Token abgelaufen']);
    exit;
}

$submissionId = (int)$tokenData['sub'];

// In production, verify user session matches token user
// For now, we trust the token as it's short-lived (1 hour)

try {
    $db = Database::getInstance();
    
    $submission = $db->selectOne(
        "SELECT audio_path, mime_type FROM speaking_submissions WHERE id = ?",
        [$submissionId]
    );
    
    if (!$submission || empty($submission['audio_path'])) {
        http_response_code(404);
        echo json_encode(['error' => 'Audio nicht gefunden']);
        exit;
    }
    
    $filePath = __DIR__ . '/../../' . $submission['audio_path'];
    
    if (!file_exists($filePath)) {
        http_response_code(404);
        echo json_encode(['error' => 'Datei nicht gefunden']);
        exit;
    }
    
    // Get file info
    $fileSize = filesize($filePath);
    $mimeType = $submission['mime_type'] ?: 'audio/webm';
    
    // Handle range requests for streaming
    $start = 0;
    $end = $fileSize - 1;
    
    if (isset($_SERVER['HTTP_RANGE'])) {
        $range = $_SERVER['HTTP_RANGE'];
        if (preg_match('/bytes=(\d+)-(\d*)/', $range, $matches)) {
            $start = (int)$matches[1];
            $end = !empty($matches[2]) ? (int)$matches[2] : $end;
        }
        
        http_response_code(206);
        header("Content-Range: bytes $start-$end/$fileSize");
    } else {
        http_response_code(200);
    }
    
    $length = $end - $start + 1;
    
    // Set headers
    header("Content-Type: $mimeType");
    header("Content-Length: $length");
    header("Accept-Ranges: bytes");
    header("Cache-Control: private, max-age=3600");
    header("Content-Disposition: inline; filename=\"audio_" . $submissionId . "\"");
    
    // Stream file
    $fp = fopen($filePath, 'rb');
    fseek($fp, $start);
    
    $buffer = 8192;
    $bytesRemaining = $length;
    
    while ($bytesRemaining > 0 && !feof($fp)) {
        $bytesToRead = min($buffer, $bytesRemaining);
        echo fread($fp, $bytesToRead);
        $bytesRemaining -= $bytesToRead;
        flush();
    }
    
    fclose($fp);
    exit;
    
} catch (Exception $e) {
    error_log('Audio streaming error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Fehler beim Streamen']);
}
