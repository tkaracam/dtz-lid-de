/**
 * AI Tutor Client Module
 * Intelligent tutoring system integration
 */

const AITutor = {
    baseUrl: `${API_BASE}/ai`,
    isLoading: false,
    
    /**
     * Explain current question
     */
    async explainQuestion(question, options = [], module = 'general') {
        if (this.isLoading) return;
        this.isLoading = true;
        
        try {
            const response = await fetch(`${this.baseUrl}/tutor.php`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Authorization': `Bearer ${getToken()}`
                },
                body: JSON.stringify({
                    action: 'explain_question',
                    question,
                    options,
                    module
                })
            });
            
            const data = await response.json();
            
            if (data.success) {
                this.showExplanation(data.explanation, data.cached);
                return data.explanation;
            } else {
                Toast.error(data.error || 'Erklärung konnte nicht geladen werden');
            }
        } catch (error) {
            console.error('AI Tutor error:', error);
            Toast.error('KI-Dienst nicht verfügbar');
        } finally {
            this.isLoading = false;
        }
    },
    
    /**
     * Get hint for question
     */
    async getHint(question, attemptCount = 0) {
        if (this.isLoading) return;
        this.isLoading = true;
        
        try {
            const response = await fetch(`${this.baseUrl}/tutor.php`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Authorization': `Bearer ${getToken()}`
                },
                body: JSON.stringify({
                    action: 'get_hint',
                    question,
                    attempt_count: attemptCount
                })
            });
            
            const data = await response.json();
            
            if (data.success) {
                this.showHint(data.hint, data.hint_level);
                return data.hint;
            }
        } catch (error) {
            console.error('Hint error:', error);
        } finally {
            this.isLoading = false;
        }
    },
    
    /**
     * Analyze mistake
     */
    async analyzeMistake(question, userAnswer, correctAnswer, module = 'general') {
        if (this.isLoading) return;
        this.isLoading = true;
        
        try {
            const response = await fetch(`${this.baseUrl}/tutor.php`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Authorization': `Bearer ${getToken()}`
                },
                body: JSON.stringify({
                    action: 'analyze_mistake',
                    question,
                    user_answer: userAnswer,
                    correct_answer: correctAnswer,
                    module
                })
            });
            
            const data = await response.json();
            
            if (data.success) {
                this.showAnalysis(data.analysis, data.mistake_type);
                return data;
            }
        } catch (error) {
            console.error('Analysis error:', error);
        } finally {
            this.isLoading = false;
        }
    },
    
    /**
     * Get personalized tip
     */
    async getPersonalizedTip(module = 'general', weakAreas = []) {
        try {
            const response = await fetch(`${this.baseUrl}/tutor.php`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Authorization': `Bearer ${getToken()}`
                },
                body: JSON.stringify({
                    action: 'personalized_tip',
                    module,
                    weak_areas: weakAreas
                })
            });
            
            const data = await response.json();
            
            if (data.success) {
                return data.tip;
            }
        } catch (error) {
            console.error('Tip error:', error);
        }
    },
    
    /**
     * Explain grammar topic
     */
    async explainGrammar(topic, example = '') {
        if (this.isLoading) return;
        this.isLoading = true;
        
        try {
            const response = await fetch(`${this.baseUrl}/tutor.php`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Authorization': `Bearer ${getToken()}`
                },
                body: JSON.stringify({
                    action: 'grammar_explain',
                    topic,
                    example
                })
            });
            
            const data = await response.json();
            
            if (data.success) {
                this.showGrammarExplanation(data.explanation, topic);
                return data.explanation;
            }
        } catch (error) {
            console.error('Grammar error:', error);
        } finally {
            this.isLoading = false;
        }
    },
    
    /**
     * Show explanation in modal
     */
    showExplanation(text, cached = false) {
        const cacheBadge = cached ? '<span class="badge badge-info">Aus Cache</span>' : '';
        
        const content = `
            <div class="ai-explanation">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem;">
                    <h3>🎓 KI-Erklärung</h3>
                    ${cacheBadge}
                </div>
                <div class="ai-content">${this.formatText(text)}</div>
            </div>
        `;
        
        Modal.create({
            title: 'Erklärung',
            content,
            className: 'ai-modal'
        });
    },
    
    /**
     * Show hint
     */
    showHint(text, level) {
        const levelLabels = ['', 'Leichter Hinweis', 'Konkreter Hinweis', 'Fast die Lösung'];
        const levelColors = ['', '#22c55e', '#f59e0b', '#ef4444'];
        
        const content = `
            <div class="ai-hint" style="border-left: 4px solid ${levelColors[level]}; padding-left: 1rem;">
                <div class="badge" style="background: ${levelColors[level]}20; color: ${levelColors[level]}; margin-bottom: 0.5rem;">
                    💡 ${levelLabels[level]}
                </div>
                <div class="ai-content">${this.formatText(text)}</div>
            </div>
        `;
        
        // Show as toast for quick hints
        if (level <= 2) {
            Toast.info(text, `Hinweis ${level}/3`);
        } else {
            Modal.create({
                title: 'Hinweis',
                content,
                className: 'ai-modal'
            });
        }
    },
    
    /**
     * Show mistake analysis
     */
    showAnalysis(text, type) {
        const content = `
            <div class="ai-analysis">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem;">
                    <h3>📚 Fehleranalyse</h3>
                    <span class="badge badge-warning">${type}</span>
                </div>
                <div class="ai-content">${this.formatText(text)}</div>
                <div style="margin-top: 1.5rem; padding-top: 1rem; border-top: 1px solid var(--glass-border);">
                    <p style="color: var(--text-muted); font-size: 0.9rem;">
                        💪 Jeder Fehler ist eine Lernchance! Üben Sie ähnliche Aufgaben.
                    </p>
                </div>
            </div>
        `;
        
        Modal.create({
            title: 'Analyse',
            content,
            className: 'ai-modal'
        });
    },
    
    /**
     * Show grammar explanation
     */
    showGrammarExplanation(text, topic) {
        const content = `
            <div class="ai-grammar">
                <h3 style="margin-bottom: 1rem;">📖 ${topic}</h3>
                <div class="ai-content">${this.formatText(text)}</div>
            </div>
        `;
        
        Modal.create({
            title: 'Grammatik',
            content,
            className: 'ai-modal'
        });
    },
    
    /**
     * Format AI text with markdown-like styling
     */
    formatText(text) {
        return text
            // Bold
            .replace(/\*\*(.+?)\*\*/g, '<strong>$1</strong>')
            // Italic
            .replace(/\*(.+?)\*/g, '<em>$1</em>')
            // Numbered lists
            .replace(/^(\d+)\.\s+(.+)$/gm, '<div style="margin: 0.5rem 0;"><span style="color: var(--accent); font-weight: 600;">$1.</span> $2</div>')
            // Bullet points
            .replace(/^[-•]\s+(.+)$/gm, '<div style="margin: 0.25rem 0; padding-left: 1rem;">• $1</div>')
            // Line breaks
            .replace(/\n/g, '<br>');
    },
    
    /**
     * Create AI help button for questions
     */
    createHelpButton(options = {}) {
        const {
            question = '',
            onExplain = null,
            onHint = null,
            showAnalysis = false,
            userAnswer = '',
            correctAnswer = ''
        } = options;
        
        const container = document.createElement('div');
        container.className = 'ai-help-buttons';
        container.style.cssText = 'display: flex; gap: 0.5rem; margin-top: 1rem;';
        
        // Explain button
        const explainBtn = document.createElement('button');
        explainBtn.className = 'btn btn-sm btn-secondary';
        explainBtn.innerHTML = '🎓 Erklärung';
        explainBtn.onclick = () => {
            if (onExplain) {
                onExplain();
            } else {
                this.explainQuestion(question);
            }
        };
        container.appendChild(explainBtn);
        
        // Hint button
        if (onHint) {
            const hintBtn = document.createElement('button');
            hintBtn.className = 'btn btn-sm btn-secondary';
            hintBtn.innerHTML = '💡 Hinweis';
            hintBtn.onclick = onHint;
            container.appendChild(hintBtn);
        }
        
        // Analysis button (for wrong answers)
        if (showAnalysis && userAnswer && correctAnswer) {
            const analyzeBtn = document.createElement('button');
            analyzeBtn.className = 'btn btn-sm btn-primary';
            analyzeBtn.innerHTML = '📚 Analyse';
            analyzeBtn.onclick = () => {
                this.analyzeMistake(question, userAnswer, correctAnswer);
            };
            container.appendChild(analyzeBtn);
        }
        
        return container;
    },
    
    /**
     * Add floating AI assistant button
     */
    addFloatingButton() {
        // Check if already exists
        if (document.getElementById('ai-floating-btn')) return;
        
        const btn = document.createElement('button');
        btn.id = 'ai-floating-btn';
        btn.innerHTML = '🤖';
        btn.style.cssText = `
            position: fixed;
            bottom: 90px;
            right: 20px;
            width: 56px;
            height: 56px;
            border-radius: 50%;
            background: linear-gradient(135deg, #6366f1, #8b5cf6);
            border: none;
            color: white;
            font-size: 24px;
            cursor: pointer;
            box-shadow: 0 4px 20px rgba(124, 92, 255, 0.4);
            z-index: 999;
            transition: transform 0.3s, box-shadow 0.3s;
        `;
        
        btn.addEventListener('mouseenter', () => {
            btn.style.transform = 'scale(1.1)';
            btn.style.boxShadow = '0 6px 30px rgba(124, 92, 255, 0.6)';
        });
        
        btn.addEventListener('mouseleave', () => {
            btn.style.transform = 'scale(1)';
            btn.style.boxShadow = '0 4px 20px rgba(124, 92, 255, 0.4)';
        });
        
        btn.addEventListener('click', () => {
            this.showAssistantMenu();
        });
        
        document.body.appendChild(btn);
    },
    
    /**
     * Show AI assistant menu
     */
    showAssistantMenu() {
        const content = `
            <div class="ai-menu">
                <button class="ai-menu-item" onclick="AITutor.showGrammarTopics()">
                    <span class="ai-menu-icon">📖</span>
                    <span>Grammatik erklären</span>
                </button>
                <button class="ai-menu-item" onclick="AITutor.showPersonalizedTip()">
                    <span class="ai-menu-icon">💡</span>
                    <span>Personalisierte Tipps</span>
                </button>
                <button class="ai-menu-item" onclick="AITutor.showWeakAreas()">
                    <span class="ai-menu-icon">📊</span>
                    <span>Meine Schwächen</span>
                </button>
            </div>
        `;
        
        Modal.create({
            title: '🤖 KI-Assistent',
            content,
            className: 'ai-menu-modal'
        });
    },
    
    /**
     * Show grammar topics
     */
    showGrammarTopics() {
        const topics = [
            'Akkusativ und Dativ',
            'Perfekt und Präteritum',
            'Nebensätze mit dass',
            'Relativsätze',
            'Adjektivdeklination',
            'Passiv',
            'Konjunktiv II',
            'Präpositionen mit Dativ/Akkusativ'
        ];
        
        const content = `
            <div class="ai-topics">
                <p style="margin-bottom: 1rem; color: var(--text-muted);">
                    Wählen Sie ein Grammatikthema:
                </p>
                ${topics.map(topic => `
                    <button class="ai-topic-btn" onclick="AITutor.explainGrammar('${topic}')">
                        📖 ${topic}
                    </button>
                `).join('')}
            </div>
        `;
        
        Modal.create({
            title: 'Grammatik-Themen',
            content,
            className: 'ai-modal'
        });
    },
    
    /**
     * Show personalized tip
     */
    async showPersonalizedTip() {
        const tip = await this.getPersonalizedTip();
        if (tip) {
            this.showExplanation(tip);
        }
    },
    
    /**
     * Show weak areas (placeholder)
     */
    showWeakAreas() {
        Toast.info('Diese Funktion wird bald verfügbar sein!', 'Demnächst');
    }
};

