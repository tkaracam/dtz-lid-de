-- Speaking Submissions Table
-- Stores audio recordings and their transcriptions for speech practice

CREATE TABLE IF NOT EXISTS speaking_submissions (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    
    -- User and task references
    user_id INTEGER NOT NULL,
    task_id VARCHAR(50),  -- Reference to question_pools.id or custom task
    teil INTEGER NOT NULL CHECK (teil BETWEEN 1 AND 3),
    
    -- Audio file info
    audio_path VARCHAR(500) NOT NULL,
    original_filename VARCHAR(255),
    mime_type VARCHAR(50),
    file_size INTEGER,  -- in bytes
    duration_seconds INTEGER,  -- recorded duration
    
    -- Transcription
    transcription TEXT,
    transcription_started_at TIMESTAMP NULL,
    transcription_completed_at TIMESTAMP NULL,
    
    -- AI Analysis
    ai_analysis TEXT,  -- JSON with detailed feedback
    ai_score INTEGER CHECK (ai_score BETWEEN 0 AND 100),
    analysis_started_at TIMESTAMP NULL,
    analysis_completed_at TIMESTAMP NULL,
    
    -- Admin review (optional)
    admin_feedback TEXT,
    admin_score INTEGER CHECK (admin_score BETWEEN 0 AND 100),
    reviewed_by INTEGER,
    reviewed_at TIMESTAMP NULL,
    
    -- Status: uploaded -> transcribing -> transcribed -> analyzing -> analyzed -> reviewed
    status VARCHAR(20) DEFAULT 'uploaded' 
        CHECK (status IN ('uploaded', 'transcribing', 'transcribed', 'analyzing', 'analyzed', 'reviewed', 'error')),
    
    error_message TEXT,
    
    -- Metadata
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (reviewed_by) REFERENCES users(id) ON DELETE SET NULL
);

-- Indexes for performance
CREATE INDEX IF NOT EXISTS idx_speaking_user ON speaking_submissions(user_id);
CREATE INDEX IF NOT EXISTS idx_speaking_status ON speaking_submissions(status);
CREATE INDEX IF NOT EXISTS idx_speaking_teil ON speaking_submissions(teil);
CREATE INDEX IF NOT EXISTS idx_speaking_created ON speaking_submissions(created_at);

-- Combined index for user's submissions by teil
CREATE INDEX IF NOT EXISTS idx_speaking_user_teil ON speaking_submissions(user_id, teil);

-- Index for pending processing
CREATE INDEX IF NOT EXISTS idx_speaking_pending ON speaking_submissions(status) WHERE status IN ('uploaded', 'transcribed');
