-- Subscriptions Table for Stripe Integration
-- Tracks all subscription data from Stripe

CREATE TABLE IF NOT EXISTS subscriptions (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    
    -- User reference
    user_id INTEGER NOT NULL,
    
    -- Stripe IDs
    stripe_subscription_id VARCHAR(100) UNIQUE NOT NULL,
    stripe_customer_id VARCHAR(100) NOT NULL,
    stripe_price_id VARCHAR(100) NOT NULL,
    
    -- Plan details
    plan_name VARCHAR(50) NOT NULL,  -- 'monthly', 'yearly'
    plan_interval VARCHAR(20) NOT NULL CHECK (plan_interval IN ('month', 'year')),
    amount DECIMAL(10, 2) NOT NULL,
    currency VARCHAR(3) DEFAULT 'EUR',
    
    -- Subscription status
    status VARCHAR(30) NOT NULL 
        CHECK (status IN ('incomplete', 'incomplete_expired', 'trialing', 'active', 'past_due', 'canceled', 'unpaid', 'paused')),
    
    -- Trial period
    trial_start TIMESTAMP NULL,
    trial_end TIMESTAMP NULL,
    
    -- Current period
    current_period_start TIMESTAMP NOT NULL,
    current_period_end TIMESTAMP NOT NULL,
    
    -- Cancellation
    cancel_at_period_end BOOLEAN DEFAULT 0,
    canceled_at TIMESTAMP NULL,
    cancellation_reason TEXT NULL,
    
    -- End dates
    ended_at TIMESTAMP NULL,
    
    -- Payment method
    default_payment_method VARCHAR(100),
    
    -- Metadata
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Payments table for invoice tracking
CREATE TABLE IF NOT EXISTS payments (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    
    user_id INTEGER NOT NULL,
    subscription_id INTEGER,
    
    -- Stripe IDs
    stripe_payment_intent_id VARCHAR(100),
    stripe_invoice_id VARCHAR(100),
    stripe_subscription_id VARCHAR(100),
    
    -- Payment details
    amount DECIMAL(10, 2) NOT NULL,
    currency VARCHAR(3) DEFAULT 'EUR',
    status VARCHAR(20) NOT NULL CHECK (status IN ('succeeded', 'pending', 'failed', 'canceled')),
    
    -- Invoice details
    invoice_number VARCHAR(50),
    invoice_url TEXT,
    receipt_url TEXT,
    
    -- Failure handling
    failure_message TEXT,
    
    -- Metadata
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (subscription_id) REFERENCES subscriptions(id) ON DELETE SET NULL
);

-- Stripe events log (for webhook idempotency)
CREATE TABLE IF NOT EXISTS stripe_events (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    stripe_event_id VARCHAR(100) UNIQUE NOT NULL,
    event_type VARCHAR(50) NOT NULL,
    event_data TEXT,
    processed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Indexes for performance
CREATE INDEX IF NOT EXISTS idx_subscriptions_user ON subscriptions(user_id);
CREATE INDEX IF NOT EXISTS idx_subscriptions_stripe_id ON subscriptions(stripe_subscription_id);
CREATE INDEX IF NOT EXISTS idx_subscriptions_status ON subscriptions(status);
CREATE INDEX IF NOT EXISTS idx_subscriptions_period_end ON subscriptions(current_period_end);
CREATE INDEX IF NOT EXISTS idx_payments_user ON payments(user_id);
CREATE INDEX IF NOT EXISTS idx_payments_subscription ON payments(subscription_id);
CREATE INDEX IF NOT EXISTS idx_stripe_events_id ON stripe_events(stripe_event_id);
