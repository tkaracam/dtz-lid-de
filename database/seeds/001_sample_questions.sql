-- DTZ-LID Sample Questions Seed Data
-- Lesen, Hören, Schreiben örnek sorular

-- LESEN TEIL 1: Anzeigen verstehen (Multiple Choice)
INSERT INTO question_pools (module, teil, level, question_type, content, correct_answer, explanation, difficulty, points, is_active) VALUES
('lesen', 1, 'A2', 'multiple_choice', '{
  "text": "Anzeige:\nZu vermieten:\n2-Zimmer-Wohnung in der Innenstadt. 65 m², 650 € warm. Ab sofort. Tel.: 030-12345678",
  "question": "Wie viel kostet die Wohnung?",
  "options": [
    "A: 650 € kalt",
    "B: 650 € warm", 
    "C: 65 € warm",
    "D: 600 € warm"
  ],
  "image_url": null
}', '{"answer": "B", "variants": ["B: 650 € warm", "650 € warm"]}', 
'In der Anzeige steht "650 € warm". "Warm" bedeutet, dass Nebenkosten bereits enthalten sind.', 2, 10, 1),

('lesen', 1, 'A2', 'multiple_choice', '{
  "text": "Schild im Park:\nHunde müssen an der Leine geführt werden.\nBitte den Park sauber halten!",
  "question": "Was muss man im Park tun?",
  "options": [
    "A: Den Hund frei laufen lassen",
    "B: Den Hund an der Leine führen",
    "C: Keine Hunde mitbringen",
    "D: Den Park nicht betreten"
  ]
}', '{"answer": "B", "variants": ["B", "Hund an der Leine"]}',
'Das Schild sagt: "Hunde müssen an der Leine geführt werden."', 1, 10, 1),

('lesen', 1, 'B1', 'multiple_choice', '{
  "text": "Stellenanzeige:\nWir suchen ab sofort eine/einen motivierte/n Bürokaufmann/-frau.\nIhr Profil:\n- Abgeschlossene Ausbildung\n- Gute Deutsch- und Englischkenntnisse\n- Teamfähigkeit\nWir bieten:\n- Unbefristetes Arbeitsverhältnis\n- 30 Urlaubstage\n- Flexible Arbeitszeiten",
  "question": "Was wird NICHT in der Anzeige erwähnt?",
  "options": [
    "A: Das Gehalt",
    "B: Die Urlaubstage",
    "C: Die Sprachkenntnisse",
    "D: Die Ausbildung"
  ]
}', '{"answer": "A", "variants": ["A", "Gehalt"]}',
'Die Anzeige erwähnt Urlaubstage (30), Sprachkenntnisse (Deutsch/Englisch) und Ausbildung, aber nicht das Gehalt.', 4, 15, 1);

-- LESEN TEIL 2: Alltagskompetenzen
INSERT INTO question_pools (module, teil, level, question_type, content, correct_answer, explanation, difficulty, points, is_active) VALUES
('lesen', 2, 'A2', 'multiple_choice', '{
  "text": "E-Mail von Maria:\nLiebe Kolleginnen und Kollegen,\nich bin nächste Woche krankgeschrieben. Meine Aufgaben übernimmt Herr Schmidt. Bei Fragen erreichen Sie mich per E-Mail.\nLiebe Grüße\nMaria",
  "question": "Was soll man tun, wenn man eine Frage hat?",
  "options": [
    "A: Maria anrufen",
    "B: Herrn Schmidt fragen",
    "C: Warten bis Maria gesund ist",
    "D: Zurückschreiben"
  ]
}', '{"answer": "B", "variants": ["B", "Herrn Schmidt"]}',
'Maria sagt: "Meine Aufgaben übernimmt Herr Schmidt." Also soll man ihn fragen.', 2, 10, 1),

