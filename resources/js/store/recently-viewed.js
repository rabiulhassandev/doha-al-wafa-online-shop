import { readIds, writeIds } from './local-list';

const KEY = 'recently_viewed';
const LIMIT = 12;

export function rememberViewed(id) {
    writeIds(KEY, [id, ...readIds(KEY).filter((viewedId) => viewedId !== id)].slice(0, LIMIT));
}

export function recentlyViewedIds() {
    return readIds(KEY);
}
