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

    const isForm = payload instanceof FormData;

    // A FormData body sets its own multipart Content-Type (with the boundary).
    if (payload !== undefined && !isForm) {
        headers['Content-Type'] = 'application/json';
    }

    const response = await fetch(url, {
        method,
        headers,
        credentials: 'same-origin',
        body: payload === undefined || isForm ? payload : JSON.stringify(payload),
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

/**
 * POST a FormData body (e.g. a file upload). With `onProgress`, the percentage of the body sent
 * (0-100) is reported while it goes out; fetch cannot do that, so this goes through XMLHttpRequest.
 */
export function upload(url, formData, onProgress) {
    if (!onProgress) {
        return request(url, { method: 'POST', payload: formData });
    }

    return new Promise((resolve, reject) => {
        const xhr = new XMLHttpRequest();
        const token = getApiToken();

        xhr.open('POST', url);
        xhr.setRequestHeader('Accept', 'application/json');
        xhr.setRequestHeader('X-CSRF-TOKEN', csrfToken());
        if (token) {
            xhr.setRequestHeader('Authorization', `Bearer ${token}`);
        }

        xhr.upload.addEventListener('progress', (event) => {
            if (event.lengthComputable) {
                onProgress((event.loaded / event.total) * 100);
            }
        });
        xhr.addEventListener('error', () => reject(new Error('Network error')));
        xhr.addEventListener('load', () => {
            if (xhr.status === 401 && window.location.pathname !== routes.pages.login) {
                clearApiToken();
                window.location.href = routes.pages.login;
            }

            let data = {};
            try {
                data = JSON.parse(xhr.responseText);
            } catch {
                data = {};
            }

            resolve({ ok: xhr.status >= 200 && xhr.status < 300, status: xhr.status, data });
        });
        xhr.send(formData);
    });
}

export const put = (url, payload) => request(url, { method: 'PUT', payload });

export const patch = (url, payload) => request(url, { method: 'PATCH', payload });

export const del = (url, payload) => request(url, { method: 'DELETE', payload });
