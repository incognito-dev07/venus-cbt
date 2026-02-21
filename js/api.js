// API wrapper for all backend calls
const api = {
    async request(endpoint, options = {}) {
        const defaultOptions = {
            credentials: 'include',
            headers: {
                'Content-Type': 'application/json',
            }
        };
        
        const response = await fetch(endpoint, { ...defaultOptions, ...options });
        const data = await response.json();
        
        if (data.redirect) {
            window.location.href = data.redirect;
            return null;
        }
        
        if (!data.success && data.error) {
            throw new Error(data.error);
        }
        
        return data;
    },
    
    async get(endpoint, params = {}) {
        const url = new URL(endpoint);
        Object.keys(params).forEach(key => url.searchParams.append(key, params[key]));
        return this.request(url.toString());
    },
    
    async post(endpoint, body = {}) {
        return this.request(endpoint, {
            method: 'POST',
            body: JSON.stringify(body)
        });
    },
    
    async postForm(endpoint, formData) {
        return this.request(endpoint, {
            method: 'POST',
            body: formData,
            headers: {} // Let browser set content-type for FormData
        });
    }
};

// Auth helpers
const auth = {
    isLoggedIn() {
        return document.cookie.includes('PHPSESSID');
    },
    
    async login(email, password) {
        return api.post(API_ENDPOINTS.login, { email, password });
    },
    
    async register(userData) {
        return api.post(API_ENDPOINTS.register, userData);
    },
    
    logout() {
        window.location.href = API_ENDPOINTS.logout;
    },
    
    getCurrentUser() {
        return api.get(API_ENDPOINTS.profile);
    }
};