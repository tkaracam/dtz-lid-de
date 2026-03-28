-- Hören (Listening) Questions for DTZ
-- Original content, no copyright violation

-- Hören Teil 1: Anrufe / Phone calls (5 questions, 1 play)
INSERT INTO question_pools (module, teil, level, question_type, content, correct_answer, explanation, difficulty, points, is_active) VALUES
('hoeren', 1, 'A2', 'multiple_choice', '{
  "text": "Telefonat: Frau Müller ruft beim Arzt an.",
  "question": "Wann hat Frau Müller den Termin?",
  "options": [
    "A: Heute um 15 Uhr",
    "B: Morgen um 10 Uhr",
    "C: Nächste Woche",
    "D: Am Freitag"
  ],
  "scenario": "phone",
  "audio_text": "Guten Tag, Praxis Dr. Schmidt. Ja, guten Tag, hier ist Frau Müller. Ich hatte einen Termin für heute um 15 Uhr, aber ich kann leider nicht kommen. Kann ich einen neuen Termin bekommen? Ja, kein Problem. Wie wäre es morgen um 10 Uhr? Das passt mir gut. Vielen Dank. Auf Wiederhören."
}', '{"answer": "B", "variants": ["B", "morgen 10"]}', 
'Die Frau sagt: "Wie wäre es morgen um 10 Uhr?" und sie antwortet: "Das passt mir gut."', 2, 5, 1);

INSERT INTO question_pools (module, teil, level, question_type, content, correct_answer, explanation, difficulty, points, is_active) VALUES
('hoeren', 1, 'A2', 'multiple_choice', '{
  "text": "Telefonat: Herr Schmidt ruft bei der Firma an.",
  "question": "Was möchte Herr Schmidt wissen?",
  "options": [
    "A: Die Öffnungszeiten",
    "B: Den Preis eines Produkts",
    "C: Ob seine Bestellung angekommen ist",
    "D: Die Adresse der Firma"
  ],
  "scenario": "phone",
  "audio_text": "Guten Tag, hier ist Schmidt. Ich habe vor zwei Tagen bei Ihnen online etwas bestellt. Können Sie mir sagen, wann die Lieferung ankommt? Ja, guten Tag Herr Schmidt. Ihre Bestellung ist gestern verschickt worden. Sie sollte morgen bei Ihnen sein. Vielen Dank für die Information."
}', '{"answer": "C", "variants": ["C", "Bestellung"]}',
'Herr Schmidt fragt: "Können Sie mir sagen, wann die Lieferung ankommt?"', 2, 5, 1);

-- Hören Teil 2: Ansagen / Announcements (5 questions, 2 plays)
INSERT INTO question_pools (module, teil, level, question_type, content, correct_answer, explanation, difficulty, points, is_active) VALUES
('hoeren', 2, 'A2', 'multiple_choice', '{
  "text": "Durchsage im Supermarkt.",
  "question": "Was kostet die Milch heute?",
  "options": [
    "A: 0,99 €",
    "B: 1,29 €",
    "C: 1,49 €",
    "D: 1,99 €"
  ],
  "scenario": "announcement",
  "audio_text": "Achtung, liebe Kunden! Heute haben wir ein besonderes Angebot für Sie. Frische Vollmilch, 1 Liter, statt 1,49 Euro nur 0,99 Euro. Das Angebot gilt nur heute und nur solange der Vorrat reicht. Vielen Dank für Ihre Aufmerksamkeit."
}', '{"answer": "A", "variants": ["A", "0,99"]}',
'Die Durchsage sagt: "statt 1,49 Euro nur 0,99 Euro"', 3, 5, 1);

INSERT INTO question_pools (module, teil, level, question_type, content, correct_answer, explanation, difficulty, points, is_active) VALUES
('hoeren', 2, 'B1', 'multiple_choice', '{
  "text": "Ansage am Bahnhof.",
  "question": "Was ist mit dem Zug ICE 512 passiert?",
  "options": [
    "A: Er fährt pünktlich",
    "B: Er hat 20 Minuten Verspätung",
    "C: Er fällt aus",
    "D: Er fährt von einem anderen Gleis"
  ],
  "scenario": "announcement",
  "audio_text": "Achtung, Fahrgäste! Der ICE 512 von Berlin nach München hat heute etwa 20 Minuten Verspätung. Grund dafür sind Bauarbeiten auf der Strecke. Der Zug wird voraussichtlich um 14:45 Uhr von Gleis 5 abfahren. Wir bitten um Entschuldigung."
}', '{"answer": "B", "variants": ["B", "20 Minuten"]}',
'Die Ansage sagt: "hat heute etwa 20 Minuten Verspätung"', 3, 5, 1);

