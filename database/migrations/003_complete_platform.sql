-- DTZ-LID Complete Platform Schema
-- PostgreSQL compatible with all features

-- Enable UUID extension
CREATE EXTENSION IF NOT EXISTS "uuid-ossp";

-- ============================================
-- SUBSCRIPTION & PAYMENTS
-- ============================================

CREATE TABLE IF NOT EXISTS subscription_plans (
    id UUID PRIMARY KEY DEFAULT uuid_generate_v4(),
    name VARCHAR(50) NOT NULL, -- 'Basic Monthly', 'Premium Yearly'
    slug VARCHAR(50) UNIQUE NOT NULL, -- 'basic-monthly'
    description TEXT,
    price DECIMAL(10, 2) NOT NULL,
    currency VARCHAR(3) DEFAULT 'EUR',
    interval VARCHAR(20) NOT NULL CHECK (interval IN ('month', 'year')),
    interval_count INTEGER DEFAULT 1,
    features JSONB DEFAULT '[]', -- ["unlimited_questions", "writing_feedback", "modelltest"]
    is_active BOOLEAN DEFAULT true,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS subscriptions (
    id UUID PRIMARY KEY DEFAULT uuid_generate_v4(),
    user_id INTEGER REFERENCES users(id) ON DELETE CASCADE,
    plan_id UUID REFERENCES subscription_plans(id),
    
    -- Stripe/PayPal data
    provider VARCHAR(20) NOT NULL CHECK (provider IN ('stripe', 'paypal')),
    provider_subscription_id VARCHAR(100) NOT NULL,
    provider_customer_id VARCHAR(100),
    
    -- Status
    status VARCHAR(20) NOT NULL CHECK (status IN ('trialing', 'active', 'past_due', 'canceled', 'unpaid')),
    
    -- Trial
    trial_start TIMESTAMP,
    trial_end TIMESTAMP,
    
    -- Billing periods
    current_period_start TIMESTAMP NOT NULL,
    current_period_end TIMESTAMP NOT NULL,
    
    -- Cancellation
    cancel_at_period_end BOOLEAN DEFAULT false,
    canceled_at TIMESTAMP,
    
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS payments (
    id UUID PRIMARY KEY DEFAULT uuid_generate_v4(),
    subscription_id UUID REFERENCES subscriptions(id) ON DELETE SET NULL,
    user_id INTEGER REFERENCES users(id) ON DELETE CASCADE,
    
    provider_payment_id VARCHAR(100) NOT NULL,
    amount DECIMAL(10, 2) NOT NULL,
    currency VARCHAR(3) DEFAULT 'EUR',
    status VARCHAR(20) NOT NULL CHECK (status IN ('succeeded', 'pending', 'failed')),
    
    -- Receipt
    receipt_url TEXT,
    invoice_pdf TEXT,
    
    -- Failure
    failure_message TEXT,
    
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- ============================================
-- AUDIO FILES (TTS)
-- ============================================

CREATE TABLE IF NOT EXISTS audio_files (
    id UUID PRIMARY KEY DEFAULT uuid_generate_v4(),
    
    -- TTS metadata
    provider VARCHAR(20) NOT NULL DEFAULT 'azure', -- 'azure', 'elevenlabs', 'google'
    voice_id VARCHAR(50) NOT NULL, -- 'de-DE-KatjaNeural'
    
    -- Content
    text_content TEXT NOT NULL,
    text_hash VARCHAR(64) UNIQUE, -- For deduplication (SHA256)
    
    -- Scenario type
    scenario VARCHAR(20) CHECK (scenario IN ('phone', 'announcement', 'conversation', 'interview')),
    
    -- File storage
    storage_provider VARCHAR(20) DEFAULT 's3', -- 's3', 'cloudflare_r2', 'local'
    file_url TEXT NOT NULL,
    file_path TEXT, -- Internal path
    file_size_bytes INTEGER,
    duration_seconds INTEGER,
    
    -- Usage stats
    play_count INTEGER DEFAULT 0,
    
    -- Relation to question (optional)
    question_id UUID REFERENCES question_pools(id) ON DELETE SET NULL,
    
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Index for text deduplication
CREATE INDEX idx_audio_text_hash ON audio_files(text_hash);

-- ============================================
-- WRITING SUBMISSIONS (AI + Admin Review)
-- ============================================

CREATE TYPE writing_status AS ENUM (
    'pending',           -- Just submitted
    'ai_processing',     -- AI analyzing
    'ai_reviewed',       -- AI done, waiting admin
    'admin_reviewing',   -- Admin looking at it
    'approved',          -- Approved, user can see
    'rejected'           -- Rejected (rare)
);

CREATE TYPE writing_task_type AS ENUM (
    'bewerbung',         -- Job application
    'beschwerde',        -- Complaint
    'anfrage',           -- Inquiry
    'termin',            -- Appointment
    'einladung',         -- Invitation
    'danksagung'         -- Thank you
);

CREATE TABLE IF NOT EXISTS writing_submissions (
    id UUID PRIMARY KEY DEFAULT uuid_generate_v4(),
    user_id INTEGER REFERENCES users(id) ON DELETE CASCADE,
    
    -- Task info
    task_type writing_task_type NOT NULL,
    task_prompt TEXT NOT NULL,
    
    -- Content
    original_text TEXT NOT NULL,
    word_count INTEGER GENERATED ALWAYS AS (array_length(regexp_split_to_array(original_text, '\s+'), 1)) STORED,
    
    -- AI Analysis (structured JSON)
    ai_feedback JSONB, -- See structure below
    ai_score INTEGER CHECK (ai_score >= 0 AND ai_score <= 20),
    ai_processed_at TIMESTAMP,
    
    -- Admin Review
    admin_id INTEGER REFERENCES users(id) ON DELETE SET NULL,
    admin_feedback JSONB, -- Admin edits to AI feedback
    admin_comment TEXT, -- Personal comment from admin
    admin_score INTEGER CHECK (admin_score >= 0 AND admin_score <= 20),
    
    -- Final (what user sees)
    final_feedback JSONB, -- merged AI + Admin
    final_score INTEGER CHECK (final_score >= 0 AND final_score <= 20),
    
    -- Status workflow
    status writing_status DEFAULT 'pending',
    
    -- Timestamps
    submitted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    ai_completed_at TIMESTAMP,
    admin_reviewed_at TIMESTAMP,
    approved_at TIMESTAMP,
    
    -- Notifications
    user_notified BOOLEAN DEFAULT false
);

-- AI Feedback JSON Structure:
-- {
--   "overallScore": 15,
--   "levelAssessment": "B1",
--   "generalFeedback": "Guter Text mit kleinen Fehlern...",
--   "categories": {
--     "grammar": { "score": 4, "max": 5, "feedback": "..." },
--     "vocabulary": { "score": 3, "max": 5, "feedback": "..." },
--     "structure": { "score": 4, "max": 5, "feedback": "..." },
--     "content": { "score": 4, "max": 5, "feedback": "..." }
--   },
--   "corrections": [
--     {
--       "id": "corr_001",
--       "type": "grammar",
--       "severity": "major",
--       "original": "Ich habe gehen",
--       "corrected": "Ich bin gegangen",
--       "explanation": "Perfekt mit sein",
--       "startIndex": 45,
--       "endIndex": 57,
--       "context": "..."
--     }
--   ],
--   "highlights": [
--     { "type": "good", "text": "Sehr geehrte Damen und Herren", "comment": "Formal gut" },
--     { "type": "improve", "text": "ich möchte", "suggestion": "ich hätte gerne", "reason": "Höflicher" }
--   ]
-- }

CREATE INDEX idx_writing_user ON writing_submissions(user_id, submitted_at DESC);
CREATE INDEX idx_writing_status ON writing_submissions(status, submitted_at);
CREATE INDEX idx_writing_admin ON writing_submissions(status) WHERE status IN ('ai_reviewed', 'admin_reviewing');

-- ============================================
-- MODELLTEST (Full Exam Simulation)
-- ============================================

CREATE TYPE modelltest_status AS ENUM ('in_progress', 'completed', 'abandoned');

CREATE TABLE IF NOT EXISTS modelltest_attempts (
    id UUID PRIMARY KEY DEFAULT uuid_generate_v4(),
    user_id INTEGER REFERENCES users(id) ON DELETE CASCADE,
    
    -- Timestamps
    started_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    completed_at TIMESTAMP,
    
    -- Time limits (in seconds from start)
    hoeren_end_time TIMESTAMP,
    lesen_end_time TIMESTAMP,
    schreiben_end_time TIMESTAMP,
    sprechen_end_time TIMESTAMP,
    
    -- Current section
    current_module VARCHAR(20) CHECK (current_module IN ('hoeren', 'lesen', 'schreiben', 'sprechen')),
    current_teil INTEGER,
    
    -- All answers stored as JSON
    answers JSONB DEFAULT '{}',
    -- Structure: {
    --   "hoeren": { "teil1": [{"q1": "A"}, {"q2": "B"}], "teil2": [...] },
    --   "lesen": { "teil1": [...], ... },
    --   "schreiben": { "text": "...", "wordCount": 120 },
    --   "sprechen": { "teil1_recording": "url", ... }
    -- }
    
    -- Scoring (calculated at end)
    hoeren_score INTEGER,
    lesen_score INTEGER,
    schreiben_score INTEGER,
    sprechen_score INTEGER,
    
    total_score INTEGER,
    max_possible_score INTEGER DEFAULT 100,
    
    -- Level estimation
    estimated_level VARCHAR(2) CHECK (estimated_level IN ('A2', 'B1')),
    passed BOOLEAN, -- true if B1 level
    
    -- Detailed breakdown
    section_analysis JSONB, -- { "hoeren": { "teil1": { "correct": 3, "total": 4 }, ... } }
    
    -- Status
    status modelltest_status DEFAULT 'in_progress',
    
    -- User can review after completion
    review_available BOOLEAN DEFAULT false
);

CREATE INDEX idx_modelltest_user ON modelltest_attempts(user_id, started_at DESC);
CREATE INDEX idx_modelltest_status ON modelltest_attempts(status) WHERE status = 'in_progress';

-- ============================================
-- DETAILED USER PROGRESS (Analytics)
-- ============================================

CREATE TABLE IF NOT EXISTS user_progress_detailed (
    id UUID PRIMARY KEY DEFAULT uuid_generate_v4(),
    user_id INTEGER REFERENCES users(id) ON DELETE CASCADE,
    
    module VARCHAR(20) NOT NULL CHECK (module IN ('hoeren', 'lesen', 'schreiben', 'sprechen', 'lid')),
    teil INTEGER NOT NULL,
    level VARCHAR(2) NOT NULL CHECK (level IN ('A1', 'A2', 'B1', 'B2')),
    
    -- Statistics
    total_attempted INTEGER DEFAULT 0,
    total_correct INTEGER DEFAULT 0,
    accuracy_rate DECIMAL(5,2) GENERATED ALWAYS AS (
        CASE WHEN total_attempted > 0 THEN 
            (total_correct::DECIMAL / total_attempted * 100) 
        ELSE 0 END
    ) STORED,
    
    -- Time tracking
    total_time_seconds INTEGER DEFAULT 0,
    avg_time_seconds INTEGER GENERATED ALWAYS AS (
        CASE WHEN total_attempted > 0 THEN 
            (total_time_seconds / total_attempted) 
        ELSE 0 END
    ) STORED,
    
    -- Weakness detection
    weak_topics JSONB DEFAULT '[]', -- ["Nebensätze", "Perfekt"]
    strong_topics JSONB DEFAULT '[]',
    
    -- Recommendations
    recommended_questions JSONB DEFAULT '[]', -- [question_id, question_id]
    
    -- Streak tracking per module
    current_streak INTEGER DEFAULT 0,
    best_streak INTEGER DEFAULT 0,
    
    last_practiced_at TIMESTAMP,
    
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    UNIQUE(user_id, module, teil, level)
);

CREATE INDEX idx_progress_user ON user_progress_detailed(user_id, module, teil);
CREATE INDEX idx_progress_weak ON user_progress_detailed(user_id) WHERE accuracy_rate < 70;

-- ============================================
-- DAILY ACTIVITY (Heatmap/Streaks)
-- ============================================

CREATE TABLE IF NOT EXISTS daily_activity (
    id UUID PRIMARY KEY DEFAULT uuid_generate_v4(),
    user_id INTEGER REFERENCES users(id) ON DELETE CASCADE,
    date DATE NOT NULL,
    
    -- Activity summary
    total_questions INTEGER DEFAULT 0,
    correct_answers INTEGER DEFAULT 0,
    total_points INTEGER DEFAULT 0,
    
    -- Time spent
    study_time_minutes INTEGER DEFAULT 0,
    
    -- Modules practiced
    modules_practiced JSONB DEFAULT '[]', -- ["lesen", "hoeren"]
    
    -- Goals
    daily_goal_reached BOOLEAN DEFAULT false,
    
    -- Streak
    streak_maintained BOOLEAN DEFAULT false,
    
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    UNIQUE(user_id, date)
);

CREATE INDEX idx_daily_user_date ON daily_activity(user_id, date);
CREATE INDEX idx_daily_streak ON daily_activity(user_id, date) WHERE streak_maintained = true;

-- ============================================
-- NOTIFICATIONS
-- ============================================

CREATE TYPE notification_type AS ENUM (
    'writing_approved',
    'writing_rejected',
    'subscription_expiring',
    'subscription_expired',
    'achievement_unlocked',
    'streak_reminder'
);

CREATE TABLE IF NOT EXISTS notifications (
    id UUID PRIMARY KEY DEFAULT uuid_generate_v4(),
    user_id INTEGER REFERENCES users(id) ON DELETE CASCADE,
    
    type notification_type NOT NULL,
    title VARCHAR(200) NOT NULL,
    message TEXT NOT NULL,
    
    -- Action link
    action_url TEXT,
    action_text VARCHAR(50),
    
    -- Status
    is_read BOOLEAN DEFAULT false,
    read_at TIMESTAMP,
    
    -- Related entity
    related_entity_type VARCHAR(50), -- 'writing_submission', 'subscription'
    related_entity_id UUID,
    
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE INDEX idx_notifications_user ON notifications(user_id, created_at DESC) WHERE is_read = false;

-- ============================================
-- ADMIN AUDIT LOG
-- ============================================

CREATE TABLE IF NOT EXISTS admin_audit_log (
    id UUID PRIMARY KEY DEFAULT uuid_generate_v4(),
    admin_id INTEGER REFERENCES users(id) ON DELETE SET NULL,
    
    action VARCHAR(50) NOT NULL, -- 'question_created', 'writing_reviewed', 'user_banned'
    entity_type VARCHAR(50), -- 'question', 'writing', 'user'
    entity_id UUID,
    
    -- Before/After for changes
    old_values JSONB,
    new_values JSONB,
    
    -- Context
    ip_address INET,
    user_agent TEXT,
    
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE INDEX idx_audit_admin ON admin_audit_log(admin_id, created_at DESC);
CREATE INDEX idx_audit_action ON admin_audit_log(action, created_at);

-- ============================================
-- BACKGROUND JOBS (Queue tracking)
-- ============================================

CREATE TABLE IF NOT EXISTS job_queue (
    id UUID PRIMARY KEY DEFAULT uuid_generate_v4(),
    
    job_type VARCHAR(50) NOT NULL, -- 'ai_writing_analysis', 'audio_generation', 'email_notification'
    payload JSONB NOT NULL,
    
    status VARCHAR(20) DEFAULT 'pending' CHECK (status IN ('pending', 'processing', 'completed', 'failed')),
    
    -- Attempt tracking
    attempts INTEGER DEFAULT 0,
    max_attempts INTEGER DEFAULT 3,
    
    -- Error tracking
    last_error TEXT,
    failed_at TIMESTAMP,
    
    -- Processing
    processed_by VARCHAR(100), -- worker ID
    started_at TIMESTAMP,
    completed_at TIMESTAMP,
    
    -- Scheduling
    scheduled_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE INDEX idx_jobs_status ON job_queue(status, scheduled_at) WHERE status IN ('pending', 'failed');

-- ============================================
-- VIEWS FOR ANALYTICS
-- ============================================

-- User performance summary
CREATE OR REPLACE VIEW user_performance_summary AS
SELECT 
    u.id as user_id,
    u.display_name,
    u.email,
    u.subscription_status,
    
    -- Total activity
    COUNT(DISTINCT da.date) as active_days,
    SUM(da.total_questions) as total_questions_answered,
    SUM(da.total_points) as total_points_earned,
    
    -- Current streak
    u.streak_count as current_streak,
    
    -- Writing submissions
    COUNT(DISTINCT ws.id) as writing_submissions_count,
    AVG(ws.final_score) as avg_writing_score,
    
    -- Modelltest
    COUNT(DISTINCT ma.id) as modelltest_count,
    AVG(ma.total_score) as avg_modelltest_score,
    
    -- Subscription
    s.status as subscription_status,
    s.current_period_end as subscription_expires

FROM users u
LEFT JOIN daily_activity da ON u.id = da.user_id
LEFT JOIN writing_submissions ws ON u.id = ws.user_id AND ws.status = 'approved'
LEFT JOIN modelltest_attempts ma ON u.id = ma.user_id AND ma.status = 'completed'
LEFT JOIN subscriptions s ON u.id = s.user_id AND s.status = 'active'

GROUP BY u.id, u.display_name, u.email, u.streak_count, u.subscription_status, s.status, s.current_period_end;

-- Question performance analytics
CREATE OR REPLACE VIEW question_performance AS
SELECT 
    q.id as question_id,
    q.module,
    q.teil,
    q.level,
    q.question_type,
    q.difficulty,
    
    COUNT(ua.id) as times_answered,
    SUM(CASE WHEN ua.is_correct THEN 1 ELSE 0 END) as times_correct,
    
    ROUND(
        100.0 * SUM(CASE WHEN ua.is_correct THEN 1 ELSE 0 END) / NULLIF(COUNT(ua.id), 0),
        2
    ) as accuracy_rate,
    
    AVG(ua.time_spent_seconds) as avg_time_seconds,
    
    -- Difficulty adjustment suggestion
    CASE 
        WHEN COUNT(ua.id) > 20 AND 
             100.0 * SUM(CASE WHEN ua.is_correct THEN 1 ELSE 0 END) / COUNT(ua.id) > 80 
        THEN 'too_easy'
        WHEN COUNT(ua.id) > 20 AND 
             100.0 * SUM(CASE WHEN ua.is_correct THEN 1 ELSE 0 END) / COUNT(ua.id) < 30
        THEN 'too_hard'
        ELSE 'balanced'
    END as difficulty_assessment

FROM question_pools q
LEFT JOIN user_answers ua ON q.id = ua.question_id
WHERE q.is_active = true
GROUP BY q.id, q.module, q.teil, q.level, q.question_type, q.difficulty;

-- Admin dashboard stats
CREATE OR REPLACE VIEW admin_dashboard_stats AS
SELECT
    -- Users
    (SELECT COUNT(*) FROM users) as total_users,
    (SELECT COUNT(*) FROM users WHERE created_at > CURRENT_DATE - INTERVAL '30 days') as new_users_30d,
    (SELECT COUNT(*) FROM subscriptions WHERE status = 'active') as active_subscribers,
    
    -- Content
    (SELECT COUNT(*) FROM question_pools WHERE is_active = true) as active_questions,
    (SELECT COUNT(*) FROM audio_files) as audio_files_count,
    
    -- Activity
    (SELECT COUNT(*) FROM user_answers WHERE created_at > CURRENT_DATE - INTERVAL '24 hours') as answers_today,
    (SELECT COUNT(*) FROM writing_submissions WHERE status = 'pending') as pending_writing_reviews,
    
    -- Revenue (last 30 days)
    (SELECT COALESCE(SUM(amount), 0) FROM payments WHERE status = 'succeeded' AND created_at > CURRENT_DATE - INTERVAL '30 days') as revenue_30d;

-- ============================================
-- FUNCTIONS & TRIGGERS
-- ============================================

-- Auto-update user streak
CREATE OR REPLACE FUNCTION update_user_streak()
RETURNS TRIGGER AS $$
BEGIN
    -- Update user's streak count in users table
    UPDATE users 
    SET streak_count = (
        SELECT COUNT(*) 
        FROM daily_activity 
        WHERE user_id = NEW.user_id 
        AND streak_maintained = true
    )
    WHERE id = NEW.user_id;
    
    RETURN NEW;
END;
$$ LANGUAGE plpgsql;

CREATE TRIGGER trigger_update_streak
AFTER INSERT OR UPDATE ON daily_activity
FOR EACH ROW
EXECUTE FUNCTION update_user_streak();

-- Auto-calculate writing score average
CREATE OR REPLACE FUNCTION update_user_writing_avg()
RETURNS TRIGGER AS $$
BEGIN
    IF NEW.status = 'approved' AND NEW.final_score IS NOT NULL THEN
        -- This could update a cache in users table if needed
        NULL;
    END IF;
    RETURN NEW;
END;
$$ LANGUAGE plpgsql;

CREATE TRIGGER trigger_update_writing_avg
AFTER UPDATE ON writing_submissions
FOR EACH ROW
EXECUTE FUNCTION update_user_writing_avg();

-- Insert sample subscription plans
INSERT INTO subscription_plans (name, slug, description, price, interval, features) VALUES
('Basis Monatlich', 'basic-monthly', 'Unbegrenztes Üben, Schreibkorrektur in 48h', 9.99, 'month', '["unlimited_questions", "writing_feedback_48h", "basic_analytics"]'),
('Premium Monatlich', 'premium-monthly', 'Priorisierte Korrektur, alle Modelltests, detaillierte Analyse', 19.99, 'month', '["unlimited_questions", "writing_feedback_24h", "unlimited_modelltests", "advanced_analytics", "priority_support"]'),
('Premium Jährlich', 'premium-yearly', '2 Monate geschenkt! Alle Premium Features.', 199.99, 'year', '["unlimited_questions", "writing_feedback_24h", "unlimited_modelltests", "advanced_analytics", "priority_support"]')
ON CONFLICT (slug) DO NOTHING;
