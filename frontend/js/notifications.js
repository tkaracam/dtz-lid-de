/**
 * Notification System
 * Push notifications, daily reminders, and goal tracking
 */

const NotificationSystem = {
    permission: 'default',
    swRegistration: null,
    scheduledNotifications: [],
    
    async init() {
        // Check if notifications are supported
        if (!('Notification' in window)) {
            console.log('Notifications not supported');
            return false;
        }
        
        // Check current permission
        this.permission = Notification.permission;
        
        // Request permission if not determined
        if (this.permission === 'default') {
            await this.requestPermission();
        }
        
        // Get service worker registration
        if ('serviceWorker' in navigator) {
            this.swRegistration = await navigator.serviceWorker.ready;
        }
        
        // Load scheduled notifications
        this.loadScheduledNotifications();
        
        // Start checking for notifications
        this.startNotificationChecker();
        
        return this.permission === 'granted';
    },
    
    async requestPermission() {
        try {
            const permission = await Notification.requestPermission();
            this.permission = permission;
            
            if (permission === 'granted') {
                console.log('✅ Notification permission granted');
                this.showWelcomeNotification();
            } else {
                console.log('❌ Notification permission denied');
            }
            
            return permission;
        } catch (error) {
            console.error('Error requesting permission:', error);
            return 'denied';
        }
    },
    
    showWelcomeNotification() {
        this.send({
            title: '🎉 Willkommen bei DTZ Learning!',
            body: 'Wir werden dich an deine Lernziele erinnern.',
            icon: '/frontend/img/icon-192x192.png',
            badge: '/frontend/img/icon-192x192.png',
            tag: 'welcome'
        });
    },
    
    send(options) {
        const {
            title = 'DTZ Learning',
            body = '',
            icon = '/frontend/img/icon-192x192.png',
            badge = '/frontend/img/icon-192x192.png',
            tag = 'default',
            requireInteraction = false,
            actions = [],
            data = {}
        } = options;
        
        if (this.permission !== 'granted') {
            console.log('Cannot send notification: permission not granted');
            return;
        }
        
        // Use service worker for notifications if available
        if (this.swRegistration) {
            this.swRegistration.showNotification(title, {
                body,
                icon,
                badge,
                tag,
                requireInteraction,
                actions,
                data,
                vibrate: [200, 100, 200]
            });
        } else {
            // Fallback to regular notification
            new Notification(title, {
                body,
                icon,
                badge,
                tag,
                requireInteraction
            });
        }
    },
    
    // Daily learning reminder
    scheduleDailyReminder(time = '09:00') {
        const notification = {
            id: 'daily-reminder',
            type: 'daily',
            time,
            title: '📚 Zeit zum Lernen!',
            body: 'Mach heute einen Schritt auf dem Weg zum DTZ. Nur 10 Minuten!',
            icon: '/frontend/img/icon-192x192.png',
            enabled: true
        };
        
        this.addScheduledNotification(notification);
        return notification;
    },
    
    // Streak reminder (if not studied today)
    scheduleStreakReminder(time = '20:00') {
        const notification = {
            id: 'streak-reminder',
            type: 'streak',
            time,
            title: '🔥 Serie in Gefahr!',
            body: 'Lerne noch heute, um deine Serie zu behalten!',
            icon: '/frontend/img/icon-192x192.png',
            enabled: true
        };
        
        this.addScheduledNotification(notification);
        return notification;
    },
    
    // Goal reminder
    scheduleGoalReminder(goalName, targetDate, time = '10:00') {
        const notification = {
            id: `goal-${Date.now()}`,
            type: 'goal',
            time,
            title: '🎯 Ziel-Erinnerung',
            body: `Dein Ziel "${goalName}" steht bevor! Zieltermin: ${targetDate}`,
            icon: '/frontend/img/icon-192x192.png',
            enabled: true
        };
        
        this.addScheduledNotification(notification);
        return notification;
    },
    
    // Weekly progress report
    scheduleWeeklyReport(day = 0, time = '18:00') { // 0 = Sunday
        const notification = {
            id: 'weekly-report',
            type: 'weekly',
            day,
            time,
            title: '📊 Dein Wochenbericht',
            body: 'Sieh dir deine Fortschritte dieser Woche an!',
            icon: '/frontend/img/icon-192x192.png',
            enabled: true
        };
        
        this.addScheduledNotification(notification);
        return notification;
    },
    
    addScheduledNotification(notification) {
        // Remove existing notification with same ID
        this.scheduledNotifications = this.scheduledNotifications.filter(
            n => n.id !== notification.id
        );
        
        this.scheduledNotifications.push(notification);
        this.saveScheduledNotifications();
    },
    
    removeScheduledNotification(id) {
        this.scheduledNotifications = this.scheduledNotifications.filter(
            n => n.id !== id
        );
        this.saveScheduledNotifications();
    },
    
    saveScheduledNotifications() {
        localStorage.setItem('scheduled_notifications', 
            JSON.stringify(this.scheduledNotifications)
        );
    },
    
    loadScheduledNotifications() {
        try {
            const saved = localStorage.getItem('scheduled_notifications');
            if (saved) {
                this.scheduledNotifications = JSON.parse(saved);
            }
        } catch (e) {
            console.error('Error loading notifications:', e);
        }
    },
    
    startNotificationChecker() {
        // Check every minute for scheduled notifications
        setInterval(() => this.checkScheduledNotifications(), 60000);
        
        // Initial check
        this.checkScheduledNotifications();
    },
    
    checkScheduledNotifications() {
        const now = new Date();
        const currentTime = `${String(now.getHours()).padStart(2, '0')}:${String(now.getMinutes()).padStart(2, '0')}`;
        const currentDay = now.getDay();
        
        this.scheduledNotifications.forEach(notification => {
            if (!notification.enabled) return;
            
            let shouldSend = false;
            
            switch (notification.type) {
                case 'daily':
                case 'streak':
                case 'goal':
                    shouldSend = notification.time === currentTime;
                    break;
                case 'weekly':
                    shouldSend = notification.day === currentDay && 
                                notification.time === currentTime;
                    break;
            }
            
            if (shouldSend) {
                // Check if we already sent this notification today
                const lastSent = localStorage.getItem(`notif_sent_${notification.id}`);
                const today = now.toDateString();
                
                if (lastSent !== today) {
                    this.send({
                        title: notification.title,
                        body: notification.body,
                        icon: notification.icon,
                        tag: notification.id,
                        actions: [
                            { action: 'open', title: 'Öffnen' },
                            { action: 'dismiss', title: 'Später' }
                        ]
                    });
                    
                    localStorage.setItem(`notif_sent_${notification.id}`, today);
                }
            }
        });
    },
    
    // Goal tracking
    goals: [],
    
    addGoal(goal) {
        const newGoal = {
            id: Date.now().toString(),
            title: goal.title,
            description: goal.description,
            targetDate: goal.targetDate,
            targetValue: goal.targetValue,
            currentValue: 0,
            type: goal.type, // 'lessons', 'streak', 'score', 'custom'
            createdAt: new Date().toISOString(),
            completed: false
        };
        
        this.goals.push(newGoal);
        this.saveGoals();
        
        // Schedule reminder if target date is set
        if (goal.targetDate) {
            this.scheduleGoalReminder(goal.title, goal.targetDate);
        }
        
        return newGoal;
    },
    
    updateGoalProgress(goalId, value) {
        const goal = this.goals.find(g => g.id === goalId);
        if (!goal) return;
        
        goal.currentValue = value;
        
        if (goal.currentValue >= goal.targetValue && !goal.completed) {
            goal.completed = true;
            goal.completedAt = new Date().toISOString();
            
            // Send congratulatory notification
            this.send({
                title: '🎉 Ziel erreicht!',
                body: `Herzlichen Glückwunsch! Du hast "${goal.title}" erreicht!`,
                icon: '/frontend/img/icon-192x192.png',
                tag: `goal-completed-${goal.id}`
            });
        }
        
        this.saveGoals();
    },
    
    removeGoal(goalId) {
        this.goals = this.goals.filter(g => g.id !== goalId);
        this.saveGoals();
    },
    
    saveGoals() {
        localStorage.setItem('user_goals', JSON.stringify(this.goals));
    },
    
    loadGoals() {
        try {
            const saved = localStorage.getItem('user_goals');
            if (saved) {
                this.goals = JSON.parse(saved);
            }
        } catch (e) {
            console.error('Error loading goals:', e);
        }
    },
    
    getActiveGoals() {
        return this.goals.filter(g => !g.completed);
    },
    
    getCompletedGoals() {
        return this.goals.filter(g => g.completed);
    },
    
    // Settings UI
    showSettings() {
        const content = `
            <div class="notification-settings">
                <h3>🔔 Benachrichtigungen</h3>
                <div class="setting-item">
                    <label class="toggle">
                        <input type="checkbox" ${this.permission === 'granted' ? 'checked' : ''} 
                               onchange="NotificationSystem.toggleNotifications(this)">
                        <span>Benachrichtigungen aktivieren</span>
                    </label>
                </div>
                
                <h4 style="margin-top: 1.5rem;">📅 Erinnerungen</h4>
                <div class="setting-item">
                    <label>Tägliche Lern-Erinnerung</label>
                    <input type="time" class="input-field" value="09:00" 
                           onchange="NotificationSystem.scheduleDailyReminder(this.value)">
                </div>
                <div class="setting-item">
                    <label>Serie-Erinnerung</label>
                    <input type="time" class="input-field" value="20:00"
                           onchange="NotificationSystem.scheduleStreakReminder(this.value)">
                </div>
                
                <h4 style="margin-top: 1.5rem;">🎯 Meine Ziele</h4>
                <div id="goals-list">
                    ${this.renderGoalsList()}
                </div>
                
                <button class="btn btn-primary" style="margin-top: 1rem; width: 100%;"
                        onclick="NotificationSystem.showAddGoalModal()">
                    ➕ Neues Ziel
                </button>
            </div>
        `;
        
        Modal.create({
            title: 'Einstellungen',
            content,
            className: 'settings-modal'
        });
    },
    
    renderGoalsList() {
        const activeGoals = this.getActiveGoals();
        
        if (activeGoals.length === 0) {
            return '<p style="color: var(--text-muted);">Noch keine Ziele gesetzt.</p>';
        }
        
        return activeGoals.map(goal => `
            <div class="goal-item" style="display: flex; justify-content: space-between; align-items: center; 
                                          padding: 0.75rem; background: rgba(255,255,255,0.05); 
                                          border-radius: 8px; margin-bottom: 0.5rem;">
                <div>
                    <div style="font-weight: 600;">${goal.title}</div>
                    <div style="font-size: 0.8rem; color: var(--text-muted);">
                        ${goal.currentValue}/${goal.targetValue} • Bis ${new Date(goal.targetDate).toLocaleDateString('de-DE')}
                    </div>
                </div>
                <button class="btn btn-sm btn-ghost" onclick="NotificationSystem.removeGoal('${goal.id}')">
                    🗑️
                </button>
            </div>
        `).join('');
    },
    
    showAddGoalModal() {
        const content = `
            <div style="display: flex; flex-direction: column; gap: 1rem;">
                <div>
                    <label>Ziel-Titel</label>
                    <input type="text" class="input-field" id="goal-title" placeholder="z.B. 100 Lektionen">
                </div>
                <div>
                    <label>Beschreibung</label>
                    <input type="text" class="input-field" id="goal-desc" placeholder="Optional">
                </div>
                <div>
                    <label>Typ</label>
                    <select class="input-field" id="goal-type">
                        <option value="lessons">Lektionen</option>
                        <option value="streak">Lernserie</option>
                        <option value="score">Punkte</option>
                        <option value="custom">Benutzerdefiniert</option>
                    </select>
                </div>
                <div>
                    <label>Zielwert</label>
                    <input type="number" class="input-field" id="goal-target" placeholder="z.B. 100">
                </div>
                <div>
                    <label>Zieltermin</label>
                    <input type="date" class="input-field" id="goal-date">
                </div>
            </div>
        `;
        
        Modal.create({
            title: 'Neues Ziel',
            content,
            onClose: () => {
                const title = document.getElementById('goal-title')?.value;
                const desc = document.getElementById('goal-desc')?.value;
                const type = document.getElementById('goal-type')?.value;
                const target = parseInt(document.getElementById('goal-target')?.value);
                const date = document.getElementById('goal-date')?.value;
                
                if (title && target) {
                    this.addGoal({
                        title,
                        description: desc,
                        type,
                        targetValue: target,
                        targetDate: date
                    });
                    Toast.success('Ziel hinzugefügt!');
                }
            }
        });
    },
    
    async toggleNotifications(checkbox) {
        if (checkbox.checked) {
            const permission = await this.requestPermission();
            if (permission !== 'granted') {
                checkbox.checked = false;
                Toast.error('Benachrichtigungen wurden abgelehnt');
            }
        }
    },
    
    // Streak tracking
    checkStreak() {
        const lastStudyDate = localStorage.getItem('last_study_date');
        const today = new Date().toDateString();
        
        if (lastStudyDate !== today) {
            // Haven't studied today
            const streak = parseInt(localStorage.getItem('current_streak') || '0');
            
            if (streak > 0) {
                // Send streak warning notification in the evening
                const hour = new Date().getHours();
                if (hour >= 18) {
                    this.send({
                        title: '🔥 Serie in Gefahr!',
                        body: `Du hast heute noch nicht gelernt. Deine ${streak}-Tage-Serie geht verloren!`,
                        icon: '/frontend/img/icon-192x192.png',
                        tag: 'streak-warning',
                        requireInteraction: true
                    });
                }
            }
        }
    },
    
    // Record study session
    recordStudySession() {
        const today = new Date().toDateString();
        const lastStudyDate = localStorage.getItem('last_study_date');
        
        if (lastStudyDate !== today) {
            // New day
            let streak = parseInt(localStorage.getItem('current_streak') || '0');
            
            if (lastStudyDate === new Date(Date.now() - 86400000).toDateString()) {
                // Studied yesterday, increment streak
                streak++;
            } else {
                // Streak broken or first time
                streak = 1;
            }
            
            localStorage.setItem('current_streak', streak.toString());
            localStorage.setItem('last_study_date', today);
            
            // Check milestones
            if (streak === 7 || streak === 30 || streak === 100) {
                this.send({
                    title: `🔥 ${streak}-Tage-Serie!`,
                    body: `Beeindruckend! Du hast ${streak} Tage hintereinander gelernt!`,
                    icon: '/frontend/img/icon-192x192.png',
                    tag: `streak-milestone-${streak}`
                });
            }
            
            // Update goal progress if applicable
            const streakGoal = this.goals.find(g => g.type === 'streak' && !g.completed);
            if (streakGoal) {
                this.updateGoalProgress(streakGoal.id, streak);
            }
        }
    }
};

