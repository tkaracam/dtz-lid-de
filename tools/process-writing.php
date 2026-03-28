#!/usr/bin/env php
<?php
declare(strict_types=1);

/**
 * Background worker for AI writing analysis
 * Usage: php tools/process-writing.php [submission-id]
 */

require_once __DIR__ . '/../src/Database/Database.php';
require_once __DIR__ . '/../src/Services/OpenAIService.php';

use DTZ\Database\Database;
use DTZ\Services\OpenAIService;

// Load environment variables
$envFile = __DIR__ . '/../.env';
if (file_exists($envFile)) {
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos($line, '=') !== false && strpos($line, '#') !== 0) {
            list($key, $value) = explode('=', $line, 2);
            $_ENV[trim($key)] = trim($value);
        }
    }
}

$db = Database::getInstance();
$ai = new OpenAIService();

// Get pending submissions
if ($argc > 1) {
    // Process specific submission
    $submissionId = $argv[1];
    processSubmission($db, $ai, $submissionId);
} else {
    // Process all pending submissions
    echo "Processing all pending writing submissions...\n";
    
    $pending = $db->select(
        "SELECT id, original_text, task_type 
         FROM writing_submissions 
         WHERE status IN ('pending', 'ai_processing')
         LIMIT 10"
    );
    
    foreach ($pending as $submission) {
        processSubmission($db, $ai, $submission['id']);
        sleep(1); // Rate limiting
    }
}

function processSubmission(Database $db, OpenAIService $ai, string $submissionId): void
{
    echo "Processing submission {$submissionId}...\n";
    
    // Get submission
    $submission = $db->selectOne(
        "SELECT * FROM writing_submissions WHERE id = ?",
        [$submissionId]
    );
    
    if (!$submission) {
        echo "  ✗ Submission not found\n";
        return;
    }
    
    if ($submission['status'] === 'ai_reviewed') {
        echo "  ✓ Already processed\n";
        return;
    }
    
    // Update status to processing
    $db->update('writing_submissions', 
        ['status' => 'ai_processing'],
        'id = ?',
        [$submissionId]
    );
    
    try {
        // Call OpenAI
        $feedback = $ai->analyzeWriting(
            $submission['original_text'],
            $submission['task_type'],
            'B1'
        );
        
        // Update submission with AI feedback
        $db->update('writing_submissions', [
            'ai_feedback' => json_encode($feedback),
            'ai_score' => $feedback['overallScore'],
            'status' => 'ai_reviewed',
            'ai_processed_at' => date('Y-m-d H:i:s'),
            'ai_completed_at' => date('Y-m-d H:i:s')
        ], 'id = ?', [$submissionId]);
        
        echo "  ✓ AI analysis complete (Score: {$feedback['overallScore']}/20)\n";
        
    } catch (Exception $e) {
        echo "  ✗ Error: " . $e->getMessage() . "\n";
        
        // Reset to pending for retry
        $db->update('writing_submissions',
            ['status' => 'pending'],
            'id = ?',
            [$submissionId]
        );
    }
}
