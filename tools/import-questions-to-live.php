<?php
/**
 * DTZ Sorularını Canlı Siteye Aktarma Scripti
 * Kullanım: php tools/import-questions-to-live.php
 * 
 * Bu script dtz_realistic_questions.sql dosyasındaki soruları canlı siteye aktarır
 */

echo "========================================\n";
echo "DTZ Sorularını Canlı Siteye Aktarma\n";
echo "========================================\n\n";

// Config
$LIVE_SITE_URL = 'https://dtz-lid.de';
$ADMIN_TOKEN = '';

// Sorular (dtz_realistic_questions.sql'den alınmıştır)
$questions = [
    // LESEN TEIL 1 - Anzeigen
    [
        'module' => 'lesen',
        'teil' => 1,
        'level' => 'A2',
        'question_type' => 'multiple_choice',
        'content' => [
            'question' => 'Sie sehen eine Anzeige. Was sucht die Familie Müller?',
            'text' => "WOHNUNG GESUCHT\n\nFamilie Müller sucht ab 01.06. eine 3- bis 4-Zimmer-Wohnung in Köln-Ehrenfeld oder Umgebung. Die Wohnung sollte mindestens 80 m² groß sein und einen Balkon haben. Wir haben zwei Kinder (3 und 5 Jahre). Wichtig: Die Wohnung muss barrierefrei sein und einen Aufzug haben.\n\nTel.: 0176-12345678 (ab 18 Uhr)",
            'options' => ['A) Eine 2-Zimmer-Wohnung mit Garten', 'B) Eine barrierefreie 3-4 Zimmer-Wohnung mit Balkon', 'C) Ein Haus mit Garten für Großfamilie', 'D) Eine möblierte Wohnung für Studenten']
        ],
        'correct_answer' => ['answer' => 'B'],
        'explanation' => 'In der Anzeige steht: "3- bis 4-Zimmer-Wohnung", "barrierefrei", "Balkon"',
        'difficulty' => 2,
        'points' => 10
    ],
    [
        'module' => 'lesen',
        'teil' => 1,
        'level' => 'A2',
        'question_type' => 'multiple_choice',
        'content' => [
            'question' => 'Sie sehen ein Plakat. Wann findet der Integrationskurs statt?',
            'text' => "INTEGRATIONSKURS A2\n\nSprach- und Orientierungskurs für Zuwanderer\n\nMontag – Donnerstag: 9:00 – 12:30 Uhr\nFreitag: 9:00 – 11:30 Uhr\n\nKursbeginn: 15. April 2024\nKursdauer: 6 Monate\n\nKosten: Kostenlos für Berechtigte\nOrt: Volkshochschule, Raum 203",
            'options' => ['A) Nur am Wochenende', 'B) Montag bis Freitag vormittags', 'C) Jeden Tag nachmittags', 'D) Nur dienstags und donnerstags']
        ],
        'correct_answer' => ['answer' => 'B'],
        'explanation' => 'Montag-Donnerstag 9:00-12:30 und Freitag 9:00-11:30 = werktags vormittags',
        'difficulty' => 2,
        'points' => 10
    ],
    [
        'module' => 'lesen',
        'teil' => 1,
        'level' => 'A2',
        'question_type' => 'multiple_choice',
        'content' => [
            'question' => 'Was kostet das Fitness-Training bei Sport Aktiv?',
            'text' => "SPORT AKTIV\nIhr Fitnessstudio in der City\n\nAngebot im März:\n• Grundfitness: 19,90 €/Monat\n• Premium (inkl. Kurse): 29,90 €/Monat\n• Studenten/Schüler: -50% Rabatt\n\nÖffnungszeiten:\nMo-Fr: 6-23 Uhr\nSa-So: 8-22 Uhr",
            'options' => ['A) 19,90 € für alle', 'B) 29,90 € für Premium', 'C) 9,95 € für Studenten', 'D) Kostenlos für alle']
        ],
        'correct_answer' => ['answer' => 'B'],
        'explanation' => 'Premium ist 29,90 €/Monat laut Anzeige',
        'difficulty' => 3,
        'points' => 10
    ],
    // LESEN TEIL 2 - Alltagstexte
    [
        'module' => 'lesen',
        'teil' => 2,
        'level' => 'A2',
        'question_type' => 'multiple_choice',
        'content' => [
            'question' => 'Warum kann Frau Schmidt nicht zur Arbeit kommen?',
            'text' => "Sehr geehrte Frau Meier,\n\nleider muss ich Ihnen mitteilen, dass ich heute und morgen nicht zur Arbeit kommen kann. Mein Sohn Tim (6 Jahre) ist sehr krank. Er hat hohes Fieber und der Arzt hat gesagt, er muss im Bett bleiben. Mein Mann ist gerade auf Geschäftsreise und meine Mutter, die sonst hilft, ist auch krank.\n\nIch werde am Donnerstag wieder kommen.\n\nMit freundlichen Grüßen\nAnna Schmidt",
            'options' => ['A) Sie ist selbst krank', 'B) Ihr Sohn ist krank und niemand kann aufpassen', 'C) Sie hat einen Arzttermin', 'D) Ihr Mann ist im Krankenhaus']
        ],
        'correct_answer' => ['answer' => 'B'],
        'explanation' => 'Text: "Mein Sohn Tim ist sehr krank", "Mein Mann ist auf Geschäftsreise", "meine Mutter ist auch krank"',
        'difficulty' => 3,
        'points' => 10
    ],
    [
        'module' => 'lesen',
        'teil' => 2,
        'level' => 'A2',
        'question_type' => 'multiple_choice',
        'content' => [
            'question' => 'Was möchte der Mieter von der Hausverwaltung?',
            'text' => "Betreff: Mängel in der Wohnung\n\nSehr geehrte Damen und Herren,\n\nich wohne seit drei Monaten in der Wohnung 4B. Ich habe mehrere Probleme:\n\n1. Die Heizung in der Küche funktioniert nicht richtig.\n2. Das Licht im Flackert. Das ist gefährlich.\n3. Der Wasserhahn im Bad tropft Tag und Nacht.\n\nIch bitte Sie, diese Probleme so schnell wie möglich zu beheben. Wenn das nicht geht, möchte ich die Miete mindern.",
            'options' => ['A) Er möchte kündigen', 'B) Er möchte die Miete mindern, wenn die Probleme nicht behoben werden', 'C) Er sucht eine neue Wohnung', 'D) Er möchte die Miete erhöhen']
        ],
        'correct_answer' => ['answer' => 'B'],
        'explanation' => 'Text: "Wenn das nicht geht, möchte ich die Miete mindern"',
        'difficulty' => 4,
        'points' => 10
    ],
    // LESEN TEIL 3 - Arbeitswelttexte
    [
        'module' => 'lesen',
        'teil' => 3,
        'level' => 'A2',
        'question_type' => 'multiple_choice',
        'content' => [
            'question' => 'Was ist die Aufgabe des neuen Mitarbeiters?',
            'text' => "Stellenanzeige: Mitarbeiter (m/w/d) im Kundenservice\n\nIhre Aufgaben:\n• Telefonische und schriftliche Beratung unserer Kunden\n• Annahme und Bearbeitung von Reklamationen\n• Terminvereinbarungen und Datenpflege\n• Unterstützung beim Versand von Ware\n\nWir erwarten:\n• Gute Deutschkenntnisse (B1-Niveau)\n• Grundkenntnisse in Englisch\n• Freundliches Auftreten\n• Teamfähigkeit",
            'options' => ['A) Nur Telefonate annehmen', 'B) Kunden beraten, Reklamationen bearbeiten, Daten pflegen', 'C) Nur E-Mails schreiben', 'D) Nur Versand vorbereiten']
        ],
        'correct_answer' => ['answer' => 'B'],
        'explanation' => 'Aufgabenliste umfasst: Beratung, Reklamationen, Termine, Datenpflege, Versand',
        'difficulty' => 3,
        'points' => 10
    ],
    // LESEN TEIL 4 - Komplexe Texte
    [
        'module' => 'lesen',
        'teil' => 4,
        'level' => 'B1',
        'question_type' => 'multiple_choice',
        'content' => [
            'question' => 'Was ist das Hauptproblem von Fatima nach dem Text?',
            'text' => "Fatima (34) kam vor zwei Jahren aus Syrien nach Deutschland. Sie hat schnell Deutsch gelernt und den Integrationskurs mit der B1-Prüfung bestanden. Jetzt sucht sie seit acht Monaten eine Arbeit als Krankenpflegehelferin.\n\nDas Problem: In Syrien war sie ausgebildete Krankenschwester, aber ihre Abschlüsse werden in Deutschland nicht anerkannt. Sie muss eine Anerkennungsprüfung machen, aber dafür braucht sie bessere Deutschkenntnisse (B2).",
            'options' => ['A) Sie kann kein Deutsch', 'B) Ihr ausländischer Abschluss wird nicht anerkannt und sie braucht B2', 'C) Sie will nicht im Krankenhaus arbeiten', 'D) Sie hat keine Arbeitserlaubnis']
        ],
        'correct_answer' => ['answer' => 'B'],
        'explanation' => 'Text: "ihre Abschlüsse werden nicht anerkannt", "braucht bessere Deutschkenntnisse (B2)"',
        'difficulty' => 4,
        'points' => 10
    ],
    // LESEN TEIL 5 - Matching
    [
        'module' => 'lesen',
        'teil' => 5,
        'level' => 'B1',
        'question_type' => 'matching',
        'content' => [
            'question' => 'Welcher Text passt zu welcher Überschrift?',
            'texts' => [
                'A) Ab sofort suchen wir Verstärkung für unser Team. Sie sollten Erfahrung im Verkauf haben.',
                'B) Am Samstag öffnen wir unsere neue Filiale. Es gibt Rabatte bis zu 50%.',
                'C) Leider müssen wir Sie informieren, dass wir ab nächstem Monat die Preise anpassen müssen.',
                'D) Wir haben unsere Öffnungszeiten geändert. Ab sofort haben wir auch sonntags geöffnet.'
            ],
            'headings' => ['1. Neue Arbeitsstelle', '2. Preiserhöhung', '3. Neue Filiale', '4. Mehr Service'],
            'options' => ['A) 1-A, 2-C, 3-B, 4-D', 'B) 1-B, 2-D, 3-A, 4-C', 'C) 1-D, 2-A, 3-B, 4-C', 'D) 1-C, 2-B, 3-D, 4-A']
        ],
        'correct_answer' => ['answer' => 'A'],
        'explanation' => '"Verstärkung suchen"=neue Arbeit, "Preise anpassen"=Preiserhöhung, "neue Filiale", "Öffnungszeiten"=mehr Service',
        'difficulty' => 5,
        'points' => 10
    ],
    // HÖREN TEIL 1 - Telefonansagen
    [
        'module' => 'hoeren',
        'teil' => 1,
        'level' => 'A2',
        'question_type' => 'multiple_choice',
        'content' => [
            'question' => 'Sie hören eine Telefonansage. Wann ist das Büro geöffnet?',
            'audio_text' => 'Guten Tag. Sie haben das Bürgeramt der Stadt Musterhausen erreicht. Unsere Öffnungszeiten sind montags und mittwochs von 8 bis 12 Uhr und dienstags von 14 bis 18 Uhr. Donnerstags und freitags haben wir geschlossen.',
            'options' => ['A) Mo-Mi 8-12 Uhr und Di 14-18 Uhr', 'B) Mo-Fr 8-16 Uhr', 'C) Nur dienstags', 'D) Jeden Tag außer Wochenende']
        ],
        'correct_answer' => ['answer' => 'A'],
        'explanation' => '"montags und mittwochs von 8 bis 12 Uhr und dienstags von 14 bis 18 Uhr"',
        'difficulty' => 3,
        'points' => 10
    ],
    [
        'module' => 'hoeren',
        'teil' => 1,
        'level' => 'A2',
        'question_type' => 'multiple_choice',
        'content' => [
            'question' => 'Was soll der Anrufer tun?',
            'audio_text' => 'Sie haben die Arztpraxis Dr. Weber erreicht. Bitte hinterlassen Sie Ihre Nachricht nach dem Signal. Nennen Sie Ihren Namen, Ihre Telefonnummer und den Grund Ihres Anrufs.',
            'options' => ['A) Sofort zum Arzt kommen', 'B) Nachricht hinterlassen mit Name, Telefonnummer und Grund', 'C) Morgen wieder anrufen', 'D) Im Internet buchen']
        ],
        'correct_answer' => ['answer' => 'B'],
        'explanation' => '"hinterlassen Sie Ihre Nachricht... Nennen Sie Ihren Namen, Ihre Telefonnummer und den Grund"',
        'difficulty' => 2,
        'points' => 10
    ],
    // HÖREN TEIL 2 - Alltagsgespräche
    [
        'module' => 'hoeren',
        'teil' => 2,
        'level' => 'A2',
        'question_type' => 'multiple_choice',
        'content' => [
            'question' => 'Was macht die Frau am Wochenende?',
            'audio_text' => 'Mann: Und, was hast du am Wochenende vor? Frau: Ach, ich muss leider arbeiten. Samstag habe ich Spätschicht bis 22 Uhr. Aber sonntag ist mein freier Tag. Da fahre ich mit den Kindern in den Zoo.',
            'options' => ['A) Sie arbeitet am Samstag und fährt am Sonntag in den Zoo', 'B) Sie arbeitet das ganze Wochenende', 'C) Sie bleibt zu Hause', 'D) Sie zieht um']
        ],
        'correct_answer' => ['answer' => 'A'],
        'explanation' => '"Samstag habe ich Spätschicht", "sonntag... fahre ich mit den Kindern in den Zoo"',
        'difficulty' => 3,
        'points' => 10
    ],
    // HÖREN TEIL 3 - Arbeitsgespräche
    [
        'module' => 'hoeren',
        'teil' => 3,
        'level' => 'A2',
        'question_type' => 'multiple_choice',
        'content' => [
            'question' => 'Was ist mit dem Projekt?',
            'audio_text' => 'Chefin: Herr Schmidt, wie sieht es mit dem Projekt aus? Die Deadline ist nächste Woche. Schmidt: Ja, ich weiß. Leider gibt es ein Problem. Die neue Software funktioniert noch nicht richtig. Chefin: Können wir die Deadline verschieben? Schmidt: Ich denke, zwei Tage mehr wären gut.',
            'options' => ['A) Das Projekt ist fertig', 'B) Es gibt Software-Probleme und sie brauchen 2 Tage mehr', 'C) Der Kunde hat abgesagt', 'D) Herr Schmidt ist krank']
        ],
        'correct_answer' => ['answer' => 'B'],
        'explanation' => '"Software funktioniert noch nicht richtig", "zwei Tage mehr wären gut"',
        'difficulty' => 4,
        'points' => 10
    ],
    // HÖREN TEIL 4 - Informationen
    [
        'module' => 'hoeren',
        'teil' => 4,
        'level' => 'B1',
        'question_type' => 'multiple_choice',
        'content' => [
            'question' => 'Wie kommt man zum Rathaus?',
            'audio_text' => 'Guten Tag. Sie möchten zum Rathaus? Gehen Sie diese Straße geradeaus bis zur Kreuzung. Da sehen Sie ein großes Einkaufszentrum. Biegen Sie dort rechts ab. Dann gehen Sie ungefähr 200 Meter weiter. Auf der linken Seite sehen Sie einen hohen Turm mit einer Uhr. Das ist das Rathaus.',
            'options' => ['A) Geradeaus, dann links abbiegen', 'B) Geradeaus bis zur Kreuzung, rechts abbiegen, dann links ist der Turm', 'C) Mit dem Bus 10 fahren', 'D) Am Einkaufszentrum ist es']
        ],
        'correct_answer' => ['answer' => 'B'],
        'explanation' => '"geradeaus bis zur Kreuzung", "biegen Sie rechts ab", "links sehen Sie einen hohen Turm"',
        'difficulty' => 4,
        'points' => 10
    ]
];

