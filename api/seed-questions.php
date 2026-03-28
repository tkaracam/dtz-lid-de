<?php
/**
 * Seed DTZ questions - call after deployment
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');

$secret = $_GET['secret'] ?? '';
if ($secret !== 'dtz2024') {
    http_response_code(403);
    echo json_encode(['error' => 'Invalid secret']);
    exit;
}

try {
    // Direct database connection
    $dbPath = '/var/www/html/database/dtz_production.db';
    
    $pdo = new PDO("sqlite:$dbPath", null, null, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    ]);
    
    $added = 0;
    $skipped = 0;
    
    // Lesen Teil 1: Anzeigen
    $questions = [
        [
            'lesen', 1, 'A2', 'multiple_choice',
            json_encode([
                'question' => 'Sie sehen eine Anzeige. Was sucht die Familie Müller?',
                'text' => "WOHNUNG GESUCHT\n\nFamilie Müller sucht ab 01.06. eine 3- bis 4-Zimmer-Wohnung in Köln-Ehrenfeld. Die Wohnung sollte mindestens 80 m² groß sein und einen Balkon haben. Wichtig: Die Wohnung muss barrierefrei sein.\n\nTel.: 0176-12345678",
                'options' => ['A) Eine 2-Zimmer-Wohnung', 'B) Eine barrierefreie 3-4 Zimmer-Wohnung mit Balkon', 'C) Ein Haus mit Garten', 'D) Eine möblierte Wohnung']
            ]),
            json_encode(['answer' => 'B']),
            'Anzeige: 3-4 Zimmer, barrierefrei, Balkon',
            2, 10
        ],
        [
            'lesen', 2, 'A2', 'multiple_choice',
            json_encode([
                'question' => 'Warum kann Frau Schmidt nicht zur Arbeit kommen?',
                'text' => "Sehr geehrte Frau Meier,\n\nleider kann ich heute nicht zur Arbeit kommen. Mein Sohn (6 Jahre) ist sehr krank. Er hat hohes Fieber. Mein Mann ist auf Geschäftsreise und meine Mutter ist auch krank.\n\nMit freundlichen Grüßen\nAnna Schmidt",
                'options' => ['A) Sie ist krank', 'B) Ihr Sohn ist krank und niemand kann aufpassen', 'C) Sie hat Urlaub', 'D) Ihr Mann ist im Krankenhaus']
            ]),
            json_encode(['answer' => 'B']),
            'Sohn krank, niemand da',
            3, 10
        ],
        [
            'hoeren', 1, 'A2', 'multiple_choice',
            json_encode([
                'question' => 'Wann ist das Bürgerbüro geöffnet?',
                'audio_text' => 'Guten Tag. Sie haben das Bürgeramt erreicht. Unsere Öffnungszeiten sind montags und mittwochs von 8 bis 12 Uhr und dienstags von 14 bis 18 Uhr.',
                'options' => ['A) Mo-Mi 8-12 und Di 14-18', 'B) Mo-Fr 8-16', 'C) Nur Di', 'D) Täglich']
            ]),
            json_encode(['answer' => 'A']),
            'Mo+Mi 8-12, Di 14-18',
            3, 10
        ],
        [
            'hoeren', 2, 'A2', 'multiple_choice',
            json_encode([
                'question' => 'Was macht die Frau am Wochenende?',
                'audio_text' => 'Mann: Was machst du am Wochenende? Frau: Ich arbeite Samstag bis 22 Uhr. Aber Sonntag fahre ich mit den Kindern in den Zoo.',
                'options' => ['A) Samstag arbeiten, Sonntag Zoo', 'B) Ganze Woche arbeiten', 'C) Zu Hause bleiben', 'D) Umziehen']
            ]),
            json_encode(['answer' => 'A']),
            'Sa arbeiten, So Zoo',
            3, 10
        ]
    ];
    
    $stmt = $pdo->prepare("INSERT OR IGNORE INTO question_pools 
        (module, teil, level, question_type, content, correct_answer, explanation, difficulty, points) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
    
    foreach ($questions as $q) {
        $stmt->execute($q);
        if ($stmt->rowCount() > 0) {
            $added++;
        } else {
            $skipped++;
        }
    }
    
    // Count total
    $total = $pdo->query("SELECT COUNT(*) FROM question_pools")->fetchColumn();
    
    echo json_encode([
        'success' => true,
        'added' => $added,
        'skipped' => $skipped,
        'total_questions' => $total
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
