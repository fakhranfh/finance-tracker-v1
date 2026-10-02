const API_TOKEN_STORAGE_KEY = 'api_token';

/**
 * Persist the Sanctum API token issued on register/login, for use by any
 * future stateless API calls made from the client.
 */
export function storeApiToken(token) {
    try {
        localStorage.setItem(API_TOKEN_STORAGE_KEY, token);
    } catch {
        // localStorage unavailable (e.g. private browsing) — ignore.
    }
}

export function clearApiToken() {
    try {
        localStorage.removeItem(API_TOKEN_STORAGE_KEY);
    } catch {
        // ignore
    }
}

export function getApiToken() {
    try {
        return localStorage.getItem(API_TOKEN_STORAGE_KEY);
    } catch {
        return null;
    }
}