('lesen', 2, 'B1', 'multiple_choice', '{
  "text": "Bürgeramt - Terminbestätigung:\nTermin: 15.03.2025 um 10:30 Uhr\nAnliegen: Personalausweis verlängern\nBitte mitbringen:\n- Aktuelles Passfoto\n- Alter Personalausweis\n- Gebühren: 37 € (bar oder EC-Karte)",
  "question": "Wie kann man die Gebühr bezahlen?",
  "options": [
    "A: Nur bar",
    "B: Nur mit EC-Karte",
    "C: Bar oder mit EC-Karte",
    "D: Mit Kreditkarte"
  ]
}', '{"answer": "C", "variants": ["C", "bar oder EC-Karte"]}',
'Die Bestätigung sagt: "Gebühren: 37 € (bar oder EC-Karte)"', 3, 10, 1);

-- LESEN TEIL 3: Arbeitswelt
INSERT INTO question_pools (module, teil, level, question_type, content, correct_answer, explanation, difficulty, points, is_active) VALUES
('lesen', 3, 'B1', 'multiple_choice', '{
  "text": "Arbeitsvertrag Klauseln:\n§ 1: Die wöchentliche Arbeitszeit beträgt 40 Stunden.\n§ 2: Der Urlaubsanspruch beträgt 24 Werktage pro Jahr.\n§ 3: Die Probezeit beträgt 6 Monate.\n§ 4: Kündigungsfrist: 4 Wochen zum Monatsende.",
  "question": "Wie lange ist die Probezeit?",
  "options": [
    "A: 4 Wochen",
    "B: 3 Monate",
    "C: 6 Monate",
    "D: 24 Tage"
  ]
}', '{"answer": "C", "variants": ["C", "6 Monate"]}',
'§ 3 besagt: "Die Probezeit beträgt 6 Monate."', 3, 15, 1);

-- LESEN TEIL 4: Komplexe Texte
INSERT INTO question_pools (module, teil, level, question_type, content, correct_answer, explanation, difficulty, points, is_active) VALUES
('lesen', 4, 'B1', 'multiple_choice', '{
  "text": "Gesundes Arbeiten:\nViele Menschen arbeiten heute im Büro und sitzen den ganzen Tag. Das ist nicht gesund. Experten empfehlen: Stehen Sie alle 30 Minuten auf und gehen Sie kurz. Machen Sie Streckenübungen für den Rücken. Am besten arbeiten Sie abwechselnd sitzend und stehend.",
  "question": "Was empfehlen Experten?",
  "options": [
    "A: Den ganzen Tag sitzen",
    "B: Jede Stunde aufstehen",
    "C: Alle 30 Minuten aufstehen",
    "D: Nur stehend arbeiten"
  ]
}', '{"answer": "C", "variants": ["C", "30 Minuten"]}',
'Der Text sagt: "Stehen Sie alle 30 Minuten auf und gehen Sie kurz."', 4, 15, 1);

-- LESEN TEIL 5: Mehrteilige Aufgaben
INSERT INTO question_pools (module, teil, level, question_type, content, correct_answer, explanation, difficulty, points, is_active) VALUES
('lesen', 5, 'B1', 'matching', '{
  "text": "Ordnen Sie zu: Rechte und Pflichten in Deutschland",
  "items": [
    "1. Jeder Mensch",
    "2. Eltern",
    "3. Arbeitnehmer",
    "4. Auto fahren"
  ],
  "options": [
    "A: Müssen Steuern zahlen",
    "B: Haben das Recht auf freie Meinungsäußerung",
    "C: Müssen ihre Kinder zur Schule schicken",
    "D: Benötigen eine Erlaubnis (Führerschein)"
  ]
}', '{"matches": {"1": "B", "2": "C", "3": "A", "4": "D"}}',
'1. Menschenrechte -> Meinungsfreiheit (B), 2. Schulpflicht (C), 3. Steuerpflicht (A), 4. Führerscheinpflicht (D)', 5, 20, 1);

