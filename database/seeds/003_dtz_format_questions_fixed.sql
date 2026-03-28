-- DTZ Format Questions - Telif hakkı ihlali olmadan özgün içerik
-- Format: Goethe-Institut DTZ (Deutsch-Test für Zuwanderer) yapısına benzer

-- ============================================
-- HÖREN (4 Teile, Toplam 20 soru)
-- ============================================

-- HÖREN TEIL 1: Telefonansagen (5 soru, A1-A2)
-- Gerçekçi senaryolarla özgün içerik
INSERT INTO question_pools (module, teil, level, question_type, content, correct_answer, explanation, difficulty, points) VALUES
('hoeren', 1, 'A2', 'multiple_choice', 
'{"question": "Sie hören eine Telefonansage. Wann ist das Bürgerbüro geöffnet?", "options": ["A) Montag bis Freitag von 8 bis 12 Uhr", "B) Dienstag und Donnerstag von 14 bis 18 Uhr", "C) Montag, Mittwoch und Freitag von 9 bis 12 Uhr", "D) Jeden Tag von 8 bis 16 Uhr"], "audio_text": "Guten Tag, Sie haben das Bürgerbüro der Stadt Neustadt erreicht. Unsere Öffnungszeiten sind montags, mittwochs und freitags von 9 bis 12 Uhr. Dienstags und donnerstags haben wir geschlossen."}',
'{"answer": "C"}',
'Öffnungszeiten verstehen: montags, mittwochs und freitags von 9 bis 12 Uhr', 3, 5),

('hoeren', 1, 'A2', 'multiple_choice', 
'{"question": "Was soll man tun, wenn man einen Termin braucht?", "options": ["A) Eine E-Mail schreiben", "B) Online buchen", "C) Nachmittags anrufen", "D) Am Samstag vorbeikommen"], "audio_text": "Für Termine nutzen Sie bitte unsere Online-Terminbuchung auf unserer Webseite www.neustadt.de. Eine telefonische Terminvereinbarung ist nicht mehr möglich."}',
'{"answer": "B"}',
'Digitale Dienstleistungen: Online-Terminbuchung', 4, 5),

('hoeren', 1, 'A2', 'multiple_choice', 
'{"question": "Welche Information gibt die Ansage?", "options": ["A) Die Praxis ist umgezogen", "B) Dr. Müller ist im Urlaub", "C) Es gibt neue Sprechzeiten", "D) Die Telefonnummer hat sich geändert"], "audio_text": "Achtung, wichtige Mitteilung an alle Patienten. Die Praxis von Dr. Müller ist in die Musterstraße 45 umgezogen. Die neue Telefonnummer lautet 0123-456789."}',
'{"answer": "A"}',
'Umzugsinformationen verstehen', 3, 5),

('hoeren', 1, 'A2', 'multiple_choice', 
'{"question": "Wann kommt der Techniker?", "options": ["A) Heute Nachmittag", "B) Morgen zwischen 9 und 11 Uhr", "C) Übermorgen am Vormittag", "D) Nächste Woche"], "audio_text": "Hier spricht die Techniker-Hotline. Ihr Termin wurde bestätigt. Der Techniker kommt morgen zwischen 9 und 11 Uhr zu Ihnen. Bitte stellen Sie sicher, dass jemand zu Hause ist."}',
'{"answer": "B"}',
'Terminbestätigung verstehen', 3, 5),

('hoeren', 1, 'A2', 'multiple_choice', 
'{"question": "Was muss der Anrufer mitbringen?", "options": ["A) Einen Terminschein", "B) Einen gültigen Ausweis", "C) Die Versicherungskarte", "D] Alle Unterlagen"], "audio_text": "Willkommen bei der Ausländerbehörde. Für Ihren Termin benötigen Sie einen gültigen Reisepass oder Personalausweis und die Anmeldebestätigung vom Wohnungsamt."}',
'{"answer": "B"}',
'Benötigte Dokumente verstehen', 4, 5);

-- HÖREN TEIL 2: Alltagsgespräche (5 soru, A2)
INSERT INTO question_pools (module, teil, level, question_type, content, correct_answer, explanation, difficulty, points) VALUES
('hoeren', 2, 'A2', 'multiple_choice', 
'{"question": "Wohin fährt die Frau am Wochenende?", "options": ["A) Zu ihren Eltern", "B) An den See", "C) In die Berge", "D) Sie bleibt zu Hause"], "audio_text": "Mann: Hast du schon Pläne für das Wochenende? Frau: Ja, ich fahre zu meinen Eltern. Sie wohnen jetzt an einem See und ich besuche sie jeden Monat. Mann: Das klingt schön. Frau: Ja, ich freue mich schon auf die Ruhe dort."}',
'{"answer": "A"}',
'Wochenendpläne verstehen', 4, 5),

('hoeren', 2, 'A2', 'multiple_choice', 
'{"question": "Warum kann der Mann nicht ins Kino gehen?", "options": ["A) Er hat keine Zeit", "B) Er hat kein Geld", "C) Er ist krank", "D) Er hat den Film schon gesehen"], "audio_text": "Frau: Kommst du heute Abend mit ins Kino? Der neue Film läuft. Mann: Ich würde ja gerne, aber ich muss noch für die Prüfung lernen. Frau: Ach, das ist wichtig. Viel Erfolg! Mann: Danke, nächste Woche geht es."}',
'{"answer": "A"}',
'Ablehnung mit Begründung verstehen', 4, 5),

