/**
 * DTZ-LID API Client
 */

const API_BASE_URL = '';

class DTZApi {
  constructor() {
    this.token = localStorage.getItem('access_token');
    this.refreshToken = localStorage.getItem('refresh_token');
  }

  // Generic request method
  async request(endpoint, options = {}) {
    const url = `${API_BASE_URL}${endpoint}`;
    
    const config = {
      headers: {
        'Content-Type': 'application/json',
        ...options.headers
      },
      ...options
    };

    // Add auth token if available
    if (this.token) {
      config.headers['Authorization'] = `Bearer ${this.token}`;
    }

    try {
      const response = await fetch(url, config);
      const data = await response.json();

      // Handle token expiration
      if (response.status === 401 && this.refreshToken) {
        const refreshed = await this.refresh();
        if (refreshed) {
          // Retry request with new token
          config.headers['Authorization'] = `Bearer ${this.token}`;
          const retryResponse = await fetch(url, config);
          return await retryResponse.json();
        }
      }

      return data;
    } catch (error) {
      console.error('API Error:', error);
      throw error;
    }
  }

  // Auth endpoints
  async register(userData) {
    const result = await this.request('/api/auth/register.php', {
      method: 'POST',
      body: JSON.stringify(userData)
    });
    
    if (result.success && result.tokens) {
      this.setTokens(result.tokens);
    }
    
    return result;
  }

  async login(credentials) {
    const result = await this.request('/api/auth/login.php', {
      method: 'POST',
      body: JSON.stringify(credentials)
    });
    
    if (result.success && result.tokens) {
      this.setTokens(result.tokens);
    }
    
    return result;
  }

  async logout() {
    await this.request('/api/auth/logout.php', {
      method: 'POST'
    });
    this.clearTokens();
  }

  async me() {
    return await this.request('/api/auth/me.php');
  }

  async refresh() {
    if (!this.refreshToken) return false;
    
    const result = await this.request('/api/auth/refresh.php', {
      method: 'POST',
      body: JSON.stringify({ refresh_token: this.refreshToken })
    });
    
    if (result.success && result.tokens) {
      this.setTokens(result.tokens);
      return true;
    }
    
    return false;
  }

  // Question endpoints
  async getNextQuestion(module = 'lesen', level = null, sessionId = null) {
    const params = new URLSearchParams({ module });
    if (level) params.append('level', level);
    if (sessionId) params.append('session_id', sessionId);
    
    return await this.request(`/api/questions/next.php?${params}`);
  }

  async submitAnswer(data) {
    return await this.request('/api/questions/submit.php', {
      method: 'POST',
      body: JSON.stringify(data)
    });
  }

  async getStats() {
    return await this.request('/api/questions/stats.php');
  }

  async getSessionStats(sessionId) {
    return await this.request(`/api/questions/session.php?session_id=${sessionId}`);
  }

  // Token management
  setTokens(tokens) {
    this.token = tokens.access_token;
    this.refreshToken = tokens.refresh_token;
    localStorage.setItem('access_token', tokens.access_token);
    localStorage.setItem('refresh_token', tokens.refresh_token);
    localStorage.setItem('token_expires', Date.now() + (tokens.expires_in * 1000));
  }

  clearTokens() {
    this.token = null;
    this.refreshToken = null;
    localStorage.removeItem('access_token');
    localStorage.removeItem('refresh_token');
    localStorage.removeItem('token_expires');
  }

  isAuthenticated() {
    return !!this.token;
  }

  // Check if token needs refresh
  shouldRefresh() {
    const expires = localStorage.getItem('token_expires');
    if (!expires) return false;
    // Refresh if less than 5 minutes remaining
    return Date.now() > (expires - 300000);
  }
}

// Create global instance
const api = new DTZApi();

// Auth guard - redirect to login if not authenticated
async function requireAuth() {
  if (!api.isAuthenticated()) {
    window.location.href = '/login.html';
    return false;
  }

  // Verify token is valid
  const user = await api.me();
  if (!user.success) {
    api.clearTokens();
    window.location.href = '/login.html';
    return false;
  }

  return user.user;
}

// Redirect if already authenticated
async function redirectIfAuth() {
  if (api.isAuthenticated()) {
    const user = await api.me();
    if (user.success) {
      window.location.href = '/dashboard.html';
      return true;
    }
  }
  return false;
}

// Format date to German locale
function formatDate(dateString) {
  return new Date(dateString).toLocaleDateString('de-DE', {
    day: '2-digit',
    month: '2-digit',
    year: 'numeric'
  });
}

// Format relative time
function timeAgo(dateString) {
  const date = new Date(dateString);
  const now = new Date();
  const diff = Math.floor((now - date) / 1000);

  if (diff < 60) return 'gerade eben';
  if (diff < 3600) return `vor ${Math.floor(diff / 60)} Minuten`;
  if (diff < 86400) return `vor ${Math.floor(diff / 3600)} Stunden`;
  return `vor ${Math.floor(diff / 86400)} Tagen`;
}

// Module names in German
const MODULE_NAMES = {
  'lesen': 'Lesen',
  'hoeren': 'Hören',
  'schreiben': 'Schreiben',
  'sprechen': 'Sprechen',
  'lid': 'Leben in Deutschland'
};

// Module icons
const MODULE_ICONS = {
  'lesen': '📖',
  'hoeren': '🎧',
  'schreiben': '✍️',
  'sprechen': '🗣️',
  'lid': '🇩🇪'
};
