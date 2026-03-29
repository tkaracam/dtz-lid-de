<?php
declare(strict_types=1);

namespace DTZ\Models;

use DTZ\Database\Database;

class Question
{
    private Database $db;
    
    public function __construct()
    {
        $this->db = Database::getInstance();
    }
    
    /**
     * Get random question for user
     */
    public function getRandom(int $userId, string $module, string $level): ?array
    {
        // Keep a healthy pool for each module so users don't run out quickly.
        $this->ensureModulePool($module, 140);

        $levels = $this->getLevelFallbackOrder($level);

        // First, try to get questions user hasn't seen recently
        $question = null;
        foreach ($levels as $lvl) {
            $question = $this->db->selectOne("
                SELECT q.* FROM question_pools q
                WHERE q.module = ?
                AND q.level = ?
                AND q.is_active = 1
                AND q.id NOT IN (
                    SELECT question_id FROM user_answers
                    WHERE user_id = ?
                    AND created_at > datetime('now', '-7 days')
                )
                ORDER BY RANDOM()
                LIMIT 1
            ", [$module, $lvl, $userId]);
            if ($question) {
                break;
            }
        }
        
        // If no unseen questions, get based on spaced repetition
        if (!$question) {
            foreach ($levels as $lvl) {
                $question = $this->getSpacedRepetitionQuestion($userId, $module, $lvl);
                if ($question) {
                    break;
                }
            }
        }
        
        // Fallback: any random question
        if (!$question) {
            foreach ($levels as $lvl) {
                $question = $this->db->selectOne("
                    SELECT * FROM question_pools
                    WHERE module = ?
                    AND level = ?
                    AND is_active = 1
                    ORDER BY RANDOM()
                    LIMIT 1
                ", [$module, $lvl]);
                if ($question) {
                    break;
                }
            }
        }

        // Last resort: any active question in the requested module.
        if (!$question) {
            $question = $this->db->selectOne("
                SELECT * FROM question_pools
                WHERE module = ?
                AND is_active = 1
                ORDER BY RANDOM()
                LIMIT 1
            ", [$module]);
        }

        return $question;
    }
    
    /**
     * Get question based on spaced repetition
     */
    private function getSpacedRepetitionQuestion(int $userId, string $module, string $level): ?array
    {
        return $this->db->selectOne("
            SELECT q.* FROM question_pools q
            JOIN user_question_history h ON q.id = h.question_id
            WHERE q.module = ?
            AND q.level = ?
            AND q.is_active = 1
            AND h.user_id = ?
            AND h.times_correct < 3
            AND (h.next_review_at IS NULL OR h.next_review_at <= datetime('now'))
            ORDER BY h.times_seen ASC, RANDOM()
            LIMIT 1
        ", [$module, $level, $userId]);
    }
    
    /**
     * Get weak topics for user
     */
    public function getWeakTopics(int $userId, string $module): array
    {
        return $this->db->select("
            SELECT 
                q.teil,
                COUNT(*) as total_attempts,
                SUM(CASE WHEN ua.is_correct THEN 1 ELSE 0 END) as correct_count,
                ROUND(
                    100.0 * SUM(CASE WHEN ua.is_correct THEN 1 ELSE 0 END) / COUNT(*), 
                    2
                ) as accuracy_rate
            FROM user_answers ua
            JOIN question_pools q ON ua.question_id = q.id
            WHERE ua.user_id = ?
            AND q.module = ?
            AND ua.created_at > datetime('now', '-30 days')
            GROUP BY q.teil
            HAVING accuracy_rate < 70
            ORDER BY accuracy_rate ASC
        ", [$userId, $module]);
    }
    
    /**
     * Get question by ID
     */
    public function findById(int $id): ?array
    {
        return $this->db->selectOne(
            "SELECT * FROM question_pools WHERE id = ? LIMIT 1",
            [$id]
        );
    }
    
    /**
     * Get questions by module and teil
     */
    public function getByModule(string $module, ?string $level = null, int $limit = 50): array
    {
        $sql = "SELECT * FROM question_pools WHERE module = ? AND is_active = 1";
        $params = [$module];
        
        if ($level) {
            $sql .= " AND level = ?";
            $params[] = $level;
        }
        
        $sql .= " ORDER BY RANDOM() LIMIT ?";
        $params[] = $limit;
        
        return $this->db->select($sql, $params);
    }
    
    /**
     * Count total questions
     */
    public function count(?string $module = null, ?string $level = null): int
    {
        $sql = "SELECT COUNT(*) as count FROM question_pools WHERE is_active = 1";
        $params = [];
        
        if ($module) {
            $sql .= " AND module = ?";
            $params[] = $module;
        }
        
        if ($level) {
            $sql .= " AND level = ?";
            $params[] = $level;
        }
        
        $result = $this->db->selectOne($sql, $params);
        return (int) ($result['count'] ?? 0);
    }
    
    /**
     * Update question statistics
     */
    public function updateStats(int $questionId, bool $isCorrect, int $timeSpent): void
    {
        $this->db->execute("
            UPDATE question_pools 
            SET 
                usage_count = usage_count + 1,
                correct_rate = (
                    SELECT ROUND(100.0 * SUM(CASE WHEN is_correct THEN 1 ELSE 0 END) / COUNT(*), 2)
                    FROM user_answers 
                    WHERE question_id = ?
                ),
                avg_time_seconds = (
                    SELECT AVG(time_spent_seconds)
                    FROM user_answers 
                    WHERE question_id = ?
                ),
                last_used_at = datetime('now')
            WHERE id = ?
        ", [$questionId, $questionId, $questionId]);
    }

    public function ensureModulePool(string $module, int $minCount = 120): array
    {
        $valid = ['lesen', 'hoeren', 'schreiben', 'sprechen', 'lid'];
        if (!in_array($module, $valid, true)) {
            return ['module' => $module, 'inserted' => 0, 'total' => 0];
        }

        $current = (int)($this->db->selectOne(
            "SELECT COUNT(*) AS c FROM question_pools WHERE module = ? AND is_active = 1",
            [$module]
        )['c'] ?? 0);

        if ($current >= $minCount) {
            return ['module' => $module, 'inserted' => 0, 'total' => $current];
        }

        $rows = $this->buildGeneratedRows($module);
        $need = $minCount - $current;
        $inserted = 0;

        foreach ($rows as $row) {
            if ($inserted >= $need) {
                break;
            }
            $this->db->insert('question_pools', $row);
            $inserted++;
        }

        $total = (int)($this->db->selectOne(
            "SELECT COUNT(*) AS c FROM question_pools WHERE module = ? AND is_active = 1",
            [$module]
        )['c'] ?? $current + $inserted);

        return ['module' => $module, 'inserted' => $inserted, 'total' => $total];
    }

    private function getLevelFallbackOrder(string $level): array
    {
        $normalized = strtoupper(trim($level));
        return match ($normalized) {
            'A1' => ['A1', 'A2', 'B1', 'B2'],
            'A2' => ['A2', 'B1', 'A1', 'B2'],
            'B1' => ['B1', 'A2', 'B2', 'A1'],
            'B2' => ['B2', 'B1', 'A2', 'A1'],
            default => ['A2', 'B1', 'A1', 'B2'],
        };
    }

    private function buildGeneratedRows(string $module): array
    {
        return match ($module) {
            'lesen' => $this->buildLesenRows(),
            'hoeren' => $this->buildHoerenRows(),
            'schreiben' => $this->buildSchreibenRows(),
            'sprechen' => $this->buildSprechenRows(),
            'lid' => $this->buildLidRows(),
            default => [],
        };
    }

    private function buildLesenRows(): array
    {
        $contexts = [
            ['title' => 'Bürgeramt', 'text' => 'Personalausweis nur mit Termin. Bitte bringen Sie ein biometrisches Foto mit.', 'q' => 'Was brauchen Sie fuer den Antrag?', 'ok' => 'Ein biometrisches Foto', 'wrong' => ['Einen Reisekoffer', 'Ein Fahrrad', 'Ein Ticket'], 'teil' => 1],
            ['title' => 'Supermarkt', 'text' => 'Heute: Vollkornbrot 1,20 Euro. Angebot nur bis 18 Uhr gueltig.', 'q' => 'Bis wann gilt das Angebot?', 'ok' => 'Bis 18 Uhr', 'wrong' => ['Bis 12 Uhr', 'Bis morgen', 'Die ganze Woche'], 'teil' => 1],
            ['title' => 'Jobcenter', 'text' => 'Unterlagen bitte spaetestens bis Freitag einreichen. Ohne Unterlagen kann der Termin nicht bearbeitet werden.', 'q' => 'Was passiert ohne Unterlagen?', 'ok' => 'Der Termin wird nicht bearbeitet', 'wrong' => ['Sie bekommen sofort Geld', 'Der Termin dauert kuerzer', 'Sie muessen nichts tun'], 'teil' => 2],
            ['title' => 'Hausverwaltung', 'text' => 'Wegen Wartung faellt das Warmwasser am Dienstag von 8 bis 14 Uhr aus.', 'q' => 'Wann gibt es kein Warmwasser?', 'ok' => 'Dienstag von 8 bis 14 Uhr', 'wrong' => ['Montag ab 14 Uhr', 'Dienstag nur 30 Minuten', 'Jeden Tag abends'], 'teil' => 2],
            ['title' => 'Arbeit', 'text' => 'Die Teamsitzung beginnt diese Woche nicht um 9:00, sondern um 8:30 Uhr in Raum 4.', 'q' => 'Was hat sich geaendert?', 'ok' => 'Die Uhrzeit der Sitzung', 'wrong' => ['Der Wochentag', 'Die Firma', 'Der Abteilungsname'], 'teil' => 3],
            ['title' => 'Kurszentrum', 'text' => 'Hausaufgaben muessen bis Sonntag 20 Uhr im Portal hochgeladen werden.', 'q' => 'Bis wann muessen die Hausaufgaben hochgeladen sein?', 'ok' => 'Bis Sonntag 20 Uhr', 'wrong' => ['Bis Samstag 10 Uhr', 'Bis Montagmittag', 'Es gibt keine Frist'], 'teil' => 3],
            ['title' => 'Bank', 'text' => 'Die Filiale hat donnerstags bis 18:30 Uhr geoeffnet. Sonst bis 16:00 Uhr.', 'q' => 'An welchem Tag ist laenger geoeffnet?', 'ok' => 'Am Donnerstag', 'wrong' => ['Am Montag', 'Am Dienstag', 'Am Freitag'], 'teil' => 4],
            ['title' => 'Schule', 'text' => 'Der Elternabend findet am 10. April um 19 Uhr in Raum 2 statt.', 'q' => 'Wo findet der Elternabend statt?', 'ok' => 'In Raum 2', 'wrong' => ['Im Sportplatz', 'Online per App', 'In der Bibliothek'], 'teil' => 4],
            ['title' => 'Kundenservice', 'text' => 'Ruecksendungen sind innerhalb von 14 Tagen moeglich. Bitte legen Sie den Kassenbon bei.', 'q' => 'Was brauchen Sie fuer die Ruecksendung?', 'ok' => 'Den Kassenbon', 'wrong' => ['Einen Reisepass', 'Eine Mitgliedskarte', 'Ein Foto vom Laden'], 'teil' => 5],
            ['title' => 'Stadtbibliothek', 'text' => 'Neue Ausweise koennen montags bis freitags von 10 bis 16 Uhr erstellt werden.', 'q' => 'Wann koennen neue Ausweise erstellt werden?', 'ok' => 'Montag bis Freitag 10-16 Uhr', 'wrong' => ['Nur samstags', 'Jeden Abend', 'Nur online'], 'teil' => 5],
        ];

        return $this->buildChoiceRows('lesen', $contexts, [10, 12], 20);
    }

    private function buildHoerenRows(): array
    {
        $contexts = [
            ['title' => 'Telefonansage Praxis', 'text' => 'Praxis Dr. Weber: Heute geschlossen. In dringenden Faellen bitte 116117 waehlen.', 'q' => 'Was sollen Sie in dringenden Faellen tun?', 'ok' => 'Die 116117 waehlen', 'wrong' => ['Morgen anrufen', 'Zur Apotheke gehen', 'Eine SMS schreiben'], 'teil' => 1],
            ['title' => 'Bahnhof', 'text' => 'Achtung: Der RE 8 nach Koeln faehrt heute von Gleis 5 statt Gleis 3.', 'q' => 'Von welchem Gleis faehrt der RE 8?', 'ok' => 'Von Gleis 5', 'wrong' => ['Von Gleis 1', 'Von Gleis 3', 'Von Gleis 9'], 'teil' => 1],
            ['title' => 'Supermarktansage', 'text' => 'Angebot im Markt: Tomaten 1,99 Euro pro Kilo, nur heute.', 'q' => 'Was kosten die Tomaten?', 'ok' => '1,99 Euro pro Kilo', 'wrong' => ['0,99 Euro', '2,99 Euro', '3,50 Euro'], 'teil' => 2],
            ['title' => 'Arbeitsteam', 'text' => 'Die Schicht beginnt morgen ausnahmsweise um 5:30 Uhr.', 'q' => 'Wann beginnt die Schicht morgen?', 'ok' => 'Um 5:30 Uhr', 'wrong' => ['Um 6:30 Uhr', 'Um 7:00 Uhr', 'Um 8:00 Uhr'], 'teil' => 2],
            ['title' => 'Dialog Kurs', 'text' => 'A: Kommst du heute zum Kurs? B: Nein, ich habe einen Arzttermin um 17 Uhr.', 'q' => 'Warum kommt Person B nicht zum Kurs?', 'ok' => 'Wegen eines Arzttermins', 'wrong' => ['Wegen Urlaub', 'Wegen Arbeit im Nachtdienst', 'Wegen eines Umzugs'], 'teil' => 3],
            ['title' => 'Dialog Familie', 'text' => 'A: Treffen wir uns Samstag? B: Samstag arbeite ich, Sonntag passt besser.', 'q' => 'Wann passt das Treffen besser?', 'ok' => 'Am Sonntag', 'wrong' => ['Am Freitag', 'Am Samstagvormittag', 'Gar nicht'], 'teil' => 3],
            ['title' => 'Radiohinweis', 'text' => 'Wegen Bauarbeiten faehrt die Buslinie 12 bis Freitag nur bis Rathaus.', 'q' => 'Wie lange gilt die Aenderung?', 'ok' => 'Bis Freitag', 'wrong' => ['Bis Montag', 'Nur heute', 'Den ganzen Monat'], 'teil' => 4],
            ['title' => 'Infoveranstaltung', 'text' => 'Der Infoabend startet um 18 Uhr im Saal 2. Eine Anmeldung ist nicht noetig.', 'q' => 'Muss man sich anmelden?', 'ok' => 'Nein, das ist nicht noetig', 'wrong' => ['Ja, telefonisch', 'Ja, per Brief', 'Nur mit Einladung'], 'teil' => 4],
        ];

        return $this->buildChoiceRows('hoeren', $contexts, [10, 12], 22, true);
    }

    private function buildSchreibenRows(): array
    {
        $contexts = [
            ['title' => 'Krankmeldung', 'text' => 'Sie schreiben eine E-Mail an die Kursleitung, weil Sie morgen fehlen.', 'q' => 'Was muss in die E-Mail unbedingt hinein?', 'ok' => 'Grund und Bitte um Entschuldigung', 'wrong' => ['Nur ein Smiley', 'Keine Anrede', 'Nur ein Foto'], 'teil' => 1],
            ['title' => 'Termin verschieben', 'text' => 'Sie koennen zum Behoerdentermin nicht kommen und schreiben eine Nachricht.', 'q' => 'Welche Formulierung ist am besten?', 'ok' => 'Koennen wir bitte einen neuen Termin vereinbaren?', 'wrong' => ['Ich komme vielleicht nie.', 'Termin egal.', 'Sie sind schuld.'], 'teil' => 1],
            ['title' => 'Beschwerde', 'text' => 'Ein Produkt ist kaputt angekommen. Sie reklamieren schriftlich.', 'q' => 'Was ist ein passender Betreff?', 'ok' => 'Reklamation zur Bestellung', 'wrong' => ['Hallo Leute', 'Wetter heute', 'Keine Ahnung'], 'teil' => 2],
            ['title' => 'Formelle Mail', 'text' => 'Sie schreiben an die Hausverwaltung wegen einer defekten Heizung.', 'q' => 'Welche Schlussformel passt?', 'ok' => 'Mit freundlichen Gruessen', 'wrong' => ['Ciao', 'Bis spaeter', 'Yo'], 'teil' => 2],
        ];

        return $this->buildChoiceRows('schreiben', $contexts, [12, 15], 45);
    }

    private function buildSprechenRows(): array
    {
        $contexts = [
            ['title' => 'Vorstellung', 'text' => 'Sie stellen sich kurz in einem Kurs vor.', 'q' => 'Welcher Einstieg ist DTZ-gerecht?', 'ok' => 'Guten Tag, ich heisse ... und komme aus ...', 'wrong' => ['Ich sage nichts.', 'Nur mein Alter.', 'Direkt tschues.'], 'teil' => 1],
            ['title' => 'Bildbeschreibung', 'text' => 'Sie beschreiben ein Bild mit zwei Personen im Park.', 'q' => 'Was passt als Start?', 'ok' => 'Auf dem Bild sehe ich zwei Personen im Park.', 'wrong' => ['Ich kenne das Bild nicht.', 'Naechste Frage.', 'Bild ist egal.'], 'teil' => 2],
            ['title' => 'Gemeinsam planen', 'text' => 'Sie planen mit Ihrem Partner einen Lerntag.', 'q' => 'Welche Aussage ist kommunikativ am besten?', 'ok' => 'Was haeltst du davon, wenn wir um 10 Uhr starten?', 'wrong' => ['Ich entscheide alles allein.', 'Wir planen nichts.', 'Du musst machen, was ich sage.'], 'teil' => 3],
            ['title' => 'Rueckfrage', 'text' => 'Ihr Partner spricht schnell und Sie haben etwas nicht verstanden.', 'q' => 'Welche Rueckfrage passt?', 'ok' => 'Kannst du das bitte noch einmal wiederholen?', 'wrong' => ['Egal, weiter.', 'Ich hoere nie zu.', 'Stop, ich gehe.'], 'teil' => 3],
        ];

        return $this->buildChoiceRows('sprechen', $contexts, [12, 15], 45);
    }

    private function buildLidRows(): array
    {
        $contexts = [
            ['title' => 'Grundgesetz', 'text' => 'Frage zur politischen Ordnung in Deutschland.', 'q' => 'Was ist die Hauptstadt von Deutschland?', 'ok' => 'Berlin', 'wrong' => ['Bonn', 'Hamburg', 'Muenchen'], 'teil' => 1],
            ['title' => 'Wahlen', 'text' => 'Demokratie und Beteiligung.', 'q' => 'Wie oft wird der Bundestag normalerweise gewaehlt?', 'ok' => 'Alle vier Jahre', 'wrong' => ['Jedes Jahr', 'Alle zwei Monate', 'Alle zehn Jahre'], 'teil' => 1],
            ['title' => 'Rechte', 'text' => 'Grundrechte im Alltag.', 'q' => 'Welches Recht steht im Grundgesetz?', 'ok' => 'Meinungsfreiheit', 'wrong' => ['Hausaufgabenfreiheit', 'Parkgebuehrenfreiheit', 'Autopflicht'], 'teil' => 1],
        ];

        return $this->buildChoiceRows('lid', $contexts, [5, 8], 5);
    }

    private function buildChoiceRows(string $module, array $contexts, array $pointsRange, int $repeat, bool $includeAudio = false): array
    {
        $rows = [];
        $levels = ['A2', 'B1'];
        $idx = 0;

        for ($round = 0; $round < $repeat; $round++) {
            foreach ($contexts as $ctx) {
                $letters = ['A', 'B', 'C', 'D'];
                $options = array_merge([$ctx['ok']], $ctx['wrong']);
                $rotation = $idx % 4;
                $options = array_merge(array_slice($options, $rotation), array_slice($options, 0, $rotation));
                $correctLetter = $letters[array_search($ctx['ok'], $options, true)];
                $optionTexts = [];
                foreach ($options as $optIdx => $opt) {
                    $optionTexts[] = $letters[$optIdx] . ') ' . $opt;
                }

                $content = [
                    'text' => $ctx['title'] . ":\n" . $ctx['text'],
                    'question' => $ctx['q'],
                    'options' => $optionTexts,
                ];
                if ($includeAudio) {
                    $content['audio_text'] = $ctx['text'];
                }

                $rows[] = [
                    'module' => $module,
                    'teil' => (int)($ctx['teil'] ?? 1),
                    'level' => $levels[$idx % 2],
                    'question_type' => 'multiple_choice',
                    'content' => json_encode($content, JSON_UNESCAPED_UNICODE),
                    'correct_answer' => json_encode(['answer' => $correctLetter], JSON_UNESCAPED_UNICODE),
                    'explanation' => 'Auto-Pool: ' . $ctx['ok'],
                    'difficulty' => min(10, 2 + (int)(($idx % 6) + ($module === 'lid' ? 0 : 1))),
                    'points' => (int)$pointsRange[$idx % 2],
                    'is_active' => 1,
                ];
                $idx++;
            }
        }

        return $rows;
    }
}
