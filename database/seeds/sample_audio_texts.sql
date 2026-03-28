-- Realistic DTZ listening samples with natural speech patterns

-- TEIL 1: Telefonansagen (Phone announcements)
INSERT INTO audio_files (text_content, scenario, voice_id, duration_seconds) VALUES
('Guten Tag, hier spricht Michael Weber vom Kundenservice der Stadtwerke Neustadt. Unsere Beratungshotline ist erreichbar von montags bis freitag, jeweils von acht Uhr bis zwölf Uhr, und am Donnerstag zusätzlich von vierzehn bis achtzehn Uhr. Für Terminvereinbarungen nutzen Sie bitte unsere Online-Plattform oder hinterlassen Sie eine Nachricht auf dem Anrufbeantworter. Wir rufen Sie zurück.', 'buero', 'de-DE-KillianNeural', 25),

('Guten Tag, Sie haben die Praxis Dr. Schmidt erreicht. Aufgrund der aktuellen Situation finden Sprechstunden nur nach telefonischer Terminvereinbarung statt. Bitte rufen Sie uns zu folgenden Zeiten an: Montag, Dienstag und Donnerstag von neun bis zwölf Uhr, sowie Mittwoch von vierzehn bis sechzehn Uhr. In akuten Notfällen wenden Sie sich bitte an den ärztlichen Bereitschaftsdienst unter der Nummer eins-eins-sechs-eins-eins-sieben.', 'arzt', 'de-DE-KatjaNeural', 28),

('Herzlich willkommen in der Information der Deutschen Bahn am Hauptbahnhof München. Aufgrund von Bauarbeiten kommt es auf der Strecke S-Bahn acht zu Verspätungen von bis zu fünfzehn Minuten. Fahrgäste nach Flughafen werden gebeten, die S-Bahn eins zu nutzen. Wir bitten um Entschuldigung für die Unannehmlichkeiten.', 'bahn', 'de-DE-ConradNeural', 22);

-- TEIL 2: Alltagsgespräche (Daily conversations)
INSERT INTO audio_files (text_content, scenario, voice_id, duration_seconds) VALUES
('Entschuldigung, könnten Sie mir bitte sagen, wann die nächste Straßenbahn in Richtung Hauptplatz fährt? Ich habe leider gerade mein Handy vergessen und kann nicht in die App schauen. Ah, danke schön! Also in fünf Minuten an Haltestelle drei. Perfekt, dann beeile ich mich mal.', 'arbeit', 'de-DE-AmalaNeural', 18),

('Hallo Sarah, wie war denn dein Wochenende? Hast du was Schönes gemacht? Ach, das klingt gut! Ich war übrigens mit meinen Eltern wandern. Das Wetter war herrlich, aber jetzt habe ich richtig Muskelkater. Kannst du mir vielleicht kurz helfen? Ich verstehe diese neue Software noch nicht so ganz.', 'arbeit', 'de-DE-LouisaNeural', 20);

-- TEIL 3: Arbeitsgespräche (Work conversations)
INSERT INTO audio_files (text_content, scenario, voice_id, duration_seconds) VALUES
('Guten Morgen Frau Müller, ich habe eine dringende Frage bezüglich des Projekts. Die Deadline ist ja schon nächste Woche, und wir haben noch nicht alle Unterlagen zusammen. Könnten wir uns kurz abstimmen? Vielleicht um elf Uhr im Besprechungsraum zwei? Das wäre super, danke!', 'arbeit', 'de-DE-KillianNeural', 19),

('Also, erstmal vielen Dank für das Gespräch. Ich hatte noch eine Frage zu meinem Arbeitsvertrag. Steht darin etwas zu Überstunden, und wie werden diese vergütet? Und noch etwas: Ab wann habe ich Anspruch auf Urlaub? Verstehe, also nach sechs Monaten Probezeit. Das ist wichtig für mich zu wissen.', 'arbeit', 'de-DE-SeraphinaMultilingualNeural', 21);

-- TEIL 4: Informationen (News/Information)
INSERT INTO audio_files (text_content, scenario, voice_id, duration_seconds) VALUES
('Liebe Bürgerinnen und Bürger, das Integrationsamt der Stadt informiert: Ab dem ersten Januar nächsten Jahres ändern sich die Öffnungszeiten der Bürgerberatung. Die neue Sprechzeit ist montags und mittwochs von neun bis zwölf Uhr. Anträge auf Niederlassungserlaubnis sind weiterhin nur mit Termin möglich. Termine können online oder telefonisch vereinbart werden.', 'buero', 'de-DE-ConradNeural', 24),

('Achtung, wichtige Mitteilung für alle Mieter des Wohnblocks Sonnenallee. Aufgrund von Sanierungsarbeiten wird das Warmwasser vom fünfzehnten bis zwanzigsten dieses Monats abgestellt. Die Kaltwasserversorgung bleibt davon unberührt. Wir bitten um Verständnis für diese notwendigen Maßnahmen. Bei Fragen wenden Sie sich bitte an die Hausverwaltung.', 'buero', 'de-DE-KatjaNeural', 26);
