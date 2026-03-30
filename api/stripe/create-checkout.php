<?php
declare(strict_types=1);

/**
 * Stripe Checkout Session Creation Endpoint
 * Creates a Stripe Checkout session for subscription
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

// Get Stripe keys
$stripeSecretKey = defined('STRIPE_SECRET_KEY') ? STRIPE_SECRET_KEY : '';
$stripePublishableKey = defined('STRIPE_PUBLISHABLE_KEY') ? STRIPE_PUBLISHABLE_KEY : '';

if (empty($stripeSecretKey) || strpos($stripeSecretKey, 'sk_') !== 0) {
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

// Get request body
$input = json_decode(file_get_contents('php://input'), true);
$plan = $input['plan'] ?? 'monthly'; // 'monthly' or 'yearly'
$successUrl = $input['success_url'] ?? 'https://dtz-lid.de/dashboard.html?payment=success';
$cancelUrl = $input['cancel_url'] ?? 'https://dtz-lid.de/dashboard.html?payment=canceled';

// Validate plan
if (!in_array($plan, ['monthly', 'yearly'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Ungültiger Plan. Wähle monthly oder yearly']);
    exit;
}

// Price IDs from config or environment
$priceIds = [
    'monthly' => defined('STRIPE_PRICE_MONTHLY') ? STRIPE_PRICE_MONTHLY : '',
    'yearly' => defined('STRIPE_PRICE_YEARLY') ? STRIPE_PRICE_YEARLY : ''
];

if (empty($priceIds[$plan])) {
    http_response_code(500);
    echo json_encode(['error' => 'Preis-ID nicht konfiguriert für Plan: ' . $plan]);
    exit;
}

try {
    $db = Database::getInstance();
    
    // Check if user already has an active subscription
    $existingSub = $db->selectOne(
        "SELECT * FROM subscriptions 
         WHERE user_id = ? AND status IN ('active', 'trialing') 
         AND (cancel_at_period_end = 0 OR current_period_end > datetime('now'))
         ORDER BY created_at DESC LIMIT 1",
        [$user['id']]
    );
    
    if ($existingSub) {
        http_response_code(409);
        echo json_encode([
            'error' => 'Aktives Abonnement vorhanden',
            'message' => 'Du hast bereits ein aktives Abonnement. Verwalte es im Dashboard.',
            'subscription_status' => $existingSub['status']
        ]);
        exit;
    }
    
    // Get or create Stripe customer
    $customerId = null;
    $existingCustomer = $db->selectOne(
        "SELECT stripe_customer_id FROM subscriptions 
         WHERE user_id = ? AND stripe_customer_id IS NOT NULL 
         ORDER BY created_at DESC LIMIT 1",
        [$user['id']]
    );
    
    if ($existingCustomer) {
        $customerId = $existingCustomer['stripe_customer_id'];
    }
    
    // Create Stripe Checkout Session
    $sessionData = [
        'mode' => 'subscription',
        'client_reference_id' => (string)$user['id'],
        'metadata' => [
            'user_id' => (string)$user['id'],
            'email' => $user['email'],
            'plan' => $plan
        ],
        'line_items' => [
            [
                'price' => $priceIds[$plan],
                'quantity' => 1
            ]
        ],
        'success_url' => $successUrl . '&session_id={CHECKOUT_SESSION_ID}',
        'cancel_url' => $cancelUrl,
        'automatic_tax' => ['enabled' => true],
        'tax_id_collection' => ['enabled' => true],
        'billing_address_collection' => 'required',
        'allow_promotion_codes' => true
    ];
    
    // Add customer if exists, otherwise use customer_email
    if ($customerId) {
        $sessionData['customer'] = $customerId;
    } else {
        $sessionData['customer_email'] = $user['email'];
    }
    
    // 14-day trial for new subscriptions
    $sessionData['subscription_data'] = [
        'trial_period_days' => 14,
        'metadata' => [
            'user_id' => (string)$user['id'],
            'plan' => $plan
        ]
    ];
    
    // Create session via Stripe API
    $ch = curl_init('https://api.stripe.com/v1/checkout/sessions');
    curl_setopt_array($ch, [
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => http_build_query($sessionData),
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
        'session_id' => $session['id'],
        'url' => $session['url'],
        'publishable_key' => $stripePublishableKey
    ]);
    
} catch (Exception $e) {
    error_log('Stripe checkout error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Checkout konnte nicht erstellt werden: ' . $e->getMessage()]);
}