('hoeren', 2, 'A2', 'multiple_choice', 
'{"question": "Was möchte die Frau kaufen?", "options": ["A) Ein neues Handy", "B) Einen Computer", "C) Ein Fahrrad", "D) Möbel"], "audio_text": "Mann: Du schaust schon lange nach einem neuen Handy. Frau: Ja, aber ich kann mich nicht entscheiden. Das neue Modell ist teuer, aber das alte ist zu langsam. Mann: Vergleich doch die Preise online. Frau: Gute Idee, mache ich heute Abend."}',
'{"answer": "A"}',
'Kaufentscheidung verstehen', 5, 5),

('hoeren', 2, 'A2', 'multiple_choice', 
'{"question": "Wo arbeitet der Mann jetzt?", "options": ["A) In einer Bank", "B] In einem Supermarkt", "C) In einer Schule", "D) In einer Fabrik"], "audio_text": "Frau: Du hast ja einen neuen Job! Wie gefällt es dir? Mann: Sehr gut. Ich arbeite jetzt als Verkäufer im Supermarkt. Die Kollegen sind nett und die Arbeitszeiten passen. Frau: Das freut mich. Mann: Ja, viel besser als vorher in der Fabrik."}',
'{"answer": "B"}',
'Berufswechsel verstehen', 4, 5),

('hoeren', 2, 'A2', 'multiple_choice', 
'{"question": "Wie ist das Wetter morgen?", "options": ["A) Sonnig und warm", "B) Regnerisch", "C) Bewölkt", "D) Stürmisch"], "audio_text": "Frau: Hast du die Wettervorhersage gesehen? Mann: Ja, leider. Morgen soll es den ganzen Tag regnen. Frau: Oh nein, und ich wollte Wäsche waschen. Mann: Dann vielleicht am Wochenende. Frau: Ja, hoffentlich wird es dann besser."}',
'{"answer": "B"}',
'Wettervorhersage verstehen', 3, 5);

-- HÖREN TEIL 3: Arbeitsgespräche/Interviews (5 soru, A2-B1)
INSERT INTO question_pools (module, teil, level, question_type, content, correct_answer, explanation, difficulty, points) VALUES
('hoeren', 3, 'B1', 'multiple_choice', 
'{"question": "Was ist die wichtigste Qualifikation für den Job?", "options": ["A) Berufserfahrung", "B) Sprachkenntnisse", "C) Computerkenntnisse", "D) Ein Führerschein"], "audio_text": "Interviewer: Für diese Position suchen wir jemanden mit guten Deutschkenntnissen. Das ist besonders wichtig, da Sie viel telefonieren werden. Bewerber: Ich habe den B2-Kurs abgeschlossen und spreche fließend. Interviewer: Ausgezeichnet. Berufserfahrung ist auch gut, aber Sprache ist hier Priorität."}',
'{"answer": "B"}',
'Prioritäten im Jobinterview erkennen', 6, 5),

('hoeren', 3, 'B1', 'multiple_choice', 
'{"question": "War hat der Bewerber früher gearbeitet?", "options": ["A) In einem Restaurant", "B] In einem Büro", "C) In einem Krankenhaus", "D) In einer Schule"], "audio_text": "Bewerber: In meinem Heimatland habe ich als Koch in einem großen Restaurant gearbeitet. Hier in Deutschland habe ich aber erst einen Hilfsjob in einem Supermarkt. Interviewer: Verstehe. Wollen Sie wieder in der Gastronomie arbeiten? Bewerber: Ja, das ist mein Wunsch."}',
'{"answer": "A"}',
'Berufserfahrung verstehen', 5, 5),

('hoeren', 3, 'B1', 'multiple_choice', 
'{"question": "Wann kann der Bewerber anfangen?", "options": ["A) Sofort", "B) In zwei Wochen", "C) Nächsten Monat", "D) Erst im nächsten Jahr"], "audio_text": "Interviewer: Wann könnten Sie bei uns anfangen? Bewerber: Ich habe noch eine Kündigungsfrist von zwei Wochen in meinem jetzigen Job. Danach kann ich sofort beginnen. Interviewer: Das passt gut. Wir suchen jemanden für nächsten Monat. Bewerber: Perfekt, das klappt dann."}',
'{"answer": "B"}',
'Kündigungsfrist verstehen', 6, 5),

('hoeren', 3, 'B1', 'multiple_choice', 
'{"question": "Was bietet die Firma an?", "options": ["A) Höheres Gehalt", "B) Weiterbildung", "C) Home-Office", "D) Überstundenvergütung"], "audio_text": "Interviewer: Wir bieten nicht nur einen guten Lohn, sondern auch regelmäßige Weiterbildungen. Einmal im Jahr gibt es eine interne Fortbildung. Bewerber: Das ist sehr wichtig für mich. Ich möchte mich weiterentwickeln. Interviewer: Das verstehen wir. Ihre Entwicklung liegt uns am Herzen."}',
'{"answer": "B"}',
'Benefits verstehen', 5, 5),

