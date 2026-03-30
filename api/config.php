<?php
/**
 * Optionale lokale Konfiguration.
 * In Produktion vorzugsweise Umgebungsvariablen nutzen:
 * - OPENAI_API_KEY
 * - OPENAI_MODEL
 * - ADMIN_PANEL_PASSWORD
 * - STRIPE_* 
 */

// Load .env file if exists
$envFile = __DIR__ . '/../.env';
if (file_exists($envFile)) {
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos($line, '#') === 0) continue;
        if (strpos($line, '=') === false) continue;
        list($key, $value) = explode('=', $line, 2);
        $key = trim($key);
        $value = trim($value);
        if (!empty($key) && !isset($_ENV[$key])) {
            $_ENV[$key] = $value;
            putenv("$key=$value");
        }
    }
}

$openaiKey = getenv('OPENAI_API_KEY');
$model = getenv('OPENAI_MODEL');
$adminPassword = getenv('ADMIN_PANEL_PASSWORD');
$adminUsername = getenv('ADMIN_PANEL_USERNAME');

define('OPENAI_API_KEY', is_string($openaiKey) ? $openaiKey : '');
define('OPENAI_MODEL', is_string($model) && $model !== '' ? $model : 'gpt-4.1-mini');
define('ADMIN_PANEL_USERNAME', is_string($adminUsername) && $adminUsername !== '' ? $adminUsername : 'hauptadmin');
define('ADMIN_PANEL_PASSWORD', is_string($adminPassword) && $adminPassword !== '' ? $adminPassword : 'HauptAdmin!2026');

// Stripe Configuration
$stripeSecret = getenv('STRIPE_SECRET_KEY');
$stripePublishable = getenv('STRIPE_PUBLISHABLE_KEY');
$stripeWebhook = getenv('STRIPE_WEBHOOK_SECRET');
$stripePriceMonthly = getenv('STRIPE_PRICE_MONTHLY');
$stripePriceYearly = getenv('STRIPE_PRICE_YEARLY');

define('STRIPE_SECRET_KEY', is_string($stripeSecret) ? $stripeSecret : '');
define('STRIPE_PUBLISHABLE_KEY', is_string($stripePublishable) ? $stripePublishable : '');
define('STRIPE_WEBHOOK_SECRET', is_string($stripeWebhook) ? $stripeWebhook : '');
define('STRIPE_PRICE_MONTHLY', is_string($stripePriceMonthly) ? $stripePriceMonthly : '');
define('STRIPE_PRICE_YEARLY', is_string($stripePriceYearly) ? $stripePriceYearly : '');