echo "📊 Aktarılacak soru sayısı: " . count($questions) . "\n\n";

// Admin token'ı al
if (empty($ADMIN_TOKEN)) {
    echo "🔑 Admin token gerekli!\n";
    echo "Token'i almak için:\n";
    echo "1. https://dtz-lid.de/admin/login.html adresine gidin\n";
    echo "2. Giriş yapın (hauptadmin / HauptAdmin!2026)\n";
    echo "3. Browser Console (F12) → localStorage.getItem('token')\n";
    echo "4. Çıkan değeri kopyalayıp buraya yapıştırın\n\n";
    
    echo "Token: ";
    $ADMIN_TOKEN = trim(fgets(STDIN));
    echo "\n";
}

if (empty($ADMIN_TOKEN)) {
    echo "❌ Token girilmedi. Çıkılıyor...\n";
    exit(1);
}

// Onay al
echo "⚠️  Bu işlem " . count($questions) . " soruyu canlı siteye (dtz-lid.de) ekleyecek.\n";
echo "Devam etmek istiyor musunuz? (evet/hayir): ";
$confirm = trim(fgets(STDIN));

if (strtolower($confirm) !== 'evet') {
    echo "İşlem iptal edildi.\n";
    exit(0);
}

echo "\n📤 Sorular gönderiliyor...\n";
echo "Hedef: $LIVE_SITE_URL/api/bulk-import-questions.php\n\n";