('hoeren', 3, 'B1', 'multiple_choice', 
'{"question": "Welche Frage stellt der Interviewer?", "options": ["A) Was sind Ihre Stärken?", "B) Warum wollen Sie hier arbeiten?", "C) Wo sehen Sie sich in 5 Jahren?", "D) Was erwarten Sie vom Gehalt?"], "audio_text": "Interviewer: Erzählen Sie mir bitte, warum Sie genau bei uns arbeiten wollen. Bewerber: Ihr Unternehmen hat einen sehr guten Ruf. Außerdem bieten Sie faire Arbeitsbedingungen. Interviewer: Das stimmt. Und was erwarten Sie von uns? Bewerber: Ich hoffe auf eine langfristige Zusammenarbeit."}',
'{"answer": "B"}',
'Interview-Fragen verstehen', 5, 5);

-- HÖREN TEIL 4: Information/Rundfunk (5 soru, B1)
INSERT INTO question_pools (module, teil, level, question_type, content, correct_answer, explanation, difficulty, points) VALUES
('hoeren', 4, 'B1', 'multiple_choice', 
'{"question": "Was ist das Hauptthema der Nachrichten?", "options": ["A) Wirtschaft", "B) Gesundheit", "C) Umwelt", "D) Bildung"], "audio_text": "Guten Abend, hier ist die Tagesschau. Unser Hauptthema heute: Die neue Klimaschutzverordnung der Regierung. Ab nächstem Jahr gelten strengere Regeln für Autofahrer. Außerdem: Mehr Geld für erneuerbare Energien. Und im Sport: Der lokale Fußballverein gewinnt das wichtige Spiel."}',
'{"answer": "C"}',
'Hauptthema erkennen', 6, 5),

('hoeren', 4, 'B1', 'multiple_choice', 
'{"question": "Ab wann gelten die neuen Regeln?", "options": ["A) Sofort", "B) Ab nächster Woche", "C) Ab nächstem Jahr", "D) In zwei Jahren"], "audio_text": "Reporter: Ab dem 1. Januar nächsten Jahres treten neue Regeln für Mülltrennung in Kraft. Bürger müssen dann Plastik, Papier und Restmüll noch genauer trennen. Bürgermeister: Wir wollen damit das Recycling verbessern und die Umwelt schützen. Reporter: Die Stadt stellt dafür neue Behälter auf."}',
'{"answer": "C"}',
'Zeitangaben in Nachrichten', 6, 5),

('hoeren', 4, 'B1', 'multiple_choice', 
'{"question": "Warum gibt es Änderungen im öffentlichen Nahverkehr?", "options": ["A) Zu wenig Fahrgäste", "B) Streik der Fahrer", "C) Bauarbeiten", "D] Neue Tarife"], "audio_text": "Achtung Fahrgäste: Wegen Bauarbeiten an der Hauptstraße kommt es zu Verspätungen bei den Bussen der Linie 5 und 12. Die Bauarbeiten dauern voraussichtlich drei Wochen. Als Alternative können Sie die U-Bahn nutzen. Wir bitten um Verständnis."}',
'{"answer": "C"}',
'Verkehrsinformationen verstehen', 5, 5),

('hoeren', 4, 'B1', 'multiple_choice', 
'{"question": "Was wird im Beitrag empfohlen?", "options": ["A) Mehr Sport treiben", "B) Weniger Fleisch essen", "C] Mehr Wasser trinken", "D) Früher schlafen gehen"], "audio_text": "Experte: Für ein gesundes Leben empfehle ich, weniger Fleisch zu essen. Versuchen Sie, an zwei Tagen in der Woche vegetarisch zu essen. Das ist gut für die Gesundheit und die Umwelt. Interviewer: Was noch? Experte: Bewegung ist natürlich auch wichtig. Aber die Ernährung ist der erste Schritt."}',
'{"answer": "B"}',
'Empfehlungen verstehen', 6, 5),

('hoeren', 4, 'B1', 'multiple_choice', 
'{"question": "Wie viel kostet der neue Kurs?", "options": ["A) 50 Euro", "B) 100 Euro", "C) 150 Euro", "D) 200 Euro"], "audio_text": "Moderator: Unser Integrationskurs startet nächsten Monat. Die Kursgebühr beträgt 150 Euro für drei Monate. Wer einen Bildungsgutschein hat, zahlt nur 50 Euro. Anmeldungen sind noch möglich. Der Kurs findet montags und mittwochs statt."}',
'{"answer": "C"}',
'Preisangaben mit Ausnahmen verstehen', 6, 5);

-- ============================================
-- LESEN (5 Teile, Toplam 25 soru)
-- ============================================

-- LESEN TEIL 1: Anzeigen (5 soru, A1-A2)
INSERT INTO question_pools (module, teil, level, question_type, content, correct_answer, explanation, difficulty, points) VALUES
('lesen', 1, 'A2', 'multiple_choice', 
'{"question": "Was wird angeboten?", "text": "***ZU VERSCHENKEN*** Altes Sofa, braun, guter Zustand. Maße: 2m x 90cm. Abzuholen in Berlin-Mitte bis 15.03. Tel: 0176-123456", "options": ["A) Ein neues Sofa zu kaufen", "B) Ein altes Sofa kostenlos", "C) Ein Sofa zu vermieten", "D) Ein Sofa zu reparieren"]}',
'{"answer": "B"}',
'Anzeigen verstehen: Zu verschenken = kostenlos', 3, 5),

