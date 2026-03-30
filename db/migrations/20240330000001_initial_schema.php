<?php
declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class InitialSchema extends AbstractMigration
{
    public function change(): void
    {
        $this->createUsersTable();
        $this->createSubscriptionsTable();
        $this->createPaymentsTable();
        $this->createQuestionPoolsTable();
        $this->createUserAnswersTable();
        $this->createDailyStatsTable();
        $this->createUserProgressCacheTable();
        $this->createUserQuestionHistoryTable();
        $this->createUserSessionsTable();
        $this->createAuditLogsTable();
        $this->createIndexes();
        $this->createTriggers();
        $this->createViews();
    }

    private function createUsersTable(): void
    {
        $table = $this->table('users');
        $table->addColumn('email', 'string', ['limit' => 255, 'null' => false])
              ->addColumn('password_hash', 'string', ['limit' => 255, 'null' => false])
              ->addColumn('display_name', 'string', ['limit' => 100, 'null' => false])
              ->addColumn('level', 'string', ['limit' => 2, 'default' => 'A2'])
              ->addColumn('subscription_status', 'string', ['limit' => 20, 'default' => 'free'])
              ->addColumn('trial_ends_at', 'datetime', ['null' => true])
              ->addColumn('daily_goal', 'integer', ['default' => 10, 'signed' => false])
              ->addColumn('streak_count', 'integer', ['default' => 0, 'signed' => false])
              ->addColumn('last_activity_at', 'datetime', ['null' => true])
              ->addColumn('email_verified_at', 'datetime', ['null' => true])
              ->addColumn('reset_token', 'string', ['limit' => 100, 'null' => true])
              ->addColumn('reset_token_expires_at', 'datetime', ['null' => true])
              ->addColumn('created_at', 'datetime', ['default' => 'CURRENT_TIMESTAMP'])
              ->addColumn('updated_at', 'datetime', ['default' => 'CURRENT_TIMESTAMP'])
              ->addIndex(['email'], ['unique' => true])
              ->addIndex(['subscription_status'])
              ->create();
    }

    private function createSubscriptionsTable(): void
    {
        $table = $this->table('subscriptions');
        $table->addColumn('user_id', 'integer', ['null' => false, 'signed' => false])
              ->addColumn('provider', 'string', ['limit' => 20, 'null' => false])
              ->addColumn('provider_subscription_id', 'string', ['limit' => 100, 'null' => false])
              ->addColumn('provider_customer_id', 'string', ['limit' => 100, 'null' => true])
              ->addColumn('plan_id', 'string', ['limit' => 50, 'null' => false])
              ->addColumn('plan_name', 'string', ['limit' => 100, 'null' => false])
              ->addColumn('amount', 'decimal', ['precision' => 10, 'scale' => 2, 'null' => false])
              ->addColumn('currency', 'string', ['limit' => 3, 'default' => 'EUR'])
              ->addColumn('status', 'string', ['limit' => 20, 'null' => false])
              ->addColumn('current_period_start', 'datetime', ['null' => false])
              ->addColumn('current_period_end', 'datetime', ['null' => false])
              ->addColumn('cancel_at_period_end', 'boolean', ['default' => false])
              ->addColumn('canceled_at', 'datetime', ['null' => true])
              ->addColumn('ended_at', 'datetime', ['null' => true])
              ->addColumn('created_at', 'datetime', ['default' => 'CURRENT_TIMESTAMP'])
              ->addColumn('updated_at', 'datetime', ['default' => 'CURRENT_TIMESTAMP'])
              ->addForeignKey('user_id', 'users', 'id', ['delete' => 'CASCADE'])
              ->addIndex(['user_id'])
              ->addIndex(['status'])
              ->addIndex(['current_period_end'])
              ->create();
    }

    private function createPaymentsTable(): void
    {
        $table = $this->table('payments');
        $table->addColumn('user_id', 'integer', ['null' => false, 'signed' => false])
              ->addColumn('subscription_id', 'integer', ['null' => true, 'signed' => false])
              ->addColumn('provider_payment_id', 'string', ['limit' => 100, 'null' => false])
              ->addColumn('amount', 'decimal', ['precision' => 10, 'scale' => 2, 'null' => false])
              ->addColumn('currency', 'string', ['limit' => 3, 'default' => 'EUR'])
              ->addColumn('status', 'string', ['limit' => 20, 'null' => false])
              ->addColumn('receipt_url', 'text', ['null' => true])
              ->addColumn('failure_message', 'text', ['null' => true])
              ->addColumn('created_at', 'datetime', ['default' => 'CURRENT_TIMESTAMP'])
              ->addForeignKey('user_id', 'users', 'id', ['delete' => 'CASCADE'])
              ->addForeignKey('subscription_id', 'subscriptions', 'id', ['delete' => 'SET_NULL'])
              ->create();
    }

    private function createQuestionPoolsTable(): void
    {
        $table = $this->table('question_pools');
        $table->addColumn('module', 'string', ['limit' => 20, 'null' => false])
              ->addColumn('teil', 'integer', ['null' => false, 'signed' => false])
              ->addColumn('level', 'string', ['limit' => 2, 'null' => false])
              ->addColumn('question_type', 'string', ['limit' => 30, 'null' => false])
              ->addColumn('content', 'json', ['null' => false])
              ->addColumn('media_urls', 'json', ['null' => true])
              ->addColumn('correct_answer', 'json', ['null' => false])
              ->addColumn('explanation', 'text', ['null' => true])
              ->addColumn('hints', 'json', ['null' => true])
              ->addColumn('difficulty', 'integer', ['null' => true, 'signed' => false])
              ->addColumn('points', 'integer', ['default' => 10, 'signed' => false])
              ->addColumn('usage_count', 'integer', ['default' => 0, 'signed' => false])
              ->addColumn('correct_rate', 'decimal', ['precision' => 5, 'scale' => 2, 'null' => true])
              ->addColumn('avg_time_seconds', 'integer', ['null' => true, 'signed' => false])
              ->addColumn('last_used_at', 'datetime', ['null' => true])
              ->addColumn('is_active', 'boolean', ['default' => true])
              ->addColumn('is_premium_only', 'boolean', ['default' => false])
              ->addColumn('created_by', 'integer', ['null' => true, 'signed' => false])
              ->addColumn('created_at', 'datetime', ['default' => 'CURRENT_TIMESTAMP'])
              ->addColumn('updated_at', 'datetime', ['default' => 'CURRENT_TIMESTAMP'])
              ->addIndex(['module', 'teil'])
              ->addIndex(['level'])
              ->addIndex(['is_active'])
              ->create();
    }

    private function createUserAnswersTable(): void
    {
        $table = $this->table('user_answers');
        $table->addColumn('user_id', 'integer', ['null' => false, 'signed' => false])
              ->addColumn('question_id', 'integer', ['null' => false, 'signed' => false])
              ->addColumn('session_id', 'string', ['limit' => 50, 'null' => false])
              ->addColumn('user_answer', 'text', ['null' => false])
              ->addColumn('is_correct', 'boolean', ['null' => false])
              ->addColumn('points_earned', 'integer', ['default' => 0, 'signed' => false])
              ->addColumn('time_spent_seconds', 'integer', ['null' => false, 'signed' => false])
              ->addColumn('attempts_count', 'integer', ['default' => 1, 'signed' => false])
              ->addColumn('ai_feedback', 'json', ['null' => true])
              ->addColumn('created_at', 'datetime', ['default' => 'CURRENT_TIMESTAMP'])
              ->addForeignKey('user_id', 'users', 'id', ['delete' => 'CASCADE'])
              ->addForeignKey('question_id', 'question_pools', 'id', ['delete' => 'CASCADE'])
              ->addIndex(['user_id'])
              ->addIndex(['created_at'])
              ->create();
    }

    private function createDailyStatsTable(): void
    {
        $table = $this->table('daily_stats');
        $table->addColumn('user_id', 'integer', ['null' => false, 'signed' => false])
              ->addColumn('date', 'date', ['null' => false])
              ->addColumn('total_questions', 'integer', ['default' => 0, 'signed' => false])
              ->addColumn('correct_count', 'integer', ['default' => 0, 'signed' => false])
              ->addColumn('total_points', 'integer', ['default' => 0, 'signed' => false])
              ->addColumn('total_time_minutes', 'integer', ['default' => 0, 'signed' => false])
              ->addColumn('module_breakdown', 'json', ['null' => true])
              ->addColumn('goal_reached', 'boolean', ['default' => false])
              ->addColumn('streak_maintained', 'boolean', ['default' => false])
              ->addColumn('created_at', 'datetime', ['default' => 'CURRENT_TIMESTAMP'])
              ->addColumn('updated_at', 'datetime', ['default' => 'CURRENT_TIMESTAMP'])
              ->addForeignKey('user_id', 'users', 'id', ['delete' => 'CASCADE'])
              ->addIndex(['user_id', 'date'], ['unique' => true])
              ->create();
    }

    private function createUserProgressCacheTable(): void
    {
        $table = $this->table('user_progress_cache', ['id' => false, 'primary_key' => ['user_id']]);
        $table->addColumn('user_id', 'integer', ['null' => false, 'signed' => false])
              ->addColumn('total_questions_answered', 'integer', ['default' => 0, 'signed' => false])
              ->addColumn('total_correct', 'integer', ['default' => 0, 'signed' => false])
              ->addColumn('accuracy_rate', 'decimal', ['precision' => 5, 'scale' => 2, 'default' => 0.00])
              ->addColumn('total_time_hours', 'decimal', ['precision' => 10, 'scale' => 2, 'default' => 0.00])
              ->addColumn('current_level', 'string', ['limit' => 2, 'default' => 'A2'])
              ->addColumn('level_progress', 'json', ['null' => true])
              ->addColumn('module_stats', 'json', ['null' => true])
              ->addColumn('best_time_of_day', 'string', ['limit' => 10, 'null' => true])
              ->addColumn('avg_session_minutes', 'integer', ['null' => true, 'signed' => false])
              ->addColumn('longest_streak', 'integer', ['default' => 0, 'signed' => false])
              ->addColumn('current_streak', 'integer', ['default' => 0, 'signed' => false])
              ->addColumn('last_practiced_at', 'datetime', ['null' => true])
              ->addColumn('last_module', 'string', ['limit' => 20, 'null' => true])
              ->addColumn('updated_at', 'datetime', ['default' => 'CURRENT_TIMESTAMP'])
              ->addForeignKey('user_id', 'users', 'id', ['delete' => 'CASCADE'])
              ->create();
    }

    private function createUserQuestionHistoryTable(): void
    {
        $table = $this->table('user_question_history');
        $table->addColumn('user_id', 'integer', ['null' => false, 'signed' => false])
              ->addColumn('question_id', 'integer', ['null' => false, 'signed' => false])
              ->addColumn('first_seen_at', 'datetime', ['null' => true])
              ->addColumn('last_seen_at', 'datetime', ['null' => true])
              ->addColumn('times_seen', 'integer', ['default' => 0, 'signed' => false])
              ->addColumn('times_correct', 'integer', ['default' => 0, 'signed' => false])
              ->addColumn('next_review_at', 'datetime', ['null' => true])
              ->addColumn('ease_factor', 'decimal', ['precision' => 3, 'scale' => 2, 'default' => 2.50])
              ->addColumn('interval_days', 'integer', ['default' => 0, 'signed' => false])
              ->addForeignKey('user_id', 'users', 'id', ['delete' => 'CASCADE'])
              ->addForeignKey('question_id', 'question_pools', 'id', ['delete' => 'CASCADE'])
              ->addIndex(['user_id'])
              ->addIndex(['next_review_at'])
              ->addIndex(['user_id', 'question_id'], ['unique' => true])
              ->create();
    }

    private function createUserSessionsTable(): void
    {
        $table = $this->table('user_sessions');
        $table->addColumn('user_id', 'integer', ['null' => false, 'signed' => false])
              ->addColumn('session_token', 'string', ['limit' => 255, 'null' => false])
              ->addColumn('refresh_token', 'string', ['limit' => 255, 'null' => true])
              ->addColumn('device_info', 'json', ['null' => true])
              ->addColumn('ip_address', 'string', ['limit' => 45, 'null' => true])
              ->addColumn('expires_at', 'datetime', ['null' => false])
              ->addColumn('last_activity_at', 'datetime', ['null' => true])
              ->addColumn('is_valid', 'boolean', ['default' => true])
              ->addColumn('created_at', 'datetime', ['default' => 'CURRENT_TIMESTAMP'])
              ->addForeignKey('user_id', 'users', 'id', ['delete' => 'CASCADE'])
              ->addIndex(['session_token'], ['unique' => true])
              ->addIndex(['refresh_token'], ['unique' => true])
              ->create();
    }

    private function createAuditLogsTable(): void
    {
        $table = $this->table('audit_logs');
        $table->addColumn('user_id', 'integer', ['null' => true, 'signed' => false])
              ->addColumn('action', 'string', ['limit' => 50, 'null' => false])
              ->addColumn('entity_type', 'string', ['limit' => 30, ['null' => true]])
              ->addColumn('entity_id', 'integer', ['null' => true, 'signed' => false])
              ->addColumn('old_values', 'json', ['null' => true])
              ->addColumn('new_values', 'json', ['null' => true])
              ->addColumn('ip_address', 'string', ['limit' => 45, 'null' => true])
              ->addColumn('user_agent', 'text', ['null' => true])
              ->addColumn('created_at', 'datetime', ['default' => 'CURRENT_TIMESTAMP'])
              ->addIndex(['user_id'])
              ->addIndex(['action'])
              ->addIndex(['created_at'])
              ->create();
    }

    private function createIndexes(): void
    {
        // Additional indexes if needed
    }

    private function createTriggers(): void
    {
        // SQLite triggers for updated_at
        $this->execute("CREATE TRIGGER IF NOT EXISTS update_users_timestamp 
            AFTER UPDATE ON users
            BEGIN
                UPDATE users SET updated_at = CURRENT_TIMESTAMP WHERE id = NEW.id;
            END;");

        $this->execute("CREATE TRIGGER IF NOT EXISTS update_subscriptions_timestamp 
            AFTER UPDATE ON subscriptions
            BEGIN
                UPDATE subscriptions SET updated_at = CURRENT_TIMESTAMP WHERE id = NEW.id;
            END;");

        $this->execute("CREATE TRIGGER IF NOT EXISTS update_daily_stats_timestamp 
            AFTER UPDATE ON daily_stats
            BEGIN
                UPDATE daily_stats SET updated_at = CURRENT_TIMESTAMP WHERE id = NEW.id;
            END;");
    }

    private function createViews(): void
    {
        $this->execute("CREATE VIEW IF NOT EXISTS active_subscriptions AS
            SELECT s.*, u.email, u.display_name
            FROM subscriptions s
            JOIN users u ON s.user_id = u.id
            WHERE s.status IN ('trialing', 'active', 'past_due')
            AND s.current_period_end > datetime('now');");

        $this->execute("CREATE VIEW IF NOT EXISTS user_leaderboard AS
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
            LIMIT 100;");
    }
}
