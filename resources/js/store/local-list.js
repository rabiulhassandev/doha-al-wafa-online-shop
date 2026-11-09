/**
 * A short list of product IDs kept in the browser. Private windows and blocked storage
 * simply behave as an empty list.
 */
export function readIds(key) {
    try {
        const value = JSON.parse(window.localStorage.getItem(key) ?? '[]');

        return Array.isArray(value) ? value.filter(Number.isInteger) : [];
    } catch {
        return [];
    }
}

export function writeIds(key, ids) {
    try {
        window.localStorage.setItem(key, JSON.stringify(ids));
    } catch {
        // Storage is unavailable; the list just won't be remembered.
    }
}
