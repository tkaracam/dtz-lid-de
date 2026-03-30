<?php
declare(strict_types=1);

/**
 * Stripe Webhook Endpoint
 * Handles Stripe webhook events for subscription lifecycle
 */

require_once __DIR__ . '/../../api/config.php';
require_once __DIR__ . '/../../src/Database/Database.php';

use DTZ\Database\Database;

header('Content-Type: application/json; charset=utf-8');

// Get Stripe webhook secret
$webhookSecret = defined('STRIPE_WEBHOOK_SECRET') ? STRIPE_WEBHOOK_SECRET : '';

if (empty($webhookSecret)) {
    http_response_code(500);
    echo json_encode(['error' => 'Webhook secret nicht konfiguriert']);
    exit;
}

// Get the raw POST body
$payload = file_get_contents('php://input');
$sigHeader = $_SERVER['HTTP_STRIPE_SIGNATURE'] ?? '';

if (empty($payload) || empty($sigHeader)) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid request']);
    exit;
}

try {
    $db = Database::getInstance();
    
    // Verify webhook signature
    $event = verifyWebhookSignature($payload, $sigHeader, $webhookSecret);
    
    if (!$event) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid signature']);
        exit;
    }
    
    // Check for duplicate event (idempotency)
    $existingEvent = $db->selectOne(
        "SELECT id FROM stripe_events WHERE stripe_event_id = ?",
        [$event['id']]
    );
    
    if ($existingEvent) {
        // Already processed
        echo json_encode(['success' => true, 'message' => 'Already processed']);
        exit;
    }
    
    // Log the event
    $db->insert('stripe_events', [
        'stripe_event_id' => $event['id'],
        'event_type' => $event['type'],
        'event_data' => json_encode($event['data'])
    ]);
    
    // Process the event
    switch ($event['type']) {
        case 'checkout.session.completed':
            handleCheckoutSessionCompleted($db, $event['data']['object']);
            break;
            
        case 'invoice.paid':
            handleInvoicePaid($db, $event['data']['object']);
            break;
            
        case 'invoice.payment_failed':
            handleInvoicePaymentFailed($db, $event['data']['object']);
            break;
            
        case 'customer.subscription.created':
        case 'customer.subscription.updated':
            handleSubscriptionUpdated($db, $event['data']['object']);
            break;
            
        case 'customer.subscription.deleted':
            handleSubscriptionDeleted($db, $event['data']['object']);
            break;
            
        case 'customer.subscription.trial_will_end':
            handleTrialWillEnd($db, $event['data']['object']);
            break;
            
        default:
            // Unhandled event type - just log it
            error_log('Unhandled Stripe event: ' . $event['type']);
    }
    
    echo json_encode(['success' => true]);
    
} catch (Exception $e) {
    error_log('Webhook error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Webhook processing failed']);
}

/**
 * Verify Stripe webhook signature
 */
function verifyWebhookSignature(string $payload, string $sigHeader, string $secret): ?array
{
    // Parse signature header
    $timestamp = null;
    $signatures = [];
    
    $items = explode(',', $sigHeader);
    foreach ($items as $item) {
        $item = trim($item);
        if (strpos($item, 't=') === 0) {
            $timestamp = substr($item, 2);
        } elseif (strpos($item, 'v1=') === 0) {
            $signatures[] = substr($item, 3);
        }
    }
    
    if (empty($timestamp) || empty($signatures)) {
        return null;
    }
    
    // Check timestamp (allow 5 minute tolerance)
    if (abs(time() - (int)$timestamp) > 300) {
        return null;
    }
    
    // Compute expected signature
    $signedPayload = $timestamp . '.' . $payload;
    $expectedSignature = hash_hmac('sha256', $signedPayload, $secret);
    
    // Verify signature
    $valid = false;
    foreach ($signatures as $signature) {
        if (hash_equals($expectedSignature, $signature)) {
            $valid = true;
            break;
        }
    }
    
    if (!$valid) {
        return null;
    }
    
    return json_decode($payload, true);
}

/**
 * Handle checkout.session.completed
 */
