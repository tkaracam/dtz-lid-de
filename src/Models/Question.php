<?php
declare(strict_types=1);

namespace DTZ\Models;

use DTZ\Database\Database;

class Question
{
    private Database $db;
    
    public function __construct()
    {
        $this->db = Database::getInstance();
    }
    
    /**
     * Get random question for user
     */
    public function getRandom(int $userId, string $module, string $level): ?array
    {
        // First, try to get questions user hasn't seen recently
        $question = $this->db->selectOne("
            SELECT q.* FROM question_pools q
            WHERE q.module = ?
            AND q.level = ?
            AND q.is_active = 1
            AND q.id NOT IN (
                SELECT question_id FROM user_answers 
                WHERE user_id = ? 
                AND created_at > datetime('now', '-7 days')
            )
            ORDER BY RANDOM()
            LIMIT 1
        ", [$module, $level, $userId]);
        
        // If no unseen questions, get based on spaced repetition
        if (!$question) {
            $question = $this->getSpacedRepetitionQuestion($userId, $module, $level);
        }
        
        // Fallback: any random question
        if (!$question) {
            $question = $this->db->selectOne("
                SELECT * FROM question_pools
                WHERE module = ?
                AND level = ?
                AND is_active = 1
                ORDER BY RANDOM()
                LIMIT 1
            ", [$module, $level]);
        }
        
        return $question;
    }
    
    /**
     * Get question based on spaced repetition
     */
    private function getSpacedRepetitionQuestion(int $userId, string $module, string $level): ?array
    {
        return $this->db->selectOne("
            SELECT q.* FROM question_pools q
            JOIN user_question_history h ON q.id = h.question_id
            WHERE q.module = ?
            AND q.level = ?
            AND q.is_active = 1
            AND h.user_id = ?
            AND h.times_correct < 3
            AND (h.next_review_at IS NULL OR h.next_review_at <= datetime('now'))
            ORDER BY h.times_seen ASC, RANDOM()
            LIMIT 1
        ", [$module, $level, $userId]);
    }
    
    /**
     * Get weak topics for user
     */
    public function getWeakTopics(int $userId, string $module): array
    {
        return $this->db->select("
            SELECT 
                q.teil,
                COUNT(*) as total_attempts,
                SUM(CASE WHEN ua.is_correct THEN 1 ELSE 0 END) as correct_count,
                ROUND(
                    100.0 * SUM(CASE WHEN ua.is_correct THEN 1 ELSE 0 END) / COUNT(*), 
                    2
                ) as accuracy_rate
            FROM user_answers ua
            JOIN question_pools q ON ua.question_id = q.id
            WHERE ua.user_id = ?
            AND q.module = ?
            AND ua.created_at > datetime('now', '-30 days')
            GROUP BY q.teil
            HAVING accuracy_rate < 70
            ORDER BY accuracy_rate ASC
        ", [$userId, $module]);
    }
    
    /**
     * Get question by ID
     */
    public function findById(int $id): ?array
    {
        return $this->db->selectOne(
            "SELECT * FROM question_pools WHERE id = ? LIMIT 1",
            [$id]
        );
    }
    
    /**
     * Get questions by module and teil
     */
    public function getByModule(string $module, ?string $level = null, int $limit = 50): array
    {
        $sql = "SELECT * FROM question_pools WHERE module = ? AND is_active = 1";
        $params = [$module];
        
        if ($level) {
            $sql .= " AND level = ?";
            $params[] = $level;
        }
        
        $sql .= " ORDER BY RANDOM() LIMIT ?";
        $params[] = $limit;
        
        return $this->db->select($sql, $params);
    }
    
    /**
     * Count total questions
     */
    public function count(?string $module = null, ?string $level = null): int
    {
        $sql = "SELECT COUNT(*) as count FROM question_pools WHERE is_active = 1";
        $params = [];
        
        if ($module) {
            $sql .= " AND module = ?";
            $params[] = $module;
        }
        
        if ($level) {
            $sql .= " AND level = ?";
            $params[] = $level;
        }
        
        $result = $this->db->selectOne($sql, $params);
        return (int) ($result['count'] ?? 0);
    }
    
    /**
     * Update question statistics
     */
    public function updateStats(int $questionId, bool $isCorrect, int $timeSpent): void
    {
        $this->db->execute("
            UPDATE question_pools 
            SET 
                usage_count = usage_count + 1,
                correct_rate = (
                    SELECT ROUND(100.0 * SUM(CASE WHEN is_correct THEN 1 ELSE 0 END) / COUNT(*), 2)
                    FROM user_answers 
                    WHERE question_id = ?
                ),
                avg_time_seconds = (
                    SELECT AVG(time_spent_seconds)
                    FROM user_answers 
                    WHERE question_id = ?
                ),
                last_used_at = datetime('now')
            WHERE id = ?
        ", [$questionId, $questionId, $questionId]);
    }
}
