// Auth utilities

function getToken() {
    return localStorage.getItem('access_token');
}

function setToken(token) {
    localStorage.setItem('access_token', token);
}

function clearAuth() {
    localStorage.removeItem('access_token');
    localStorage.removeItem('user');
}

function getUser() {
    try {
        const user = localStorage.getItem('user');
        return user ? JSON.parse(user) : null;
    } catch (e) {
        console.error('Error parsing user data:', e);
        localStorage.removeItem('user');
        return null;
    }
}

function setUser(user) {
    localStorage.setItem('user', JSON.stringify(user));
}

function isAuthenticated() {
    return !!getToken();
}

function checkAuth() {
    if (!isAuthenticated()) {
        window.location.href = 'login.html';
        return false;
    }
    return true;
}

function requireAuth() {
    return checkAuth();
}

// API helper with auth
async function api(endpoint, options = {}) {
    const token = getToken();
    
    const defaults = {
        headers: {
            'Content-Type': 'application/json',
            ...(token && { 'Authorization': `Bearer ${token}` }),
            ...options.headers
        }
    };
    
    const response = await fetch(`${API_BASE}${endpoint}`, {
        ...defaults,
        ...options,
        headers: {
            ...defaults.headers,
            ...(options.headers || {})
        }
    });
    
    if (response.status === 401) {
        clearAuth();
        window.location.href = 'login.html';
        return null;
    }
    
    return response;
}

// Logout
function logout() {
    clearAuth();
    window.location.href = 'login.html';
}
