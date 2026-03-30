<?php
declare(strict_types=1);

/**
 * Cancel Subscription Endpoint
 * Cancels subscription at period end
 */

require_once __DIR__ . '/../../api/config.php';
require_once __DIR__ . '/../../src/Auth/AuthController.php';
require_once __DIR__ . '/../../src/Database/Database.php';

use DTZ\Auth\AuthController;
use DTZ\Database\Database;

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
header('Access-Control-Allow-Methods: POST, OPTIONS');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Nur POST erlaubt']);
    exit;
}

$stripeSecretKey = defined('STRIPE_SECRET_KEY') ? STRIPE_SECRET_KEY : '';

if (empty($stripeSecretKey)) {
    http_response_code(500);
    echo json_encode(['error' => 'Stripe nicht konfiguriert']);
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
    
    // Get active subscription
    $subscription = $db->selectOne(
        "SELECT * FROM subscriptions 
         WHERE user_id = ? AND status IN ('active', 'trialing')
         AND (cancel_at_period_end = 0 OR cancel_at_period_end IS NULL)
         ORDER BY created_at DESC LIMIT 1",
        [$user['id']]
    );
    
    if (!$subscription) {
        http_response_code(404);
        echo json_encode(['error' => 'Kein aktives Abonnement gefunden']);
        exit;
    }
    
    $stripeSubId = $subscription['stripe_subscription_id'];
    
    // Call Stripe API to cancel at period end
    $ch = curl_init("https://api.stripe.com/v1/subscriptions/$stripeSubId");
    curl_setopt_array($ch, [
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => http_build_query(['cancel_at_period_end' => 'true']),
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => [
            'Authorization: Bearer ' . $stripeSecretKey,
            'Content-Type: application/x-www-form-urlencoded'
        ]
    ]);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($httpCode !== 200) {
        $error = json_decode($response, true);
        throw new Exception($error['error']['message'] ?? 'Stripe API error');
    }
    
    $result = json_decode($response, true);
    
    // Update database
    $db->update('subscriptions', [
        'cancel_at_period_end' => 1,
        'canceled_at' => date('Y-m-d H:i:s'),
        'updated_at' => date('Y-m-d H:i:s')
    ], 'stripe_subscription_id = ?', [$stripeSubId]);
    
    echo json_encode([
        'success' => true,
        'message' => 'Abonnement wird am Ende der Laufzeit gekündigt',
        'current_period_end' => date('Y-m-d H:i:s', $result['current_period_end']),
        'cancel_at_period_end' => true
    ]);
    
} catch (Exception $e) {
    error_log('Cancel error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Kündigung fehlgeschlagen: ' . $e->getMessage()]);
}
