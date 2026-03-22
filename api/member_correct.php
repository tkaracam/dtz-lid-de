<?php
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Content-Type');
header('Access-Control-Allow-Methods: POST, OPTIONS');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Nur POST wird unterstützt.'], JSON_UNESCAPED_UNICODE);
    exit;
}

require_once __DIR__ . '/auth.php';
require_member_session_json();
require_once __DIR__ . '/correction_engine.php';

$raw = file_get_contents('php://input') ?: '';
$body = json_decode($raw, true);
if (!is_array($body)) {
    http_response_code(400);
    echo json_encode(['error' => 'Ungültiges JSON wurde gesendet.'], JSON_UNESCAPED_UNICODE);
    exit;
}

$letterText = trim((string)($body['letter_text'] ?? ''));
$taskPrompt = trim((string)($body['task_prompt'] ?? ''));
$requiredPoints = $body['required_points'] ?? [];

if ($letterText === '') {
    http_response_code(400);
    echo json_encode(['error' => 'letter_text darf nicht leer sein.'], JSON_UNESCAPED_UNICODE);
    exit;
}

if (!is_array($requiredPoints)) {
    $requiredPoints = [];
}

if (!dtz_is_likely_meaningful_german_text($letterText)) {
    http_response_code(422);
    echo json_encode(['error' => 'Der Text wirkt nicht sinnvoll. Bitte schreiben Sie zusammenhängende deutsche Sätze.'], JSON_UNESCAPED_UNICODE);
    exit;
}

$wordCount = dtz_word_count($letterText);
if ($wordCount < 20) {
    http_response_code(422);
    echo json_encode(['error' => 'Der Text ist zu kurz. Bitte mindestens 20 Wörter schreiben.'], JSON_UNESCAPED_UNICODE);
    exit;
}

function member_local_correction_fallback(string $letterText, string $taskPrompt, array $requiredPoints, string $reason): array
{
    $points = dtz_evaluate_points($letterText, $requiredPoints);
    $coveredCount = count($points['covered']);
    $missingCount = count($points['missing']);
    $wordCount = dtz_word_count($letterText);

    $aufgaben = max(0, 5 - min(5, $missingCount * 2));
    $textaufbau = $wordCount >= 50 ? 4 : ($wordCount >= 30 ? 3 : 2);
    $grammatik = 3;
    $wortschatz = 3;
    $score = max(0, min(20, $aufgaben + $textaufbau + $grammatik + $wortschatz));
    $niveau = $score >= 15 ? 'B1' : ($score >= 7 ? 'A2' : 'A1');

    return [
        'score_total' => $score,
        'niveau_einschaetzung' => $niveau,
        'rubric' => [
            'aufgabenbezug' => $aufgaben,
            'textaufbau' => $textaufbau,
            'grammatik' => $grammatik,
            'wortschatz_orthografie' => $wortschatz,
        ],
        'mistakes' => [],
        'corrected_letter' => trim($letterText),
        'teacher_feedback_de' => 'KI war kurzzeitig nicht erreichbar. Vorläufige Bewertung wurde lokal erstellt. Bitte später erneut prüfen.',
        'covered_points' => $points['covered'],
        'missing_points' => $points['missing'],
        'source' => 'local-fallback',
        'system_note' => $reason,
        'fallback' => true,
        'task_prompt' => $taskPrompt,
        'required_points' => $requiredPoints,
        'stats' => [
            'word_count' => $wordCount,
            'covered_points' => $coveredCount,
            'missing_points' => $missingCount,
        ],
    ];
}

try {
    $result = dtz_run_correction($letterText, $taskPrompt, $requiredPoints);
    echo json_encode($result, JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
    $msg = dtz_sanitize_external_error($e->getMessage());
    $fallback = member_local_correction_fallback($letterText, $taskPrompt, $requiredPoints, $msg);
    echo json_encode($fallback, JSON_UNESCAPED_UNICODE);
}
