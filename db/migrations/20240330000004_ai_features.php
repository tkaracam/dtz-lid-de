<?php
declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class AiFeatures extends AbstractMigration
{
    public function change(): void
    {
        $this->createAiCacheTable();
        $this->createAiInteractionsTable();
        $this->createUserMistakesTable();
        $this->createAiWritingFeedbackTable();
        $this->createAiSpeakingAnalysisTable();
        $this->createAiRecommendationsTable();
    }

    private function createAiCacheTable(): void
    {
        $table = $this->table('ai_cache');
        $table->addColumn('cache_key', 'string', ['limit' => 255, 'null' => false])
              ->addColumn('response', 'text', ['null' => false])
              ->addColumn('expires_at', 'datetime', ['null' => false])
              ->addColumn('created_at', 'datetime', ['default' => 'CURRENT_TIMESTAMP'])
              ->addIndex(['cache_key'], ['unique' => true])
              ->addIndex(['expires_at'])
              ->create();
    }

    private function createAiInteractionsTable(): void
    {
        $table = $this->table('ai_interactions');
        $table->addColumn('user_id', 'integer', ['null' => false, 'signed' => false])
              ->addColumn('action_type', 'string', ['limit' => 50, 'null' => false])
              ->addColumn('input_preview', 'text', ['null' => true])
              ->addColumn('response_preview', 'text', ['null' => true])
              ->addColumn('created_at', 'datetime', ['default' => 'CURRENT_TIMESTAMP'])
              ->addForeignKey('user_id', 'users', 'id', ['delete' => 'CASCADE'])
              ->addIndex(['user_id'])
              ->addIndex(['action_type'])
              ->addIndex(['created_at'])
              ->create();
    }

    private function createUserMistakesTable(): void
    {
        $table = $this->table('user_mistakes');
        $table->addColumn('user_id', 'integer', ['null' => false, 'signed' => false])
              ->addColumn('question_preview', 'text', ['null' => true])
              ->addColumn('user_answer', 'text', ['null' => true])
              ->addColumn('correct_answer', 'text', ['null' => true])
              ->addColumn('module', 'string', ['limit' => 20, 'null' => true])
              ->addColumn('mistake_type', 'string', ['limit' => 50, 'null' => true])
              ->addColumn('ai_analysis', 'text', ['null' => true])
              ->addColumn('created_at', 'datetime', ['default' => 'CURRENT_TIMESTAMP'])
              ->addForeignKey('user_id', 'users', 'id', ['delete' => 'CASCADE'])
              ->addIndex(['user_id'])
              ->addIndex(['module'])
              ->addIndex(['mistake_type'])
              ->addIndex(['created_at'])
              ->create();
    }

    private function createAiWritingFeedbackTable(): void
    {
        $table = $this->table('ai_writing_feedback');
        $table->addColumn('user_id', 'integer', ['null' => false, 'signed' => false])
              ->addColumn('task_type', 'string', ['limit' => 50, 'null' => false])
              ->addColumn('submission_hash', 'string', ['limit' => 64, 'null' => false])
              ->addColumn('feedback', 'text', ['null' => false])
              ->addColumn('score', 'integer', ['null' => true, 'signed' => false])
              ->addColumn('criteria_scores', 'json', ['null' => true])
              ->addColumn('expires_at', 'datetime', ['null' => false])
              ->addColumn('created_at', 'datetime', ['default' => 'CURRENT_TIMESTAMP'])
              ->addForeignKey('user_id', 'users', 'id', ['delete' => 'CASCADE'])
              ->addIndex(['user_id'])
              ->addIndex(['submission_hash'], ['unique' => true])
              ->addIndex(['expires_at'])
              ->create();
    }

    private function createAiSpeakingAnalysisTable(): void
    {
        $table = $this->table('ai_speaking_analysis');
        $table->addColumn('user_id', 'integer', ['null' => false, 'signed' => false])
              ->addColumn('recording_hash', 'string', ['limit' => 64, 'null' => false])
              ->addColumn('transcription', 'text', ['null' => true])
              ->addColumn('analysis', 'text', ['null' => false])
              ->addColumn('pronunciation_score', 'integer', ['null' => true, 'signed' => false])
              ->addColumn('grammar_score', 'integer', ['null' => true, 'signed' => false])
              ->addColumn('fluency_score', 'integer', ['null' => true, 'signed' => false])
              ->addColumn('vocabulary_score', 'integer', ['null' => true, 'signed' => false])
              ->addColumn('overall_score', 'integer', ['null' => true, 'signed' => false])
              ->addColumn('expires_at', 'datetime', ['null' => false])
              ->addColumn('created_at', 'datetime', ['default' => 'CURRENT_TIMESTAMP'])
              ->addForeignKey('user_id', 'users', 'id', ['delete' => 'CASCADE'])
              ->addIndex(['user_id'])
              ->addIndex(['recording_hash'], ['unique' => true])
              ->addIndex(['expires_at'])
              ->create();
    }

    private function createAiRecommendationsTable(): void
    {
        $table = $this->table('ai_recommendations');
        $table->addColumn('user_id', 'integer', ['null' => false, 'signed' => false])
              ->addColumn('recommendation_type', 'string', ['limit' => 50, 'null' => false])
              ->addColumn('content', 'text', ['null' => false])
              ->addColumn('priority', 'integer', ['default' => 1, 'signed' => false])
              ->addColumn('is_shown', 'boolean', ['default' => false])
              ->addColumn('shown_at', 'datetime', ['null' => true])
              ->addColumn('is_completed', 'boolean', ['default' => false])
              ->addColumn('completed_at', 'datetime', ['null' => true])
              ->addColumn('expires_at', 'datetime', ['null' => true])
              ->addColumn('created_at', 'datetime', ['default' => 'CURRENT_TIMESTAMP'])
              ->addForeignKey('user_id', 'users', 'id', ['delete' => 'CASCADE'])
              ->addIndex(['user_id'])
              ->addIndex(['recommendation_type'])
              ->addIndex(['is_shown'])
              ->addIndex(['expires_at'])
              ->create();
    }
}
