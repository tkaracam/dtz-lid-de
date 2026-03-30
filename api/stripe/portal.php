<?php
declare(strict_types=1);

/**
 * Stripe Customer Portal Session
 * Creates a billing portal session for managing subscriptions
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
    
    // Get customer ID
    $subscription = $db->selectOne(
        "SELECT stripe_customer_id FROM subscriptions 
         WHERE user_id = ? AND stripe_customer_id IS NOT NULL
         ORDER BY created_at DESC LIMIT 1",
        [$user['id']]
    );
    
    if (!$subscription || empty($subscription['stripe_customer_id'])) {
        http_response_code(404);
        echo json_encode(['error' => 'Kein Stripe-Kunde gefunden']);
        exit;
    }
    
    $customerId = $subscription['stripe_customer_id'];
    $returnUrl = $input['return_url'] ?? 'https://dtz-lid.de/dashboard.html';
    
    // Create portal session
    $ch = curl_init('https://api.stripe.com/v1/billing_portal/sessions');
    curl_setopt_array($ch, [
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => http_build_query([
            'customer' => $customerId,
            'return_url' => $returnUrl
        ]),
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
    
    $session = json_decode($response, true);
    
    echo json_encode([
        'success' => true,
        'url' => $session['url']
    ]);
    
} catch (Exception $e) {
    error_log('Portal error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Portal konnte nicht erstellt werden']);
}
