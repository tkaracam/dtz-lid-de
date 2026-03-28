<?php
declare(strict_types=1);

namespace DTZ\Services;

/**
 * Azure Text-to-Speech Service
 * Generates realistic German audio for DTZ listening exercises
 */
class AzureTTSService
{
    private string $subscriptionKey;
    private string $region;
    private string $endpoint;
    
    // Voice mapping for different scenarios
    private array $voices = [
        'phone' => 'de-DE-KatjaNeural',        // Clear, professional (phone calls)
        'announcement' => 'de-DE-ConradNeural', // Official tone (announcements)
        'conversation' => 'de-DE-AmalaNeural',  // Natural, friendly (conversations)
        'interview' => 'de-DE-KillianNeural',   // Male voice (interviews)
        'news' => 'de-DE-SeraphinaNeural',      // News reader style
    ];
    
    public function __construct()
    {
        $this->subscriptionKey = $_ENV['AZURE_TTS_KEY'] ?? '';
        $this->region = $_ENV['AZURE_TTS_REGION'] ?? 'westeurope';
        $this->endpoint = "https://{$this->region}.tts.speech.microsoft.com/";
        
        if (empty($this->subscriptionKey)) {
            throw new \RuntimeException('Azure TTS key not configured');
        }
    }
    
    /**
     * Generate audio for text
     */
    public function generateAudio(
        string $text,
        string $scenario = 'conversation',
        ?string $customVoice = null
    ): array {
        
        $voice = $customVoice ?? ($this->voices[$scenario] ?? 'de-DE-KatjaNeural');
        
        // Build SSML for better control
        $ssml = $this->buildSSML($text, $voice, $scenario);
        
        // Call Azure TTS API
        $audioData = $this->callAzureTTS($ssml);
        
        // Save to storage
        $fileInfo = $this->saveAudio($audioData, $text);
        
        return [
            'success' => true,
            'file_url' => $fileInfo['url'],
            'file_path' => $fileInfo['path'],
            'duration_seconds' => $this->estimateDuration($text),
            'voice' => $voice,
            'scenario' => $scenario
        ];
    }
    
    /**
     * Build SSML with prosody control
     */
    private function buildSSML(string $text, string $voice, string $scenario): string
    {
        // Scenario-specific prosody settings
        $prosodySettings = [
            'phone' => 'rate="0%" pitch="0%"',      // Normal
            'announcement' => 'rate="-10%" pitch="-5%"', // Slower, deeper
            'conversation' => 'rate="0%" pitch="0%"',     // Natural
            'interview' => 'rate="0%" pitch="0%"',
            'news' => 'rate="-5%" pitch="0%"',
        ];
        
        $prosody = $prosodySettings[$scenario] ?? 'rate="0%" pitch="0%"';
        
        // Escape special XML characters
        $escapedText = htmlspecialchars($text, ENT_XML1, 'UTF-8');
        
        return <<<SSML
<speak version="1.0" xmlns="http://www.w3.org/2001/10/synthesis" xml:lang="de-DE">
    <voice name="{$voice}">
        <prosody {$prosody}>
            {$escapedText}
        </prosody>
    </voice>
</speak>
SSML;
    }
    
    /**
     * Call Azure TTS API
     */
    private function callAzureTTS(string $ssml): string
    {
        $url = $this->endpoint . 'cognitiveservices/v1';
        
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $ssml,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/ssml+xml',
                'X-Microsoft-OutputFormat: audio-24khz-160kbitrate-mono-mp3',
                'Ocp-Apim-Subscription-Key: ' . $this->subscriptionKey
            ],
            CURLOPT_TIMEOUT => 60,
        ]);
        
        $response = curl_exec($ch);
        $error = curl_error($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($error) {
            throw new \RuntimeException('Azure TTS request failed: ' . $error);
        }
        
        if ($httpCode !== 200) {
            throw new \RuntimeException('Azure TTS error: HTTP ' . $httpCode);
        }
        
        return $response;
    }
    
    /**
     * Save audio to storage
     */
    private function saveAudio(string $audioData, string $text): array
    {
        // Generate unique filename based on text hash
        $hash = md5($text);
        $filename = "audio_{$hash}_" . time() . '.mp3';
        
        // Local storage path
        $uploadDir = __DIR__ . '/../../public/uploads/audio/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }
        
        $filePath = $uploadDir . $filename;
        file_put_contents($filePath, $audioData);
        
        // Public URL
        $fileUrl = '/uploads/audio/' . $filename;
        
        return [
            'path' => $filePath,
            'url' => $fileUrl,
            'filename' => $filename,
            'size_bytes' => strlen($audioData)
        ];
    }
    
    /**
     * Estimate audio duration (rough calculation)
     */
    private function estimateDuration(string $text): int
    {
        // Average speaking rate: ~130 words per minute
        $wordCount = str_word_count($text);
        $duration = ceil(($wordCount / 130) * 60);
        return max(1, $duration);
    }
    
    /**
     * Generate audio for a question and save to database
     */
    public function generateForQuestion(
        string $questionId,
        string $text,
        string $scenario
    ): array {
        
        // Check if audio already exists
        $db = \DTZ\Database\Database::getInstance();
        $existing = $db->selectOne(
            "SELECT id, file_url FROM audio_files WHERE text_hash = ?",
            [md5($text)]
        );
        
        if ($existing) {
            return [
                'success' => true,
                'file_url' => $existing['file_url'],
                'cached' => true
            ];
        }
        
        // Generate new audio
        $result = $this->generateAudio($text, $scenario);
        
        if ($result['success']) {
            // Save to database
            $db->insert('audio_files', [
                'question_id' => $questionId,
                'provider' => 'azure',
                'voice_id' => $result['voice'],
                'text_content' => $text,
                'text_hash' => md5($text),
                'scenario' => $scenario,
                'file_url' => $result['file_url'],
                'file_path' => $result['file_path'],
                'file_size_bytes' => $result['size_bytes'] ?? 0,
                'duration_seconds' => $result['duration_seconds']
            ]);
        }
        
        return $result;
    }
    
    /**
     * Get list of available voices
     */
    public function getAvailableVoices(): array
    {
        return [
            ['id' => 'de-DE-KatjaNeural', 'name' => 'Katja', 'gender' => 'female', 'style' => 'clear'],
            ['id' => 'de-DE-ConradNeural', 'name' => 'Conrad', 'gender' => 'male', 'style' => 'formal'],
            ['id' => 'de-DE-AmalaNeural', 'name' => 'Amala', 'gender' => 'female', 'style' => 'natural'],
            ['id' => 'de-DE-KillianNeural', 'name' => 'Killian', 'gender' => 'male', 'style' => 'friendly'],
            ['id' => 'de-DE-SeraphinaNeural', 'name' => 'Seraphina', 'gender' => 'female', 'style' => 'news'],
        ];
    }
}
