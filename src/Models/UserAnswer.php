<?php
declare(strict_types=1);

namespace DTZ\Models;

use DTZ\Database\Database;

class UserAnswer
{
    private Database $db;
    
    public function __construct()
    {
        $this->db = Database::getInstance();
    }
    
    /**
     * Save user answer
     */
    public function create(array $data): int
    {
        return $this->db->insert('user_answers', [
            'user_id' => $data['user_id'],
            'question_id' => $data['question_id'],
            'session_id' => $data['session_id'],
            'user_answer' => is_array($data['user_answer']) ? json_encode($data['user_answer']) : $data['user_answer'],
            'is_correct' => $data['is_correct'] ? 1 : 0,
            'points_earned' => $data['points_earned'] ?? 0,
            'time_spent_seconds' => $data['time_spent_seconds'],
            'attempts_count' => $data['attempts_count'] ?? 1,
            'ai_feedback' => !empty($data['ai_feedback']) ? json_encode($data['ai_feedback']) : null,
        ]);
    }
    
    /**
     * Update or create question history (spaced repetition)
     */
    public function updateHistory(int $userId, int $questionId, bool $isCorrect): void
    {
        $history = $this->db->selectOne("
            SELECT * FROM user_question_history 
            WHERE user_id = ? AND question_id = ?
        ", [$userId, $questionId]);
        
        if (!$history) {
            // First time seeing this question
            $this->db->insert('user_question_history', [
                'user_id' => $userId,
                'question_id' => $questionId,
                'first_seen_at' => date('Y-m-d H:i:s'),
                'last_seen_at' => date('Y-m-d H:i:s'),
                'times_seen' => 1,
                'times_correct' => $isCorrect ? 1 : 0,
                'next_review_at' => $this->calculateNextReview($isCorrect, 0, 2.5),
                'ease_factor' => $isCorrect ? 2.5 : 2.0,
                'interval_days' => $isCorrect ? 1 : 0,
            ]);
        } else {
            // Update existing history
            $newEaseFactor = $this->calculateEaseFactor(
                (float) $history['ease_factor'], 
                $isCorrect
            );
            
            $newInterval = $isCorrect 
                ? max(1, (int) $history['interval_days'] * $newEaseFactor)
                : 0;
            
            $this->db->update('user_question_history', [
                'last_seen_at' => date('Y-m-d H:i:s'),
                'times_seen' => (int) $history['times_seen'] + 1,
                'times_correct' => (int) $history['times_correct'] + ($isCorrect ? 1 : 0),
                'next_review_at' => $this->calculateNextReview($isCorrect, $newInterval, $newEaseFactor),
                'ease_factor' => $newEaseFactor,
                'interval_days' => $newInterval,
            ], 'id = ?', [$history['id']]);
        }
    }
    
    /**
     * Calculate next review date using SM-2 algorithm
     */
    private function calculateNextReview(bool $isCorrect, float $intervalDays, float $easeFactor): string
    {
        if (!$isCorrect) {
            // Review tomorrow if wrong
            return date('Y-m-d H:i:s', strtotime('+1 day'));
        }
        
        $days = match(true) {
            $intervalDays === 0 => 1,
            $intervalDays === 1 => 6,
            default => (int) round($intervalDays * $easeFactor),
        };
        
        return date('Y-m-d H:i:s', strtotime("+{$days} days"));
    }
    
    /**
     * Calculate new ease factor
     */
    private function calculateEaseFactor(float $currentEaseFactor, bool $isCorrect): float
    {
        if ($isCorrect) {
            return min(2.5, $currentEaseFactor + 0.1);
        } else {
            return max(1.3, $currentEaseFactor - 0.2);
        }
    }
    
    /**
     * Get today's stats for user
     */
    public function getTodayStats(int $userId): array
    {
        $result = $this->db->selectOne("
            SELECT 
                COUNT(*) as total_questions,
                SUM(CASE WHEN is_correct THEN 1 ELSE 0 END) as correct_count,
                SUM(points_earned) as total_points,
                SUM(time_spent_seconds) as total_time_seconds
            FROM user_answers
            WHERE user_id = ?
            AND date(created_at) = date('now')
        ", [$userId]);
        
        return [
            'total_questions' => (int) ($result['total_questions'] ?? 0),
            'correct_count' => (int) ($result['correct_count'] ?? 0),
            'total_points' => (int) ($result['total_points'] ?? 0),
            'total_time_minutes' => (int) (($result['total_time_seconds'] ?? 0) / 60),
            'accuracy_rate' => $result['total_questions'] > 0 
                ? round(100 * $result['correct_count'] / $result['total_questions'], 2)
                : 0,
        ];
    }
    
    /**
     * Get answer history for user
     */
    public function getHistory(int $userId, int $limit = 50): array
    {
        return $this->db->select("
            SELECT 
                ua.*,
                q.module,
                q.teil,
                q.level,
                q.content
            FROM user_answers ua
            JOIN question_pools q ON ua.question_id = q.id
            WHERE ua.user_id = ?
            ORDER BY ua.created_at DESC
            LIMIT ?
        ", [$userId, $limit]);
    }
    
    /**
     * Get session stats
     */
    public function getSessionStats(string $sessionId): array
    {
        $result = $this->db->selectOne("
            SELECT 
                COUNT(*) as total_questions,
                SUM(CASE WHEN is_correct THEN 1 ELSE 0 END) as correct_count,
                SUM(points_earned) as total_points
            FROM user_answers
            WHERE session_id = ?
        ", [$sessionId]);
        
        return [
            'total_questions' => (int) ($result['total_questions'] ?? 0),
            'correct_count' => (int) ($result['correct_count'] ?? 0),
            'total_points' => (int) ($result['total_points'] ?? 0),
        ];
    }
    
    /**
     * Check if user has answered question today
     */
    public function hasAnsweredToday(int $userId, int $questionId): bool
    {
        $result = $this->db->selectOne("
            SELECT 1 FROM user_answers
            WHERE user_id = ?
            AND question_id = ?
            AND date(created_at) = date('now')
            LIMIT 1
        ", [$userId, $questionId]);
        
        return $result !== null;
    }
}
