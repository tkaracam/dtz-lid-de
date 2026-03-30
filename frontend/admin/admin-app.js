/**
 * DTZ Admin Dashboard - Single Page Application
 */

const AdminApp = {
    currentPage: 'dashboard',
    charts: {},
    data: {
        stats: null,
        users: [],
        questions: [],
        submissions: []
    },
    
    init() {
        this.checkAuth();
        this.setupNavigation();
        this.setupEventListeners();
        this.loadPage('dashboard');
        this.startRealtimeUpdates();
    },
    
    checkAuth() {
        const token = getToken();
        const user = getUser();
        
        if (!token || !user || user.role !== 'admin') {
            window.location.href = 'login.html';
            return;
        }
        
        document.getElementById('admin-name').textContent = user.name || user.email;
    },
    
    setupNavigation() {
        document.querySelectorAll('.nav-item[data-page]').forEach(item => {
            item.addEventListener('click', (e) => {
                e.preventDefault();
                const page = item.dataset.page;
                this.loadPage(page);
                
                // Update active state
                document.querySelectorAll('.nav-item').forEach(nav => nav.classList.remove('active'));
                item.classList.add('active');
            });
        });
    },
    
    setupEventListeners() {
        // Global search
        const searchInput = document.getElementById('global-search');
        searchInput?.addEventListener('input', this.debounce((e) => {
            this.handleSearch(e.target.value);
        }, 300));
        
        // Keyboard shortcuts
        document.addEventListener('keydown', (e) => {
            if ((e.metaKey || e.ctrlKey) && e.key === 'k') {
                e.preventDefault();
                document.getElementById('global-search')?.focus();
            }
        });
        
        // Mobile menu
        const mobileToggle = document.getElementById('mobile-menu-toggle');
        mobileToggle?.addEventListener('click', () => {
            document.querySelector('.admin-sidebar').classList.toggle('open');
        });
    },
    
    async loadPage(page) {
        this.currentPage = page;
        const container = document.getElementById('admin-content');
        
        // Show loading
        container.innerHTML = '<div style="text-align: center; padding: 4rem;"><div class="loading"></div></div>';
        
        switch(page) {
            case 'dashboard':
                await this.loadDashboard(container);
                break;
            case 'users':
                await this.loadUsers(container);
                break;
            case 'questions':
                await this.loadQuestions(container);
                break;
            case 'content':
                await this.loadContent(container);
                break;
            case 'analytics':
                await this.loadAnalytics(container);
                break;
            case 'settings':
                await this.loadSettings(container);
                break;
            case 'logs':
                await this.loadLogs(container);
                break;
            case 'health':
                await this.loadHealth(container);
                break;
            default:
                container.innerHTML = '<div class="error">Seite nicht gefunden</div>';
        }
    },
    
    // ========== DASHBOARD ==========
    async loadDashboard(container) {
        // Fetch stats
        try {
            const response = await fetch(`${API_BASE}/admin/stats.php`, {
                headers: { 'Authorization': `Bearer ${getToken()}` }
            });
            if (!response.ok) throw new Error('Failed to load stats');
            this.data.stats = await response.json();
        } catch(e) {
            console.error('Failed to load stats:', e);
            this.data.stats = this.getMockStats();
            Toast.error('Statistiken konnten nicht geladen werden');
        }
        
        const stats = this.data.stats;
        
        container.innerHTML = `
            <div class="page-header">
                <h1>Dashboard</h1>
                <p>Willkommen zurück! Hier ist die Übersicht Ihrer Plattform.</p>
            </div>
            
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-header">
                        <div class="stat-title">Gesamtbenutzer</div>
                        <div class="stat-icon">👥</div>
                    </div>
                    <div class="stat-value">${stats.total_users?.toLocaleString() || 0}</div>
                    <div class="stat-change positive">
                        ↑ ${stats.new_users_today || 0} heute
                    </div>
                </div>
                
                <div class="stat-card success">
                    <div class="stat-header">
                        <div class="stat-title">Premium Abonnenten</div>
                        <div class="stat-icon">💎</div>
                    </div>
                    <div class="stat-value">${stats.premium_users?.toLocaleString() || 0}</div>
                    <div class="stat-change positive">
                        ↑ ${stats.premium_growth || 0}% diesen Monat
                    </div>
                </div>
                
                <div class="stat-card warning">
                    <div class="stat-header">
                        <div class="stat-title">Wartende Inhalte</div>
                        <div class="stat-icon">⏳</div>
                    </div>
                    <div class="stat-value">${stats.pending_content || 0}</div>
                    <div class="stat-change ${stats.pending_content > 10 ? 'negative' : 'positive'}">
                        ${stats.pending_content > 10 ? '⚠️' : '✓'} Review erforderlich
                    </div>
                </div>
                
                <div class="stat-card danger">
                    <div class="stat-header">
                        <div class="stat-title">Umsatz (Monat)</div>
                        <div class="stat-icon">💶</div>
                    </div>
                    <div class="stat-value">€${stats.monthly_revenue?.toLocaleString() || 0}</div>
                    <div class="stat-change positive">
                        ↑ ${stats.revenue_growth || 0}% vs letzter Monat
                    </div>
                </div>
            </div>
            
            <div class="dashboard-grid">
                <div class="dashboard-card">
                    <div class="dashboard-card-header">
                        <h3 class="dashboard-card-title">Aktivität</h3>
                        <select id="activity-filter" style="padding: 0.5rem; border-radius: 8px; background: var(--bg); border: 1px solid var(--glass-border);">
                            <option value="24h">Letzte 24h</option>
                            <option value="7d">Letzte 7 Tage</option>
                            <option value="30d">Letzte 30 Tage</option>
                        </select>
                    </div>
                    <div class="dashboard-card-body">
                        <canvas id="activityChart" height="300"></canvas>
                    </div>
                </div>
                
                <div>
                    <div class="dashboard-card" style="margin-bottom: 1.5rem;">
                        <div class="dashboard-card-header">
                            <h3 class="dashboard-card-title">Schnellzugriff</h3>
                        </div>
                        <div class="dashboard-card-body">
                            <div class="quick-actions">
                                <a href="#questions" class="quick-action" data-page="questions">
                                    <div class="quick-action-icon">➕</div>
                                    <div class="quick-action-label">Frage erstellen</div>
                                </a>
                                <a href="#users" class="quick-action" data-page="users">
                                    <div class="quick-action-icon">👤</div>
                                    <div class="quick-action-label">Benutzer suchen</div>
                                </a>
                                <a href="#content" class="quick-action" data-page="content">
                                    <div class="quick-action-icon">✅</div>
                                    <div class="quick-action-label">Inhalte prüfen</div>
                                </a>
                                <a href="#analytics" class="quick-action" data-page="analytics">
                                    <div class="quick-action-icon">📊</div>
                                    <div class="quick-action-label">Berichte</div>
                                </a>
                            </div>
                        </div>
                    </div>
                    
                    <div class="dashboard-card">
                        <div class="dashboard-card-header">
                            <h3 class="dashboard-card-title">Letzte Aktivitäten</h3>
                            <a href="#logs" data-page="logs" style="font-size: 0.875rem; color: var(--primary);">Alle anzeigen</a>
                        </div>
                        <div class="dashboard-card-body">
                            <div class="activity-list" id="recent-activities">
                                ${this.renderRecentActivities()}
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        `;
        
        // Setup quick action navigation
        container.querySelectorAll('.quick-action[data-page]').forEach(action => {
            action.addEventListener('click', (e) => {
                e.preventDefault();
                const page = action.dataset.page;
                this.loadPage(page);
                document.querySelectorAll('.nav-item').forEach(nav => nav.classList.remove('active'));
                document.querySelector(`.nav-item[data-page="${page}"]`)?.classList.add('active');
            });
        });
        
        // Initialize chart with light theme colors
        setTimeout(() => this.initActivityChart(), 100);
    },
    
    renderRecentActivities() {
        const activities = [
            { icon: '✍️', title: 'Neue Writing-Abgabe', meta: 'von Max Mustermann', time: '2 Min.', color: 'blue' },
            { icon: '🎤', title: 'Speaking-Aufnahme', meta: 'Teil 2 - B1 Level', time: '15 Min.', color: 'purple' },
            { icon: '👤', title: 'Neuer Benutzer', meta: 'Anna Schmidt registriert', time: '1 Std.', color: 'green' },
            { icon: '💎', title: 'Premium Upgrade', meta: '€29.90 Umsatz', time: '2 Std.', color: 'yellow' },
            { icon: '⚠️', title: 'System Warnung', meta: 'CPU Auslastung > 80%', time: '3 Std.', color: 'red' },
        ];
        
        return activities.map(a => `
            <div class="activity-item">
                <div class="activity-icon" style="background: ${a.color}20; color: ${a.color};">${a.icon}</div>
                <div class="activity-content">
                    <div class="activity-title">${a.title}</div>
                    <div class="activity-meta">${a.meta}</div>
                </div>
                <div class="activity-time">${a.time}</div>
            </div>
        `).join('');
    },
    
    initActivityChart() {
        const ctx = document.getElementById('activityChart');
        if (!ctx) return;
        
        // Light theme colors
        const primaryColor = '#6366f1';
        const primaryLight = 'rgba(99, 102, 241, 0.15)';
        const successColor = '#10b981';
        const successLight = 'rgba(16, 185, 129, 0.15)';
        const gridColor = 'rgba(148, 163, 184, 0.2)';
        const textColor = '#64748b';
        
        this.charts.activity = new Chart(ctx, {
            type: 'line',
            data: {
                labels: ['00:00', '04:00', '08:00', '12:00', '16:00', '20:00', '23:59'],
                datasets: [{
                    label: 'Aktive Benutzer',
                    data: [45, 30, 120, 280, 350, 420, 180],
                    borderColor: primaryColor,
                    backgroundColor: primaryLight,
                    borderWidth: 3,
                    pointRadius: 4,
                    pointHoverRadius: 6,
                    pointBackgroundColor: primaryColor,
                    pointBorderColor: '#fff',
                    pointBorderWidth: 2,
                    tension: 0.4,
                    fill: true
                }, {
                    label: 'Fragen beantwortet',
                    data: [120, 80, 450, 890, 1200, 1500, 600],
                    borderColor: successColor,
                    backgroundColor: successLight,
                    borderWidth: 3,
                    pointRadius: 4,
                    pointHoverRadius: 6,
                    pointBackgroundColor: successColor,
                    pointBorderColor: '#fff',
                    pointBorderWidth: 2,
                    tension: 0.4,
                    fill: true
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                interaction: {
                    intersect: false,
                    mode: 'index'
                },
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: {
                            usePointStyle: true,
                            pointStyle: 'circle',
                            padding: 20,
                            font: {
                                size: 12,
                                family: "'Plus Jakarta Sans', sans-serif"
                            },
                            color: textColor
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        grid: {
                            color: gridColor,
                            drawBorder: false
                        },
                        ticks: {
                            color: textColor,
                            font: {
                                size: 11,
                                family: "'Plus Jakarta Sans', sans-serif"
                            }
                        }
                    },
                    x: {
                        grid: {
                            display: false,
                            drawBorder: false
                        },
                        ticks: {
                            color: textColor,
                            font: {
                                size: 11,
                                family: "'Plus Jakarta Sans', sans-serif"
                            }
                        }
                    }
                }
            }
        });
    },
    
    // ========== USERS ==========
    async loadUsers(container) {
        container.innerHTML = `
            <div class="page-title">
                <h1>Benutzerverwaltung</h1>
                <p>Verwalten Sie Benutzer, Rollen und Berechtigungen.</p>
            </div>
            
            <div class="card">
                <div class="card-header" style="display: flex; gap: 1rem; flex-wrap: wrap; align-items: center;">
                    <input type="text" id="user-search" placeholder="🔍 Benutzer suchen..." 
                           style="flex: 1; min-width: 200px; padding: 0.875rem 1.25rem; border-radius: 9999px; border: 1px solid var(--border-color); background: var(--bg-tertiary); font-size: 0.9375rem; transition: all 0.2s;">
                    <select id="user-filter" style="padding: 0.875rem 1.25rem; border-radius: 9999px; border: 1px solid var(--border-color); background: var(--bg-tertiary); font-size: 0.9375rem; min-width: 140px;">
                        <option value="">📊 Alle Status</option>
                        <option value="premium">💎 Premium</option>
                        <option value="free">🆓 Free</option>
                        <option value="trial">🎯 Trial</option>
                    </select>
                    <button class="btn btn-primary" onclick="AdminApp.showAddUserModal()">
                        <span>➕</span>
                        Benutzer hinzufügen
                    </button>
                </div>
                <div class="card-body" style="padding: 0;">
                    <div class="table-container">
                        <table class="data-table" id="users-table">
                            <thead>
                                <tr>
                                    <th>Benutzer</th>
                                    <th>Status</th>
                                    <th>Level</th>
                                    <th>Letzte Aktivität</th>
                                    <th style="text-align: right;">Aktionen</th>
                                </tr>
                            </thead>
                            <tbody id="users-tbody">
                                <tr><td colspan="5" style="text-align: center; padding: 3rem;"><div class="loading"></div></td></tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        `;
        
        await this.loadUsersData();
    },
    
    async loadUsersData(page = 1) {
        try {
            const search = document.getElementById('user-search')?.value || '';
            const status = document.getElementById('user-filter')?.value || '';
            
            const params = new URLSearchParams({ page, per_page: 20 });
            if (search) params.append('search', search);
            if (status) params.append('status', status);
            
            const response = await fetch(`${API_BASE}/admin/users.php?${params}`, {
                headers: { 'Authorization': `Bearer ${getToken()}` }
            });
            
            if (!response.ok) throw new Error('Failed to load users');
            const data = await response.json();
            
            this.data.users = data.users || [];
            this.data.usersPagination = data.pagination;
        } catch(e) {
            console.error('Failed to load users:', e);
            Toast.error('Benutzer konnten nicht geladen werden');
            this.data.users = [];
        }
        
        this.renderUsersTable();
    },
    
    renderUsersTable() {
        const tbody = document.getElementById('users-tbody');
        if (!tbody) return;
        
        const users = this.data.users;
        
        if (users.length === 0) {
            tbody.innerHTML = `<tr><td colspan="5" style="text-align: center; padding: 3rem; color: var(--text-tertiary);">Keine Benutzer gefunden</td></tr>`;
            return;
        }
        
        tbody.innerHTML = users.map(user => {
            const statusClass = user.subscription_status === 'premium' ? 'badge-success' : 
                               user.subscription_status === 'trialing' ? 'badge-warning' : 'badge-info';
            const statusText = user.subscription_status === 'premium' ? 'Premium' : 
                              user.subscription_status === 'trialing' ? 'Trial' : 'Free';
            
            return `
            <tr>
                <td>
                    <div class="user-cell">
                        <div class="user-avatar-sm">${(user.name || user.email).charAt(0).toUpperCase()}</div>
                        <div class="user-info-sm">
                            <h4>${Security.escapeHtml(user.name || '')}</h4>
                            <p>${Security.escapeHtml(user.email)}</p>
                        </div>
                    </div>
                </td>
                <td><span class="badge ${statusClass}">${statusText}</span></td>
                <td><span class="badge badge-info">${user.level || 'A2'}</span></td>
                <td style="color: var(--text-secondary); font-size: 0.9375rem;">${user.last_activity || 'Nie'}</td>
                <td style="text-align: right;">
                    <button class="btn btn-sm btn-secondary" onclick="AdminApp.editUser(${user.id})" style="margin-right: 0.5rem;">✏️</button>
                    <button class="btn btn-sm btn-danger" onclick="AdminApp.deleteUser(${user.id})">🗑️</button>
                </td>
            </tr>
        `}).join('');
    },
    
    // ========== QUESTIONS ==========
    async loadQuestions(container) {
        container.innerHTML = `
            <div class="page-header">
                <h1>Fragenverwaltung</h1>
                <p>Erstellen und verwalten Sie Fragen für alle Module.</p>
            </div>
            
            <div class="dashboard-card">
                <div class="dashboard-card-header" style="display: flex; gap: 1rem; flex-wrap: wrap;">
                    <input type="text" id="question-search" placeholder="Frage suchen..." 
                           style="flex: 1; min-width: 200px; padding: 0.75rem 1rem; border-radius: 10px; border: 1px solid var(--glass-border); background: var(--bg);">
                    <select id="module-filter" style="padding: 0.75rem; border-radius: 10px; border: 1px solid var(--glass-border); background: var(--bg);">
                        <option value="">Alle Module</option>
                        <option value="lesen">Lesen</option>
                        <option value="hoeren">Hören</option>
                        <option value="schreiben">Schreiben</option>
                        <option value="sprechen">Sprechen</option>
                        <option value="lid">LiD</option>
                    </select>
                    <button class="btn btn-primary" onclick="AdminApp.showAddQuestionModal()">
                        ➕ Frage erstellen
                    </button>
                </div>
                <div class="dashboard-card-body" style="padding: 0;">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Modul</th>
                                <th>Teil</th>
                                <th>Level</th>
                                <th>Typ</th>
                                <th>Status</th>
                                <th>Aktionen</th>
                            </tr>
                        </thead>
                        <tbody id="questions-tbody">
                            <tr>
                                <td colspan="7" style="text-align: center; padding: 2rem;">Loading...</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        `;
        
        await this.loadQuestionsData();
    },
    
    async loadQuestionsData(page = 1) {
        try {
            const module = document.getElementById('module-filter')?.value || '';
            
            const params = new URLSearchParams({ page, per_page: 20 });
            if (module) params.append('module', module);
            
            const response = await fetch(`${API_BASE}/admin/questions.php?${params}`, {
                headers: { 'Authorization': `Bearer ${getToken()}` }
            });
            
            if (!response.ok) throw new Error('Failed to load questions');
            const data = await response.json();
            
            this.data.questions = data.questions || [];
            this.data.questionsPagination = data.pagination;
        } catch(e) {
            console.error('Failed to load questions:', e);
            Toast.error('Fragen konnten nicht geladen werden');
            this.data.questions = [];
        }
        
        this.renderQuestionsTable();
    },
    
    renderQuestionsTable() {
        const tbody = document.getElementById('questions-tbody');
        if (!tbody) return;
        
        if (this.data.questions.length === 0) {
            tbody.innerHTML = '<tr><td colspan="7" style="text-align: center; padding: 2rem;">Keine Fragen gefunden</td></tr>';
            return;
        }
        
        tbody.innerHTML = this.data.questions.map(q => `
            <tr>
                <td>#${q.id}</td>
                <td><span class="status-badge ${q.module}">${q.module}</span></td>
                <td>Teil ${q.teil}</td>
                <td>${q.level}</td>
                <td>${q.question_type}</td>
                <td>
                    <span class="status-badge ${q.is_active ? 'active' : 'inactive'}">
                        ${q.is_active ? 'Aktiv' : 'Inaktiv'}
                    </span>
                </td>
                <td>
                    <button class="btn btn-sm btn-secondary" onclick="AdminApp.editQuestion(${q.id})">Bearbeiten</button>
                    <button class="btn btn-sm btn-danger" onclick="AdminApp.deleteQuestion(${q.id})">Löschen</button>
                </td>
            </tr>
        `).join('');
    },
    
    // ========== CONTENT REVIEW ==========
    async loadContent(container) {
        container.innerHTML = `
            <div class="page-header">
                <h1>Inhaltsprüfung</h1>
                <p>Überprüfen Sie Writing- und Speaking-Abgaben.</p>
            </div>
            
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem;">
                <div class="dashboard-card">
                    <div class="dashboard-card-header">
                        <h3 class="dashboard-card-title">📝 Writing Abgaben</h3>
                        <span class="nav-badge">8</span>
                    </div>
                    <div class="dashboard-card-body" id="writing-submissions">
                        ${this.renderSubmissionsList('writing')}
                    </div>
                </div>
                
                <div class="dashboard-card">
                    <div class="dashboard-card-header">
                        <h3 class="dashboard-card-title">🎤 Speaking Aufnahmen</h3>
                        <span class="nav-badge">4</span>
                    </div>
                    <div class="dashboard-card-body" id="speaking-submissions">
                        ${this.renderSubmissionsList('speaking')}
                    </div>
                </div>
            </div>
        `;
    },
    
    renderSubmissionsList(type) {
        const items = type === 'writing' 
            ? [
                { id: 1, user: 'Max Mustermann', type: 'Bewerbung', status: 'pending', date: '2024-03-30' },
                { id: 2, user: 'Anna Schmidt', type: 'Beschwerde', status: 'ai_reviewed', date: '2024-03-30' },
                { id: 3, user: 'Peter Müller', type: 'Einladung', status: 'pending', date: '2024-03-29' },
            ]
            : [
                { id: 1, user: 'Lisa Weber', teil: 2, status: 'pending', date: '2024-03-30' },
                { id: 2, user: 'Tom Becker', teil: 1, status: 'ai_processing', date: '2024-03-29' },
            ];
        
        return items.map(item => `
            <div class="activity-item" style="cursor: pointer;" onclick="AdminApp.review${type === 'writing' ? 'Writing' : 'Speaking'}(${item.id})">
                <div class="activity-icon">${type === 'writing' ? '📝' : '🎤'}</div>
                <div class="activity-content">
                    <div class="activity-title">${item.user}</div>
                    <div class="activity-meta">${type === 'writing' ? item.type : `Teil ${item.teil}`} • ${item.date}</div>
                </div>
                <span class="status-badge ${item.status === 'pending' ? 'pending' : 'active'}">
                    ${item.status}
                </span>
            </div>
        `).join('');
    },
    
    // ========== UTILITIES ==========
    handleSearch(query) {
        console.log('Searching:', query);
        // Implement search logic
    },
    
    debounce(fn, ms) {
        let timeout;
        return (...args) => {
            clearTimeout(timeout);
            timeout = setTimeout(() => fn(...args), ms);
        };
    },
    
    startRealtimeUpdates() {
        // Update stats every 30 seconds
        setInterval(() => {
            if (this.currentPage === 'dashboard') {
                this.updateBadgeCounts();
            }
        }, 30000);
    },
    
    async updateBadgeCounts() {
        try {
            const response = await fetch(`${API_BASE}/admin/stats.php?quick=1`, {
                headers: { 'Authorization': `Bearer ${getToken()}` }
            });
            if (!response.ok) return;
            const stats = await response.json();
            
            const contentBadge = document.getElementById('pending-content-badge');
            if (contentBadge) contentBadge.textContent = stats.pending_content || 0;
            
            // Update any other badges
            const usersBadge = document.getElementById('pending-users-badge');
            if (usersBadge && stats.pending_users) usersBadge.textContent = stats.pending_users;
        } catch(e) {
            // Silent fail
        }
    },
    
    // Mock data for development
    getMockStats() {
        return {
            total_users: 1250,
            new_users_today: 12,
            premium_users: 180,
            premium_growth: 15,
            pending_content: 12,
            monthly_revenue: 5240,
            revenue_growth: 8
        };
    },
    
    getMockUsers() {
        return Array.from({ length: 20 }, (_, i) => ({
            id: i + 1,
            email: `user${i + 1}@example.com`,
            name: `Benutzer ${i + 1}`,
            subscription_status: ['free', 'trial', 'premium'][Math.floor(Math.random() * 3)],
            level: ['A1', 'A2', 'B1'][Math.floor(Math.random() * 3)],
            last_activity: ['Gerade eben', 'Vor 1 Std.', 'Vor 2 Std.', 'Gestern'][Math.floor(Math.random() * 4)]
        }));
    },
    
    getMockQuestions() {
        return Array.from({ length: 15 }, (_, i) => ({
            id: i + 1,
            module: ['lesen', 'hoeren', 'lid'][Math.floor(Math.random() * 3)],
            teil: Math.floor(Math.random() * 5) + 1,
            level: ['A1', 'A2', 'B1'][Math.floor(Math.random() * 3)],
            question_type: 'multiple_choice',
            is_active: Math.random() > 0.2
        }));
    },
    
    // Action handlers
    showAddUserModal() { 
        Modal.create({
            title: 'Benutzer hinzufügen',
            content: `
                <form id="add-user-form">
                    <div class="form-group">
                        <label>Name</label>
                        <input type="text" name="name" required class="form-control">
                    </div>
                    <div class="form-group">
                        <label>E-Mail</label>
                        <input type="email" name="email" required class="form-control">
                    </div>
                    <div class="form-group">
                        <label>Passwort</label>
                        <input type="password" name="password" required class="form-control" minlength="8">
                    </div>
                    <div class="form-group">
                        <label>Level</label>
                        <select name="level" class="form-control">
                            <option value="A1">A1</option>
                            <option value="A2" selected>A2</option>
                            <option value="B1">B1</option>
                            <option value="B2">B2</option>
                        </select>
                    </div>
                    <button type="submit" class="btn btn-primary">Erstellen</button>
                </form>
            `
        });
        
        document.getElementById('add-user-form')?.addEventListener('submit', async (e) => {
            e.preventDefault();
            const formData = new FormData(e.target);
            const data = Object.fromEntries(formData);
            
            try {
                const response = await fetch(`${API_BASE}/admin/users.php`, {
                    method: 'POST',
                    headers: { 
                        'Authorization': `Bearer ${getToken()}`,
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify(data)
                });
                
                if (response.ok) {
                    Toast.success('Benutzer erstellt');
                    this.loadUsersData();
                } else {
                    const error = await response.json();
                    Toast.error(error.error || 'Fehler beim Erstellen');
                }
            } catch(err) {
                Toast.error('Verbindungsfehler');
            }
        });
    },
    
    editUser(id) { 
        // Load user and show edit modal
        fetch(`${API_BASE}/admin/users.php?id=${id}`, {
            headers: { 'Authorization': `Bearer ${getToken()}` }
        })
        .then(r => r.json())
        .then(data => {
            const user = data.user;
            Modal.create({
                title: 'Benutzer bearbeiten',
                content: `
                    <form id="edit-user-form">
                        <input type="hidden" name="id" value="${id}">
                        <div class="form-group">
                            <label>Name</label>
                            <input type="text" name="name" value="${Security.escapeHtml(user.name)}" class="form-control">
                        </div>
                        <div class="form-group">
                            <label>Level</label>
                            <select name="level" class="form-control">
                                <option value="A1" ${user.level === 'A1' ? 'selected' : ''}>A1</option>
                                <option value="A2" ${user.level === 'A2' ? 'selected' : ''}>A2</option>
                                <option value="B1" ${user.level === 'B1' ? 'selected' : ''}>B1</option>
                                <option value="B2" ${user.level === 'B2' ? 'selected' : ''}>B2</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Status</label>
                            <select name="subscription_status" class="form-control">
                                <option value="free" ${user.subscription_status === 'free' ? 'selected' : ''}>Free</option>
                                <option value="trialing" ${user.subscription_status === 'trialing' ? 'selected' : ''}>Trial</option>
                                <option value="premium" ${user.subscription_status === 'premium' ? 'selected' : ''}>Premium</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>
                                <input type="checkbox" name="is_active" ${user.is_active ? 'checked' : ''}>
                                Aktiv
                            </label>
                        </div>
                        <button type="submit" class="btn btn-primary">Speichern</button>
                    </form>
                `
            });
            
            document.getElementById('edit-user-form')?.addEventListener('submit', async (e) => {
                e.preventDefault();
                const formData = new FormData(e.target);
                const data = {
                    id: formData.get('id'),
                    name: formData.get('name'),
                    level: formData.get('level'),
                    subscription_status: formData.get('subscription_status'),
                    is_active: formData.get('is_active') === 'on'
                };
                
                try {
                    const response = await fetch(`${API_BASE}/admin/users.php`, {
                        method: 'PUT',
                        headers: { 
                            'Authorization': `Bearer ${getToken()}`,
                            'Content-Type': 'application/json'
                        },
                        body: JSON.stringify(data)
                    });
                    
                    if (response.ok) {
                        Toast.success('Benutzer aktualisiert');
                        this.loadUsersData();
                    } else {
                        Toast.error('Fehler beim Speichern');
                    }
                } catch(err) {
                    Toast.error('Verbindungsfehler');
                }
            });
        });
    },
    
    async deleteUser(id) { 
        if (!confirm(`Benutzer #${id} wirklich löschen?`)) return;
        
        try {
            const response = await fetch(`${API_BASE}/admin/users.php?id=${id}`, {
                method: 'DELETE',
                headers: { 'Authorization': `Bearer ${getToken()}` }
            });
            
            if (response.ok) {
                Toast.success('Benutzer gelöscht');
                this.loadUsersData();
            } else {
                Toast.error('Fehler beim Löschen');
            }
        } catch(err) {
            Toast.error('Verbindungsfehler');
        }
    },
    
    showAddQuestionModal() { window.location.href = 'questions.html?action=create'; },
    editQuestion(id) { window.location.href = `questions.html?action=edit&id=${id}`; },
    
    async deleteQuestion(id) {
        if (!confirm(`Frage #${id} wirklich löschen?`)) return;
        
        try {
            const response = await fetch(`${API_BASE}/admin/questions.php?id=${id}`, {
                method: 'DELETE',
                headers: { 'Authorization': `Bearer ${getToken()}` }
            });
            
            if (response.ok) {
                Toast.success('Frage gelöscht');
                this.loadQuestionsData();
            } else {
                Toast.error('Fehler beim Löschen');
            }
        } catch(err) {
            Toast.error('Verbindungsfehler');
        }
    },
    
    reviewWriting(id) { window.location.href = `writing.html?id=${id}`; },
    reviewSpeaking(id) { window.location.href = `speaking-review.html?id=${id}`; },
    loadAnalytics(c) { c.innerHTML = '<div class="page-header"><h1>Analytics</h1><p>Detaillierte Statistiken und Berichte.</p></div><div class="dashboard-card"><div class="dashboard-card-body"><canvas id="analyticsChart"></canvas></div></div>'; setTimeout(() => this.initActivityChart(), 100); },
    loadSettings(c) { c.innerHTML = '<div class="page-header"><h1>Einstellungen</h1><p>Systemeinstellungen verwalten.</p></div><div class="dashboard-card"><div class="dashboard-card-body">Einstellungen werden geladen...</div></div>'; },
    loadLogs(c) { c.innerHTML = '<div class="page-header"><h1>System Logs</h1><p>Audit Logs und Fehlerberichte.</p></div><div class="dashboard-card"><div class="dashboard-card-body">Logs werden geladen...</div></div>'; },
    loadHealth(c) { 
        c.innerHTML = '<div class="page-header"><h1>System Health</h1><p>Systemstatus und Monitoring.</p></div><div class="dashboard-card"><div class="dashboard-card-body" id="health-status">Checking...</div></div>';
        fetch('/api/health.php').then(r => r.json()).then(data => {
            document.getElementById('health-status').innerHTML = `<pre>${JSON.stringify(data, null, 2)}</pre>`;
        }).catch(() => {
            document.getElementById('health-status').innerHTML = '<div class="error">Health check failed</div>';
        });
    }
};

// Initialize when DOM is ready
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => AdminApp.init());
} else {
    AdminApp.init();
}

// Expose for debugging
window.AdminApp = AdminApp;
