-- REALISTISCHE DTZ-FORMAT FRAGEN
-- Basierend auf G.A.S.T. und Goethe Institut DTZ Format

-- ============================================
-- LESEN TEIL 1: Anzeigen, Plakate, Schilder (5 Fragen)
-- Zeit: 10 Minuten | Niveau: A2
-- ============================================

INSERT INTO question_pools (module, teil, level, question_type, content, correct_answer, explanation, difficulty, points) VALUES
('lesen', 1, 'A2', 'multiple_choice', 
'{"question": "Sie sehen eine Anzeige. Was sucht die Familie Müller?", 
"text": "WOHNUNG GESUCHT\n\nFamilie Müller sucht ab 01.06. eine 3- bis 4-Zimmer-Wohnung in Köln-Ehrenfeld oder Umgebung. Die Wohnung sollte mindestens 80 m² groß sein und einen Balkon haben. Wir haben zwei Kinder (3 und 5 Jahre). Wichtig: Die Wohnung muss barrierefrei sein und einen Aufzug haben. Wir sind Nichtraucher und haben keine Haustiere.\n\nTel.: 0176-12345678 (ab 18 Uhr)\nE-Mail: mueller.familie@email.de", 
"options": ["A) Eine 2-Zimmer-Wohnung mit Garten", "B) Eine barrierefreie 3-4 Zimmer-Wohnung mit Balkon", "C) Ein Haus mit Garten für Großfamilie", "D) Eine möblierte Wohnung für Studenten"]}',
'{"answer": "B"}', 'In der Anzeige steht: "3- bis 4-Zimmer-Wohnung", "barrierefrei", "Balkon"', 2, 10),

('lesen', 1, 'A2', 'multiple_choice',
'{"question": "Sie sehen ein Plakat. Wann findet der Integrationskurs statt?",
"text": "INTEGRATIONSKURS A2\n\nSprach- und Orientierungskurs\nfür Zuwanderer\n\nMontag – Donnerstag: 9:00 – 12:30 Uhr\nFreitag: 9:00 – 11:30 Uhr\n\nKursbeginn: 15. April 2024\nKursdauer: 6 Monate\n\nKosten: Kostenlos für Berechtigte\nOrt: Volkshochschule, Raum 203\n\nAnmeldung: Ab sofort im Bürgeramt",
"options": ["A) Nur am Wochenende", "B) Montag bis Freitag vormittags", "C) Jeden Tag nachmittags", "D) Nur dienstags und donnerstags"]}',
'{"answer": "B"}', 'Montag-Donnerstag 9:00-12:30 und Freitag 9:00-11:30 = werktags vormittags', 2, 10),

('lesen', 1, 'A2', 'multiple_choice',
'{"question": "Was kostet das Fitness-Training bei Sport Aktiv?",
"text": "SPORT AKTIV\nIhr Fitnessstudio in der City\n\nAngebot im März:\n• Grundfitness: 19,90 €/Monat\n• Premium (inkl. Kurse): 29,90 €/Monat\n• Studenten/Schüler: -50% Rabatt\n\nÖffnungszeiten:\nMo-Fr: 6-23 Uhr\nSa-So: 8-22 Uhr\n\nProbetraining: Kostenlos!\nTel.: 0221-9876543",
"options": ["A) 19,90 € für alle", "B) 29,90 € für Premium", "C) 9,95 € für Studenten", "D) Kostenlos für alle"]}',
'{"answer": "B"}', 'Premium ist 29,90 €/Monat laut Anzeige', 3, 10),

-- ============================================
-- LESEN TEIL 2: Alltagstexte verstehen (5 Fragen)
-- Zeit: 10 Minuten | Niveau: A2
-- ============================================