('lesen', 1, 'A2', 'multiple_choice', 
'{"question": "Wann ist der Umzug?", "text": "***GESUCHT*** Suche dringend Umzugshilfe für Samstag, den 20. März. Start: 9 Uhr. Bezahlung: 15 Euro/Stunde + Essen. Tel: 030-987654", "options": ["A) Am 15. März", "B) Am 20. März", "C) Am 25. März", "D) Am 30. März"]}',
'{"answer": "B"}',
'Datum in Anzeigen erkennen', 3, 5),

('lesen', 1, 'A2', 'multiple_choice', 
'{"question": "Was verkauft die Person?", "text": "***VERKAUFE*** Fahrrad, 28 Zoll, 3 Jahre alt, guter Zustand. Neuer Preis war 400 Euro, jetzt 150 Euro VB. Abholung in München-Nord.", "options": ["A) Ein Auto", "B] Ein Motorrad", "C) Ein Fahrrad", "D) Ein Roller"]}',
'{"answer": "C"}',
'Verkaufsanzeige verstehen', 3, 5),

('lesen', 1, 'A2', 'multiple_choice', 
'{"question": "Was braucht die Familie?", "text": "***BABYSITTER GESUCHT*** Wir suchen für unsere Kinder (3 und 5 Jahre) eine zuverlässige Betreuung. Zeit: Mo-Fr, 15-18 Uhr. Ort: Hamburg-Eimsbüttel. Tel: 040-111222", "options": ["A) Einen Lehrer", "B] Eine Putzfrau", "C) Eine Kinderbetreuung", "D) Eine Köchin"]}',
'{"answer": "C"}',
'Betreuungsanzeige verstehen', 4, 5),

('lesen', 1, 'A2', 'multiple_choice', 
'{"question": "Wie viel kostet die Wohnung warm?", "text": "***2-ZIMMER WOHNUNG*** 55 qm, Küche, Bad, Balkon. Kaltmiete: 600 Euro. Nebenkosten: 150 Euro. Kaution: 1800 Euro. Ab sofort frei. Tel: 089-333444", "options": ["A) 600 Euro", "B] 750 Euro", "C) 150 Euro", "D) 1800 Euro"]}',
'{"answer": "B"}',
'Warmmiete berechnen: Kaltmiete + Nebenkosten', 4, 5);

-- LESEN TEIL 2: Alltagstexte (5 soru, A2)
INSERT INTO question_pools (module, teil, level, question_type, content, correct_answer, explanation, difficulty, points) VALUES
('lesen', 2, 'A2', 'multiple_choice', 
'{"question": "Was ist in der neuen Regelung?", "text": "Liebe Kunden, ab nächstem Monat ändern sich unsere Öffnungszeiten. Montag bis Freitag sind wir von 8 bis 20 Uhr für Sie da. Samstags schließen wir schon um 18 Uhr. Sonntags bleibt der Laden geschlossen. Ihr Supermarkt-Team", "options": ["A) Länger geöffnet", "B) Kürzer geöffnet", "C) Gleiche Zeiten", "D) Nur online"]}',
'{"answer": "B"}',
'Öffnungszeitenänderung verstehen', 4, 5),

('lesen', 2, 'A2', 'multiple_choice', 
'{"question": "Warum schreibt die Schule?", "text": "Sehr geehrte Eltern, am 15. Juni findet der jährliche Schulfest statt. Beginn ist um 14 Uhr. Jede Klasse bereitet ein Buffet vor. Bitte melden Sie sich beim Klassenlehrer, wenn Sie helfen können. Mit freundlichen Grüßen, Direktor Schmidt", "options": ["A) Für eine Prüfung", "B] Für ein Fest", "C) Für einen Ausflug", "D) Für eine Versammlung"]}',
'{"answer": "B"}',
'Schulnachrichten verstehen', 3, 5),

('lesen', 2, 'A2', 'multiple_choice', 
'{"question": "Was muss man tun?", "text": "Wichtige Mitteilung: Der Personalausweis von Herrn Müller wurde gefunden. Er liegt jetzt im Fundbüro im Rathaus. Abholung nur mit amtlichem Lichtbildausweis möglich. Öffnungszeiten: Mo-Fr 9-17 Uhr.", "options": ["A) Einen neuen Ausweis beantragen", "B] Den Ausweis mit Lichtbild abholen", "C) Das Rathaus anrufen", "D) Eine Anzeige machen"]}',
'{"answer": "B"}',
'Fundbüro-Informationen verstehen', 4, 5),

('lesen', 2, 'A2', 'multiple_choice', 
'{"question": "Was passiert mit den alten Möbeln?", "text": "Liebe Mieter, im Treppenhaus stehen seit Wochen alte Möbel. Diese werden am kommenden Freitag entsorgt, falls niemand sie bis dahin abholt. Bitte räumen Sie rechtzeitig. Die Hausverwaltung", "options": ["A) Sie werden verkauft", "B] Sie werden gespendet", "C) Sie werden weggeworfen", "D) Sie bleiben dort"]}',
'{"answer": "C"}',
'Mieterinformationen verstehen', 4, 5),

('lesen', 2, 'A2', 'multiple_choice', 
'{"question": "Wann ist der Arzt nicht da?", "text": "Praxis Dr. Schmidt - Sprechzeiten: Montag, Dienstag, Donnerstag: 8-12 Uhr und 15-18 Uhr. Mittwoch: nur 8-12 Uhr. Freitag: 8-14 Uhr. Termine nur telefonisch vereinbaren.", "options": ["A) Montag", "B] Mittwoch Nachmittag", "C) Donnerstag", "D) Freitag"]}',
'{"answer": "B"}',
'Sprechzeiten verstehen', 4, 5);

