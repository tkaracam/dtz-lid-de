<?php
declare(strict_types=1);

namespace DTZ\Services;

/**
 * Azure Text-to-Speech Service
 * Realistic German voices for DTZ listening comprehension
 */
class AzureTTSService {
    private string $subscriptionKey;
    private string $region;
    private string $baseUrl;
    private bool $demoMode;
    
    // Enhanced voice mapping with styles
    private array $voices = [
        // Female voices
        'phone_female' => 'de-DE-KatjaNeural',           // Friendly phone voice
        'service_female' => 'de-DE-SeraphinaMultilingualNeural', // Service/call center
        'young_female' => 'de-DE-LouisaNeural',          // Young natural
        
        // Male voices  
        'announcement_male' => 'de-DE-ConradNeural',     // Clear announcements
        'conversation_male' => 'de-DE-KillianNeural',    // Natural speaking
        'professional_male' => 'de-DE-BerndNeural',      // Business/professional
        
        // DTZ specific scenarios
        'buero' => 'de-DE-SeraphinaMultilingualNeural',  // Büro/Hotel reception
        'arzt' => 'de-DE-KatjaNeural',                   // Doctor's office
        'bahn' => 'de-DE-ConradNeural',                  // Train/transport
        'arbeit' => 'de-DE-KillianNeural',               // Work conversations
        'default' => 'de-DE-KatjaNeural'
    ];
    
    // Speaking styles for more natural sound
    private array $styles = [
        'cheerful' => '<mstts:express-as style="cheerful">',
        'sad' => '<mstts:express-as style="sad">',
        'angry' => '<mstts:express-as style="angry">',
        'friendly' => '<mstts:express-as style="friendly">',
        'excited' => '<mstts:express-as style="excited">',
        'default' => ''
    ];
    
    public function __construct(?string $subscriptionKey = null, string $region = 'westeurope') {
        $this->subscriptionKey = $subscriptionKey ?? $_ENV['AZURE_TTS_KEY'] ?? '';
        $this->region = $region;
        $this->baseUrl = "https://{$region}.tts.speech.microsoft.com";
        $this->demoMode = empty($this->subscriptionKey);
    }
    
    /**
     * Generate speech for DTZ listening scenarios
     * 
     * @param string $text Text to speak
     * @param string $scenario DTZ scenario: buero, arzt, bahn, arbeit
     * @param array $options Additional options: speed, pause, style
     * @return array Audio data or demo fallback
     */
    public function generateSpeech(string $text, string $scenario = 'default', array $options = []): array {
        // Demo mode: Use browser TTS hints
        if ($this->demoMode) {
            return $this->generateDemoAudio($text, $scenario, $options);
        }
        
        $voice = $this->voices[$scenario] ?? $this->voices['default'];
        $speed = $options['speed'] ?? 0.95; // Slightly slower for learners
        $style = $options['style'] ?? 'default';
        $pause = $options['pause'] ?? true; // Add natural pauses
        
        // Build enhanced SSML
        $ssml = $this->buildEnhancedSSML($text, $voice, $speed, $style, $pause);
        
        // Call Azure API
        $result = $this->callTTSAPI($ssml);
        
        if ($result === null) {
            // Fallback to demo mode on API error
            return $this->generateDemoAudio($text, $scenario, $options);
        }
        
        return [
            'success' => true,
            'audio' => base64_encode($result),
            'format' => 'audio/mp3',
            'voice' => $voice,
            'scenario' => $scenario,
            'demo' => false
        ];
    }
    
    /**
     * Build enhanced SSML with natural speech patterns
     */
    private function buildEnhancedSSML(string $text, string $voice, float $rate, string $style, bool $addPauses): string {
        // Clean text
        $text = htmlspecialchars($text, ENT_XML1, 'UTF-8');
        
        // Add natural pauses for commas and periods
        if ($addPauses) {
            $text = str_replace(', ', '<break time="300ms"/>', $text);
            $text = str_replace('. ', '<break time="500ms"/>', $text);
        }
        
        // Rate calculation
        $ratePercent = round(($rate - 1) * 100);
        $rateAttr = $ratePercent >= 0 ? "+{$ratePercent}%" : "{$ratePercent}%";
        
        // Style wrapper
        $styleOpen = $this->styles[$style] ?? '';
        $styleClose = $styleOpen ? '</mstts:express-as>' : '';
        
        return <<<SSML
<?xml version="1.0" encoding="UTF-8"?>
<speak version="1.0" xmlns="http://www.w3.org/2001/10/synthesis"
       xmlns:mstts="https://www.w3.org/2001/mstts"
       xml:lang="de-DE">
    <voice name="{$voice}">
        <prosody rate="{$rateAttr}" pitch="0%">
            {$styleOpen}
            {$text}
            {$styleClose}
        </prosody>
    </voice>
</speak>
SSML;
    }
    
    /**
     * Demo mode: Generate audio hints for browser TTS
     */
    private function generateDemoAudio(string $text, string $scenario, array $options): array {
        // Voice hints for browser SpeechSynthesis
        $voiceHints = [
            'buero' => [
                'voice' => 'female',
                'pitch' => 1.0,
                'rate' => 0.9,
                'description' => 'Büro/Hotel Rezeption - freundlich, professionell'
            ],
            'arzt' => [
                'voice' => 'female',
                'pitch' => 1.1,
                'rate' => 0.85,
                'description' => 'Arztpraxis - ruhig, verständlich'
            ],
            'bahn' => [
                'voice' => 'male',
                'pitch' => 0.9,
                'rate' => 0.95,
                'description' => 'Bahn/Durchsage - klar, deutlich'
            ],
            'arbeit' => [
                'voice' => 'male',
                'pitch' => 1.0,
                'rate' => 0.9,
                'description' => 'Arbeitsgespräch - natürlich, locker'
            ],
            'default' => [
                'voice' => 'female',
                'pitch' => 1.0,
                'rate' => 0.9,
                'description' => 'Standard - natürliche Aussprache'
            ]
        ];
        
        $hint = $voiceHints[$scenario] ?? $voiceHints['default'];
        
        return [
            'success' => true,
            'demo' => true,
            'text' => $text,
            'scenario' => $scenario,
            'voiceHint' => $hint,
            'instructions' => 'Verwenden Sie die Browser-Sprachausgabe mit diesen Einstellungen',
            'browserTTS' => [
                'lang' => 'de-DE',
                'pitch' => $hint['pitch'],
                'rate' => $hint['rate'],
                'voicePreference' => $hint['voice']
            ]
        ];
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
            'X-Microsoft-OutputFormat: audio-24khz-160kbitrate-mono-mp3',
            'Ocp-Apim-Subscription-Key: ' . $this->subscriptionKey,
            'User-Agent: DTZLearning'
        ]);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        
        curl_close($ch);
        
        if ($httpCode !== 200 || $response === false) {
            error_log("Azure TTS API error: HTTP $httpCode");
            return null;
        }
        
        return $response;
    }
    
    /**
     * Get available voices info
     */
    public function getVoiceInfo(): array {
        return [
            'available' => $this->demoMode ? false : true,
            'demoMode' => $this->demoMode,
            'voices' => $this->voices,
            'recommendations' => [
                'buero' => 'Hotel/Bank/Behörde - höflich, professionell',
                'arzt' => 'Arztpraxis - ruhig, geduldig',
                'bahn' => 'Ansagen - klar, langsam',
                'arbeit' => 'Kollegen - natürlich, locker'
            ]
        ];
    }
}