-- HÖREN TEIL 1: Alltagsgespräche
INSERT INTO question_pools (module, teil, level, question_type, content, media_urls, correct_answer, explanation, difficulty, points, is_active) VALUES
('hoeren', 1, 'A2', 'multiple_choice', '{
  "text": "Hören Sie das Gespräch.\nFrau Müller spricht mit ihrem Kollegen.",
  "question": "Wann ist das Meeting?",
  "options": [
    "A: Heute Nachmittag",
    "B: Morgen um 10 Uhr",
    "C: Nächste Woche",
    "D: Es gibt kein Meeting"
  ]
}', '["/audio/hoeren_teil1_beispiel.mp3"]', '{"answer": "B", "variants": ["B", "morgen 10"]}',
'Frau Müller sagt: "Morgen um 10 Uhr im Konferenzraum."', 3, 10, 1);

-- HÖREN TEIL 2: Informationen aus dem Radio
INSERT INTO question_pools (module, teil, level, question_type, content, media_urls, correct_answer, explanation, difficulty, points, is_active) VALUES
('hoeren', 2, 'B1', 'multiple_choice', '{
  "text": "Hören Sie die Nachrichten.",
  "question": "Wie ist das Wetter am Wochenende?",
  "options": [
    "A: Sonnig und warm",
    "B: Regen und kalt",
    "C: Bewölkt aber trocken",
    "D: Gewitter"
  ]
}', '["/audio/hoeren_teil2_wetter.mp3"]', '{"answer": "A", "variants": ["A", "sonnig"]}',
'Der Wetterbericht sagt: "Am Wochenende scheint die Sonne und es wird bis zu 25 Grad warm."', 4, 15, 1);

-- HÖREN TEIL 3: Anrufe und Durchsagen
INSERT INTO question_pools (module, teil, level, question_type, content, media_urls, correct_answer, explanation, difficulty, points, is_active) VALUES
('hoeren', 3, 'B1', 'multiple_choice', '{
  "text": "Hören Sie die Durchsage im Bahnhof.",
  "question": "Welcher Zug hat Verspätung?",
  "options": [
    "A: ICE 512 nach Berlin",
    "B: RE 7 nach Hamburg",
    "C: IC 2288 nach München",
    "D: Alle Züge pünktlich"
  ]
}', '["/audio/hoeren_teil3_bahnhof.mp3"]', '{"answer": "C", "variants": ["C", "IC 2288"]}',
'Die Durchsage: "IC 2288 nach München fährt heute 20 Minuten später."', 4, 15, 1);

-- SCHREIBEN TEIL 1: Formular ausfüllen
INSERT INTO question_pools (module, teil, level, question_type, content, correct_answer, explanation, difficulty, points, is_active, is_premium_only) VALUES
('schreiben', 1, 'A2', 'text_input', '{
  "text": "Sie möchten sich für einen Sprachkurs anmelden. Füllen Sie das Formular aus.",
  "fields": [
    "Vorname: _____",
    "Nachname: _____",
    "Geburtsdatum: _____",
    "E-Mail: _____",
    "Telefon: _____"
  ],
  "scenario": "Melden Sie sich für einen B1-Deutschkurs an. Ihre Daten: Max Mustermann, 15.03.1990, max@muster.de, 0176-12345678"
}', '{"evaluation_criteria": ["all_fields_filled", "correct_format", "appropriate_register"]}',
'Alle Felder korrekt ausgefüllt, passende Anrede, korrekte Datums- und Telefonformatierung.', 3, 15, 1, 0);

-- SCHREIBEN TEIL 2: E-Mail/SMS schreiben
INSERT INTO question_pools (module, teil, level, question_type, content, correct_answer, explanation, difficulty, points, is_active, is_premium_only) VALUES
('schreiben', 2, 'B1', 'text_input', '{
  "text": "Sie können am vereinbarten Termin nicht kommen. Schreiben Sie eine E-Mail.",
  "situation": "Sie haben einen Termin beim Arzt am Freitag um 14 Uhr. Sie müssen arbeiten.",
  "requirements": ["Entschuldigung", "Neuer Termin", "Telefonnummer"],
  "min_words": 40,
  "max_words": 80
}', '{"evaluation_criteria": ["apology", "reason", "new_appointment", "contact_info", "word_count"]}',
'Bewertungskriterien: Entschuldigung vorhanden, Grund genannt, neuer Termin vorgeschlagen, Kontaktdaten, Wortanzahl (40-80).', 5, 30, 1, 0),