-- LESEN TEIL 3: Arbeitswelttexte (5 soru, A2-B1)
INSERT INTO question_pools (module, teil, level, question_type, content, correct_answer, explanation, difficulty, points) VALUES
('lesen', 3, 'B1', 'multiple_choice', 
'{"question": "Was bedeutet die Meldung?", "text": "Sehr geehrte Arbeitnehmer, laut Arbeitsvertrag gilt ab nächstem Monat eine neue Arbeitszeitregelung. Die wöchentliche Arbeitszeit bleibt bei 38 Stunden. Allerdings werden Überstunden nicht mehr bar ausgezahlt, sondern als Freizeit gutgeschrieben. Ihre Personalabteilung", "options": ["A] Mehr Gehalt für Überstunden", "B) Freizeit statt Geld", "C) Weniger Arbeitszeit", "D) Mehr Urlaubstage"]}',
'{"answer": "B"}',
'Arbeitsvertragliche Änderungen verstehen', 6, 5),

('lesen', 3, 'B1', 'multiple_choice', 
'{"question": "Was muss man tun, wenn man krank ist?", "text": "Krankmeldung: Bei Krankheit müssen Sie am ersten Tag telefonisch Bescheid geben. Am dritten Tag brauchen wir eine Krankschreibung vom Arzt. Ohne Attest kann der Krankentag nicht genehmigt werden. Bitte denken Sie auch daran, sich rechtzeitig wieder abzumelden.", "options": ["A) Nur anrufen", "B) Ein Attest nach einer Woche bringen", "C) Ein Attest bis zum 3. Tag", "D) Gar nichts"]}',
'{"answer": "C"}',
'Krankmeldungsprozess verstehen', 5, 5),

('lesen', 3, 'B1', 'multiple_choice', 
'{"question": "Was bietet das Unternehmen an?", "text": "Wir suchen Verstärkung! Unser mittelständisches Unternehmen bietet: 30 Tage Urlaub, flexible Arbeitszeiten, Weiterbildungsmöglichkeiten und ein Betriebsrestaurant. Das Gehalt ist je nach Qualifikation zwischen 2500 und 3500 Euro brutto.", "options": ["A) 25 Tage Urlaub", "B] 30 Tage Urlaub", "C) 35 Tage Urlaub", "D) Unbegrenzter Urlaub"]}',
'{"answer": "B"}',
'Benefits in Stellenanzeigen', 5, 5),

('lesen', 3, 'B1', 'multiple_choice', 
'{"question": "Warum ist die Fabrik geschlossen?", "text": "Betriebsferien: Unsere Produktion ist vom 15. Juli bis 6. August wegen jährlicher Betriebsferien geschlossen. Während dieser Zeit ist auch die Verwaltung nicht besetzt. Dringende Anfragen bitte per E-Mail. Wir sind ab dem 7. August wieder für Sie da.", "options": ["A) Wegen Umbau", "B] Wegen Urlaub", "C) Wegen Insolvenz", "D) Wegen Feiertagen"]}',
'{"answer": "B"}',
'Betriebsferien verstehen', 5, 5),

('lesen', 3, 'B1', 'multiple_choice', 
'{"question": "Was passiert, wenn man zu spät kommt?", "text": "Pünktlichkeit ist uns wichtig. Bei Verspätung müssen Sie sich bei Ihrem Vorgesetzten melden. Wiederholtes Zu-spät-Kommen führt zu einem Gespräch mit der Personalabteilung. Im Ernstfall kann das auch zur Kündigung führen.", "options": ["A) Nichts", "B) Ein Verwarnung", "C) Ein Gespräch mit der Personalabteilung", "D) Sofortige Kündigung"]}',
'{"answer": "C"}',
'Konsequenzen verstehen', 6, 5);

-- LESEN TEIL 4: Komplexe Alltagstexte (5 soru, B1)
INSERT INTO question_pools (module, teil, level, question_type, content, correct_answer, explanation, difficulty, points) VALUES
('lesen', 4, 'B1', 'multiple_choice', 
'{"question": "Was ist der Hauptgrund für die Erhöhung?", "text": "Liebe Kunden, leider müssen wir unsere Preise ab Januar anpassen. Die gestiegenen Energiekosten und höheren Lieferpreise machen dies notwendig. Wir haben lange gezögert, aber jetzt gibt es keine Alternative. Durchschnittlich werden die Preise um 5% steigen. Wir danken für Ihr Verständnis.", "options": ["A) Mehr Gewinn", "B] Höhere Kosten", "C) Neue Produkte", "D) Mehr Personal"]}',
'{"answer": "B"}',
'Ursachen-Wirkung in Texten', 6, 5),

('lesen', 4, 'B1', 'multiple_choice', 
'{"question": "Was bedeutet das neue Gesetz?", "text": "Ab dem 1. Januar ändert sich das Verpackungsgesetz. Geschäfte müssen nun alle Plastiktüten kostenpflichtig anbieten. Papiertüten bleiben kostenlos. Das Ziel ist, weniger Plastikmüll zu produzieren. Bürger sollen ihre eigenen Taschen mitbringen.", "options": ["A] Alle Tüten sind kostenlos", "B) Nur Plastiktüten kostenlos", "C) Plastiktüten kosten Geld", "D) Papiertüten kosten Geld"]}',
'{"answer": "C"}',
'Gesetzliche Änderungen verstehen', 6, 5),

