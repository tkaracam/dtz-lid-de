<?php

declare(strict_types=1);

use Phinx\Seed\AbstractSeed;

class QuestionSeeder extends AbstractSeed
{
    public function run(): void
    {
        $data = [
            // Lesen Teil 1 - Multiple Choice
            [
                'module' => 'lesen',
                'teil' => 1,
                'level' => 'A2',
                'question_type' => 'multiple_choice',
                'content' => json_encode([
                    'text' => 'Sie lesen einen Eintrag im Internetforum. Beantworten Sie die Frage.\n\n"Hallo zusammen, ich suche eine Wohnung in Berlin. Ich brauche zwei Zimmer und eine Küche. Die Wohnung sollte im Stadtzentrum sein. Ich kann bis 800 Euro warm bezahlen. Wer kennt eine passende Wohnung?"',
                    'question' => 'Was sucht der Autor?',
                    'options' => [
                        'A' => 'Ein Haus auf dem Land',
                        'B' => 'Eine Wohnung in der Stadt',
                        'C' => 'Ein Zimmer in einer WG',
                        'D' => 'Ein Büro im Stadtzentrum'
                    ]
                ]),
                'correct_answer' => json_encode(['B']),
                'explanation' => 'Der Autor sucht eine Wohnung ("zwei Zimmer und eine Küche") im Stadtzentrum von Berlin.',
                'difficulty' => 3,
                'points' => 5,
                'is_active' => true,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ],
            // Hören Teil 1 - Audio (text representation)
            [
                'module' => 'hoeren',
                'teil' => 1,
                'level' => 'A2',
                'question_type' => 'multiple_choice',
                'content' => json_encode([
                    'audio_url' => '/audio/hoeren_teil1_beispiel.mp3',
                    'question' => 'Wo findet das Gespräch statt?',
                    'options' => [
                        'A' => 'Im Restaurant',
                        'B' => 'Im Zug',
                        'C' => 'Im Supermarkt',
                        'D' => 'Im Büro'
                    ]
                ]),
                'correct_answer' => json_encode(['B']),
                'explanation' => 'Die Personen sprechen über Fahrkarten und Stationen.',
                'difficulty' => 4,
                'points' => 5,
                'is_active' => true,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ],
            // LID - Sprachbausteine
            [
                'module' => 'lid',
                'teil' => 1,
                'level' => 'B1',
                'question_type' => 'multiple_choice',
                'content' => json_encode([
                    'text' => 'Ich freue mich ___ Ihr Angebot.',
                    'options' => [
                        'A' => 'auf',
                        'B' => 'für',
                        'C' => 'über',
                        'D' => 'mit'
                    ]
                ]),
                'correct_answer' => json_encode(['C']),
                'explanation' => '"Sich freuen über" ist die richtige Präpositionalverbindung.',
                'difficulty' => 5,
                'points' => 5,
                'is_active' => true,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ],
        ];

        $questions = $this->table('question_pools');
        $questions->insert($data)->saveData();
    }
}
