/**
 * User Scope helpers — ensures every user's POS data is namespaced
 * separately in IndexedDB so no two users ever share cached records.
 */

const SCOPE_KEY = 'pos_user_scope';

export interface UserScope {
  userId: number;
  businessId: number;
}

/** Read the current scope from localStorage (SSR-safe). */
export function getUserScope(): UserScope | null {
  if (typeof window === 'undefined') return null;
  try {
    const raw = localStorage.getItem(SCOPE_KEY);
    if (!raw) return null;
    return JSON.parse(raw) as UserScope;
  } catch {
    return null;
  }
}

/** Persist the scope for the current session. */
export function setUserScope(userId: number, businessId: number): void {
  if (typeof window === 'undefined') return;
  localStorage.setItem(SCOPE_KEY, JSON.stringify({ userId, businessId }));
}

/** Remove scope — typically called on logout. */
export function clearUserScope(): void {
  if (typeof window === 'undefined') return;
  localStorage.removeItem(SCOPE_KEY);
}

/**
 * Build a namespaced cache key.
 * e.g. getScopedKey('catalog_data') → 'u12_b3_catalog_data'
 * Falls back to 'unscoped_<base>' when no session is active yet.
 */
export function getScopedKey(base: string): string {
  const scope = getUserScope();
  if (!scope) return `unscoped_${base}`;
  return `u${scope.userId}_b${scope.businessId}_${base}`;
}

/**
 * Returns whether the incoming identity matches the stored scope.
 * If they differ, the caller MUST wipe all cached data before proceeding.
 */
export function isSameUser(userId: number, businessId: number): boolean {
  const scope = getUserScope();
  return scope !== null && scope.userId === userId && scope.businessId === businessId;
}