('lesen', 4, 'B1', 'multiple_choice', 
'{"question": "Was ist das Ziel des Programms?", "text": "Das neue Integrationsprogramm der Stadt zielt darauf ab, Zuwanderern den Einstieg in den Arbeitsmarkt zu erleichtern. Teilnehmer lernen nicht nur die Sprache, sondern auch berufsspezifische Fachbegriffe. Nach dem Kurs vermittelt die Agentur Praktika in Betrieben.", "options": ["A) Sprache lernen", "B] Arbeit finden", "C) Wohnung suchen", "D) Kinder betreuen"]}',
'{"answer": "B"}',
'Ziele in Beschreibungen erkennen', 6, 5),

('lesen', 4, 'B1', 'multiple_choice', 
'{"question": "Warum ist der Kurs wichtig?", "text": "Der Erste-Hilfe-Kurs ist für alle Führerscheinbewerber Pflicht. Ohne Teilnahmebescheinigung kann man keine Prüfung machen. Der Kurs dauert einen Tag und kostet 40 Euro. Inhalte sind: Wie reagiere ich bei Unfällen? Wie leiste ich Erste Hilfe? Wie rufe ich den Notarzt?", "options": ["A) Er ist freiwillig", "B) Er ist billig", "C) Er ist Voraussetzung für den Führerschein", "D) Er dauert lange"]}',
'{"answer": "C"}',
'Voraussetzungen verstehen', 5, 5),

('lesen', 4, 'B1', 'multiple_choice', 
'{"question": "Was passiert mit alten Elektrogeräten?", "text": "Neue EU-Richtlinie: Elektrogeschäfte müssen alte Geräte kostenlos zurücknehmen, wenn Sie neue kaufen. Das gilt für Kühlschränke, Waschmaschinen und kleine Geräte. Die Geräte werden dann recycelt. So soll Elektroschrott reduziert werden.", "options": ["A) Man muss sie behalten", "B] Man kann sie umtauschen", "C) Geschäfte nehmen sie kostenlos zurück", "D) Man muss dafür zahlen"]}',
'{"answer": "C"}',
'Umweltvorschriften verstehen', 6, 5);

-- LESEN TEIL 5: Mehrere Texte zu einem Thema (5 soru, B1)
INSERT INTO question_pools (module, teil, level, question_type, content, correct_answer, explanation, difficulty, points) VALUES
('lesen', 5, 'B1', 'multiple_choice', 
'{"question": "Welche Aussage ist richtig?", "text": "Text A: Das neue Schwimmbad öffnet am 1. Juni. Es hat ein 50-Meter-Becken und eine Rutsche.\n\nText B: Der Eintritt kostet 5 Euro für Erwachsene. Kinder unter 6 Jahren sind kostenlos.\n\nText C: Das Restaurant im Schwimmbad bietet Snacks und Getränke. Warme Küche gibt es nur bis 14 Uhr.", "options": ["A) Kinder zahlen 5 Euro", "B) Es gibt keine Rutsche", "C) Warmes Essen nur vormittags", "D) Das Becken ist 25 Meter"]}',
'{"answer": "C"}',
'Mehrere Texte vergleichen', 7, 5),

('lesen', 5, 'B1', 'multiple_choice', 
'{"question": "Was ist in beiden Angeboten gleich?", "text": "Angebot 1: Deutschkurs A1, Mo-Fr 9-12 Uhr, 300 Euro, inklusive Bücher\n\nAngebot 2: Deutschkurs A1, Mo/Mi/Fr 18-21 Uhr, 300 Euro, Bücher extra", "options": ["A) Die Zeiten", "B] Der Preis", "C) Die Bücher", "D) Die Tage"]}',
'{"answer": "B"}',
'Vergleichende Informationen', 6, 5),

('lesen', 5, 'B1', 'multiple_choice', 
'{"question": "Welche Information fehlt?", "text": "Wohnungsanzeige 1: 3-Zimmer, 75 qm, 800 Euro warm, Balkon, ab sofort\n\nWohnungsanzeige 2: 3-Zimmer, 70 qm, 750 Euro warm, Kein Balkon, ab 01.04., Tiefgarage 50 Euro\n\nWohnungsanzeige 3: 2-Zimmer, 60 qm, 700 Euro warm, Balkon, ab sofort", "options": ["A) Anzeige 1: Keine Garage erwähnt", "B] Anzeige 2: Mehr Informationen", "C) Anzeige 3: Zu teuer", "D) Alle haben gleiche Info"]}',
'{"answer": "A"}',
'Fehlende Informationen erkennen', 7, 5),

('lesen', 5, 'B1', 'multiple_choice', 
'{"question": "Welcher Arzt passt am besten?", "text": "Patient braucht: Abends Termin, spricht Türkisch, Frauenarzt\n\nDr. A: Allgemeinarzt, Mo-Fr 8-17 Uhr, spricht Englisch\n\nDr. B: Frauenarzt, Mo-Fr 9-18 Uhr und Mi bis 20 Uhr, spricht Türkisch\n\nDr. C: Frauenarzt, nur Vormittags, spricht Deutsch", "options": ["A) Dr. A", "B] Dr. B", "C) Dr. C", "D) Keiner"]}',
'{"answer": "B"}',
'Passende Information auswählen', 7, 5),

