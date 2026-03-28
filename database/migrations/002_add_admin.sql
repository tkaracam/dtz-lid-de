-- Add admin support to users table
ALTER TABLE users ADD COLUMN is_admin BOOLEAN DEFAULT 0;
ALTER TABLE users ADD COLUMN is_super_admin BOOLEAN DEFAULT 0;

-- Create default admin user (password: Admin123!)
-- Run this manually after migration and change password!
-- INSERT INTO users (email, password_hash, display_name, is_admin, is_super_admin, subscription_status) 
-- VALUES ('admin@dtz-lid.de', '$argon2id$v=19$m=65536,t=4,p=1$...', 'Administrator', 1, 1, 'premium');

-- Index for admin queries
CREATE INDEX IF NOT EXISTS idx_users_admin ON users(is_admin);
