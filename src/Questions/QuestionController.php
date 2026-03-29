<?php
declare(strict_types=1);

namespace DTZ\Questions;

use DTZ\Models\Question;
use DTZ\Models\UserAnswer;
use DTZ\Models\DailyStats;
use DTZ\Models\User;
use DTZ\Auth\JWT;

class QuestionController
{
    private Question $questionModel;
    private UserAnswer $answerModel;
    private DailyStats $statsModel;
    private User $userModel;
    private JWT $jwt;
    
    public function __construct()
    {
        $this->questionModel = new Question();
        $this->answerModel = new UserAnswer();
        $this->statsModel = new DailyStats();
        $this->userModel = new User();
        
        $secret = $_ENV['JWT_SECRET'] ?? $this->getFallbackSecret();
        $this->jwt = new JWT($secret);
    }
    
    /**
     * Get next question for user
     */
    public function next(array $params, array $user): array
    {
        $module = $params['module'] ?? 'lesen';
        $level = $params['level'] ?? $user['level'];
        
        // Validate module
        $validModules = ['lesen', 'hoeren', 'schreiben', 'sprechen', 'lid'];
        if (!in_array($module, $validModules)) {
            return [
                'success' => false,
                'error' => 'Ungültiges Modul'
            ];
        }
        
        // Check subscription for premium questions
        if (!$this->canAccessPremium($user)) {
            return [
                'success' => false,
                'error' => 'Abonnement erforderlich',
                'code' => 'SUBSCRIPTION_REQUIRED'
            ];
        }
        
        // Get random question
        $question = $this->questionModel->getRandom($user['id'], $module, $level);
        
        if (!$question) {
            return [
                'success' => false,
                'error' => 'Keine Fragen verfügbar'
            ];
        }
        
        // Parse content
        $content = json_decode($question['content'], true);
        
        // Generate session ID if not exists
        $sessionId = $params['session_id'] ?? bin2hex(random_bytes(16));
        
        return [
            'success' => true,
            'session_id' => $sessionId,
            'question' => [
                'id' => $question['id'],
                'module' => $question['module'],
                'teil' => $question['teil'],
                'level' => $question['level'],
                'type' => $question['question_type'],
                'content' => $content,
                'media_urls' => json_decode($question['media_urls'] ?? '[]'),
                'points' => $question['points'],
            ],
            'stats' => [
                'today' => $this->answerModel->getTodayStats($user['id']),
                'streak' => $this->statsModel->getStreak($user['id']),
            ]
        ];
    }
    
    /**
     * Submit answer
     */
    public function submit(array $data, array $user): array
    {
        $questionId = $data['question_id'] ?? null;
        $userAnswer = $data['answer'] ?? null;
        $sessionId = $data['session_id'] ?? null;
        $timeSpent = $data['time_spent_seconds'] ?? 60;
        
        if (!$questionId || !$userAnswer) {
            return [
                'success' => false,
                'error' => 'Frage-ID und Antwort erforderlich'
            ];
        }
        
        // Get question
        $question = $this->questionModel->findById($questionId);
        if (!$question) {
            return [
                'success' => false,
                'error' => 'Frage nicht gefunden'
            ];
        }
        
        // Check answer
        $result = $this->checkAnswer($question, $userAnswer);
        
        // Calculate points
        $points = $result['is_correct'] ? $question['points'] : 0;
        $points = $this->calculatePointsWithBonus($points, $timeSpent, $question['avg_time_seconds'] ?? null);
        
        // Save answer
        $this->answerModel->create([
            'user_id' => $user['id'],
            'question_id' => $questionId,
            'session_id' => $sessionId ?? bin2hex(random_bytes(16)),
            'user_answer' => $userAnswer,
            'is_correct' => $result['is_correct'],
            'points_earned' => $points,
            'time_spent_seconds' => $timeSpent,
        ]);
        
        // Update history for spaced repetition
        $this->answerModel->updateHistory($user['id'], $questionId, $result['is_correct']);
        
        // Update question stats
        $this->questionModel->updateStats($questionId, $result['is_correct'], $timeSpent);
        
        // Update daily stats
        $this->statsModel->recordAnswer(
            $user['id'],
            $question['module'],
            $result['is_correct'],
            $points,
            (int) ($timeSpent / 60)
        );
        
        // Update user activity
        $this->userModel->updateLastActivity($user['id']);
        
        // Update streak
        $streakInfo = $this->statsModel->getStreak($user['id']);
        if ($streakInfo['current_streak'] > 0) {
            // Update streak in users table
            $this->userModel->incrementStreak($user['id']);
        }
        
        return [
            'success' => true,
            'result' => [
                'is_correct' => $result['is_correct'],
                'correct_answer' => $result['correct_answer'],
                'explanation' => $question['explanation'],
                'points_earned' => $points,
            ],
            'stats' => [
                'today' => $this->answerModel->getTodayStats($user['id']),
                'streak' => $this->statsModel->getStreak($user['id']),
            ]
        ];
    }
    
