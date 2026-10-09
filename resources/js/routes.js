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
    accounts: {
        index: endpoint('/accounts'),
        show: (id) => endpoint(`/accounts/${id}`),
        store: endpoint('/accounts'),
        update: (id) => endpoint(`/accounts/${id}`),
        destroy: (id) => endpoint(`/accounts/${id}`),
        bulkDestroy: endpoint('/accounts'),
        generate: endpoint('/accounts/generate'),
        reorder: endpoint('/accounts/order'),
        summary: (id) => endpoint(`/accounts/${id}/summary`),
        generateRecords: (id) => endpoint(`/accounts/${id}/records/generate`),
        deleteRecords: (id) => endpoint(`/accounts/${id}/records`),
    },
    records: {
        index: endpoint('/records'),
        show: (id) => endpoint(`/records/${id}`),
        store: endpoint('/records'),
        update: (id) => endpoint(`/records/${id}`),
        destroy: (id) => endpoint(`/records/${id}`),
        bulkDestroy: endpoint('/records'),
        generate: endpoint('/records/generate'),
        summary: endpoint('/records/summary'),
        export: (format) => endpoint(`/records/export/${format}`),
    },
    categories: {
        index: endpoint('/categories'),
        show: (id) => endpoint(`/categories/${id}`),
        store: endpoint('/categories'),
        update: (id) => endpoint(`/categories/${id}`),
        destroy: (id) => endpoint(`/categories/${id}`),
        bulkDestroy: endpoint('/categories'),
        generate: endpoint('/categories/generate'),
    },
    pages: {
        home: '/',
        login: '/login',
        verifyEmail: '/email/verify',
        dashboard: '/dashboard',
        profile: '/profile',
        accounts: '/accounts',
        accountCreate: '/accounts/create',
        accountEdit: (id) => `/accounts/${id}/edit`,
        accountSummary: (id) => `/accounts/${id}/summary`,
        records: '/records',
        recordCreate: '/records/create',
        recordEdit: (id) => `/records/${id}/edit`,
        categories: '/categories',
        categoryCreate: '/categories/create',
        categoryEdit: (id) => `/categories/${id}/edit`,
    },
};
