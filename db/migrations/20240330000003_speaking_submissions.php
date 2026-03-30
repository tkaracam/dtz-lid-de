<?php
declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class SpeakingSubmissions extends AbstractMigration
{
    public function change(): void
    {
        $table = $this->table('speaking_submissions');
        $table->addColumn('user_id', 'integer', ['null' => false, 'signed' => false])
              ->addColumn('teil', 'integer', ['null' => false, 'signed' => false])
              ->addColumn('audio_path', 'string', ['limit' => 255, 'null' => false])
              ->addColumn('transcription', 'text', ['null' => true])
              ->addColumn('duration_seconds', 'integer', ['null' => true, 'signed' => false])
              ->addColumn('status', 'string', ['limit' => 20, 'default' => 'pending'])
              ->addColumn('ai_analysis', 'json', ['null' => true])
              ->addColumn('pronunciation_score', 'integer', ['null' => true, 'signed' => false])
              ->addColumn('grammar_score', 'integer', ['null' => true, 'signed' => false])
              ->addColumn('fluency_score', 'integer', ['null' => true, 'signed' => false])
              ->addColumn('vocabulary_score', 'integer', ['null' => true, 'signed' => false])
              ->addColumn('overall_score', 'integer', ['null' => true, 'signed' => false])
              ->addColumn('submitted_at', 'datetime', ['default' => 'CURRENT_TIMESTAMP'])
              ->addForeignKey('user_id', 'users', 'id', ['delete' => 'CASCADE'])
              ->addIndex(['user_id'])
              ->addIndex(['status'])
              ->addIndex(['submitted_at'])
              ->create();
    }
}