    /**
     * Check if answer is correct
     */
    private function checkAnswer(array $question, $userAnswer): array
    {
        $correctAnswer = json_decode($question['correct_answer'], true);
        
        switch ($question['question_type']) {
            case 'multiple_choice':
                $isCorrect = strtoupper($userAnswer) === strtoupper($correctAnswer['answer'] ?? '');
                return [
                    'is_correct' => $isCorrect,
                    'correct_answer' => $correctAnswer['answer'],
                ];
                
            case 'matching':
                // For matching questions, compare arrays
                $userMatches = is_array($userAnswer) ? $userAnswer : json_decode($userAnswer, true);
                $correctMatches = $correctAnswer['matches'] ?? [];
                $isCorrect = $userMatches === $correctMatches;
                return [
                    'is_correct' => $isCorrect,
                    'correct_answer' => $correctMatches,
                ];
                
            case 'text_input':
            case 'audio':
                // For writing/speaking - needs AI review or manual check
                // For now, mark as pending
                return [
                    'is_correct' => false,
                    'correct_answer' => $correctAnswer,
                    'pending_review' => true,
                ];
                
            default:
                return [
                    'is_correct' => false,
                    'correct_answer' => $correctAnswer,
                ];
        }
    }
    
    /**
     * Calculate points with time bonus
     */
    private function calculatePointsWithBonus(int $basePoints, int $timeSpent, ?int $avgTime): int
    {
        if (!$avgTime || $avgTime <= 0) {
            return $basePoints;
        }
        
        // Speed bonus: answered faster than average
        if ($timeSpent < $avgTime * 0.7) {
            return (int) ($basePoints * 1.2); // 20% bonus
        }
        
        return $basePoints;
    }
    
    /**
     * Get user dashboard stats
     */
    public function dashboard(array $user): array
    {
        $userId = $user['id'];
        $moduleStatsRaw = $this->answerModel->getModuleStats($userId, 30);
        $moduleStats = [
            'lesen' => ['total_questions' => 0, 'correct_count' => 0, 'accuracy_rate' => 0.0],
            'hoeren' => ['total_questions' => 0, 'correct_count' => 0, 'accuracy_rate' => 0.0],
            'schreiben' => ['total_questions' => 0, 'correct_count' => 0, 'accuracy_rate' => 0.0],
            'sprechen' => ['total_questions' => 0, 'correct_count' => 0, 'accuracy_rate' => 0.0],
            'lid' => ['total_questions' => 0, 'correct_count' => 0, 'accuracy_rate' => 0.0],
        ];
        foreach ($moduleStatsRaw as $row) {
            $module = (string)($row['module'] ?? '');
            if (!isset($moduleStats[$module])) {
                continue;
            }
            $moduleStats[$module] = [
                'total_questions' => (int)($row['total_questions'] ?? 0),
                'correct_count' => (int)($row['correct_count'] ?? 0),
                'accuracy_rate' => (float)($row['accuracy_rate'] ?? 0.0),
            ];
        }
        
        return [
            'success' => true,
            'stats' => [
                'today' => $this->answerModel->getTodayStats($userId),
                'streak' => $this->statsModel->getStreak($userId),
                'weekly' => $this->statsModel->getWeekly($userId),
            ],
            'progress' => [
                'total_questions_available' => $this->questionModel->count(),
                'weak_areas' => $this->questionModel->getWeakTopics($userId, 'lesen'),
                'module_stats' => $moduleStats,
            ]
        ];
    }
    
    /**
     * Check if user can access premium content
     */
    private function canAccessPremium(array $user): bool
    {
        return in_array($user['subscription_status'], ['trialing', 'premium']);
    }
    
    private function getFallbackSecret(): string
    {
        $secretFile = __DIR__ . '/../../.jwt_secret';
        if (file_exists($secretFile)) {
            return file_get_contents($secretFile);
        }
        return bin2hex(random_bytes(32));
    }
}
