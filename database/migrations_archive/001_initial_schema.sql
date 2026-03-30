-- DTZ-LID Abonelik Platformu - Initial Schema
-- PostgreSQL/SQLite uyumlu

-- Enable UUID extension (PostgreSQL)
-- CREATE EXTENSION IF NOT EXISTS "uuid-ossp";

-- USERS: Abonelikli kullanıcılar
CREATE TABLE IF NOT EXISTS users (
    id INTEGER PRIMARY KEY AUTOINCREMENT, -- SQLite
    -- id UUID PRIMARY KEY DEFAULT uuid_generate_v4(), -- PostgreSQL
    email VARCHAR(255) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    display_name VARCHAR(100) NOT NULL,
    level VARCHAR(2) DEFAULT 'A2' CHECK (level IN ('A1', 'A2', 'B1', 'B2')),
    
    -- Abonelik durumu
    subscription_status VARCHAR(20) DEFAULT 'free' 
        CHECK (subscription_status IN ('free', 'trialing', 'premium', 'expired', 'canceled')),
    trial_ends_at TIMESTAMP NULL,
    
    -- Öğrenme hedefleri
    daily_goal INTEGER DEFAULT 10 CHECK (daily_goal BETWEEN 1 AND 100),
    streak_count INTEGER DEFAULT 0,
    last_activity_at TIMESTAMP NULL,
    
    -- Güvenlik
    email_verified_at TIMESTAMP NULL,
    reset_token VARCHAR(100) NULL,
    reset_token_expires_at TIMESTAMP NULL,
    
    -- Metadata
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- SUBSCRIPTIONS: Abonelik geçmişi ve aktif abonelikler
CREATE TABLE IF NOT EXISTS subscriptions (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER NOT NULL,
    
    -- Ödeme sağlayıcı bilgileri
    provider VARCHAR(20) NOT NULL CHECK (provider IN ('stripe', 'paypal', 'manual')),
    provider_subscription_id VARCHAR(100) NOT NULL,
    provider_customer_id VARCHAR(100),
    
    -- Plan bilgileri
    plan_id VARCHAR(50) NOT NULL, -- basic_monthly, premium_yearly
    plan_name VARCHAR(100) NOT NULL,
    amount DECIMAL(10, 2) NOT NULL,
    currency VARCHAR(3) DEFAULT 'EUR',
    
    -- Durum
    status VARCHAR(20) NOT NULL 
        CHECK (status IN ('trialing', 'active', 'past_due', 'canceled', 'unpaid')),
    
    -- Tarihler
    current_period_start TIMESTAMP NOT NULL,
    current_period_end TIMESTAMP NOT NULL,
    cancel_at_period_end BOOLEAN DEFAULT 0,
    canceled_at TIMESTAMP NULL,
    ended_at TIMESTAMP NULL,
    
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- PAYMENTS: Ödeme geçmişi
CREATE TABLE IF NOT EXISTS payments (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER NOT NULL,
    subscription_id INTEGER,
    
    provider_payment_id VARCHAR(100) NOT NULL,
    amount DECIMAL(10, 2) NOT NULL,
    currency VARCHAR(3) DEFAULT 'EUR',
    status VARCHAR(20) NOT NULL CHECK (status IN ('succeeded', 'pending', 'failed')),
    
    receipt_url TEXT,
    failure_message TEXT,
    
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (subscription_id) REFERENCES subscriptions(id) ON DELETE SET NULL
);

-- QUESTION_POOLS: Soru bankası (dinamik)
CREATE TABLE IF NOT EXISTS question_pools (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    
    -- Kategorizasyon
    module VARCHAR(20) NOT NULL 
        CHECK (module IN ('lesen', 'hoeren', 'schreiben', 'sprechen', 'lid')),
    teil INTEGER NOT NULL CHECK (teil BETWEEN 1 AND 5),
    level VARCHAR(2) NOT NULL CHECK (level IN ('A1', 'A2', 'B1', 'B2')),
    
    -- Soru içeriği (JSON)
    question_type VARCHAR(30) NOT NULL 
        CHECK (question_type IN ('multiple_choice', 'text_input', 'matching', 'ordering', 'audio')),
    content JSON NOT NULL, -- Soru metni, seçenekler, görseller
    media_urls JSON, -- Ses/video dosyaları ["url1", "url2"]
    correct_answer JSON NOT NULL, -- Doğru cevap ve varyasyonlar
    explanation TEXT, -- Açıklama/çözüm
    hints JSON, -- İpuçları ["hint1", "hint2"]
    
    -- Zorluk ve kalite
    difficulty INTEGER CHECK (difficulty BETWEEN 1 AND 10),
    points INTEGER DEFAULT 10, -- Soru puanı
    
    -- Kullanım istatistikleri
    usage_count INTEGER DEFAULT 0,
    correct_rate DECIMAL(5,2), -- % başarı oranı (cache)
    avg_time_seconds INTEGER, -- Ortalama cevaplama süresi
    last_used_at TIMESTAMP, -- Son kullanım tarihi
    
    -- Durum
    is_active BOOLEAN DEFAULT 1,
    is_premium_only BOOLEAN DEFAULT 0, -- Sadece premium kullanıcılara
    
    -- Metadata
    created_by INTEGER, -- Admin ID
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- USER_ANSWERS: Kullanıcı cevapları
CREATE TABLE IF NOT EXISTS user_answers (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER NOT NULL,
    question_id INTEGER NOT NULL,
    session_id VARCHAR(50) NOT NULL, -- Günlük/günlük oturum ID
    
    -- Cevap detayları
    user_answer TEXT NOT NULL, -- JSON veya text
    is_correct BOOLEAN NOT NULL,
    points_earned INTEGER DEFAULT 0,
    
    -- Performans metrikleri
    time_spent_seconds INTEGER NOT NULL,
    attempts_count INTEGER DEFAULT 1, -- Kaç denemede bildi
    
    -- İsteğe bağlı: KI değerlendirmesi (yazma/konuşma)
    ai_feedback JSON, -- {"score": 85, "feedback": "...", "improvements": [...]}
    
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (question_id) REFERENCES question_pools(id) ON DELETE CASCADE
);

-- DAILY_STATS: Günlük özet istatistikler
CREATE TABLE IF NOT EXISTS daily_stats (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER NOT NULL,
    date DATE NOT NULL,
    
    -- Genel
    total_questions INTEGER DEFAULT 0,
    correct_count INTEGER DEFAULT 0,
    total_points INTEGER DEFAULT 0,
    total_time_minutes INTEGER DEFAULT 0,
    
    -- Modül bazlı (JSON)
    module_breakdown JSON, -- {"lesen": 10, "hoeren": 5, ...}
    
    -- Hedef
    goal_reached BOOLEAN DEFAULT 0,
    streak_maintained BOOLEAN DEFAULT 0,
    
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE(user_id, date)
);

-- USER_PROGRESS_CACHE: Kullanıcı istatistik özetleri (performans için)
CREATE TABLE IF NOT EXISTS user_progress_cache (
    user_id INTEGER PRIMARY KEY,
    
    -- Genel istatistikler
    total_questions_answered INTEGER DEFAULT 0,
    total_correct INTEGER DEFAULT 0,
    accuracy_rate DECIMAL(5,2) DEFAULT 0.00,
    total_time_hours DECIMAL(10,2) DEFAULT 0.00,
    
    -- Seviye ilerlemesi
    current_level VARCHAR(2) DEFAULT 'A2',
    level_progress JSON, -- {"A2": {"completed": 150, "total": 300}, ...}
    
    -- Modül bazlı güçlü/zayıf alanlar
    module_stats JSON, -- {"lesen": {"accuracy": 85, "weak_topics": [...]}}
    
    -- Öğrenme alışkanlıkları
    best_time_of_day VARCHAR(10), -- "morning", "afternoon", "evening"
    avg_session_minutes INTEGER,
    longest_streak INTEGER DEFAULT 0,
    current_streak INTEGER DEFAULT 0,
    
    -- Son aktivite
    last_practiced_at TIMESTAMP,
    last_module VARCHAR(20),
    
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- USER_QUESTION_HISTORY: Kullanıcı-soru etkileşim geçmişi
CREATE TABLE IF NOT EXISTS user_question_history (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER NOT NULL,
    question_id INTEGER NOT NULL,
    
    -- Etkileşim
    first_seen_at TIMESTAMP,
    last_seen_at TIMESTAMP,
    times_seen INTEGER DEFAULT 0,
    times_correct INTEGER DEFAULT 0,
    
    -- Spaced repetition
    next_review_at TIMESTAMP, -- Bir sonraki tekrar zamanı
    ease_factor DECIMAL(3,2) DEFAULT 2.50, -- SM-2 algoritması
    interval_days INTEGER DEFAULT 0,
    
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (question_id) REFERENCES question_pools(id) ON DELETE CASCADE,
    UNIQUE(user_id, question_id)
);

-- SESSIONS: Aktif oturumlar (güvenlik)
CREATE TABLE IF NOT EXISTS user_sessions (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER NOT NULL,
    
    session_token VARCHAR(255) UNIQUE NOT NULL,
    refresh_token VARCHAR(255) UNIQUE,
    
    device_info JSON, -- {"browser": "Chrome", "os": "iOS", ...}
    ip_address VARCHAR(45),
    
    expires_at TIMESTAMP NOT NULL,
    last_activity_at TIMESTAMP,
    is_valid BOOLEAN DEFAULT 1,
    
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- AUDIT_LOG: Yönetim ve güvenlik logları
CREATE TABLE IF NOT EXISTS audit_logs (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER,
    
    action VARCHAR(50) NOT NULL, -- login, subscription_created, question_answered, etc.
    entity_type VARCHAR(30), -- user, subscription, question
    entity_id INTEGER,
    
    old_values JSON,
    new_values JSON,
    
    ip_address VARCHAR(45),
    user_agent TEXT,
    
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Indexes for performance
CREATE INDEX IF NOT EXISTS idx_users_email ON users(email);
CREATE INDEX IF NOT EXISTS idx_users_subscription_status ON users(subscription_status);
CREATE INDEX IF NOT EXISTS idx_subscriptions_user_id ON subscriptions(user_id);
CREATE INDEX IF NOT EXISTS idx_subscriptions_status ON subscriptions(status);
CREATE INDEX IF NOT EXISTS idx_subscriptions_period_end ON subscriptions(current_period_end);
CREATE INDEX IF NOT EXISTS idx_questions_module_teil ON question_pools(module, teil);
CREATE INDEX IF NOT EXISTS idx_questions_level ON question_pools(level);
CREATE INDEX IF NOT EXISTS idx_questions_active ON question_pools(is_active);
CREATE INDEX IF NOT EXISTS idx_answers_user_id ON user_answers(user_id);
CREATE INDEX IF NOT EXISTS idx_answers_created_at ON user_answers(created_at);
CREATE INDEX IF NOT EXISTS idx_daily_stats_user_date ON daily_stats(user_id, date);
CREATE INDEX IF NOT EXISTS idx_question_history_user ON user_question_history(user_id);
CREATE INDEX IF NOT EXISTS idx_question_history_review ON user_question_history(next_review_at);
CREATE INDEX IF NOT EXISTS idx_audit_logs_user ON audit_logs(user_id);
CREATE INDEX IF NOT EXISTS idx_audit_logs_action ON audit_logs(action);
CREATE INDEX IF NOT EXISTS idx_audit_logs_created ON audit_logs(created_at);

-- Triggers for updated_at (SQLite)
CREATE TRIGGER IF NOT EXISTS update_users_timestamp 
AFTER UPDATE ON users
BEGIN
    UPDATE users SET updated_at = CURRENT_TIMESTAMP WHERE id = NEW.id;
END;

CREATE TRIGGER IF NOT EXISTS update_subscriptions_timestamp 
AFTER UPDATE ON subscriptions
BEGIN
    UPDATE subscriptions SET updated_at = CURRENT_TIMESTAMP WHERE id = NEW.id;
END;

CREATE TRIGGER IF NOT EXISTS update_daily_stats_timestamp 
AFTER UPDATE ON daily_stats
BEGIN
    UPDATE daily_stats SET updated_at = CURRENT_TIMESTAMP WHERE id = NEW.id;
END;

-- Views for common queries
CREATE VIEW IF NOT EXISTS active_subscriptions AS
SELECT s.*, u.email, u.display_name
FROM subscriptions s
JOIN users u ON s.user_id = u.id
WHERE s.status IN ('trialing', 'active', 'past_due')
AND s.current_period_end > datetime('now');

CREATE VIEW IF NOT EXISTS user_leaderboard AS
SELECT 
    u.id,
    u.display_name,
    upc.total_questions_answered,
    upc.accuracy_rate,
    upc.total_time_hours,
    upc.longest_streak,
    RANK() OVER (ORDER BY upc.total_questions_answered DESC) as rank
FROM users u
JOIN user_progress_cache upc ON u.id = upc.user_id
WHERE u.subscription_status = 'premium'
ORDER BY upc.total_questions_answered DESC
LIMIT 100;