// API'ye gönder
$ch = curl_init("$LIVE_SITE_URL/api/bulk-import-questions.php");

$payload = json_encode(['questions' => $questions]);

curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'Authorization: Bearer ' . $ADMIN_TOKEN
]);
curl_setopt($ch, CURLOPT_TIMEOUT, 120);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

if (curl_errno($ch)) {
    echo "❌ Bağlantı hatası: " . curl_error($ch) . "\n";
    exit(1);
}

curl_close($ch);

if ($httpCode !== 200) {
    echo "❌ API Hatası (HTTP $httpCode):\n";
    echo $response . "\n";
    exit(1);
}

$result = json_decode($response, true);

if (!$result || !$result['success']) {
    echo "❌ Import başarısız: " . ($result['error'] ?? 'Bilinmeyen hata') . "\n";
    exit(1);
}

echo "\n✅ BAŞARILI!\n\n";
echo "📈 Sonuçlar:\n";
echo "  ✓ Eklenen soru: {$result['added']}\n";
echo "  ⊘ Atlanan soru (zaten var): {$result['skipped']}\n";
echo "  ○ Toplam soru (site): {$result['total_questions']}\n";

if (!empty($result['errors'])) {
    echo "\n⚠️  Hatalar:\n";
    foreach ($result['errors'] as $error) {
        echo "  - $error\n";
    }
}

echo "\n========================================\n";
echo "İşlem tamamlandı!\n";
echo "Siteyi kontrol edin: https://dtz-lid.de\n";
echo "========================================\n";