('lesen', 2, 'A2', 'multiple_choice',
'{"question": "Warum kann Frau Schmidt nicht zur Arbeit kommen?",
"text": "Sehr geehrte Frau Meier,\n\nleider muss ich Ihnen mitteilen, dass ich heute und morgen nicht zur Arbeit kommen kann. Mein Sohn Tim (6 Jahre) ist sehr krank. Er hat hohes Fieber und der Arzt hat gesagt, er muss im Bett bleiben. Mein Mann ist gerade auf Geschäftsreise und meine Mutter, die sonst hilft, ist auch krank.\n\nIch werde am Donnerstag wieder kommen und die überfälligen Berichte dann fertig machen.\n\nMit freundlichen Grüßen\nAnna Schmidt",
"options": ["A) Sie ist selbst krank", "B) Ihr Sohn ist krank und niemand kann aufpassen", "C) Sie hat einen Arzttermin", "D) Ihr Mann ist im Krankenhaus"]}',
'{"answer": "B"}', 'Text: "Mein Sohn Tim ist sehr krank", "Mein Mann ist auf Geschäftsreise", "meine Mutter ist auch krank"', 3, 10),

('lesen', 2, 'A2', 'multiple_choice',
'{"question": "Was möchte der Mieter von der Hausverwaltung?",
"text": "Betreff: Mängel in der Wohnung\n\nSehr geehrte Damen und Herren,\n\nich wohne seit drei Monaten in der Wohnung 4B in der Hauptstraße 45. Ich habe mehrere Probleme:\n\n1. Die Heizung in der Küche funktioniert nicht richtig. Es ist immer sehr kalt.
2. Das Licht im Flackert. Das ist gefährlich.
3. Der Wasserhahn im Bad tropft Tag und Nacht.
\nIch bitte Sie, diese Probleme so schnell wie möglich zu beheben. Wenn das nicht geht, möchte ich die Miete mindern.\n\nMit freundlichen Grüßen\nMax Mustermann",
"options": ["A) Er möchte kündigen", "B) Er möchte die Miete mindern, wenn die Probleme nicht behoben werden", "C) Er sucht eine neue Wohnung", "D) Er möchte die Miete erhöhen"]}',
'{"answer": "B"}', 'Text: "Wenn das nicht geht, möchte ich die Miete mindern"', 4, 10),

-- ============================================
-- LESEN TEIL 3: Arbeitswelttexte (5 Fragen)
-- Zeit: 10 Minuten | Niveau: A2/B1
-- ============================================

('lesen', 3, 'A2', 'multiple_choice',
'{"question": "Was ist die Aufgabe des neuen Mitarbeiters?",
"text": "Stellenanzeige: Mitarbeiter (m/w/d) im Kundenservice\n\nWir suchen ab sofort einen neuen Kollegen für unser Team.\n\nIhre Aufgaben:\n• Telefonische und schriftliche Beratung unserer Kunden\n• Annahme und Bearbeitung von Reklamationen\n• Terminvereinbarungen und Datenpflege\n• Unterstützung beim Versand von Ware\n\nWir erwarten:\n• Gute Deutschkenntnisse (B1-Niveau)\n• Grundkenntnisse in Englisch\n• Freundliches Auftreten\n• Teamfähigkeit\n\nWir bieten:\n• Festanstellung mit 40 Stunden/Woche\n• Gehalt: 2.400 € brutto/Monat\n• 30 Tage Urlaub im Jahr\n• Weiterbildungsmöglichkeiten",
"options": ["A) Nur Telefonate annehmen", "B) Kunden beraten, Reklamationen bearbeiten, Daten pflegen", "C) Nur E-Mails schreiben", "D) Nur Versand vorbereiten"]}',
'{"answer": "B"}', 'Aufgabenliste umfasst: Beratung, Reklamationen, Termine, Datenpflege, Versand', 3, 10),

-- ============================================
-- LESEN TEIL 4: Komplexe Texte (10 Fragen)
-- Zeit: 10 Minuten | Niveau: B1
-- ============================================

