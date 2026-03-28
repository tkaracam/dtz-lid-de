<?php
declare(strict_types=1);

namespace DTZ\Services;

/**
 * OpenAI Service for DTZ Writing Analysis
 * Structured feedback for A2-B1 level German learners
 */
class OpenAIService
{
    private string $apiKey;
    private string $model;
    private string $baseUrl = 'https://api.openai.com/v1';
    
    public function __construct()
    {
        $this->apiKey = $_ENV['OPENAI_API_KEY'] ?? '';
        $this->model = $_ENV['OPENAI_MODEL'] ?? 'gpt-4';
        
        if (empty($this->apiKey)) {
            throw new \RuntimeException('OpenAI API key not configured');
        }
    }
    
    /**
     * Analyze DTZ writing submission
     */
    public function analyzeWriting(string $text, string $taskType, string $expectedLevel = 'B1'): array
    {
        $systemPrompt = $this->buildSystemPrompt($expectedLevel);
        $userPrompt = $this->buildUserPrompt($text, $taskType);
        
        $response = $this->callOpenAI($systemPrompt, $userPrompt);
        
        return $this->parseResponse($response);
    }
    
    private function buildSystemPrompt(string $expectedLevel): string
    {
        return <<<PROMPT
Du bist ein DTZ-Prüfer (Deutsch-Test für Zuwanderer, B1-Niveau).

WICHTIGE REGELN:
1. Korrigiere NUR echte Fehler (Grammatik, Wortschatz, Satzbau)
2. Korrigiere NICHT den gesamten Text neu
3. Erklärungen müssen auf A2-B1-Niveau verständlich sein

BEWERTUNG (max. 20 Punkte):
- Aufgabenerfüllung (0-5)
- Textaufbau (0-5)
- Sprachrichtigkeit (0-5)
- Sprachbeherrschung (0-5)

AUSGABE (JSON):
{
  "overallScore": 0-20,
  "levelAssessment": "A2" oder "B1",
  "passed": true/false,
  "generalFeedback": "2-3 Sätze",
  "categories": {
    "taskCompletion": { "score": 0-5, "feedback": "..." },
    "structure": { "score": 0-5, "feedback": "..." },
    "languageAccuracy": { "score": 0-5, "feedback": "..." },
    "languageRange": { "score": 0-5, "feedback": "..." }
  },
  "corrections": [
    {
      "id": "corr_001",
      "type": "grammar|vocabulary|spelling|style",
      "severity": "major|minor",
      "original": "...",
      "corrected": "...",
      "explanation": "Einfache Erklärung",
      "startIndex": 45,
      "endIndex": 57
    }
  ],
  "highlights": [
    { "type": "good", "text": "...", "comment": "..." },
    { "type": "improve", "text": "...", "suggestion": "...", "reason": "..." }
  ]
}
PROMPT;
    }
    
    private function buildUserPrompt(string $text, string $taskType): string
    {
        $tasks = [
            'bewerbung' => 'Bewerbungsschreiben',
            'beschwerde' => 'Beschwerdebrief',
            'anfrage' => 'Anfrage',
            'termin' => 'Terminabsage',
            'einladung' => 'Einladung',
            'danksagung' => 'Dankesschreiben',
        ];
        
        return "AUFGABE: " . ($tasks[$taskType] ?? 'Brief') . "\n\nTEXT:\n" . $text;
    }
    
    private function callOpenAI(string $systemPrompt, string $userPrompt): string
    {
        $payload = [
            'model' => $this->model,
            'messages' => [
                ['role' => 'system', 'content' => $systemPrompt],
                ['role' => 'user', 'content' => $userPrompt]
            ],
            'temperature' => 0.3,
            'max_tokens' => 2000,
            'response_format' => ['type' => 'json_object']
        ];
        
        $ch = curl_init($this->baseUrl . '/chat/completions');
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode($payload),
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'Authorization: Bearer ' . $this->apiKey
            ],
            CURLOPT_TIMEOUT => 60,
        ]);
        
        $response = curl_exec($ch);
        curl_close($ch);
        
        $data = json_decode($response, true);
        return $data['choices'][0]['message']['content'] ?? '{}';
    }
    
    private function parseResponse(string $json): array
    {
        $data = json_decode($json, true);
        
        $defaults = [
            'overallScore' => 0,
            'levelAssessment' => 'A2',
            'passed' => false,
            'generalFeedback' => '',
            'categories' => [],
            'corrections' => [],
            'highlights' => [],
        ];
        
        return array_merge($defaults, $data ?? []);
    }
}