function handleCheckoutSessionCompleted(Database $db, array $session): void
{
    $userId = (int)($session['client_reference_id'] ?? 0);
    $customerId = $session['customer'] ?? null;
    $subscriptionId = $session['subscription'] ?? null;
    
    if (!$userId || !$customerId || !$subscriptionId) {
        error_log('Missing data in checkout.session.completed');
        return;
    }
    
    // Update user's subscription status to trialing (trial starts immediately)
    $db->update('users', [
        'subscription_status' => 'trialing',
        'updated_at' => date('Y-m-d H:i:s')
    ], 'id = ?', [$userId]);
    
    // Subscription details will be updated by customer.subscription.created event
}

/**
 * Handle invoice.paid
 */
function handleInvoicePaid(Database $db, array $invoice): void
{
    $subscriptionId = $invoice['subscription'] ?? null;
    $customerId = $invoice['customer'] ?? null;
    $paymentIntentId = $invoice['payment_intent'] ?? null;
    
    if (!$subscriptionId) {
        return; // One-time payment or other invoice
    }
    
    // Find subscription in our database
    $sub = $db->selectOne(
        "SELECT id, user_id FROM subscriptions WHERE stripe_subscription_id = ?",
        [$subscriptionId]
    );
    
    if (!$sub) {
        error_log('Subscription not found for invoice: ' . $subscriptionId);
        return;
    }
    
    // Record payment
    $db->insert('payments', [
        'user_id' => $sub['user_id'],
        'subscription_id' => $sub['id'],
        'stripe_payment_intent_id' => $paymentIntentId,
        'stripe_invoice_id' => $invoice['id'],
        'stripe_subscription_id' => $subscriptionId,
        'amount' => $invoice['amount_paid'] / 100, // Convert from cents
        'currency' => strtoupper($invoice['currency']),
        'status' => 'succeeded',
        'invoice_number' => $invoice['number'] ?? null,
        'invoice_url' => $invoice['hosted_invoice_url'] ?? null,
        'receipt_url' => $invoice['receipt_url'] ?? null
    ]);
    
    // Update user's subscription status to premium
    $db->update('users', [
        'subscription_status' => 'premium',
        'updated_at' => date('Y-m-d H:i:s')
    ], 'id = ?', [$sub['user_id']]);
}

/**
 * Handle invoice.payment_failed
 */
function handleInvoicePaymentFailed(Database $db, array $invoice): void
{
    $subscriptionId = $invoice['subscription'] ?? null;
    
    if (!$subscriptionId) {
        return;
    }
    
    $sub = $db->selectOne(
        "SELECT id, user_id FROM subscriptions WHERE stripe_subscription_id = ?",
        [$subscriptionId]
    );
    
    if (!$sub) {
        return;
    }
    
    // Record failed payment
    $db->insert('payments', [
        'user_id' => $sub['user_id'],
        'subscription_id' => $sub['id'],
        'stripe_invoice_id' => $invoice['id'],
        'stripe_subscription_id' => $subscriptionId,
        'amount' => ($invoice['amount_due'] ?? 0) / 100,
        'currency' => strtoupper($invoice['currency'] ?? 'eur'),
        'status' => 'failed',
        'failure_message' => $invoice['last_finalization_error'] ?? 'Payment failed'
    ]);
    
    // Update subscription status
    $db->update('subscriptions', [
        'status' => 'past_due',
        'updated_at' => date('Y-m-d H:i:s')
    ], 'stripe_subscription_id = ?', [$subscriptionId]);
    
    // Update user status
    $db->update('users', [
        'subscription_status' => 'expired',
        'updated_at' => date('Y-m-d H:i:s')
    ], 'id = ?', [$sub['user_id']]);
}

/**
 * Handle subscription created/updated
 */
