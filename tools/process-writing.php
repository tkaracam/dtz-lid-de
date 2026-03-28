<?php
/**
 * CLI Tool: Process pending writing submissions with OpenAI
 * 
 * Usage: php tools/process-writing.php
 * Cron: 0,5,10,15,20,25,30,35,40,45,50,55 * * * * cd /path/to/project && php tools/process-writing.php >> logs/writing.log 2>&1
 */

require_once __DIR__ . '/../src/Database/Database.php';
require_once __DIR__ . '/../src/Services/OpenAIService.php';

use DTZ\Database\Database;
use DTZ\Services\OpenAIService;

// Load .env if exists
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

$apiKey = $_ENV['OPENAI_API_KEY'] ?? '';
$demoMode = empty($apiKey) || $apiKey === 'sk-test-key-placeholder';

if ($demoMode) {
    echo "[INFO] DEMO MODE: OpenAI API key not configured\n";
    echo "[INFO] Using simulated responses\n";
    echo "[INFO] To use real AI, add OPENAI_API_KEY to .env file\n\n";
}

echo "[" . date('Y-m-d H:i:s') . "] Starting writing analysis...\n";

try {
    $_ENV['DB_DRIVER'] = 'sqlite';
    $_ENV['DB_PATH'] = __DIR__ . '/../database/dtz_learning.db';
    
    $db = Database::getInstance();
    
    if (!$demoMode) {
        $openai = new OpenAIService($apiKey);
        
        // Test connection
        if (!$openai->testConnection()) {
            echo "[ERROR] OpenAI API connection failed\n";
            exit(1);
        }
        
        echo "[INFO] OpenAI connection OK\n";
    } else {
        echo "[INFO] Demo mode active - no API calls will be made\n";
    }
    
    // Get pending submissions
    $pending = $db->select(
        "SELECT id, task_type, original_text 
         FROM writing_submissions 
         WHERE status = 'pending' 
         LIMIT 10"
    );
    
    echo "[INFO] Found " . count($pending) . " pending submissions\n";
    
    if (empty($pending)) {
        echo "[INFO] Nothing to process\n";
        exit(0);
    }
    
    $processed = 0;
    $failed = 0;
    
    foreach ($pending as $submission) {
        echo "[INFO] Processing submission #{$submission['id']}... ";
        
        // Mark as processing
        $db->update('writing_submissions', [
            'status' => 'ai_processing'
        ], 'id = ?', [$submission['id']]);
        
        try {
            if ($demoMode) {
                // Generate simulated analysis based on text characteristics
                $analysis = generateDemoAnalysis($submission['original_text'], $submission['task_type']);
            } else {
                // Call OpenAI
                $analysis = $openai->analyzeWriting($submission['original_text'], $submission['task_type']);
            }
            
            // Store results
            $db->update('writing_submissions', [
                'ai_feedback' => json_encode($analysis),
                'ai_score' => $analysis['overallScore'],
                'status' => 'ai_reviewed',
                'ai_processed_at' => date('Y-m-d H:i:s'),
                'ai_completed_at' => date('Y-m-d H:i:s'),
                'final_feedback' => json_encode($analysis),
                'final_score' => $analysis['overallScore']
            ], 'id = ?', [$submission['id']]);
            
            echo "OK (Score: {$analysis['overallScore']}/20)\n";
            $processed++;
            
        } catch (Exception $e) {
            echo "FAILED: " . $e->getMessage() . "\n";
            
            // Revert to pending for retry
            $db->update('writing_submissions', [
                'status' => 'pending'
            ], 'id = ?', [$submission['id']]);
            
            $failed++;
        }
        
        // Delay to avoid rate limiting
        sleep(1);
    }
    
    echo "[" . date('Y-m-d H:i:s') . "] Completed: $processed processed, $failed failed\n";
    
} catch (Exception $e) {
    echo "[ERROR] " . $e->getMessage() . "\n";
    exit(1);
}

