<?php
declare(strict_types=1);

namespace DTZ\Models;

use DTZ\Database\Database;

class DailyStats
{
    private Database $db;
    
    public function __construct()
    {
        $this->db = Database::getInstance();
    }
    
    /**
     * Get or create today's stats
     */
    public function getToday(int $userId): array
    {
        $stats = $this->db->selectOne("
            SELECT * FROM daily_stats 
            WHERE user_id = ? AND date = date('now')
        ", [$userId]);
        
        if (!$stats) {
            // Create new stats row
            $this->db->insert('daily_stats', [
                'user_id' => $userId,
                'date' => date('Y-m-d'),
                'total_questions' => 0,
                'correct_count' => 0,
                'total_points' => 0,
                'total_time_minutes' => 0,
                'module_breakdown' => json_encode(['lesen' => 0, 'hoeren' => 0, 'schreiben' => 0, 'sprechen' => 0, 'lid' => 0]),
                'goal_reached' => 0,
                'streak_maintained' => 0,
            ]);
            
            $stats = $this->db->selectOne("
                SELECT * FROM daily_stats 
                WHERE user_id = ? AND date = date('now')
            ", [$userId]);
        }
        
        return $stats;
    }
    
    /**
     * Update stats after answer
     */
    public function recordAnswer(int $userId, string $module, bool $isCorrect, int $points, int $timeMinutes): void
    {
        $stats = $this->getToday($userId);
        
        $newTotal = (int) $stats['total_questions'] + 1;
        $newCorrect = (int) $stats['correct_count'] + ($isCorrect ? 1 : 0);
        $newPoints = (int) $stats['total_points'] + $points;
        $newTime = (int) $stats['total_time_minutes'] + $timeMinutes;
        
        // Update module breakdown
        $breakdown = json_decode($stats['module_breakdown'] ?? '{}', true);
        $breakdown[$module] = ($breakdown[$module] ?? 0) + 1;
        
        // Check if daily goal reached (get from user settings)
        $user = $this->db->selectOne("SELECT daily_goal FROM users WHERE id = ?", [$userId]);
        $dailyGoal = (int) ($user['daily_goal'] ?? 10);
        $goalReached = $newTotal >= $dailyGoal ? 1 : 0;
        
        $this->db->update('daily_stats', [
            'total_questions' => $newTotal,
            'correct_count' => $newCorrect,
            'total_points' => $newPoints,
            'total_time_minutes' => $newTime,
            'module_breakdown' => json_encode($breakdown),
            'goal_reached' => $goalReached,
        ], 'id = ?', [$stats['id']]);
    }
    
    /**
     * Get weekly stats
     */
    public function getWeekly(int $userId): array
    {
        return $this->db->select("
            SELECT 
                date,
                total_questions,
                correct_count,
                total_points,
                goal_reached
            FROM daily_stats
            WHERE user_id = ?
            AND date >= date('now', '-7 days')
            ORDER BY date ASC
        ", [$userId]);
    }
    
    /**
     * Get monthly stats
     */
    public function getMonthly(int $userId): array
    {
        return $this->db->select("
            SELECT 
                strftime('%Y-%m', date) as month,
                SUM(total_questions) as total_questions,
                SUM(correct_count) as correct_count,
                SUM(total_points) as total_points,
                SUM(total_time_minutes) as total_time,
                SUM(CASE WHEN goal_reached THEN 1 ELSE 0 END) as days_goal_reached
            FROM daily_stats
            WHERE user_id = ?
            AND date >= date('now', '-12 months')
            GROUP BY strftime('%Y-%m', date)
            ORDER BY month DESC
        ", [$userId]);
    }
    
    /**
     * Get streak info
     */
    public function getStreak(int $userId): array
    {
        // Get all dates with activity, ordered by date
        $dates = $this->db->select("
            SELECT date FROM daily_stats
            WHERE user_id = ?
            AND total_questions > 0
            ORDER BY date DESC
        ", [$userId]);
        
        if (empty($dates)) {
            return [
                'current_streak' => 0,
                'longest_streak' => 0,
                'last_activity' => null,
            ];
        }
        
        $currentStreak = 0;
        $expectedDate = new \DateTime();
        $expectedDate->setTime(0, 0, 0);
        
        // Check if active today or yesterday
        $lastDate = $dates[0]['date'];
        $lastActivity = new \DateTime($lastDate);
        $diff = $expectedDate->diff($lastActivity)->days;
        
        if ($diff > 1) {
            // Streak broken
            return [
                'current_streak' => 0,
                'longest_streak' => $this->calculateLongestStreak($dates),
                'last_activity' => $lastDate,
            ];
        }
        
        // Count current streak
        $currentStreak = 1;
        $expectedDate->modify('-1 day');
        
        for ($i = 1; $i < count($dates); $i++) {
            $date = new \DateTime($dates[$i]['date']);
            if ($date->format('Y-m-d') === $expectedDate->format('Y-m-d')) {
                $currentStreak++;
                $expectedDate->modify('-1 day');
            } else {
                break;
            }
        }
        
        return [
            'current_streak' => $currentStreak,
            'longest_streak' => max($currentStreak, $this->calculateLongestStreak($dates)),
            'last_activity' => $lastDate,
        ];
    }
    
    /**
     * Calculate longest streak from date list
     */
    private function calculateLongestStreak(array $dates): int
    {
        if (empty($dates)) return 0;
        
        $longest = 1;
        $current = 1;
        
        for ($i = 1; $i < count($dates); $i++) {
            $prevDate = new \DateTime($dates[$i-1]['date']);
            $currDate = new \DateTime($dates[$i]['date']);
            
            $diff = $prevDate->diff($currDate)->days;
            
            if ($diff === 1) {
                $current++;
                $longest = max($longest, $current);
            } else {
                $current = 1;
            }
        }
        
        return $longest;
    }
}
