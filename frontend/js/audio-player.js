/**
 * Enhanced Audio Player for DTZ Learning
 * Realistic German speech synthesis with natural pauses
 */

class DTZAudioPlayer {
    constructor() {
        this.synth = window.speechSynthesis;
        this.currentUtterance = null;
        this.isPlaying = false;
        this.scenario = 'default';
        
        // Voice cache
        this.voices = [];
        this.preferredVoice = null;
        
        // Load voices
        this.loadVoices();
        
        // Voice change handler
        if (this.synth) {
            this.synth.onvoiceschanged = () => this.loadVoices();
        }
    }
    
    loadVoices() {
        if (!this.synth) return;
        
        this.voices = this.synth.getVoices();
        
        // Find best German voices
        const germanVoices = this.voices.filter(v => 
            v.lang.startsWith('de') || 
            v.lang === 'de-DE' ||
            v.name.includes('German') ||
            v.name.includes('Deutsch')
        );
        
        // Preference order for natural sound
        const preferredNames = [
            'Anna', 'Markus', 'Yannick', 'Stefan', 'Katja',
            'Google Deutsch', 'Microsoft Anna'
        ];
        
        for (const name of preferredNames) {
            const voice = germanVoices.find(v => v.name.includes(name));
            if (voice) {
                this.preferredVoice = voice;
                break;
            }
        }
        
        // Fallback to any German voice
        if (!this.preferredVoice && germanVoices.length > 0) {
            this.preferredVoice = germanVoices[0];
        }
    }
    
    /**
     * Play text with natural DTZ scenario settings
     */
    async play(text, scenario = 'default', options = {}) {
        if (!this.synth) {
            console.error('Speech synthesis not supported');
            return false;
        }
        
        // Stop any current speech
        this.stop();
        
        // Scenario settings for realistic sound
        const scenarioSettings = {
            'buero': { rate: 0.88, pitch: 1.0, pause: 400 },      // Office - professional, clear
            'arzt': { rate: 0.85, pitch: 1.05, pause: 500 },       // Doctor - slower, friendly
            'bahn': { rate: 0.82, pitch: 0.95, pause: 600 },       // Train - slower, announcements
            'arbeit': { rate: 0.92, pitch: 1.0, pause: 350 },      // Work - natural, conversational
            'telefon': { rate: 0.9, pitch: 1.02, pause: 400 },     // Phone - clear
            'default': { rate: 0.88, pitch: 1.0, pause: 400 }
        };
        
        const settings = scenarioSettings[scenario] || scenarioSettings['default'];
        
        // Add natural pauses for punctuation
        const enhancedText = this.addNaturalPauses(text, settings.pause);
        
        // Create utterance
        this.currentUtterance = new SpeechSynthesisUtterance(enhancedText);
        this.currentUtterance.lang = 'de-DE';
        this.currentUtterance.rate = options.rate || settings.rate;
        this.currentUtterance.pitch = options.pitch || settings.pitch;
        
        // Use preferred voice
        if (this.preferredVoice) {
            this.currentUtterance.voice = this.preferredVoice;
        }
        
        // Event handlers
        this.currentUtterance.onstart = () => {
            this.isPlaying = true;
            if (options.onStart) options.onStart();
        };
        
        this.currentUtterance.onend = () => {
            this.isPlaying = false;
            if (options.onEnd) options.onEnd();
        };
        
        this.currentUtterance.onerror = (e) => {
            console.error('Speech error:', e);
            this.isPlaying = false;
            if (options.onError) options.onError(e);
        };
        
        // Play
        this.synth.speak(this.currentUtterance);
        return true;
    }
    
    /**
     * Add natural pauses for better comprehension
     */
    addNaturalPauses(text, basePause) {
        // Break long sentences
        let result = text
            .replace(/([.!?])\s+/g, '$1 [PAUSE] ')
            .replace(/,\s+/g, ', [SHORT] ')
            .replace(/;/g, '; [SHORT] ')
            .replace(/:/g, ': [SHORT] ');
        
        // Split into chunks for better control
        const chunks = result.split(/\[PAUSE\]|\[SHORT\]/);
        
        return result;
    }
    
    /**
     * Play with sentence-by-sentence control
     */
    async playSentences(text, scenario = 'default', onSentenceEnd) {
        // Split into sentences
        const sentences = text.match(/[^.!?]+[.!?]+/g) || [text];
        
        for (let i = 0; i < sentences.length; i++) {
            const sentence = sentences[i].trim();
            if (!sentence) continue;
            
            await this.playSentence(sentence, scenario);
            
            if (onSentenceEnd) {
                onSentenceEnd(i, sentences.length);
            }
            
            // Pause between sentences
            if (i < sentences.length - 1) {
                await this.sleep(800);
            }
        }
    }
    
    playSentence(text, scenario) {
        return new Promise((resolve) => {
            this.play(text, scenario, {
                onEnd: resolve,
                onError: resolve
            });
        });
    }
    
    sleep(ms) {
        return new Promise(resolve => setTimeout(resolve, ms));
    }
    
    /**
     * Stop playback
     */
    stop() {
        if (this.synth) {
            this.synth.cancel();
            this.isPlaying = false;
        }
    }
    
    /**
     * Pause playback
     */
    pause() {
        if (this.synth && this.isPlaying) {
            this.synth.pause();
        }
    }
    
    /**
     * Resume playback
     */
    resume() {
        if (this.synth) {
            this.synth.resume();
        }
    }
    
    /**
     * Check if playing
     */
    getPlaying() {
        return this.isPlaying;
    }
    
    /**
     * Get available voices
     */
    getVoices() {
        return this.voices.filter(v => v.lang.startsWith('de'));
    }
}

// Global instance
const audioPlayer = new DTZAudioPlayer();
