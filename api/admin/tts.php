<?php
declare(strict_types=1);

// Include auth check
require_once __DIR__ . '/auth.php';

require_once __DIR__ . '/../../src/Services/AzureTTSService.php';

use DTZ\Services\AzureTTSService;

$method = $_SERVER['REQUEST_METHOD'];

try {
    switch ($method) {
        case 'POST':
            // Generate audio
            $input = json_decode(file_get_contents('php://input'), true);
            
            if (empty($input['text'])) {
                http_response_code(400);
                echo json_encode(['error' => 'Text erforderlich']);
                exit;
            }
            
            $text = $input['text'];
            $scenario = $input['scenario'] ?? 'conversation';
            $questionId = $input['question_id'] ?? null;
            
            $tts = new AzureTTSService();
            
            if ($questionId) {
                // Generate and link to question
                $result = $tts->generateForQuestion($questionId, $text, $scenario);
            } else {
                // Just generate audio
                $result = $tts->generateAudio($text, $scenario);
            }
            
            if ($result['success']) {
                http_response_code(201);
                echo json_encode([
                    'success' => true,
                    'audio_url' => $result['file_url'],
                    'duration' => $result['duration_seconds'],
                    'voice' => $result['voice'],
                    'cached' => $result['cached'] ?? false
                ]);
            } else {
                http_response_code(500);
                echo json_encode(['error' => 'Audio-Generierung fehlgeschlagen']);
            }
            break;
            
        case 'GET':
            // List generated audio files
            $db = \DTZ\Database\Database::getInstance();
            
            $audio = $db->select(
                "SELECT a.*, q.module, q.teil, q.level
                 FROM audio_files a
                 LEFT JOIN question_pools q ON a.question_id = q.id
                 ORDER BY a.created_at DESC
                 LIMIT 50"
            );
            
            http_response_code(200);
            echo json_encode([
                'success' => true,
                'audio_files' => $audio
            ]);
            break;
            
        case 'DELETE':
            // Delete audio file
            $input = json_decode(file_get_contents('php://input'), true);
            
            if (empty($input['id'])) {
                http_response_code(400);
                echo json_encode(['error' => 'ID erforderlich']);
                exit;
            }
            
            $db = \DTZ\Database\Database::getInstance();
            
            // Get file path
            $audio = $db->selectOne(
                "SELECT file_path FROM audio_files WHERE id = ?",
                [$input['id']]
            );
            
            if ($audio && file_exists($audio['file_path'])) {
                unlink($audio['file_path']);
            }
            
            $db->delete('audio_files', 'id = ?', [$input['id']]);
            
            http_response_code(200);
            echo json_encode(['success' => true, 'message' => 'Gelöscht']);
            break;
            
        default:
            http_response_code(405);
            echo json_encode(['error' => 'Method not allowed']);
    }
    
} catch (Exception $e) {
    error_log('TTS error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