('lesen', 4, 'B1', 'multiple_choice',
'{"question": "Was ist das Hauptproblem von Fatima nach dem Text?",
"text": "Fatima (34) kam vor zwei Jahren aus Syrien nach Deutschland. Sie hat schnell Deutsch gelernt und den Integrationskurs mit der B1-Prüfung bestanden. Jetzt sucht sie seit acht Monaten eine Arbeit als Krankenpflegehelferin.\n\nDas Problem: In Syrien war sie ausgebildete Krankenschwester, aber ihre Abschlüsse werden in Deutschland nicht anerkannt. Sie muss eine Anerkennungsprüfung machen, aber dafür braucht sie bessere Deutschkenntnisse (B2).\n\nFatima arbeitet momentan als Putzfrau in einem Krankenhaus. Sie sagt: „Ich bin froh, dass ich überhaupt arbeiten darf. Aber mein Traum ist es, wieder im Krankenhaus zu arbeiten und Patienten zu helfen.\"",
"options": ["A) Sie kann kein Deutsch", "B) Ihr ausländischer Abschluss wird nicht anerkannt und sie braucht B2", "C) Sie will nicht im Krankenhaus arbeiten", "D) Sie hat keine Arbeitserlaubnis"]}',
'{"answer": "B"}', 'Text: "ihre Abschlüsse werden nicht anerkannt", "braucht bessere Deutschkenntnisse (B2)"', 4, 10),

-- ============================================
-- LESEN TEIL 5: Mehrere Texte zuordnen (5 Fragen)
-- Zeit: 5 Minuten | Niveau: B1
-- ============================================

('lesen', 5, 'B1', 'matching',
'{"question": "Welcher Text passt zu welcher Überschrift?",
"texts": [
  "A) Ab sofort suchen wir Verstärkung für unser Team. Sie sollten Erfahrung im Verkauf haben und gerne mit Menschen arbeiten.",
  "B) Am Samstag, den 15. Juni, öffnen wir unsere neue Filiale in der Innenstadt. Es gibt Rabatte bis zu 50%.",
  "C) Leider müssen wir Sie informieren, dass wir ab nächstem Monat die Preise anpassen müssen.",
  "D) Wir haben unsere Öffnungszeiten geändert. Ab sofort haben wir auch sonntags von 12-18 Uhr geöffnet."
],
"headings": ["1. Neue Arbeitsstelle", "2. Preiserhöhung", "3. Neue Filiale", "4. Mehr Service"],
"options": ["A) 1-A, 2-C, 3-B, 4-D", "B) 1-B, 2-D, 3-A, 4-C", "C) 1-D, 2-A, 3-B, 4-C", "D) 1-C, 2-B, 3-D, 4-A"]}',
'{"answer": "A"}', '"Verstärkung suchen"=neue Arbeit, "Preise anpassen"=Preiserhöhung, "neue Filiale öffnen", "Öffnungszeiten geändert"=mehr Service', 5, 10);

-- ============================================
-- HÖREN TEIL 1: Telefonansagen (5 Fragen)
-- Zeit: ca. 3 Minuten | Niveau: A2
-- ============================================

('hoeren', 1, 'A2', 'multiple_choice',
'{"question": "Sie hören eine Telefonansage. Wann ist das Büro geöffnet?",
"audio_text": "Guten Tag. Sie haben das Bürgeramt der Stadt Musterhausen erreicht. Unsere Öffnungszeiten sind montags und mittwochs von 8 bis 12 Uhr und dienstags von 14 bis 18 Uhr. Donnerstags und freitags haben wir geschlossen. Für Termine buchen Sie bitte online unter www.buergeramt-musterhausen.de. Auf Wiederhören.",
"options": ["A) Mo-Mi 8-12 Uhr und Di 14-18 Uhr", "B) Mo-Fr 8-16 Uhr", "C) Nur dienstags", "D) Jeden Tag außer Wochenende"]}',
'{"answer": "A"}', '"montags und mittwochs von 8 bis 12 Uhr und dienstags von 14 bis 18 Uhr"', 3, 10),