('lesen', 5, 'B1', 'multiple_choice', 
'{"question": "Was ist das gemeinsame Thema?", "text": "Text 1: Die Stadt baut neue Fahrradwege.\n\nText 2: Busfahren wird mit der neuen App günstiger.\n\nText 3: Carsharing-Stationen werden erweitert.", "options": ["A) Umweltschutz", "B] Mobilität/Verkehr", "C) Technologie", "D) Geld sparen"]}',
'{"answer": "B"}',
'Gemeinsames Thema erkennen', 6, 5);

-- ============================================
-- SCHREIBEN (2 Teile)
-- ============================================

-- SCHREIBEN TEIL 1: Formular ausfüllen (~30 Wörter, A2)
INSERT INTO question_pools (module, teil, level, question_type, content, correct_answer, explanation, difficulty, points) VALUES
('schreiben', 1, 'A2', 'text_input', 
'{"question": "Teil 1: Formular ausfüllen (circa 30 Wörter)", "instruction": "Sie möchten sich für einen Sprachkurs anmelden. Füllen Sie das Formular aus.", "prompt": "Schreiben Sie: Warum lernen Sie Deutsch? Wann möchten Sie den Kurs besuchen?", "word_count": 30}',
'{"criteria": ["Aufgabenerfüllung", "Sprachrichtigkeit", "Textaufbau"]}',
'Bewertungskriterien: 1) Alle gefragten Punkte enthalten 2) Verständlicher Text 3) Grundlegende Grammatik korrekt', 5, 10),

('schreiben', 1, 'A2', 'text_input', 
'{"question": "Teil 1: Formular ausfüllen (circa 30 Wörter)", "instruction": "Sie haben eine Wohnung gesehen und möchten sich bewerben.", "prompt": "Schreiben Sie: Warum möchten Sie diese Wohnung? Wie viele Personen wohnen bei Ihnen?", "word_count": 30}',
'{"criteria": ["Aufgabenerfüllung", "Sprachrichtigkeit", "Textaufbau"]}',
'Wohnungsbewerbung: Formalitäten, Familienstand, Gründe', 5, 10),

('schreiben', 1, 'A2', 'text_input', 
'{"question": "Teil 1: Formular ausfüllen (circa 30 Wörter)", "instruction": "Sie möchten Ihrem Kind einen Platz in der Kita suchen.", "prompt": "Schreiben Sie: Wie alt ist Ihr Kind? Ab wann brauchen Sie den Platz?", "word_count": 30}',
'{"criteria": ["Aufgabenerfüllung", "Sprachrichtigkeit", "Textaufbau"]}',
'Kitaplatz-Anfrage: Altersangabe, Zeitpunkt, Bedarf', 5, 10);

-- SCHREIBEN TEIL 2: Brief schreiben (~80 Wörter, B1)
INSERT INTO question_pools (module, teil, level, question_type, content, correct_answer, explanation, difficulty, points) VALUES
('schreiben', 2, 'B1', 'text_input', 
'{"question": "Teil 2: Brief schreiben (circa 80 Wörter)", "instruction": "Schreiben Sie eine E-Mail an Ihren Freund/Ihre Freundin. Sie haben eine neue Wohnung.", "prompt": "Beschreiben Sie: Wie sieht die Wohnung aus? Wo liegt sie? Wann können Sie sich treffen?", "word_count": 80, "type": "informal"}',
'{"criteria": ["Aufgabenerfüllung: 5 Punkte", "Textaufbau: 5 Punkte", "Sprachrichtigkeit: 5 Punkte", "Sprachumfang: 5 Punkte"], "total": 20}',
'Bewertungskriterien B1: Alle 4 Punkte erklärt - Aufgabe (alle 3 Aspekte?), Struktur (Anrede/Schluss), Sprache (Grammatik/Wortschatz), Variation', 7, 10),

('schreiben', 2, 'B1', 'text_input', 
'{"question": "Teil 2: Brief schreiben (circa 80 Wörter)", "instruction": "Schreiben Sie eine formelle E-Mail an das Einwohnermeldeamt. Sie haben umgezogen.", "prompt": "Beschreiben Sie: Wann sind Sie umgezogen? Was ist Ihre neue Adresse? Wann können Sie vorbeikommen?", "word_count": 80, "type": "formal"}',
'{"criteria": ["Aufgabenerfüllung: 5 Punkte", "Textaufbau: 5 Punkte", "Sprachrichtigkeit: 5 Punkte", "Sprachumfang: 5 Punkte"], "total": 20}',
'Formelle E-Mail: Anrede (Sehr geehrte...), Höflichkeitsformen, Schlussformel', 7, 10),

('schreiben', 2, 'B1', 'text_input', 
'{"question": "Teil 2: Brief schreiben (circa 80 Wörter)", "instruction": "Beschweren Sie sich bei einem Online-Shop. Sie haben ein defektes Produkt erhalten.", "prompt": "Beschreiben Sie: Was haben Sie bestellt? Was ist das Problem? Was möchten Sie? (Ersatz/Geld zurück)", "word_count": 80, "type": "formal"}',
'{"criteria": ["Aufgabenerfüllung: 5 Punkte", "Textaufbau: 5 Punkte", "Sprachrichtigkeit: 5 Punkte", "Sprachumfang: 5 Punkte"], "total": 20}',
'Beschwerde: Sachlich bleiben, Problem klar beschreiben, Lösung vorschlagen', 8, 10);

