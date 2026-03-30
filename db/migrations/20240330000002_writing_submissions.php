<?php
declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class WritingSubmissions extends AbstractMigration
{
    public function change(): void
    {
        $table = $this->table('writing_submissions');
        $table->addColumn('user_id', 'integer', ['null' => false, 'signed' => false])
              ->addColumn('task_type', 'string', ['limit' => 50, 'null' => false])
              ->addColumn('text', 'text', ['null' => false])
              ->addColumn('teil', 'integer', ['null' => false, 'signed' => false, 'default' => 1])
              ->addColumn('status', 'string', ['limit' => 20, 'null' => false, 'default' => 'pending'])
              ->addColumn('word_count', 'integer', ['null' => false, 'signed' => false, 'default' => 0])
              ->addColumn('ai_score', 'integer', ['null' => true, 'signed' => false])
              ->addColumn('ai_feedback', 'json', ['null' => true])
              ->addColumn('admin_score', 'integer', ['null' => true, 'signed' => false])
              ->addColumn('admin_feedback', 'text', ['null' => true])
              ->addColumn('final_score', 'integer', ['null' => true, 'signed' => false])
              ->addColumn('final_feedback', 'json', ['null' => true])
              ->addColumn('submitted_at', 'datetime', ['default' => 'CURRENT_TIMESTAMP'])
              ->addColumn('reviewed_at', 'datetime', ['null' => true])
              ->addForeignKey('user_id', 'users', 'id', ['delete' => 'CASCADE'])
              ->addIndex(['user_id'])
              ->addIndex(['status'])
              ->addIndex(['submitted_at'])
              ->create();
    }
}
