<?php
/**
 * Hardcoded DTZ questions - guaranteed to work
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

$module = $_GET['module'] ?? 'lesen';
$teil = $_GET['teil'] ?? '1';

// REAL DTZ QUESTIONS - HARDCODED
$questions = [
    // LESEN TEIL 1
    [
        'id' => 1,
        'module' => 'lesen',
        'teil' => 1,
        'question_text' => 'Sie sehen eine Anzeige. Was sucht die Familie Müller?',
        'text' => "WOHNUNG GESUCHT\n\nFamilie Müller sucht ab 01.06. eine 3- bis 4-Zimmer-Wohnung in Köln-Ehrenfeld. Die Wohnung sollte mindestens 80 m² groß sein und einen Balkon haben. Wichtig: Die Wohnung muss barrierefrei sein.\n\nTel.: 0176-12345678",
        'options' => [
            'A) Eine 2-Zimmer-Wohnung mit Garten',
            'B) Eine barrierefreie 3-4 Zimmer-Wohnung mit Balkon',
            'C) Ein Haus mit Garten für Großfamilie',
            'D) Eine möblierte Wohnung für Studenten'
        ],
        'correct' => 'B',
        'explanation' => 'In der Anzeige steht: "3- bis 4-Zimmer-Wohnung", "barrierefrei", "Balkon"'
    ],
    [
        'id' => 2,
        'module' => 'lesen',
        'teil' => 1,
        'question_text' => 'Was kostet der Deutschkurs?',
        'text' => "Sprachkurs Deutsch A2\nIntensivkurs 4 Wochen\nMontag bis Freitag: 9:00 - 12:00 Uhr\n\nKosten: Kostenlos für Asylbewerber und Geduldete\nOrt: Volkshochschule",
        'options' => [
            'A) 100 Euro',
            'B) 200 Euro',
            'C) Kostenlos für Berechtigte',
            'D) 50 Euro'
        ],
        'correct' => 'C',
        'explanation' => 'Text: "Kostenlos für Asylbewerber und Geduldete"'
    ],
    [
        'id' => 3,
        'module' => 'lesen',
        'teil' => 1,
        'question_text' => 'Wann hat das Fitness-Studio geöffnet?',
        'text' => "FITNESS CENTER\n\nÖffnungszeiten:\nMontag - Freitag: 6:00 - 23:00 Uhr\nSamstag - Sonntag: 8:00 - 22:00 Uhr\n\nProbetraining kostenlos!",
        'options' => [
            'A) Nur werktags',
            'B) Mo-Fr 6-23 Uhr, Wochenende 8-22 Uhr',
            'C) Nur am Wochenende',
            'D) 24 Stunden täglich'
        ],
        'correct' => 'B',
        'explanation' => 'Öffnungszeiten: Mo-Fr 6-23, Sa-So 8-22'
    ],
    // LESEN TEIL 2
    [
        'id' => 4,
        'module' => 'lesen',
        'teil' => 2,
        'question_text' => 'Warum kann Frau Schmidt nicht zur Arbeit kommen?',
        'text' => "Sehr geehrte Frau Meier,\n\nleider kann ich heute nicht zur Arbeit kommen. Mein Sohn Tim (6 Jahre) ist sehr krank. Er hat hohes Fieber und der Arzt hat gesagt, er muss im Bett bleiben. Mein Mann ist auf Geschäftsreise und meine Mutter, die sonst hilft, ist auch krank.\n\nIch komme morgen wieder.\n\nAnna Schmidt",
        'options' => [
            'A) Sie ist selbst krank',
            'B) Ihr Sohn ist krank und niemand kann aufpassen',
            'C) Sie hat einen Arzttermin',
            'D) Ihr Mann ist im Krankenhaus'
        ],
        'correct' => 'B',
        'explanation' => 'Sohn krank, Mann auf Geschäftsreise, Mutter krank'
    ],
    // HÖREN TEIL 1
    [
        'id' => 5,
        'module' => 'hoeren',
        'teil' => 1,
        'question_text' => 'Sie hören eine Ansage. Wann ist das Bürgerbüro geöffnet?',
        'audio_text' => 'Guten Tag. Sie haben das Bürgeramt erreicht. Unsere Öffnungszeiten sind montags und mittwochs von 8 bis 12 Uhr und dienstags von 14 bis 18 Uhr. Donnerstags und freitags haben wir geschlossen.',
        'options' => [
            'A) Mo+Mi 8-12 Uhr und Di 14-18 Uhr',
            'B) Mo-Fr 8-16 Uhr',
            'C) Nur dienstags',
            'D) Jeden Tag außer Wochenende'
        ],
        'correct' => 'A',
        'explanation' => 'Audio: Mo+Mi 8-12, Di 14-18'
    ],
    [
        'id' => 6,
        'module' => 'hoeren',
        'teil' => 1,
        'question_text' => 'Was soll der Anrufer tun?',
        'audio_text' => 'Sie haben die Arztpraxis Dr. Weber erreicht. Bitte hinterlassen Sie Ihre Nachricht nach dem Signal. Nennen Sie Ihren Namen, Ihre Telefonnummer und den Grund Ihres Anrufs. Wir rufen Sie zurück.',
        'options' => [
            'A) Sofort zum Arzt kommen',
            'B) Nachricht hinterlassen mit Name, Telefonnummer und Grund',
            'C) Morgen wieder anrufen',
            'D) Im Internet buchen'
        ],
        'correct' => 'B',
        'explanation' => '"hinterlassen Sie Ihre Nachricht... Name, Telefonnummer und Grund"'
    ],
    // HÖREN TEIL 2
    [
        'id' => 7,
        'module' => 'hoeren',
        'teil' => 2,
        'question_text' => 'Was macht die Frau am Wochenende?',
        'audio_text' => 'Mann: Was machst du am Wochenende? Frau: Ich muss Samstag arbeiten bis 22 Uhr. Aber Sonntag fahre ich mit den Kindern in den Zoo.',
        'options' => [
            'A) Samstag arbeiten, Sonntag Zoo',
            'B) Ganze Woche arbeiten',
            'C) Zu Hause bleiben',
            'D) Umziehen'
        ],
        'correct' => 'A',
        'explanation' => 'Samstag arbeiten, Sonntag Zoo'
    ],
    // SCHREIBEN
    [
        'id' => 8,
        'module' => 'schreiben',
        'teil' => 1,
        'question_text' => 'Schreiben Sie eine E-Mail an Ihren Chef. Sie sind krank und können nicht zur Arbeit kommen.',
        'situation' => 'Krankmeldung',
        'min_words' => 50,
        'max_words' => 80,
        'hints' => ['Begrüßung', 'Entschuldigung', 'Grund', 'Schluss']
    ]
];

// Filter by module and teil
$filtered = array_filter($questions, function($q) use ($module, $teil) {
    return $q['module'] === $module && $q['teil'] == $teil;
});

// If no questions found, return all (fallback)
if (empty($filtered)) {
    $filtered = $questions;
}

// Re-index array
$filtered = array_values($filtered);

echo json_encode([
    'success' => true,
    'module' => $module,
    'teil' => $teil,
    'count' => count($filtered),
    'questions' => $filtered
]);