function handleSubscriptionUpdated(Database $db, array $subscription): void
{
    $stripeSubId = $subscription['id'];
    $customerId = $subscription['customer'];
    $userId = null;
    
    // Try to find user by existing subscription
    $existingSub = $db->selectOne(
        "SELECT user_id FROM subscriptions WHERE stripe_subscription_id = ?",
        [$stripeSubId]
    );
    
    if ($existingSub) {
        $userId = $existingSub['user_id'];
    } else {
        // Try to find user by client_reference_id from checkout session
        // or by customer ID from previous subscriptions
        $prevSub = $db->selectOne(
            "SELECT user_id FROM subscriptions WHERE stripe_customer_id = ? ORDER BY created_at DESC LIMIT 1",
            [$customerId]
        );
        
        if ($prevSub) {
            $userId = $prevSub['user_id'];
        }
    }
    
    if (!$userId) {
        error_log('Could not find user for subscription: ' . $stripeSubId);
        return;
    }
    
    // Map Stripe status to our status
    $status = $subscription['status'];
    
    // Determine plan details from price
    $priceId = $subscription['items']['data'][0]['price']['id'] ?? '';
    $planName = 'monthly';
    $planInterval = 'month';
    $amount = ($subscription['items']['data'][0]['price']['unit_amount'] ?? 0) / 100;
    
    // Check if it's yearly plan (you may need to adjust this logic based on your price IDs)
    if (strpos($priceId, 'year') !== false || 
        ($subscription['items']['data'][0]['price']['recurring']['interval'] ?? '') === 'year') {
        $planName = 'yearly';
        $planInterval = 'year';
    }
    
    $subData = [
        'user_id' => $userId,
        'stripe_subscription_id' => $stripeSubId,
        'stripe_customer_id' => $customerId,
        'stripe_price_id' => $priceId,
        'plan_name' => $planName,
        'plan_interval' => $planInterval,
        'amount' => $amount,
        'currency' => strtoupper($subscription['currency'] ?? 'eur'),
        'status' => $status,
        'current_period_start' => date('Y-m-d H:i:s', $subscription['current_period_start']),
        'current_period_end' => date('Y-m-d H:i:s', $subscription['current_period_end']),
        'cancel_at_period_end' => $subscription['cancel_at_period_end'] ? 1 : 0,
        'updated_at' => date('Y-m-d H:i:s')
    ];
    
    // Add optional fields
    if (!empty($subscription['trial_start'])) {
        $subData['trial_start'] = date('Y-m-d H:i:s', $subscription['trial_start']);
    }
    if (!empty($subscription['trial_end'])) {
        $subData['trial_end'] = date('Y-m-d H:i:s', $subscription['trial_end']);
    }
    if (!empty($subscription['canceled_at'])) {
        $subData['canceled_at'] = date('Y-m-d H:i:s', $subscription['canceled_at']);
    }
    if (!empty($subscription['ended_at'])) {
        $subData['ended_at'] = date('Y-m-d H:i:s', $subscription['ended_at']);
    }
    if (!empty($subscription['default_payment_method'])) {
        $subData['default_payment_method'] = $subscription['default_payment_method'];
    }
    
    if ($existingSub) {
        // Update existing
        $db->update('subscriptions', $subData, 'stripe_subscription_id = ?', [$stripeSubId]);
    } else {
        // Insert new
        $subData['created_at'] = date('Y-m-d H:i:s');
        $db->insert('subscriptions', $subData);
    }
    
    // Update user subscription status
    $userStatus = match ($status) {
        'active' => 'premium',
        'trialing' => 'trialing',
        'past_due', 'unpaid', 'canceled' => 'expired',
        default => 'free'
    };
    
    $db->update('users', [
        'subscription_status' => $userStatus,
        'updated_at' => date('Y-m-d H:i:s')
    ], 'id = ?', [$userId]);
}

/**
 * Handle subscription deleted
 */
function handleSubscriptionDeleted(Database $db, array $subscription): void
{
    $stripeSubId = $subscription['id'];
    
    $sub = $db->selectOne(
        "SELECT id, user_id FROM subscriptions WHERE stripe_subscription_id = ?",
        [$stripeSubId]
    );
    
    if (!$sub) {
        return;
    }
    
    // Update subscription
    $db->update('subscriptions', [
        'status' => 'canceled',
        'ended_at' => date('Y-m-d H:i:s', $subscription['ended_at'] ?? time()),
        'updated_at' => date('Y-m-d H:i:s')
    ], 'stripe_subscription_id = ?', [$stripeSubId]);
    
    // Update user status
    $db->update('users', [
        'subscription_status' => 'free',
        'updated_at' => date('Y-m-d H:i:s')
    ], 'id = ?', [$sub['user_id']]);
}

/**
 * Handle trial_will_end (notify user trial is ending)
 */
function handleTrialWillEnd(Database $db, array $subscription): void
{
    $stripeSubId = $subscription['id'];
    
    $sub = $db->selectOne(
        "SELECT user_id FROM subscriptions WHERE stripe_subscription_id = ?",
        [$stripeSubId]
    );
    
    if (!$sub) {
        return;
    }
    
    // Here you could send an email notification or update a flag
    // For now, just log it
    error_log('Trial ending soon for user: ' . $sub['user_id']);
}
