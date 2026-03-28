<?php
declare(strict_types=1);

require_once __DIR__ . '/../../src/Auth/AuthController.php';
require_once __DIR__ . '/../../src/Database/Database.php';
require_once __DIR__ . '/../../src/Services/OpenAIService.php';

use DTZ\Auth\AuthController;
use DTZ\Database\Database;
use DTZ\Services\OpenAIService;

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// This endpoint can be called by cron job or admin to trigger AI analysis

$apiKey = $_ENV['OPENAI_API_KEY'] ?? '';

if (empty($apiKey)) {
    http_response_code(500);
    echo json_encode(['error' => 'OpenAI API key not configured']);
    exit;
}

try {
    $db = Database::getInstance();
    $openai = new OpenAIService($apiKey);
    
    // Get pending submissions
    $pending = $db->select(
        "SELECT id, task_type, original_text 
         FROM writing_submissions 
         WHERE status = 'pending' 
         LIMIT 5"
    );
    
    $processed = 0;
    
    foreach ($pending as $submission) {
        // Mark as processing
        $db->update('writing_submissions', [
            'status' => 'ai_processing',
            'ai_processed_at' => date('Y-m-d H:i:s')
        ], 'id = ?', [$submission['id']]);
        
        try {
            // Call OpenAI
            $analysis = $openai->analyzeWriting($submission['original_text'], $submission['task_type']);
            
            // Store results
            $db->update('writing_submissions', [
                'ai_feedback' => json_encode($analysis),
                'ai_score' => $analysis['overallScore'],
                'status' => 'ai_reviewed',
                'ai_completed_at' => date('Y-m-d H:i:s'),
                'final_feedback' => json_encode($analysis), // Initially same as AI
                'final_score' => $analysis['overallScore']
            ], 'id = ?', [$submission['id']]);
            
            $processed++;
            
        } catch (Exception $e) {
            error_log('AI analysis failed for submission ' . $submission['id'] . ': ' . $e->getMessage());
            
            // Revert to pending for retry
            $db->update('writing_submissions', [
                'status' => 'pending'
            ], 'id = ?', [$submission['id']]);
        }
        
        // Small delay to avoid rate limiting
        usleep(500000); // 0.5 seconds
    }
    
    echo json_encode([
        'success' => true,
        'processed' => $processed,
        'pending' => count($pending)
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Analysis failed: ' . $e->getMessage()]);
}
