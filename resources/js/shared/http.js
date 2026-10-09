/**
 * Generic fetch client for the app's REST API calls.
 */

import { clearApiToken, getApiToken } from './api-token.js';
import { csrfToken } from './csrf.js';
import { routes } from '../routes.js';

async function request(url, { method = 'GET', payload } = {}) {
    const headers = {
        Accept: 'application/json',
        'X-CSRF-TOKEN': csrfToken(),
    };

    const token = getApiToken();
    if (token) {
        headers.Authorization = `Bearer ${token}`;
    }

    if (payload !== undefined) {
        headers['Content-Type'] = 'application/json';
    }

    const response = await fetch(url, {
        method,
        headers,
        credentials: 'same-origin',
        body: payload !== undefined ? JSON.stringify(payload) : undefined,
    });

    if (response.status === 401 && window.location.pathname !== routes.pages.login) {
        clearApiToken();
        window.location.href = routes.pages.login;
    }

    let data = {};
    try {
        data = await response.json();
    } catch {
        data = {};
    }

    return { ok: response.ok, status: response.status, data };
}

export const get = (url) => request(url, { method: 'GET' });

/**
 * GET every page of a paginated list endpoint and return all items.
 * Resolves to null when any page request fails.
 */
export async function getAll(url) {
    const separator = url.includes('?') ? '&' : '?';
    const items = [];
    let page = 1;
    let lastPage = 1;

    do {
        const { ok, data } = await get(`${url}${separator}per_page=100&page=${page}`);

        if (!ok) {
            return null;
        }

        items.push(...data.data);
        lastPage = data.meta?.last_page ?? 1;
        page += 1;
    } while (page <= lastPage);

    return items;
}

export const post = (url, payload) => request(url, { method: 'POST', payload });

export const put = (url, payload) => request(url, { method: 'PUT', payload });

export const patch = (url, payload) => request(url, { method: 'PATCH', payload });

export const del = (url, payload) => request(url, { method: 'DELETE', payload });
