-- SQLite Schema for DTZ Learning Platform

-- Users table
CREATE TABLE IF NOT EXISTS users (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    email VARCHAR(255) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    display_name VARCHAR(100) NOT NULL,
    name VARCHAR(100),
    level VARCHAR(2) DEFAULT 'A2' CHECK (level IN ('A1', 'A2', 'B1', 'B2')),
    role VARCHAR(20) DEFAULT 'user' CHECK (role IN ('user', 'admin')),
    subscription_status VARCHAR(20) DEFAULT 'free' 
        CHECK (subscription_status IN ('free', 'trialing', 'premium', 'expired', 'canceled')),
    trial_ends_at TIMESTAMP,
    is_active BOOLEAN DEFAULT 1,
    daily_goal INTEGER DEFAULT 10,
    streak_count INTEGER DEFAULT 0,
    last_activity_at TIMESTAMP,
    email_verified_at TIMESTAMP,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Question pools
CREATE TABLE IF NOT EXISTS question_pools (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    module VARCHAR(20) NOT NULL CHECK (module IN ('lesen', 'hoeren', 'schreiben', 'sprechen', 'lid')),
    teil INTEGER NOT NULL CHECK (teil BETWEEN 1 AND 5),
    level VARCHAR(2) NOT NULL CHECK (level IN ('A1', 'A2', 'B1', 'B2')),
    question_type VARCHAR(30) NOT NULL CHECK (question_type IN ('multiple_choice', 'text_input', 'matching', 'ordering', 'audio')),
    content TEXT NOT NULL,
    media_urls TEXT,
    correct_answer TEXT NOT NULL,
    explanation TEXT,
    hints TEXT,
    difficulty INTEGER CHECK (difficulty BETWEEN 1 AND 10),
    points INTEGER DEFAULT 5,
    usage_count INTEGER DEFAULT 0,
    correct_rate DECIMAL(5,2),
    avg_time_seconds INTEGER,
    last_used_at TIMESTAMP,
    is_active BOOLEAN DEFAULT 1,
    is_premium_only BOOLEAN DEFAULT 0,
    created_by INTEGER,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Modelltest attempts
CREATE TABLE IF NOT EXISTS modelltest_attempts (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER NOT NULL,
    started_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    completed_at TIMESTAMP,
    hoeren_end_time TIMESTAMP,
    lesen_end_time TIMESTAMP,
    schreiben_end_time TIMESTAMP,
    sprechen_end_time TIMESTAMP,
    current_module VARCHAR(20) CHECK (current_module IN ('hoeren', 'lesen', 'schreiben', 'sprechen')),
    questions TEXT,
    answers TEXT DEFAULT '{}',
    hoeren_score INTEGER,
    lesen_score INTEGER,
    schreiben_score INTEGER,
    sprechen_score INTEGER,
    total_score INTEGER,
    max_possible_score INTEGER DEFAULT 100,
    estimated_level VARCHAR(2) CHECK (estimated_level IN ('A2', 'B1')),
    passed BOOLEAN,
    status VARCHAR(20) DEFAULT 'in_progress' CHECK (status IN ('in_progress', 'completed', 'abandoned')),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- User answers
CREATE TABLE IF NOT EXISTS user_answers (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER NOT NULL,
    question_id INTEGER NOT NULL,
    session_id VARCHAR(50),
    user_answer TEXT NOT NULL,
    is_correct BOOLEAN,
    points_earned INTEGER DEFAULT 0,
    time_spent_seconds INTEGER DEFAULT 0,
    ai_feedback TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (question_id) REFERENCES question_pools(id) ON DELETE CASCADE
);

-- User progress (simplified)
CREATE TABLE IF NOT EXISTS user_progress (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER NOT NULL,
    question_id INTEGER NOT NULL,
    is_correct BOOLEAN,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (question_id) REFERENCES question_pools(id) ON DELETE CASCADE
);

-- Daily stats
CREATE TABLE IF NOT EXISTS daily_stats (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER NOT NULL,
    date DATE NOT NULL,
    total_questions INTEGER DEFAULT 0,
    correct_count INTEGER DEFAULT 0,
    total_points INTEGER DEFAULT 0,
    total_time_minutes INTEGER DEFAULT 0,
    module_breakdown TEXT,
    goal_reached BOOLEAN DEFAULT 0,
    streak_maintained BOOLEAN DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE(user_id, date)
);

-- Writing submissions
CREATE TABLE IF NOT EXISTS writing_submissions (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER NOT NULL,
    task_type VARCHAR(50) NOT NULL,
    task_prompt TEXT NOT NULL,
    original_text TEXT NOT NULL,
    word_count INTEGER DEFAULT 0,
    ai_feedback TEXT,
    ai_score INTEGER CHECK (ai_score >= 0 AND ai_score <= 20),
    ai_processed_at TIMESTAMP,
    admin_id INTEGER,
    admin_feedback TEXT,
    admin_comment TEXT,
    admin_score INTEGER CHECK (admin_score >= 0 AND admin_score <= 20),
    final_feedback TEXT,
    final_score INTEGER CHECK (final_score >= 0 AND final_score <= 20),
    status VARCHAR(20) DEFAULT 'pending' CHECK (status IN ('pending', 'ai_processing', 'ai_reviewed', 'admin_reviewing', 'approved', 'rejected')),
    submitted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    ai_completed_at TIMESTAMP,
    admin_reviewed_at TIMESTAMP,
    approved_at TIMESTAMP,
    user_notified BOOLEAN DEFAULT 0,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (admin_id) REFERENCES users(id) ON DELETE SET NULL
);

-- Indexes
CREATE INDEX IF NOT EXISTS idx_users_email ON users(email);
CREATE INDEX IF NOT EXISTS idx_modelltest_user ON modelltest_attempts(user_id);
CREATE INDEX IF NOT EXISTS idx_modelltest_status ON modelltest_attempts(status);
CREATE INDEX IF NOT EXISTS idx_questions_module ON question_pools(module);
CREATE INDEX IF NOT EXISTS idx_user_progress_user ON user_progress(user_id);
CREATE INDEX IF NOT EXISTS idx_daily_stats_user_date ON daily_stats(user_id, date);

-- Insert admin user (password: admin123)
INSERT OR IGNORE INTO users (id, email, password_hash, display_name, name, role, is_active, subscription_status) 
VALUES (1, 'admin@dtz.de', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Admin', 'Admin', 'admin', 1, 'premium');
