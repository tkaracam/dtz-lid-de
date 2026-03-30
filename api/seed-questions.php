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
    
    // DTZ Realistic Questions - Lesen + Hören
    $questions = [
        // === LESEN TEIL 1: Anzeigen ===
        [
            'lesen', 1, 'A2', 'multiple_choice',
            json_encode([
                'question' => 'Sie sehen eine Anzeige. Was sucht die Familie Müller?',
                'text' => "WOHNUNG GESUCHT\n\nFamilie Müller sucht ab 01.06. eine 3- bis 4-Zimmer-Wohnung in Köln-Ehrenfeld oder Umgebung. Die Wohnung sollte mindestens 80 m² groß sein und einen Balkon haben. Wir haben zwei Kinder (3 und 5 Jahre). Wichtig: Die Wohnung muss barrierefrei sein und einen Aufzug haben.\n\nTel.: 0176-12345678 (ab 18 Uhr)",
                'options' => ['A) Eine 2-Zimmer-Wohnung mit Garten', 'B) Eine barrierefreie 3-4 Zimmer-Wohnung mit Balkon', 'C) Ein Haus mit Garten für Großfamilie', 'D) Eine möblierte Wohnung für Studenten']
            ]),
            json_encode(['answer' => 'B']),
            'In der Anzeige steht: 3- bis 4-Zimmer-Wohnung, barrierefrei, Balkon',
            2, 10
        ],
        [
            'lesen', 1, 'A2', 'multiple_choice',
            json_encode([
                'question' => 'Sie sehen ein Plakat. Wann findet der Integrationskurs statt?',
                'text' => "INTEGRATIONSKURS A2\n\nSprach- und Orientierungskurs für Zuwanderer\n\nMontag – Donnerstag: 9:00 – 12:30 Uhr\nFreitag: 9:00 – 11:30 Uhr\n\nKursbeginn: 15. April 2024\nKursdauer: 6 Monate\n\nKosten: Kostenlos für Berechtigte\nOrt: Volkshochschule, Raum 203",
                'options' => ['A) Nur am Wochenende', 'B) Montag bis Freitag vormittags', 'C) Jeden Tag nachmittags', 'D) Nur dienstags und donnerstags']
            ]),
            json_encode(['answer' => 'B']),
            'Montag-Donnerstag 9:00-12:30 und Freitag 9:00-11:30 = werktags vormittags',
            2, 10
        ],
        [
            'lesen', 1, 'A2', 'multiple_choice',
            json_encode([
                'question' => 'Was kostet das Fitness-Training bei Sport Aktiv?',
                'text' => "SPORT AKTIV\nIhr Fitnessstudio in der City\n\nAngebot im März:\n• Grundfitness: 19,90 €/Monat\n• Premium (inkl. Kurse): 29,90 €/Monat\n• Studenten/Schüler: -50% Rabatt\n\nÖffnungszeiten:\nMo-Fr: 6-23 Uhr\nSa-So: 8-22 Uhr",
                'options' => ['A) 19,90 € für alle', 'B) 29,90 € für Premium', 'C) 9,95 € für Studenten', 'D) Kostenlos für alle']
            ]),
            json_encode(['answer' => 'B']),
            'Premium ist 29,90 €/Monat laut Anzeige',
            3, 10
        ],
        // === LESEN TEIL 2: Alltagstexte ===
        [
            'lesen', 2, 'A2', 'multiple_choice',
            json_encode([
                'question' => 'Warum kann Frau Schmidt nicht zur Arbeit kommen?',
                'text' => "Sehr geehrte Frau Meier,\n\nleider muss ich Ihnen mitteilen, dass ich heute und morgen nicht zur Arbeit kommen kann. Mein Sohn Tim (6 Jahre) ist sehr krank. Er hat hohes Fieber und der Arzt hat gesagt, er muss im Bett bleiben. Mein Mann ist gerade auf Geschäftsreise und meine Mutter, die sonst hilft, ist auch krank.\n\nIch werde am Donnerstag wieder kommen.\n\nMit freundlichen Grüßen\nAnna Schmidt",
                'options' => ['A) Sie ist selbst krank', 'B) Ihr Sohn ist krank und niemand kann aufpassen', 'C) Sie hat einen Arzttermin', 'D) Ihr Mann ist im Krankenhaus']
            ]),
            json_encode(['answer' => 'B']),
            'Text: Mein Sohn Tim ist sehr krank, Mein Mann ist auf Geschäftsreise, meine Mutter ist auch krank',
            3, 10
        ],
        [
            'lesen', 2, 'A2', 'multiple_choice',
            json_encode([
                'question' => 'Was möchte der Mieter von der Hausverwaltung?',
                'text' => "Betreff: Mängel in der Wohnung\n\nSehr geehrte Damen und Herren,\n\nich wohne seit drei Monaten in der Wohnung 4B in der Hauptstraße 45. Ich habe mehrere Probleme:\n\n1. Die Heizung in der Küche funktioniert nicht richtig.\n2. Das Licht im Flur flackert. Das ist gefährlich.\n3. Der Wasserhahn im Bad tropft Tag und Nacht.\n\nIch bitte Sie, diese Probleme so schnell wie möglich zu beheben. Wenn das nicht geht, möchte ich die Miete mindern.",
                'options' => ['A) Er möchte kündigen', 'B) Er möchte die Miete mindern, wenn die Probleme nicht behoben werden', 'C) Er sucht eine neue Wohnung', 'D) Er möchte die Miete erhöhen']
            ]),
            json_encode(['answer' => 'B']),
            'Text: Wenn das nicht geht, möchte ich die Miete mindern',
            4, 10
        ],
        // === LESEN TEIL 3: Arbeitswelttexte ===
        [
            'lesen', 3, 'A2', 'multiple_choice',
            json_encode([
                'question' => 'Was ist die Aufgabe des neuen Mitarbeiters?',
                'text' => "Stellenanzeige: Mitarbeiter (m/w/d) im Kundenservice\n\nIhre Aufgaben:\n• Telefonische und schriftliche Beratung unserer Kunden\n• Annahme und Bearbeitung von Reklamationen\n• Terminvereinbarungen und Datenpflege\n• Unterstützung beim Versand von Ware\n\nWir erwarten:\n• Gute Deutschkenntnisse (B1-Niveau)\n• Grundkenntnisse in Englisch\n• Freundliches Auftreten\n• Teamfähigkeit",
                'options' => ['A) Nur Telefonate annehmen', 'B) Kunden beraten, Reklamationen bearbeiten, Daten pflegen', 'C) Nur E-Mails schreiben', 'D) Nur Versand vorbereiten']
            ]),
            json_encode(['answer' => 'B']),
            'Aufgabenliste umfasst: Beratung, Reklamationen, Termine, Datenpflege, Versand',
            3, 10
        ],
        // === LESEN TEIL 4: Komplexe Texte ===
        [
            'lesen', 4, 'B1', 'multiple_choice',
            json_encode([
                'question' => 'Was ist das Hauptproblem von Fatima nach dem Text?',
                'text' => "Fatima (34) kam vor zwei Jahren aus Syrien nach Deutschland. Sie hat schnell Deutsch gelernt und den Integrationskurs mit der B1-Prüfung bestanden. Jetzt sucht sie seit acht Monaten eine Arbeit als Krankenpflegehelferin.\n\nDas Problem: In Syrien war sie ausgebildete Krankenschwester, aber ihre Abschlüsse werden in Deutschland nicht anerkannt. Sie muss eine Anerkennungsprüfung machen, aber dafür braucht sie bessere Deutschkenntnisse (B2).",
                'options' => ['A) Sie kann kein Deutsch', 'B) Ihr ausländischer Abschluss wird nicht anerkannt und sie braucht B2', 'C) Sie will nicht im Krankenhaus arbeiten', 'D) Sie hat keine Arbeitserlaubnis']
            ]),
            json_encode(['answer' => 'B']),
            'Text: ihre Abschlüsse werden nicht anerkannt, braucht bessere Deutschkenntnisse (B2)',
            4, 10
        ],
        // === HÖREN TEIL 1: Telefonansagen ===
        [
            'hoeren', 1, 'A2', 'multiple_choice',
            json_encode([
                'question' => 'Sie hören eine Telefonansage. Wann ist das Büro geöffnet?',
                'audio_text' => 'Guten Tag. Sie haben das Bürgeramt der Stadt Musterhausen erreicht. Unsere Öffnungszeiten sind montags und mittwochs von 8 bis 12 Uhr und dienstags von 14 bis 18 Uhr. Donnerstags und freitags haben wir geschlossen.',
                'options' => ['A) Mo-Mi 8-12 Uhr und Di 14-18 Uhr', 'B) Mo-Fr 8-16 Uhr', 'C) Nur dienstags', 'D) Jeden Tag außer Wochenende']
            ]),
            json_encode(['answer' => 'A']),
            'montags und mittwochs von 8 bis 12 Uhr und dienstags von 14 bis 18 Uhr',
            3, 10
        ],
        [
            'hoeren', 1, 'A2', 'multiple_choice',
            json_encode([
                'question' => 'Was soll der Anrufer tun?',
                'audio_text' => 'Sie haben die Arztpraxis Dr. Weber erreicht. Bitte hinterlassen Sie Ihre Nachricht nach dem Signal. Nennen Sie Ihren Namen, Ihre Telefonnummer und den Grund Ihres Anrufs.',
                'options' => ['A) Sofort zum Arzt kommen', 'B) Nachricht hinterlassen mit Name, Telefonnummer und Grund', 'C) Morgen wieder anrufen', 'D) Im Internet buchen']
            ]),
            json_encode(['answer' => 'B']),
            'hinterlassen Sie Ihre Nachricht... Nennen Sie Ihren Namen, Ihre Telefonnummer und den Grund',
            2, 10
        ],
        // === HÖREN TEIL 2: Alltagsgespräche ===
        [
            'hoeren', 2, 'A2', 'multiple_choice',
            json_encode([
                'question' => 'Was macht die Frau am Wochenende?',
                'audio_text' => 'Mann: Und, was hast du am Wochenende vor? Frau: Ach, ich muss leider arbeiten. Samstag habe ich Spätschicht bis 22 Uhr. Aber sonntag ist mein freier Tag. Da fahre ich mit den Kindern in den Zoo.',
                'options' => ['A) Sie arbeitet am Samstag und fährt am Sonntag in den Zoo', 'B) Sie arbeitet das ganze Wochenende', 'C) Sie bleibt zu Hause', 'D) Sie zieht um']
            ]),
            json_encode(['answer' => 'A']),
            'Samstag habe ich Spätschicht, sonntag... fahre ich mit den Kindern in den Zoo',
            3, 10
        ],
        // === HÖREN TEIL 3: Arbeitsgespräche ===
        [
            'hoeren', 3, 'A2', 'multiple_choice',
            json_encode([
                'question' => 'Was ist mit dem Projekt?',
                'audio_text' => 'Chefin: Herr Schmidt, wie sieht es mit dem Projekt aus? Die Deadline ist nächste Woche. Schmidt: Ja, ich weiß. Leider gibt es ein Problem. Die neue Software funktioniert noch nicht richtig. Chefin: Können wir die Deadline verschieben? Schmidt: Ich denke, zwei Tage mehr wären gut.',
                'options' => ['A) Das Projekt ist fertig', 'B) Es gibt Software-Probleme und sie brauchen 2 Tage mehr', 'C) Der Kunde hat abgesagt', 'D) Herr Schmidt ist krank']
            ]),
            json_encode(['answer' => 'B']),
            'Software funktioniert noch nicht richtig, zwei Tage mehr wären gut',
            4, 10
        ],
        // === HÖREN TEIL 4: Informationen ===
        [
            'hoeren', 4, 'B1', 'multiple_choice',
            json_encode([
                'question' => 'Wie kommt man zum Rathaus?',
                'audio_text' => 'Guten Tag. Sie möchten zum Rathaus? Das ist ganz einfach. Gehen Sie diese Straße geradeaus bis zur Kreuzung. Da sehen Sie ein großes Einkaufszentrum. Biegen Sie dort rechts ab. Dann gehen Sie ungefähr 200 Meter weiter. Auf der linken Seite sehen Sie einen hohen Turm mit einer Uhr. Das ist das Rathaus.',
                'options' => ['A) Geradeaus, dann links abbiegen', 'B) Geradeaus bis zur Kreuzung, rechts abbiegen, dann links ist der Turm', 'C) Mit dem Bus 10 fahren', 'D) Am Einkaufszentrum ist es']
            ]),
            json_encode(['answer' => 'B']),
            'geradeaus bis zur Kreuzung, biegen Sie rechts ab, links sehen Sie einen hohen Turm',
            4, 10
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