('hoeren', 1, 'A2', 'multiple_choice',
'{"question": "Was soll der Anrufer tun?",
"audio_text": "Sie haben die Arztpraxis Dr. Weber erreicht. Bitte hinterlassen Sie Ihre Nachricht nach dem Signal. Nennen Sie Ihren Namen, Ihre Telefonnummer und den Grund Ihres Anrufs. Wenn Sie einen Termin brauchen, rufen wir Sie bis spätestens 18 Uhr zurück. Bei akuten Beschwerden wählen Sie bitte die 116 117.",
"options": ["A) Sofort zum Arzt kommen", "B) Nachricht hinterlassen mit Name, Telefonnummer und Grund", "C) Morgen wieder anrufen", "D) Im Internet buchen"]}',
'{"answer": "B"}', '"hinterlassen Sie Ihre Nachricht... Nennen Sie Ihren Namen, Ihre Telefonnummer und den Grund"', 2, 10),

-- ============================================
-- HÖREN TEIL 2: Alltagsgespräche (5 Fragen)
-- Zeit: ca. 5 Minuten | Niveau: A2
-- ============================================

('hoeren', 2, 'A2', 'multiple_choice',
'{"question": "Was macht die Frau am Wochenende?",
"audio_text": "Mann: Und, was hast du am Wochenende vor?\nFrau: Ach, ich muss leider arbeiten. Samstag habe ich Spätschicht bis 22 Uhr. Aber sonntag ist mein freier Tag. Da fahre ich mit den Kindern in den Zoo.\nMann: Oh, das ist schön. Ich muss am Wochenende umziehen. Mein Cousin hilft mir.",
"options": ["A) Sie arbeitet am Samstag und fährt am Sonntag in den Zoo", "B) Sie arbeitet das ganze Wochenende", "C) Sie bleibt zu Hause", "D) Sie zieht um"]}',
'{"answer": "A"}', '"Samstag habe ich Spätschicht", "sonntag... fahre ich mit den Kindern in den Zoo"', 3, 10),

-- ============================================
-- HÖREN TEIL 3: Arbeitsgespräche (5 Fragen)
-- Zeit: ca. 5 Minuten | Niveau: A2/B1
-- ============================================

('hoeren', 3, 'A2', 'multiple_choice',
'{"question": "Was ist mit dem Projekt?",
"audio_text": "Chefin: Herr Schmidt, wie sieht es mit dem Projekt aus? Die Deadline ist nächste Woche.\nSchmidt: Ja, ich weiß. Leider gibt es ein Problem. Die neue Software funktioniert noch nicht richtig. Mein Kollege aus der IT arbeitet daran.\nChefin: Können wir die Deadline verschieben?\nSchmidt: Ich denke, zwei Tage mehr wären gut. Dann können wir alles testen.\nChefin: In Ordnung, ich informiere den Kunden.",
"options": ["A) Das Projekt ist fertig", "B) Es gibt Software-Probleme und sie brauchen 2 Tage mehr", "C) Der Kunde hat abgesagt", "D) Herr Schmidt ist krank"]}',
'{"answer": "B"}', '"Software funktioniert noch nicht richtig", "zwei Tage mehr wären gut"', 4, 10),

-- ============================================
-- HÖREN TEIL 4: Informationen und Anweisungen (5 Fragen)
-- Zeit: ca. 7 Minuten | Niveau: B1
-- ============================================

('hoeren', 4, 'B1', 'multiple_choice',
'{"question": "Wie kommt man zum Rathaus?",
"audio_text": "Guten Tag. Sie möchten zum Rathaus? Das ist ganz einfach. Gehen Sie diese Straße geradeaus bis zur Kreuzung. Da sehen Sie ein großes Einkaufszentrum. Biegen Sie dort rechts ab. Dann gehen Sie ungefähr 200 Meter weiter. Auf der linken Seite sehen Sie einen hohen Turm mit einer Uhr. Das ist das Rathaus. Die Bushaltestelle ist direkt davor. Sie können auch die Buslinie 15 nehmen. Die fährt alle 10 Minuten.",
"options": ["A) Geradeaus, dann links abbiegen", "B) Geradeaus bis zur Kreuzung, rechts abbiegen, dann links ist der Turm", "C) Mit dem Bus 10 fahren", "D) Am Einkaufszentrum ist es"]}',
'{"answer": "B"}', '"geradeaus bis zur Kreuzung", "biegen Sie rechts ab", "links sehen Sie einen hohen Turm"', 4, 10);