// Add AI styles
const aiStyles = document.createElement('style');
aiStyles.textContent = `
    .ai-modal .modal {
        max-width: 600px;
    }
    
    .ai-content {
        line-height: 1.8;
        font-size: 0.95rem;
    }
    
    .ai-content strong {
        color: var(--accent);
    }
    
    .ai-menu {
        display: flex;
        flex-direction: column;
        gap: 0.75rem;
    }
    
    .ai-menu-item {
        display: flex;
        align-items: center;
        gap: 1rem;
        padding: 1rem;
        background: rgba(255, 255, 255, 0.05);
        border: 1px solid rgba(255, 255, 255, 0.1);
        border-radius: 12px;
        color: var(--text);
        cursor: pointer;
        transition: all 0.3s;
        text-align: left;
        font-size: 1rem;
    }
    
    .ai-menu-item:hover {
        background: rgba(124, 92, 255, 0.2);
        border-color: var(--accent);
    }
    
    .ai-menu-icon {
        font-size: 1.5rem;
    }
    
    .ai-topics {
        display: flex;
        flex-direction: column;
        gap: 0.5rem;
    }
    
    .ai-topic-btn {
        padding: 0.875rem 1rem;
        background: rgba(255, 255, 255, 0.05);
        border: 1px solid rgba(255, 255, 255, 0.1);
        border-radius: 10px;
        color: var(--text);
        cursor: pointer;
        transition: all 0.3s;
        text-align: left;
    }
    
    .ai-topic-btn:hover {
        background: rgba(124, 92, 255, 0.2);
        border-color: var(--accent);
        transform: translateX(5px);
    }
    
    .ai-help-buttons {
        flex-wrap: wrap;
    }
    
    .ai-help-buttons .btn {
        font-size: 0.875rem;
    }
`;
document.head.appendChild(aiStyles);

// Export
window.AITutor = AITutor;
