<?php
declare(strict_types=1);

/**
 * Subscription Status Endpoint
 * Returns current user's subscription details
 */

require_once __DIR__ . '/../../api/config.php';
require_once __DIR__ . '/../../src/Auth/AuthController.php';
require_once __DIR__ . '/../../src/Database/Database.php';

use DTZ\Auth\AuthController;
use DTZ\Database\Database;

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
header('Access-Control-Allow-Methods: GET, OPTIONS');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['error' => 'Nur GET erlaubt']);
    exit;
}

// Auth
$authHeader = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
$token = '';
if (preg_match('/Bearer\s+(\S+)/', $authHeader, $matches)) {
    $token = $matches[1];
}

if (empty($token)) {
    http_response_code(401);
    echo json_encode(['error' => 'Nicht autorisiert']);
    exit;
}

$auth = new AuthController();
$user = $auth->me($token);

if (!$user) {
    http_response_code(401);
    echo json_encode(['error' => 'Token ungültig']);
    exit;
}

try {
    $db = Database::getInstance();
    
    // Get current subscription
    $subscription = $db->selectOne(
        "SELECT * FROM subscriptions 
         WHERE user_id = ? 
         ORDER BY created_at DESC LIMIT 1",
        [$user['id']]
    );
    
    // Get payment history
    $payments = $db->select(
        "SELECT 
            id,
            amount,
            currency,
            status,
            invoice_url,
            receipt_url,
            created_at
         FROM payments 
         WHERE user_id = ? 
         ORDER BY created_at DESC 
         LIMIT 10",
        [$user['id']]
    );
    
    // Format payments
    $formattedPayments = array_map(function ($p) {
        return [
            'id' => (int)$p['id'],
            'amount' => (float)$p['amount'],
            'currency' => $p['currency'],
            'status' => $p['status'],
            'invoice_url' => $p['invoice_url'],
            'receipt_url' => $p['receipt_url'],
            'date' => $p['created_at']
        ];
    }, $payments);
    
    // Build response
    $response = [
        'success' => true,
        'subscription_status' => $user['subscription_status'],
        'is_premium' => in_array($user['subscription_status'], ['premium', 'trialing']),
        'is_trial' => $user['subscription_status'] === 'trialing',
        'subscription' => null,
        'payments' => $formattedPayments
    ];
    
    if ($subscription) {
        $now = time();
        $trialEnd = $subscription['trial_end'] ? strtotime($subscription['trial_end']) : null;
        $periodEnd = strtotime($subscription['current_period_end']);
        
        $response['subscription'] = [
            'id' => (int)$subscription['id'],
            'plan' => $subscription['plan_name'],
            'interval' => $subscription['plan_interval'],
            'amount' => (float)$subscription['amount'],
            'currency' => $subscription['currency'],
            'status' => $subscription['status'],
            'trial_end' => $subscription['trial_end'],
            'trial_days_remaining' => $trialEnd ? max(0, floor(($trialEnd - $now) / 86400)) : null,
            'current_period_end' => $subscription['current_period_end'],
            'days_until_renewal' => max(0, floor(($periodEnd - $now) / 86400)),
            'cancel_at_period_end' => (bool)$subscription['cancel_at_period_end'],
            'canceled_at' => $subscription['canceled_at'],
            'created_at' => $subscription['created_at']
        ];
    }
    
    echo json_encode($response);
    
} catch (Exception $e) {
    error_log('Status error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Status konnte nicht geladen werden']);
}
