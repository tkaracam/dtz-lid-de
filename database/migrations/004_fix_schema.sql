-- Schema fixes for compatibility
-- Add missing columns to users table

-- Add name column (alias for display_name)
ALTER TABLE users ADD COLUMN IF NOT EXISTS name VARCHAR(100);

-- Add role column
ALTER TABLE users ADD COLUMN IF NOT EXISTS role VARCHAR(20) DEFAULT 'user' CHECK (role IN ('user', 'admin'));

-- Add is_active column
ALTER TABLE users ADD COLUMN IF NOT EXISTS is_active BOOLEAN DEFAULT TRUE;

-- Update existing users: copy display_name to name
UPDATE users SET name = display_name WHERE name IS NULL;

-- Add points column to question_pools if not exists
ALTER TABLE question_pools ADD COLUMN IF NOT EXISTS points INTEGER DEFAULT 5;

-- Add is_active to question_pools if using different name
-- (already exists in 001_initial_schema as is_active)

-- Fix modelltest_attempts table to use proper constraints
-- First drop existing table if needed and recreate
DROP TABLE IF EXISTS modelltest_attempts;

CREATE TABLE modelltest_attempts (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER NOT NULL,
    
    started_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    completed_at TIMESTAMP,
    
    -- Time limits per module
    hoeren_end_time TIMESTAMP,
    lesen_end_time TIMESTAMP,
    schreiben_end_time TIMESTAMP,
    sprechen_end_time TIMESTAMP,
    
    -- Current module
    current_module VARCHAR(20) CHECK (current_module IN ('hoeren', 'lesen', 'schreiben', 'sprechen')),
    
    -- All questions for this attempt (JSON)
    questions JSON,
    
    -- All answers stored as JSON
    answers JSON DEFAULT '{}',
    
    -- Scoring (calculated at end)
    hoeren_score INTEGER,
    lesen_score INTEGER,
    schreiben_score INTEGER,
    sprechen_score INTEGER,
    
    total_score INTEGER,
    max_possible_score INTEGER DEFAULT 100,
    
    -- Level estimation
    estimated_level VARCHAR(2) CHECK (estimated_level IN ('A2', 'B1')),
    passed BOOLEAN,
    
    -- Status
    status VARCHAR(20) DEFAULT 'in_progress' CHECK (status IN ('in_progress', 'completed', 'abandoned')),
    
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE INDEX idx_modelltest_user ON modelltest_attempts(user_id, started_at DESC);
CREATE INDEX idx_modelltest_status ON modelltest_attempts(status) WHERE status = 'in_progress';

-- Create user_progress table if not exists (simplified version)
CREATE TABLE IF NOT EXISTS user_progress (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER NOT NULL,
    question_id INTEGER NOT NULL,
    is_correct BOOLEAN,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (question_id) REFERENCES question_pools(id) ON DELETE CASCADE
);

-- Create indexes
CREATE INDEX IF NOT EXISTS idx_user_progress_user ON user_progress(user_id);
