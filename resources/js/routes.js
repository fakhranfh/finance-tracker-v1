/**
 * Central registry of REST API endpoints consumed by frontend JS.
 * Never hardcode an `/api/...` path inline in a call site — add it here instead.
 */

export const apiBaseUrl = '/api';

function endpoint(path) {
    return `${apiBaseUrl}${path}`;
}

export const routes = {
    auth: {
        register: endpoint('/register'),
        login: endpoint('/login'),
        logout: endpoint('/logout'),
        devAutoLogin: endpoint('/dev/auto-login'),
        forgotPassword: endpoint('/forgot-password'),
        resetPassword: endpoint('/reset-password'),
        sendVerification: endpoint('/email/verification-notification'),
    },
    profile: {
        show: endpoint('/profile'),
        update: endpoint('/profile'),
    },
    users: {
        index: endpoint('/users'),
        show: (id) => endpoint(`/users/${id}`),
        store: endpoint('/users'),
        update: (id) => endpoint(`/users/${id}`),
        destroy: (id) => endpoint(`/users/${id}`),
        bulkDestroy: endpoint('/users'),
        generate: endpoint('/users/generate'),
    },
    pages: {
        home: '/',
        login: '/login',
        verifyEmail: '/email/verify',
        dashboard: '/dashboard',
        profile: '/profile',
        users: '/users',
        userCreate: '/users/create',
        userEdit: (id) => `/users/${id}/edit`,
    },
};