/**
 * Generate simulated AI analysis for demo mode
 */
function generateDemoAnalysis(string $text, string $taskType): array {
    $wordCount = str_word_count($text);
    
    // Calculate a realistic score based on text length and simple heuristics
    $baseScore = min(18, max(8, 10 + ($wordCount / 10)));
    
    // Adjust based on common words that indicate better German
    $goodWords = ['sehr', 'gern', 'immer', 'wichtig', 'richtig', 'gute', 'gerne', 'weil', 'dass'];
    $textLower = strtolower($text);
    foreach ($goodWords as $word) {
        if (strpos($textLower, $word) !== false) {
            $baseScore += 0.5;
        }
    }
    
    $overallScore = min(20, max(5, round($baseScore)));
    $level = $overallScore >= 12 ? 'B1' : 'A2';
    
    $taskTitles = [
        'bewerbung' => 'Bewerbung',
        'beschwerde' => 'Beschwerde',
        'einladung' => 'Einladung',
        'anfrage' => 'Anfrage',
        'termin' => 'Terminvereinbarung',
        'danksagung' => 'Danksagung'
    ];
    
    return [
        'overallScore' => $overallScore,
        'levelAssessment' => $level,
        'generalFeedback' => "Guter Text für {$level}-Niveau. Die Aufgabe ist verständlich erfüllt und der Text hat eine klare Struktur. Weiter so!",
        'categories' => [
            'aufgabenerfuellung' => [
                'score' => min(5, max(2, round($overallScore / 4))),
                'feedback' => 'Die wichtigsten Punkte sind enthalten.'
            ],
            'textaufbau' => [
                'score' => min(5, max(2, round($overallScore / 4))),
                'feedback' => 'Gute Struktur mit Einleitung und Schluss.'
            ],
            'sprachrichtigkeit' => [
                'score' => min(5, max(2, round($overallScore / 4) - 1)),
                'feedback' => 'Einige kleine Fehler, aber gut verständlich.'
            ],
            'sprachumfang' => [
                'score' => min(5, max(2, round($overallScore / 4))),
                'feedback' => 'Angemessener Wortschatz für dieses Niveau.'
            ]
        ],
        'corrections' => generateDemoCorrections($text),
        'highlights' => [
            [
                'type' => 'good',
                'text' => strlen($text) > 50 ? substr($text, 0, 30) . '...' : $text,
                'comment' => 'Gute Formulierung!'
            ]
        ]
    ];
}

function generateDemoCorrections(string $text): array {
    $corrections = [];
    $textLower = strtolower($text);
    
    // Common error patterns
    if (strpos($textLower, 'ich habe gehen') !== false) {
        $corrections[] = [
            'type' => 'grammar',
            'severity' => 'major',
            'original' => 'Ich habe gehen',
            'corrected' => 'Ich bin gegangen',
            'explanation' => 'Perfekt mit "sein" bei Bewegungsverben'
        ];
    }
    
    if (strpos($textLower, 'sehr geehrte') !== false && strpos($text, 'Sehr geehrte') === false) {
        $corrections[] = [
            'type' => 'spelling',
            'severity' => 'minor',
            'original' => 'sehr geehrte',
            'corrected' => 'Sehr geehrte',
            'explanation' => 'Großschreibung am Satzanfang'
        ];
    }
    
    if (strpos($textLower, 'deutsch') !== false && strpos($textLower, 'deutschland') === false) {
        $corrections[] = [
            'type' => 'spelling',
            'severity' => 'minor',
            'original' => 'deutsch',
            'corrected' => 'Deutsch',
            'explanation' => 'Sprachen werden großgeschrieben'
        ];
    }
    
    // Always add at least one generic correction if none found
    if (empty($corrections)) {
        $corrections[] = [
            'type' => 'style',
            'severity' => 'minor',
            'original' => '...',
            'corrected' => '...',
            'explanation' => 'Achten Sie auf Satzzeichen am Satzende'
        ];
    }
    
    return $corrections;
}