-- Hören Teil 3: Gespräche / Conversations (5 questions, 1 play)
INSERT INTO question_pools (module, teil, level, 'A2', 'question_type', content, correct_answer, explanation, difficulty, points, is_active) VALUES
('hoeren', 3, 'A2', 'multiple_choice', '{
  "text": "Zwei Freunde unterhalten sich.",
  "question": "Wohin gehen die Freunde am Samstag?",
  "options": [
    "A: Ins Kino",
    "B: In ein Restaurant",
    "C: In den Park",
    "D: Zum Sport"
  ],
  "scenario": "conversation",
  "audio_text": "Hallo Maria! Hast du am Samstag Zeit? Ja, warum? Lass uns ins Kino gehen! Der neue Film mit Tom Hanks läuft. Das ist eine gute Idee. Um wie viel Uhr? Der Film beginnt um 19 Uhr. Perfekt, bis dann!"
}', '{"answer": "A", "variants": ["A", "Kino"]}',
'Sie sagen: "Lass uns ins Kino gehen!"', 2, 5, 1);

INSERT INTO question_pools (module, teil, level, question_type, content, correct_answer, explanation, difficulty, points, is_active) VALUES
('hoeren', 3, 'B1', 'multiple_choice', '{
  "text": "Gespräch zwischen zwei Kollegen.",
  "question": "Warum kann Anna nicht zum Meeting kommen?",
  "options": [
    "A: Sie ist krank",
    "B: Sie hat einen Arzttermin",
    "C: Sie muss zum Kinderarzt mit ihrem Sohn",
    "D: Sie ist im Urlaub"
  ],
  "scenario": "conversation",
  "audio_text": "Entschuldigung, kannst du dem Chef sagen, dass ich heute Nachmittag nicht zum Meeting kommen kann? Klar, aber warum nicht? Mein Sohn ist krank. Ich muss mit ihm zum Kinderarzt. Oh, das tut mir leid! Kein Problem, ich sage es ihm. Danke!"
}', '{"answer": "C", "variants": ["C", "Kinderarzt"]}',
'Sie sagt: "Mein Sohn ist krank. Ich muss mit ihm zum Kinderarzt."', 3, 5, 1);

-- Hören Teil 4: Interview / Radio (5 questions, 1 play, 3 min prep)
INSERT INTO question_pools (module, teil, level, 'A2', 'question_type', content, correct_answer, explanation, difficulty, points, is_active) VALUES
('hoeren', 4, 'B1', 'multiple_choice', '{
  "text": "Radiobeitrag über Arbeiten von Zuhause.",
  "question": "Was findet Frau Weber am schwierigsten beim Homeoffice?",
  "options": [
    "A: Die Technik",
    "B: Die Unterscheidung zwischen Arbeit und Privatleben",
    "C: Die Kommunikation mit Kollegen",
    "D: Die Zeitplanung"
  ],
  "scenario": "interview",
  "audio_text": "Frau Weber, Sie arbeiten seit zwei Jahren von zu Hause. Was gefällt Ihnen daran? Ich mag die Flexibilität. Ich kann meine Zeit selbst einteilen. Aber es gibt auch Nachteile. Zum Beispiel? Das Schlimmste ist die Grenze zwischen Arbeit und Privatleben. Manchmal sitze ich bis spät abends am Computer, weil ich die Arbeit nicht abschalten kann. Haben Sie einen Tipp? Ja, unbedingt feste Arbeitszeiten einhalten und den Computer danach ausschalten!"
}', '{"answer": "B", "variants": ["B", "Grenze", "Privatleben"]}',
'Sie sagt: "Das Schlimmste ist die Grenze zwischen Arbeit und Privatleben."', 4, 5, 1);

INSERT INTO question_pools (module, teil, level, question_type, content, correct_answer, explanation, difficulty, points, is_active) VALUES
('hoeren', 4, 'B1', 'multiple_choice', '{
  "text": "Interview mit einem Nachbarschaftshelfer.",
  "question": "Was macht Herr Müller in seiner Freizeit für die Nachbarschaft?",
  "options": [
    "A: Er gibt Deutschkurse",
    "B: Er hilft alten Menschen beim Einkaufen",
    "C: Er organisiert Sportaktivitäten",
    "D: Er repariert Fahrräder"
  ],
  "scenario": "interview",
  "audio_text": "Herr Müller, Sie engagieren sich stark in Ihrer Nachbarschaft. Was genau machen Sie? Ich helfe vor allem älteren Menschen. Zum Beispiel fahre ich mit ihnen zum Arzt oder helfe beim schweren Einkaufen. Warum machen Sie das? Ich bin selbst Rentner und habe Zeit. Außerdem tut es mir gut, anderen zu helfen. Es macht mich glücklich, wenn ich sehe, dass ich jemandem helfen kann."
}', '{"answer": "B", "variants": ["B", "alten Menschen", "Einkaufen"]}',
'Er sagt: "Ich helfe vor allem älteren Menschen... helfe beim schweren Einkaufen."', 3, 5, 1);
