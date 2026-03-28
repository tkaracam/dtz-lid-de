<?php
/**
 * Seed realistic DTZ questions into database
 */

require_once __DIR__ . '/../src/Database/Database.php';

use DTZ\Database\Database;

try {
    $db = Database::getInstance();
    
    echo "✅ Database connected\n";
    
    // Check current count
    $before = $db->selectOne("SELECT COUNT(*) as count FROM question_pools")['count'];
    echo "Current questions: $before\n";
    
    // Realistic DTZ Lesen Teil 1 - Anzeigen
    $questions = [
        // LESEN TEIL 1: Anzeigen verstehen
        [
            'module' => 'lesen',
            'teil' => 1,
            'level' => 'A2',
            'question_type' => 'multiple_choice',
            'content' => json_encode([
                'question' => 'Sie sehen eine Anzeige. Was sucht die Familie Müller?',
                'text' => "WOHNUNG GESUCHT\n\nFamilie Müller sucht ab 01.06. eine 3- bis 4-Zimmer-Wohnung in Köln-Ehrenfeld oder Umgebung. Die Wohnung sollte mindestens 80 m² groß sein und einen Balkon haben. Wir haben zwei Kinder (3 und 5 Jahre). Wichtig: Die Wohnung muss barrierefrei sein.\n\nTel.: 0176-12345678 (ab 18 Uhr)",
                'options' => [
                    'A) Eine 2-Zimmer-Wohnung mit Garten',
                    'B) Eine barrierefreie 3-4 Zimmer-Wohnung mit Balkon',
                    'C) Ein Haus mit Garten für Großfamilie',
                    'D) Eine möblierte Wohnung für Studenten'
                ]
            ]),
            'correct_answer' => json_encode(['answer' => 'B']),
            'explanation' => 'Anzeige: "3- bis 4-Zimmer-Wohnung", "barrierefrei", "Balkon"',
            'difficulty' => 2,
            'points' => 10
        ],
        
        // LESEN TEIL 2: Alltagstexte
        [
            'module' => 'lesen',
            'teil' => 2,
            'level' => 'A2',
            'question_type' => 'multiple_choice',
            'content' => json_encode([
                'question' => 'Warum kann Frau Schmidt nicht zur Arbeit kommen?',
                'text' => "Sehr geehrte Frau Meier,\n\nleider muss ich Ihnen mitteilen, dass ich heute und morgen nicht zur Arbeit kommen kann. Mein Sohn Tim (6 Jahre) ist sehr krank. Er hat hohes Fieber und der Arzt hat gesagt, er muss im Bett bleiben. Mein Mann ist gerade auf Geschäftsreise und meine Mutter, die sonst hilft, ist auch krank.\n\nIch werde am Donnerstag wieder kommen.\n\nMit freundlichen Grüßen\nAnna Schmidt",
                'options' => [
                    'A) Sie ist selbst krank',
                    'B) Ihr Sohn ist krank und niemand kann aufpassen',
                    'C) Sie hat einen Arzttermin',
                    'D) Ihr Mann ist im Krankenhaus'
                ]
            ]),
            'correct_answer' => json_encode(['answer' => 'B']),
            'explanation' => 'Sohn krank, Mann auf Geschäftsreise, Mutter auch krank',
            'difficulty' => 3,
            'points' => 10
        ],
        
        // LESEN TEIL 3: Arbeitswelttexte
        [
            'module' => 'lesen',
            'teil' => 3,
            'level' => 'A2',
            'question_type' => 'multiple_choice',
            'content' => json_encode([
                'question' => 'Was muss man für die Stelle mitbringen?',
                'text' => "STELLENANZEIGE: Verkäufer/in gesucht\n\nWir suchen für unseren Supermarkt in der Innenstadt eine/n freundliche/n Verkäufer/in.\n\nIhre Aufgaben:\n• Bedienung der Kasse\n• Beratung unserer Kunden\n• Auffüllen der Regale\n\nAnforderungen:\n• Gute Deutschkenntnisse (mind. A2)\n• Erfahrung im Verkauf wünschenswert\n• Teamfähigkeit und Zuverlässigkeit\n\nWir bieten:\n• 30 Stunden/Woche\n• Faire Bezahlung nach Tarif\n• Angenehmes Arbeitsklima",
                'options' => [
                    'A) Einen Universitätsabschluss',
                    'B) Gute Deutschkenntnisse und Teamfähigkeit',
                    'C) 10 Jahre Erfahrung',
                    'D) Einen Führerschein'
                ]
            ]),
            'correct_answer' => json_encode(['answer' => 'B']),
            'explanation' => 'Text: "Gute Deutschkenntnisse", "Teamfähigkeit"',
            'difficulty' => 3,
            'points' => 10
        ],
        
        // HÖREN TEIL 1: Telefonansagen
        [
            'module' => 'hoeren',
            'teil' => 1,
            'level' => 'A2',
            'question_type' => 'multiple_choice',
            'content' => json_encode([
                'question' => 'Sie hören eine Telefonansage. Wann ist das Büro geöffnet?',
                'audio_text' => 'Guten Tag. Sie haben das Bürgeramt erreicht. Unsere Öffnungszeiten sind montags und mittwochs von 8 bis 12 Uhr und dienstags von 14 bis 18 Uhr. Donnerstags und freitags haben wir geschlossen.',
                'options' => [
                    'A) Mo-Mi 8-12 Uhr und Di 14-18 Uhr',
                    'B) Mo-Fr 8-16 Uhr',
                    'C) Nur dienstags',
                    'D) Jeden Tag außer Wochenende'
                ]
            ]),
            'correct_answer' => json_encode(['answer' => 'A']),
            'explanation' => '"montags und mittwochs von 8 bis 12 Uhr und dienstags von 14 bis 18 Uhr"',
            'difficulty' => 3,
            'points' => 10
        ],
        
        // HÖREN TEIL 2: Alltagsgespräche
        [
            'module' => 'hoeren',
            'teil' => 2,
            'level' => 'A2',
            'question_type' => 'multiple_choice',
            'content' => json_encode([
                'question' => 'Was macht die Frau am Wochenende?',
                'audio_text' => 'Mann: Und, was hast du am Wochenende vor? Frau: Ach, ich muss leider arbeiten. Samstag habe ich Spätschicht bis 22 Uhr. Aber sonntag ist mein freier Tag. Da fahre ich mit den Kindern in den Zoo.',
                'options' => [
                    'A) Sie arbeitet am Samstag und fährt am Sonntag in den Zoo',
                    'B) Sie arbeitet das ganze Wochenende',
                    'C) Sie bleibt zu Hause',
                    'D) Sie zieht um'
                ]
            ]),
            'correct_answer' => json_encode(['answer' => 'A']),
            'explanation' => '"Samstag habe ich Spätschicht", "sonntag... fahre ich mit den Kindern in den Zoo"',
            'difficulty' => 3,
            'points' => 10
        ]
    ];
    
    $stmt = $db->getPdo()->prepare("INSERT INTO question_pools 
        (module, teil, level, question_type, content, correct_answer, explanation, difficulty, points) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
    
    $added = 0;
    foreach ($questions as $q) {
        try {
            $stmt->execute([
                $q['module'], $q['teil'], $q['level'], $q['question_type'],
                $q['content'], $q['correct_answer'], $q['explanation'],
                $q['difficulty'], $q['points']
            ]);
            $added++;
        } catch (Exception $e) {
            echo "Skip duplicate: " . $e->getMessage() . "\n";
        }
    }
    
    $after = $db->selectOne("SELECT COUNT(*) as count FROM question_pools")['count'];
    
    echo "\n✅ Added $added new questions\n";
    echo "Total questions now: $after\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
