<?php
declare(strict_types=1);

namespace DTZ\Services;

/**
 * Azure Text-to-Speech Service
 * Uses Azure Cognitive Services Speech API
 */
class AzureTTSService {
    private string $subscriptionKey;
    private string $region;
    private string $baseUrl;
    
    // Voice mapping for different scenarios
    private array $voices = [
        'phone' => 'de-DE-KatjaNeural',      // Friendly female for phone calls
        'announcement' => 'de-DE-ConradNeural', // Clear male for announcements
        'conversation' => 'de-DE-AmalaNeural',  // Natural female for conversations
        'interview' => 'de-DE-KillianNeural',   // Professional male for interviews
        'news' => 'de-DE-SeraphinaMultilingualNeural', // News-style
        'default' => 'de-DE-KatjaNeural'
    ];
    
    public function __construct(string $subscriptionKey, string $region = 'westeurope') {
        $this->subscriptionKey = $subscriptionKey;
        $this->region = $region;
        $this->baseUrl = "https://{$region}.tts.speech.microsoft.com";
    }
    
    /**
     * Generate speech from text
     * 
     * @param string $text Text to speak
     * @param string $scenario Voice scenario (phone, announcement, conversation, interview, news)
     * @param float $speed Speech rate (0.5 = slow, 1.0 = normal, 1.5 = fast)
     * @return array ['success' => bool, 'audio' => string (base64), 'format' => string]
     */
    public function generateSpeech(string $text, string $scenario = 'default', float $speed = 1.0): array {
        if (empty($this->subscriptionKey)) {
            return ['success' => false, 'error' => 'Azure key not configured'];
        }
        
        $voice = $this->voices[$scenario] ?? $this->voices['default'];
        
        // Build SSML
        $ssml = $this->buildSSML($text, $voice, $speed);
        
        // Call Azure API
        $result = $this->callTTSAPI($ssml);
        
        if ($result === null) {
            return ['success' => false, 'error' => 'TTS API call failed'];
        }
        
        return [
            'success' => true,
            'audio' => base64_encode($result),
            'format' => 'audio/mp3',
            'voice' => $voice,
            'scenario' => $scenario
        ];
    }
    
    /**
     * Build SSML (Speech Synthesis Markup Language)
     */
    private function buildSSML(string $text, string $voice, float $rate): string {
        // Escape special XML characters
        $text = htmlspecialchars($text, ENT_XML1, 'UTF-8');
        
        // Convert rate to percentage
        $ratePercent = round(($rate - 1) * 100);
        $rateAttr = $ratePercent >= 0 ? "+{$ratePercent}%" : "{$ratePercent}%";
        
        return <<<SSML
<?xml version="1.0" encoding="UTF-8"?>
<speak version="1.0" xmlns="http://www.w3.org/2001/10/synthesis" xml:lang="de-DE">
    <voice name="{$voice}">
        <prosody rate="{$rateAttr}" pitch="0%">
            {$text}
        </prosody>
    </voice>
</speak>
SSML;
    }
    
    /**
     * Call Azure TTS API
     */
    private function callTTSAPI(string $ssml): ?string {
        $url = "{$this->baseUrl}/cognitiveservices/v1";
        
        $ch = curl_init($url);
        
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $ssml);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/ssml+xml',
            'X-Microsoft-OutputFormat: audio-16khz-128kbitrate-mono-mp3',
            'Ocp-Apim-Subscription-Key: ' . $this->subscriptionKey,
            'User-Agent: DTZ-Learning-Platform'
        ]);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        
        if (curl_errno($ch)) {
            error_log('Azure TTS curl error: ' . curl_error($ch));
            curl_close($ch);
            return null;
        }
        
        curl_close($ch);
        
        if ($httpCode !== 200) {
            error_log("Azure TTS API error: HTTP {$httpCode}");
            return null;
        }
        
        return $response;
    }
    
    /**
     * Get available voices
     */
    public function getVoices(): array {
        return $this->voices;
    }
    
    /**
     * Test Azure connection
     */
    public function testConnection(): bool {
        $result = $this->generateSpeech('Hallo', 'default', 1.0);
        return $result['success'] ?? false;
    }
    
    /**
     * Generate speech for Hören Teil 1-4 scenarios
     */
    public function generateForHoeren(int $teil, string $text): array {
        $scenarioMap = [
            1 => 'phone',         // Telefonansagen
            2 => 'conversation',  // Gespräche
            3 => 'interview',     // Interviews
            4 => 'announcement'   // Durchsagen/Information
        ];
        
        $scenario = $scenarioMap[$teil] ?? 'default';
        
        // Adjust speed based on teil
        $speedMap = [
            1 => 1.0,  // Normal
            2 => 0.9,  // Slightly slower for conversations
            3 => 0.85, // Slower for interviews
            4 => 1.0   // Normal for announcements
        ];
        
        $speed = $speedMap[$teil] ?? 1.0;
        
        return $this->generateSpeech($text, $scenario, $speed);
    }
}
