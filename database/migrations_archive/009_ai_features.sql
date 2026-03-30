-- AI Features Migration
-- Creates tables for AI tutor, caching, and mistake tracking

-- Migration tracking table (if not exists)
CREATE TABLE IF NOT EXISTS schema_migrations (
    version TEXT PRIMARY KEY,
    applied_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    description TEXT
);

-- AI Response Cache
CREATE TABLE IF NOT EXISTS ai_cache (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    cache_key TEXT UNIQUE NOT NULL,
    response TEXT NOT NULL,
    expires_at DATETIME NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

CREATE INDEX IF NOT EXISTS idx_ai_cache_expires ON ai_cache(expires_at);
CREATE INDEX IF NOT EXISTS idx_ai_cache_key ON ai_cache(cache_key);

-- AI Interactions Log
CREATE TABLE IF NOT EXISTS ai_interactions (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER NOT NULL,
    action_type TEXT NOT NULL,
    input_preview TEXT,
    response_preview TEXT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE INDEX IF NOT EXISTS idx_ai_interactions_user ON ai_interactions(user_id);
CREATE INDEX IF NOT EXISTS idx_ai_interactions_type ON ai_interactions(action_type);
CREATE INDEX IF NOT EXISTS idx_ai_interactions_created ON ai_interactions(created_at);

-- User Mistakes for Pattern Analysis
CREATE TABLE IF NOT EXISTS user_mistakes (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER NOT NULL,
    question_preview TEXT,
    user_answer TEXT,
    correct_answer TEXT,
    module TEXT,
    mistake_type TEXT,
    ai_analysis TEXT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE INDEX IF NOT EXISTS idx_user_mistakes_user ON user_mistakes(user_id);
CREATE INDEX IF NOT EXISTS idx_user_mistakes_module ON user_mistakes(module);
CREATE INDEX IF NOT EXISTS idx_user_mistakes_type ON user_mistakes(mistake_type);
CREATE INDEX IF NOT EXISTS idx_user_mistakes_created ON user_mistakes(created_at);

-- AI Writing Feedback Cache
CREATE TABLE IF NOT EXISTS ai_writing_feedback (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER NOT NULL,
    task_type TEXT NOT NULL,
    submission_hash TEXT UNIQUE NOT NULL,
    feedback TEXT NOT NULL,
    score INTEGER,
    criteria_scores TEXT, -- JSON format
    expires_at DATETIME NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE INDEX IF NOT EXISTS idx_ai_writing_user ON ai_writing_feedback(user_id);
CREATE INDEX IF NOT EXISTS idx_ai_writing_hash ON ai_writing_feedback(submission_hash);
CREATE INDEX IF NOT EXISTS idx_ai_writing_expires ON ai_writing_feedback(expires_at);

-- Speaking Analysis Cache
CREATE TABLE IF NOT EXISTS ai_speaking_analysis (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER NOT NULL,
    recording_hash TEXT UNIQUE NOT NULL,
    transcription TEXT,
    analysis TEXT NOT NULL,
    pronunciation_score INTEGER,
    grammar_score INTEGER,
    fluency_score INTEGER,
    vocabulary_score INTEGER,
    overall_score INTEGER,
    expires_at DATETIME NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE INDEX IF NOT EXISTS idx_ai_speaking_user ON ai_speaking_analysis(user_id);
CREATE INDEX IF NOT EXISTS idx_ai_speaking_hash ON ai_speaking_analysis(recording_hash);
CREATE INDEX IF NOT EXISTS idx_ai_speaking_expires ON ai_speaking_analysis(expires_at);

-- Personalized Learning Recommendations
CREATE TABLE IF NOT EXISTS ai_recommendations (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER NOT NULL,
    recommendation_type TEXT NOT NULL,
    content TEXT NOT NULL,
    based_on_data TEXT, -- JSON format
    is_read BOOLEAN DEFAULT 0,
    is_applied BOOLEAN DEFAULT 0,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    expires_at DATETIME,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE INDEX IF NOT EXISTS idx_ai_recommendations_user ON ai_recommendations(user_id);
CREATE INDEX IF NOT EXISTS idx_ai_recommendations_type ON ai_recommendations(recommendation_type);
CREATE INDEX IF NOT EXISTS idx_ai_recommendations_read ON ai_recommendations(is_read);

-- Grammar Explanation Cache
CREATE TABLE IF NOT EXISTS ai_grammar_cache (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    topic TEXT UNIQUE NOT NULL,
    explanation TEXT NOT NULL,
    examples TEXT, -- JSON array
    related_topics TEXT, -- JSON array
    difficulty_level TEXT,
    expires_at DATETIME NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

CREATE INDEX IF NOT EXISTS idx_ai_grammar_topic ON ai_grammar_cache(topic);
CREATE INDEX IF NOT EXISTS idx_ai_grammar_expires ON ai_grammar_cache(expires_at);

-- AI Usage Statistics (for rate limiting and monitoring)
CREATE TABLE IF NOT EXISTS ai_usage_stats (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER NOT NULL,
    date DATE NOT NULL,
    requests_count INTEGER DEFAULT 0,
    tokens_used INTEGER DEFAULT 0,
    cost_estimate REAL DEFAULT 0,
    UNIQUE(user_id, date),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE INDEX IF NOT EXISTS idx_ai_usage_user_date ON ai_usage_stats(user_id, date);

-- Add AI preferences to users table
ALTER TABLE users ADD COLUMN ai_enabled BOOLEAN DEFAULT 1;
ALTER TABLE users ADD COLUMN ai_hint_level INTEGER DEFAULT 1;
ALTER TABLE users ADD COLUMN ai_personalization_enabled BOOLEAN DEFAULT 1;

-- Migration metadata
INSERT OR REPLACE INTO schema_migrations (version, applied_at, description)
VALUES ('009', datetime('now'), 'AI features: tutor, caching, mistake tracking');