// Add notification styles
const notificationStyles = document.createElement('style');
notificationStyles.textContent = `
    .notification-settings h3,
    .notification-settings h4 {
        margin-bottom: 1rem;
    }
    
    .setting-item {
        margin-bottom: 1rem;
    }
    
    .setting-item label {
        display: block;
        margin-bottom: 0.5rem;
        color: var(--text-muted);
    }
    
    .setting-item .toggle {
        display: flex;
        align-items: center;
        gap: 0.75rem;
        cursor: pointer;
    }
    
    .setting-item input[type="checkbox"] {
        width: 48px;
        height: 24px;
        appearance: none;
        background: rgba(255, 255, 255, 0.1);
        border-radius: 12px;
        position: relative;
        cursor: pointer;
        transition: background 0.3s;
    }
    
    .setting-item input[type="checkbox"]::after {
        content: '';
        position: absolute;
        width: 20px;
        height: 20px;
        background: white;
        border-radius: 50%;
        top: 2px;
        left: 2px;
        transition: transform 0.3s;
    }
    
    .setting-item input[type="checkbox"]:checked {
        background: var(--accent);
    }
    
    .setting-item input[type="checkbox"]:checked::after {
        transform: translateX(24px);
    }
`;
document.head.appendChild(notificationStyles);

// Initialize on load
document.addEventListener('DOMContentLoaded', () => {
    NotificationSystem.init();
    NotificationSystem.loadGoals();
});

// Export
window.NotificationSystem = NotificationSystem;