-- ============================================
-- SPRECHEN (3 Teile)
-- ============================================

-- SPRECHEN TEIL 1: Sich vorstellen (A2)
INSERT INTO question_pools (module, teil, level, question_type, content, correct_answer, explanation, difficulty, points) VALUES
('sprechen', 1, 'A2', 'text_input', 
'{"question": "Teil 1: Sich vorstellen (1-2 Minuten)", "instruction": "Stellen Sie sich vor.", "topics": ["Name und Alter", "Herkunft", "Familie", "Beruf/Ausbildung", "Wohnort", "Hobbys"]}',
'{"duration": "1-2 Minuten", "expected_content": ["Name", "Alter", "Herkunft", "Wohnort", "Beruf/Familie"]}',
'Kriterien: Flüssigkeit, Aussprache, Wortschatz, Inhalt', 4, 5),

('sprechen', 1, 'A2', 'text_input', 
'{"question": "Teil 1: Sich vorstellen (1-2 Minuten)", "instruction": "Erzählen Sie etwas über Ihren Alltag.", "topics": ["Tagesablauf", "Arbeit/Schule", "Freizeit", "Wochenende"]}',
'{"duration": "1-2 Minuten", "expected_content": ["Tageszeiten", "Aktivitäten", "Zeitangaben"]}',
'Präsens verwenden, Zeitangaben machen', 5, 5);

-- SPRECHEN TEIL 2: Ein Thema erzählen (A2-B1)
INSERT INTO question_pools (module, teil, level, question_type, content, correct_answer, explanation, difficulty, points) VALUES
('sprechen', 2, 'B1', 'text_input', 
'{"question": "Teil 2: Ein Thema erzählen (2-3 Minuten)", "instruction": "Bereiten Sie sich vor. Erzählen Sie dann über eines dieser Themen:", "topics": ["Mein letzter Urlaub", "Meine Heimatstadt", "Eine wichtige Entscheidung", "Mein Traumberuf"]}',
'{"duration": "2-3 Minuten", "expected_content": ["Einleitung", "Hauptteil", "Schluss"], "grammar": ["Vergangenheit", "Konjunktiv", "Modalverben"]}',
'Vergangenheitsformen nutzen, Gefühle ausdrücken, Begründungen geben', 6, 5),

('sprechen', 2, 'B1', 'text_input', 
'{"question": "Teil 2: Ein Thema erzählen (2-3 Minuten)", "instruction": "Beschreiben Sie ein Bild und erzählen Sie eine Geschichte dazu.", "topics": ["Bild beschreiben", "Was passiert?", "Was passierte vorher?", "Was wird als Nächstes passieren?"]}',
'{"duration": "2-3 Minuten", "expected_content": ["Beschreibung", "Interpretation", "Spekulation"]}',
'Bildbeschreibung: Präsens für Bild, Präteritum für Geschichte, Vermutungen', 7, 5);

-- SPRECHEN TEIL 3: Diskussion/Meinung äußern (B1)
INSERT INTO question_pools (module, teil, level, question_type, content, correct_answer, explanation, difficulty, points) VALUES
('sprechen', 3, 'B1', 'text_input', 
'{"question": "Teil 3: Diskussion (3-4 Minuten)", "instruction": "Diskutieren Sie mit dem Prüfer über folgende Aussage:", "statement": "In Deutschland soll es für alle kostenloses öffentliches Verkehr geben.", "aspects": ["Vorteile", "Nachteile", "Ihre Meinung", "Alternativen"]}',
'{"duration": "3-4 Minuten", "expected_content": ["Eigene Meinung", "Begründung", "Gegenargumente", "Beispiele"]}',
'Argumentieren: Meinung äußern, begründen, Konjunktiv II für hypothetische Situationen', 7, 5),

('sprechen', 3, 'B1', 'text_input', 
'{"question": "Teil 3: Diskussion (3-4 Minuten)", "instruction": "Was halten Sie von folgender Aussage? Begründen Sie Ihre Meinung.", "statement": "Homeschooling sollte in Deutschland erlaubt sein.", "aspects": ["Vorteile", "Nachteile", "Persönliche Erfahrung", "Schlussfolgerung"]}',
'{"duration": "3-4 Minuten", "expected_content": ["Eigene Meinung", "Begründung", "Gegenargumente", "Beispiele"]}',
'Konnektoren: einerseits/andererseits, zwar/aber, deshalb/trotzdem', 8, 5);

-- Schreiben Kriterien Tablosu için açıklama
-- Kriterien:
-- 1. Aufgabenerfüllung (0-5 Punkte): Sind alle gefragten Punkte enthalten?
-- 2. Textaufbau (0-5 Punkte): Einleitung, Hauptteil, Schluss logisch verbunden?
-- 3. Sprachrichtigkeit (0-5 Punkte): Grammatik, Rechtschreibung, Zeichensetzung
-- 4. Sprachumfang (0-5 Punkte): Wortschatz, Satzvielfalt
-- Toplam: 20 puan
