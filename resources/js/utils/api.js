/**
 * @param {string} url
 * @param {RequestInit} [options]
 * @returns {Promise<any>}
 */
export async function apiRequest(url, options = {}) {
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

    const response = await fetch(url, {
        ...options,
        credentials: 'same-origin',
        headers: {
            Accept: 'application/json',
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': csrfToken || '',
            'X-Requested-With': 'XMLHttpRequest',
            ...options.headers,
        },
    });

    if (response.status === 204) {
        return null;
    }

    const data = await response.json();

    if (!response.ok) {
        const error = new Error(data.message || 'Request failed');
        error.status = response.status;
        error.errors = data.errors;
        throw error;
    }

    return data;
}

/**
 * @param {string} url
 * @returns {Promise<any>}
 */
export function apiGet(url) {
    return apiRequest(url);
}

/**
 * @param {string} url
 * @param {object} body
 * @returns {Promise<any>}
 */
export function apiPost(url, body) {
    return apiRequest(url, { method: 'POST', body: JSON.stringify(body) });
}

/**
 * @param {string} url
 * @param {object} body
 * @returns {Promise<any>}
 */
export function apiPut(url, body) {
    return apiRequest(url, { method: 'PUT', body: JSON.stringify(body) });
}

/**
 * @param {string} url
 * @returns {Promise<any>}
 */
export function apiDelete(url) {
    return apiRequest(url, { method: 'DELETE' });
}
