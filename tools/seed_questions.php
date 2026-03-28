<?php
require_once __DIR__ . '/../src/Database/Database.php';
use DTZ\Database\Database;

$_ENV['DB_DRIVER'] = 'sqlite';
$_ENV['DB_PATH'] = __DIR__ . '/../database/dtz_learning.db';

$db = Database::getInstance();

// Sample questions for Hören (Listening)
$hoerenQuestions = [
    [
        'module' => 'hoeren',
        'teil' => 1,
        'level' => 'A2',
        'question_type' => 'multiple_choice',
        'content' => json_encode(['question' => 'Sie hören eine Telefonansage. Wann ist das Büro geöffnet?', 'options' => ['A) Montag bis Freitag 9-17 Uhr', 'B) Montag bis Samstag 8-18 Uhr', 'C) Montag bis Donnerstag 9-16 Uhr', 'D) Dienstag bis Freitag 10-15 Uhr']]),
        'correct_answer' => json_encode(['answer' => 'A']),
        'points' => 5,
        'difficulty' => 5
    ],
    [
        'module' => 'hoeren',
        'teil' => 1,
        'level' => 'A2',
        'question_type' => 'multiple_choice',
        'content' => json_encode(['question' => 'Sie hören eine Durchsage im Zug. Wo müssen die Fahrgäste aussteigen?', 'options' => ['A) Gleis 3', 'B) Gleis 5', 'C) Gleis 7', 'D) Gleis 9']]),
        'correct_answer' => json_encode(['answer' => 'B']),
        'points' => 5,
        'difficulty' => 4
    ],
    [
        'module' => 'hoeren',
        'teil' => 2,
        'level' => 'A2',
        'question_type' => 'multiple_choice',
        'content' => json_encode(['question' => 'Zwei Freunde sprechen über das Wochenende. Was macht Frau Müller am Samstag?', 'options' => ['A) Sie geht ins Kino', 'B) Sie besucht ihre Schwester', 'C) Sie arbeitet im Garten', 'D) Sie fährt zum See']]),
        'correct_answer' => json_encode(['answer' => 'D']),
        'points' => 5,
        'difficulty' => 6
    ],
    [
        'module' => 'hoeren',
        'teil' => 3,
        'level' => 'B1',
        'question_type' => 'multiple_choice',
        'content' => json_encode(['question' => 'Ein Interview mit einem Arzt. Was empfiehlt der Arzt für gesunden Schlaf?', 'options' => ['A) Kaffee am Abend trinken', 'B) Vor dem Schlafen Fernsehen', 'C) Regelmäßige Schlafzeiten einhalten', 'D) Am Wochenende ausschlafen']]),
        'correct_answer' => json_encode(['answer' => 'C']),
        'points' => 5,
        'difficulty' => 7
    ],
    [
        'module' => 'hoeren',
        'teil' => 4,
        'level' => 'B1',
        'question_type' => 'multiple_choice',
        'content' => json_encode(['question' => 'Sie hören einen Vortrag über Umweltschutz. Was ist das Hauptthema?', 'options' => ['A) Plastikverbrauch reduzieren', 'B) Mehr Autofahren', 'C) Mehr Fleisch essen', 'D) Strom sparen']]),
        'correct_answer' => json_encode(['answer' => 'A']),
        'points' => 5,
        'difficulty' => 8
    ]
];

// Sample questions for Lesen (Reading)
$lesenQuestions = [
    [
        'module' => 'lesen',
        'teil' => 1,
        'level' => 'A2',
        'question_type' => 'multiple_choice',
        'content' => json_encode(['question' => 'Lesen Sie die Anzeige: "Zu verschenken: alter Schreibtisch, Holz, Maße 120x60 cm, abzuholen in Berlin-Mitte." Was sucht der Verkäufer?', 'options' => ['A) Einen Käufer', 'B) Jemanden, der den Tisch abholt', 'C) Einen neuen Schreibtisch', 'D) Geld für den Tisch']]),
        'correct_answer' => json_encode(['answer' => 'B']),
        'points' => 5,
        'difficulty' => 4
    ],
    [
        'module' => 'lesen',
        'teil' => 2,
        'level' => 'A2',
        'question_type' => 'multiple_choice',
        'content' => json_encode(['question' => 'Lesen Sie den Text: "Liebe Kunden, ab nächster Woche haben wir neue Öffnungszeiten. Wir sind jetzt auch sonntags von 10 bis 16 Uhr für Sie da." Was ändert sich?', 'options' => ['A) Der Laden schließt früher', 'B) Der Laden hat sonntags geöffnet', 'C) Der Laden zieht um', 'D) Die Preise werden erhöht']]),
        'correct_answer' => json_encode(['answer' => 'B']),
        'points' => 5,
        'difficulty' => 5
    ],
    [
        'module' => 'lesen',
        'teil' => 3,
        'level' => 'B1',
        'question_type' => 'multiple_choice',
        'content' => json_encode(['question' => 'Lesen Sie den Arbeitsvertrag: "Die wöchentliche Arbeitszeit beträgt 40 Stunden. Überstunden werden mit Freizeit ausgeglichen." Was bedeutet das?', 'options' => ['A) Man bekommt mehr Geld für Überstunden', 'B) Man bekommt für Überstunden frei', 'C) Man muss samstags arbeiten', 'D) Die Arbeitszeit ist flexibel']]),
        'correct_answer' => json_encode(['answer' => 'B']),
        'points' => 5,
        'difficulty' => 6
    ]
];

// Sample questions for Schreiben
$schreibenQuestions = [
    [
        'module' => 'schreiben',
        'teil' => 1,
        'level' => 'A2',
        'question_type' => 'text_input',
        'content' => json_encode(['question' => 'Füllen Sie das Formular aus: Sie möchten sich für einen Sprachkurs anmelden. Schreiben Sie circa 30 Wörter.', 'prompt' => 'Schreiben Sie: Warum lernen Sie Deutsch? Wann möchten Sie den Kurs besuchen?']),
        'correct_answer' => json_encode(['answer' => 'manuell']),
        'points' => 10,
        'difficulty' => 5
    ],
    [
        'module' => 'schreiben',
        'teil' => 2,
        'level' => 'B1',
        'question_type' => 'text_input',
        'content' => json_encode(['question' => 'Schreiben Sie einen Brief an Ihren Freund/ Ihre Freundin. Sie haben eine neue Wohnung. Schreiben Sie circa 80 Wörter.', 'prompt' => 'Beschreiben Sie: Wie sieht die Wohnung aus? Wo liegt sie? Wann können Sie sich treffen?']),
        'correct_answer' => json_encode(['answer' => 'manuell']),
        'points' => 10,
        'difficulty' => 7
    ]
];

// Insert questions
$allQuestions = array_merge($hoerenQuestions, $lesenQuestions, $schreibenQuestions);
foreach ($allQuestions as $q) {
    $db->insert('question_pools', $q);
}

echo "Inserted " . count($allQuestions) . " sample questions\n";

// Verify
$counts = $db->select("SELECT module, COUNT(*) as cnt FROM question_pools GROUP BY module");
foreach ($counts as $c) {
    echo $c['module'] . ': ' . $c['cnt'] . " questions\n";
}
