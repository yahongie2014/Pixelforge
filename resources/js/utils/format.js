/**
 * Centralized formatting utilities for currency, tokens and dates.
 * Import from this file instead of re-defining formatters in each component.
 */

/**
 * Format a numeric string as USD.
 * @param {string|number|null} val
 * @param {number} decimals
 * @returns {string}
 */
export function formatUSD(val, decimals = 4) {
    if (val === null || val === undefined || val === '') return '$0.0000';
    const num = Number(val);
    return '$' + num.toLocaleString('en-US', {
        minimumFractionDigits: decimals,
        maximumFractionDigits: decimals,
    });
}

/**
 * Format a numeric value to 9 decimal places (full precision for provider costs).
 * @param {string|number|null} val
 * @returns {string}
 */
export function formatUSDExact(val) {
    if (val === null || val === undefined) return '$0.000000000';
    return '$' + Number(val).toFixed(9);
}

/**
 * Format a token count with locale-aware thousands separators.
 * @param {string|number|null} val
 * @returns {string}
 */
export function formatTokens(val) {
    return Number(val || 0).toLocaleString();
}

/**
 * Format a balance for display in the nav (2–4 dp).
 * @param {string|number|null} val
 * @returns {string}
 */
export function formatBalance(val) {
    return Number(val || 0).toLocaleString('en-US', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 4,
    });
}

/**
 * Format a datetime string to "HH:MM · Mon D" display.
 * @param {string|null} dateStr
 * @returns {string}
 */
export function formatDate(dateStr) {
    if (!dateStr) return '';
    const d = new Date(dateStr);
    return (
        d.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' }) +
        ' · ' +
        d.toLocaleDateString([], { month: 'short', day: 'numeric' })
    );
}
