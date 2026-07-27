import { apiPost } from './api';

/**
 * @param {string} query
 * @returns {Promise<{ categoryId: number | null, categoryName: string | null, confidence: number | null, source: string | null }>}
 */
export async function fetchCategorySuggestion(query) {
    if (!query || query.length < 2) {
        return { categoryId: null, categoryName: null, confidence: null, source: null };
    }

    const res = await apiPost('/api/transactions/suggestions', { query });

    return {
        categoryId: res.data?.predicted_category_id ?? null,
        categoryName: res.data?.predicted_category_name ?? null,
        confidence: res.data?.confidence ?? null,
        source: res.data?.source ?? null,
    };
}
