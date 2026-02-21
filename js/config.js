// Use your exact Termux IP
const API_BASE_URL = 'http://10.191.152.229:8000';

const API_ENDPOINTS = {
    login: `${API_BASE_URL}/api/login`,
    register: `${API_BASE_URL}/api/register`,
    profile: `${API_BASE_URL}/api/profile`,
    viewProfile: `${API_BASE_URL}/api/view-profile`,
    settings: `${API_BASE_URL}/api/settings`,
    leaderboard: `${API_BASE_URL}/api/leaderboard`,
    selectTest: `${API_BASE_URL}/api/select-test`,
    takeTest: `${API_BASE_URL}/api/take-test`,
    submitTest: `${API_BASE_URL}/api/submit-test`,
    history: `${API_BASE_URL}/api/history`,
    messages: `${API_BASE_URL}/api/messages`,
    notifications: `${API_BASE_URL}/api/notifications`,
    google: `${API_BASE_URL}/api/google`,
    logout: `${API_BASE_URL}/api/logout`,
    testActions: `${API_BASE_URL}/api/test-actions`,
    upload: `${API_BASE_URL}/api/upload`
};

// Make sure it's available globally
window.API_BASE_URL = API_BASE_URL;
window.API_ENDPOINTS = API_ENDPOINTS;