('schreiben', 2, 'B1', 'text_input', '{
  "text": "Beschwerde schreiben: Sie haben ein Produkt online bestellt, aber es ist defekt angekommen.",
  "situation": "Sie haben einen Kaffeevollautomaten bestellt. Die Maschine funktioniert nicht.",
  "requirements": ["Beschreibung des Problems", "Reklamation", "Lösungsvorschlag"],
  "min_words": 60,
  "max_words": 100
}', '{"evaluation_criteria": ["description", "complaint", "solution", "tone", "word_count"]}',
'Formelle Beschwerde: Problem klar beschrieben, Rückgabe/Umtausch gefordert, höflicher Ton, 60-100 Wörter.', 7, 40, 1, 1);

-- LiD (Leben in Deutschland) Fragen
INSERT INTO question_pools (module, teil, level, question_type, content, correct_answer, explanation, difficulty, points, is_active) VALUES
('lid', 1, 'A2', 'multiple_choice', '{
  "text": "Frage zur politischen Bildung:",
  "question": "Was ist die Hauptstadt von Deutschland?",
  "options": [
    "A: München",
    "B: Hamburg",
    "C: Berlin",
    "D: Köln"
  ]
}', '{"answer": "C", "variants": ["C", "Berlin"]}',
'Die Hauptstadt der Bundesrepublik Deutschland ist Berlin.', 1, 5, 1),

('lid', 1, 'A2', 'multiple_choice', '{
  "text": "Frage zur Geschichte:",
  "question": "Wann war die Wiedervereinigung Deutschlands?",
  "options": [
    "A: 1989",
    "B: 1990",
    "C: 1991",
    "D: 1995"
  ]
}', '{"answer": "B", "variants": ["B", "1990"]}',
'Deutschland wurde am 3. Oktober 1990 wiedervereinigt.', 2, 5, 1),

('lid', 1, 'A2', 'multiple_choice', '{
  "text": "Frage zum Rechtssystem:",
  "question": "Wer macht die Gesetze in Deutschland?",
  "options": [
    "A: Der Bundespräsident",
    "B: Der Bundestag",
    "C: Das Bundesverfassungsgericht",
    "D: Die Polizei"
  ]
}', '{"answer": "B", "variants": ["B", "Bundestag"]}',
'Der Bundestag ist das Gesetzgebungsorgan und beschließt die Gesetze.', 3, 5, 1),

('lid', 1, 'B1', 'multiple_choice', '{
  "text": "Frage zur Sozialversicherung:",
  "question": "Was gehört NICHT zur Sozialversicherung?",
  "options": [
    "A: Krankenversicherung",
    "B: Rentenversicherung",
    "C: Lebensversicherung",
    "D: Arbeitslosenversicherung"
  ]
}', '{"answer": "C", "variants": ["C", "Lebensversicherung"]}',
'Die Sozialversicherung besteht aus KV, RV, AV und Pflegeversicherung. Lebensversicherung ist privat.', 4, 10, 1),

('lid', 1, 'B1', 'multiple_choice', '{
  "text": "Frage zur EU:",
  "question": "Wie viele Mitgliedstaaten hat die EU?",
  "options": [
    "A: 25",
    "B: 27",
    "C: 28",
    "D: 30"
  ]
}', '{"answer": "B", "variants": ["B", "27"]}',
'Die Europäische Union hat aktuell 27 Mitgliedstaaten (nach dem Brexit).', 4, 10, 1);

-- Indexes for JSON queries (SQLite)
-- Note: For production PostgreSQL, use GIN indexes
