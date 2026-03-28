<?php
declare(strict_types=1);

namespace DTZ\Models;

use DTZ\Database\Database;

class QuestionAdmin
{
    private Database $db;
    
    public function __construct()
    {
        $this->db = Database::getInstance();
    }
    
    /**
     * Get all questions with pagination
     */
    public function getAll(int $page = 1, int $perPage = 20, array $filters = []): array
    {
        $offset = ($page - 1) * $perPage;
        $params = [];
        $whereConditions = [];
        
        if (!empty($filters['module'])) {
            $whereConditions[] = "module = ?";
            $params[] = $filters['module'];
        }
        
        if (!empty($filters['level'])) {
            $whereConditions[] = "level = ?";
            $params[] = $filters['level'];
        }
        
        if (!empty($filters['teil'])) {
            $whereConditions[] = "teil = ?";
            $params[] = $filters['teil'];
        }
        
        if (isset($filters['is_active'])) {
            $whereConditions[] = "is_active = ?";
            $params[] = $filters['is_active'] ? 1 : 0;
        }
        
        if (!empty($filters['search'])) {
            $whereConditions[] = "(content LIKE ? OR explanation LIKE ?)";
            $params[] = "%{$filters['search']}%";
            $params[] = "%{$filters['search']}%";
        }
        
        $whereClause = !empty($whereConditions) ? 'WHERE ' . implode(' AND ', $whereConditions) : '';
        
        $questions = $this->db->select("
            SELECT 
                id, module, teil, level, question_type,
                content, correct_answer, explanation,
                difficulty, points, is_active, is_premium_only,
                usage_count, correct_rate, created_at
            FROM question_pools
            {$whereClause}
            ORDER BY created_at DESC
            LIMIT ? OFFSET ?
        ", array_merge($params, [$perPage, $offset]));
        
        // Get total count
        $countResult = $this->db->selectOne("
            SELECT COUNT(*) as total FROM question_pools {$whereClause}
        ", $params);
        
        // Parse JSON fields
        foreach ($questions as &$question) {
            $question['content'] = json_decode($question['content'], true);
            $question['correct_answer'] = json_decode($question['correct_answer'], true);
        }
        
        return [
            'questions' => $questions,
            'total' => (int) ($countResult['total'] ?? 0),
            'page' => $page,
            'per_page' => $perPage,
            'total_pages' => ceil((int) ($countResult['total'] ?? 0) / $perPage)
        ];
    }
    
    /**
     * Create new question
     */
    public function create(array $data): int
    {
        return $this->db->insert('question_pools', [
            'module' => $data['module'],
            'teil' => $data['teil'],
            'level' => $data['level'],
            'question_type' => $data['question_type'],
            'content' => json_encode($data['content']),
            'correct_answer' => json_encode($data['correct_answer']),
            'explanation' => $data['explanation'] ?? '',
            'hints' => !empty($data['hints']) ? json_encode($data['hints']) : null,
            'difficulty' => $data['difficulty'] ?? 5,
            'points' => $data['points'] ?? 10,
            'is_active' => $data['is_active'] ?? 1,
            'is_premium_only' => $data['is_premium_only'] ?? 0,
            'created_by' => $data['created_by'] ?? null,
        ]);
    }
    
    /**
     * Update question
     */
    public function update(int $id, array $data): bool
    {
        $updateData = [];
        
        $fields = [
            'module', 'teil', 'level', 'question_type',
            'explanation', 'difficulty', 'points',
            'is_active', 'is_premium_only'
        ];
        
        foreach ($fields as $field) {
            if (isset($data[$field])) {
                $updateData[$field] = $data[$field];
            }
        }
        
        // Handle JSON fields
        if (isset($data['content'])) {
            $updateData['content'] = json_encode($data['content']);
        }
        
        if (isset($data['correct_answer'])) {
            $updateData['correct_answer'] = json_encode($data['correct_answer']);
        }
        
        if (isset($data['hints'])) {
            $updateData['hints'] = json_encode($data['hints']);
        }
        
        if (empty($updateData)) {
            return false;
        }
        
        return $this->db->update('question_pools', $updateData, 'id = ?', [$id]) > 0;
    }
    
    /**
     * Delete question
     */
    public function delete(int $id): bool
    {
        return $this->db->delete('question_pools', 'id = ?', [$id]) > 0;
    }
    
    /**
     * Toggle question active status
     */
    public function toggleActive(int $id): bool
    {
        $question = $this->db->selectOne(
            "SELECT is_active FROM question_pools WHERE id = ?",
            [$id]
        );
        
        if (!$question) {
            return false;
        }
        
        $newStatus = $question['is_active'] ? 0 : 1;
        
        return $this->db->update(
            'question_pools',
            ['is_active' => $newStatus],
            'id = ?',
            [$id]
        ) > 0;
    }
    
    /**
     * Get question statistics
     */
    public function getStats(): array
    {
        $stats = $this->db->selectOne("
            SELECT 
                COUNT(*) as total,
                SUM(CASE WHEN is_active = 1 THEN 1 ELSE 0 END) as active,
                AVG(correct_rate) as avg_correct_rate,
                SUM(usage_count) as total_usage
            FROM question_pools
        ");
        
        $byModule = $this->db->select("
            SELECT 
                module,
                COUNT(*) as count,
                AVG(correct_rate) as avg_correct_rate
            FROM question_pools
            WHERE is_active = 1
            GROUP BY module
        ");
        
        $byLevel = $this->db->select("
            SELECT 
                level,
                COUNT(*) as count
            FROM question_pools
            WHERE is_active = 1
            GROUP BY level
        ");
        
        return [
            'total' => $stats,
            'by_module' => $byModule,
            'by_level' => $byLevel
        ];
    }
    
    /**
     * Bulk import questions
     */
    public function bulkImport(array $questions, int $adminId): array
    {
        $results = [
            'imported' => 0,
            'failed' => 0,
            'errors' => []
        ];
        
        foreach ($questions as $index => $question) {
            try {
                $question['created_by'] = $adminId;
                $this->create($question);
                $results['imported']++;
            } catch (\Exception $e) {
                $results['failed']++;
                $results['errors'][] = "Row {$index}: " . $e->getMessage();
            }
        }
        
        return $results;
    }
}
