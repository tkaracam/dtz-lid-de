<?php
declare(strict_types=1);

require_once __DIR__ . '/../../src/Auth/AuthController.php';
require_once __DIR__ . '/../../src/Database/Database.php';
require_once __DIR__ . '/../../src/Security/SecurityHeaders.php';

use DTZ\Auth\AuthController;
use DTZ\Database\Database;
use DTZ\Security\SecurityHeaders;

SecurityHeaders::set();
SecurityHeaders::setCors();

// Check admin authentication
$auth = new AuthController();
$user = $auth->authenticate();

if (!$user || $user['role'] !== 'admin') {
    SecurityHeaders::jsonResponse(['error' => 'Unauthorized'], 401);
}

$period = $_GET['period'] ?? '24h';
$quick = isset($_GET['quick']);

try {
    $db = Database::getInstance();
    
    // Base statistics
    $stats = [
        'timestamp' => date('c'),
        'period' => $period
    ];
    
    // Total users
    $userStats = $db->selectOne("SELECT 
        COUNT(*) as total,
        COUNT(CASE WHEN subscription_status = 'premium' THEN 1 END) as premium,
        COUNT(CASE WHEN subscription_status = 'trialing' THEN 1 END) as trial,
        COUNT(CASE WHEN DATE(created_at) = DATE('now') THEN 1 END) as new_today,
        COUNT(CASE WHEN DATE(created_at) >= DATE('now', '-7 days') THEN 1 END) as new_this_week
    FROM users");
    
    $stats['total_users'] = (int)$userStats['total'];
    $stats['premium_users'] = (int)$userStats['premium'];
    $stats['trial_users'] = (int)$userStats['trial'];
    $stats['new_users_today'] = (int)$userStats['new_today'];
    $stats['new_users_week'] = (int)$userStats['new_this_week'];
    
    // Pending content for review
    $pendingWriting = $db->selectOne("SELECT COUNT(*) as count FROM writing_submissions WHERE status = 'pending'");
    $pendingSpeaking = $db->selectOne("SELECT COUNT(*) as count FROM speaking_submissions WHERE status = 'pending'");
    $stats['pending_content'] = (int)$pendingWriting['count'] + (int)$pendingSpeaking['count'];
    $stats['pending_writing'] = (int)$pendingWriting['count'];
    $stats['pending_speaking'] = (int)$pendingSpeaking['count'];
    
    // Quick mode - return early
    if ($quick) {
        SecurityHeaders::jsonResponse($stats);
    }
    
    // Revenue statistics (mock for now, integrate with Stripe in production)
    $revenueStats = $db->selectOne("SELECT 
        COALESCE(SUM(amount), 0) as total_revenue,
        COUNT(*) as total_payments
    FROM payments WHERE status = 'succeeded' AND created_at >= DATE('now', '-30 days')");
    
    $stats['monthly_revenue'] = round((float)$revenueStats['total_revenue'], 2);
    $stats['total_payments'] = (int)$revenueStats['total_payments'];
    
    // Previous month for comparison
    $prevMonthRevenue = $db->selectOne("SELECT COALESCE(SUM(amount), 0) as revenue 
        FROM payments WHERE status = 'succeeded' 
        AND created_at >= DATE('now', '-60 days') 
        AND created_at < DATE('now', '-30 days')");
    
    $prevRevenue = (float)$prevMonthRevenue['revenue'];
    $currentRevenue = (float)$revenueStats['total_revenue'];
    $stats['revenue_growth'] = $prevRevenue > 0 
        ? round((($currentRevenue - $prevRevenue) / $prevRevenue) * 100, 1)
        : 0;
    
    // Premium growth
    $prevMonthPremium = $db->selectOne("SELECT COUNT(*) as count FROM users 
        WHERE subscription_status = 'premium' 
        AND created_at < DATE('now', '-30 days')");
    $prevPremium = (int)$prevMonthPremium['count'];
    $currentPremium = (int)$userStats['premium'];
    $stats['premium_growth'] = $prevPremium > 0
        ? round((($currentPremium - $prevPremium) / $prevPremium) * 100, 1)
        : ($currentPremium > 0 ? 100 : 0);
    
    // Activity data for charts
    $activityData = $db->selectAll("SELECT 
        DATE(created_at) as date,
        COUNT(*) as new_users,
        COUNT(CASE WHEN subscription_status IN ('premium', 'trialing') THEN 1 END) as conversions
    FROM users 
    WHERE created_at >= DATE('now', '-7 days')
    GROUP BY DATE(created_at)
    ORDER BY date");
    
    $stats['activity_chart'] = array_map(fn($row) => [
        'date' => $row['date'],
        'new_users' => (int)$row['new_users'],
        'conversions' => (int)$row['conversions']
    ], $activityData);
    
    // Question statistics
    $questionStats = $db->selectOne("SELECT 
        COUNT(*) as total,
        COUNT(CASE WHEN is_active = 1 THEN 1 END) as active,
        COUNT(CASE WHEN module = 'lesen' THEN 1 END) as lesen,
        COUNT(CASE WHEN module = 'hoeren' THEN 1 END) as hoeren,
        COUNT(CASE WHEN module = 'schreiben' THEN 1 END) as schreiben,
        COUNT(CASE WHEN module = 'sprechen' THEN 1 END) as sprechen,
        COUNT(CASE WHEN module = 'lid' THEN 1 END) as lid
    FROM question_pools");
    
    $stats['questions'] = [
        'total' => (int)$questionStats['total'],
        'active' => (int)$questionStats['active'],
        'by_module' => [
            'lesen' => (int)$questionStats['lesen'],
            'hoeren' => (int)$questionStats['hoeren'],
            'schreiben' => (int)$questionStats['schreiben'],
            'sprechen' => (int)$questionStats['sprechen'],
            'lid' => (int)$questionStats['lid']
        ]
    ];
    
    // Answer statistics
    $answerStats = $db->selectOne("SELECT 
        COUNT(*) as total,
        COUNT(CASE WHEN is_correct = 1 THEN 1 END) as correct,
        AVG(time_spent_seconds) as avg_time
    FROM user_answers WHERE created_at >= DATE('now', '-30 days')");
    
    $stats['answers'] = [
        'total_30d' => (int)$answerStats['total'],
        'correct_count' => (int)$answerStats['correct'],
        'accuracy_rate' => (int)$answerStats['total'] > 0
            ? round(((int)$answerStats['correct'] / (int)$answerStats['total']) * 100, 1)
            : 0,
        'avg_time_seconds' => round((float)$answerStats['avg_time'], 1)
    ];
    
    // System health
    $stats['system'] = [
        'database_size_mb' => round(filesize(__DIR__ . '/../../database/dtz.db') / 1024 / 1024, 2),
        'php_version' => PHP_VERSION,
        'memory_usage_mb' => round(memory_get_usage(true) / 1024 / 1024, 2),
        'uptime_hours' => round(time() / 3600, 1) // Placeholder
    ];
    
    SecurityHeaders::jsonResponse($stats);
    
} catch (Exception $e) {
    error_log('Admin stats error: ' . $e->getMessage());
    SecurityHeaders::jsonResponse(['error' => 'Failed to load statistics'], 500);
}
