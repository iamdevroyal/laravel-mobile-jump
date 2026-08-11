/**
 * useMobileJump — Vue 3 composable for the Mobile Jump package.
 *
 * Drop this into your project (e.g. src/composables/useMobileJump.js)
 * and import it wherever you need to detect runner context or derive URLs.
 *
 * Published by: php artisan mobile:jump:install
 */

import { computed, readonly, ref } from 'vue'

// ─── Runtime detection ────────────────────────────────────────────────────────

/**
 * Returns true when the app is running inside the Mobile Jump Android WebView.
 * The native bridge injects `window.MobileJumpNative` on startup.
 */
export function isRunnerApp() {
  return typeof window !== 'undefined' && Boolean(window.MobileJumpNative)
}

/**
 * Returns true when the page is loaded from a device over LAN (not localhost).
 */
export function isLanSession() {
  if (typeof window === 'undefined') return false
  const host = window.location.hostname
  return host !== 'localhost' && host !== '127.0.0.1'
}

// ─── URL helpers ──────────────────────────────────────────────────────────────

/**
 * Derives the API base URL dynamically.
 *
 * - On a LAN IP (phone / runner): points at the same host the page was served from.
 * - On localhost (normal laptop dev): always hits 127.0.0.1 regardless of .env.
 *
 * @param {number} [apiPort=8000] The port Laravel is listening on.
 * @returns {string}
 */
export function getApiBaseUrl(apiPort = 8000) {
  if (typeof window !== 'undefined') {
    const host = window.location.hostname
    if (host !== 'localhost' && host !== '127.0.0.1') {
      return `http://${host}:${apiPort}/api/v1`
    }
  }
  return import.meta?.env?.VITE_API_URL ?? `http://127.0.0.1:${apiPort}/api/v1`
}

/**
 * Returns the WebSocket / Reverb host string.
 * @param {number} [reverbPort=8080]
 * @returns {string}
 */
export function getWsHost(reverbPort = 8080) {
  if (typeof window !== 'undefined') {
    const host = window.location.hostname
    if (host !== 'localhost' && host !== '127.0.0.1') {
      return `${host}:${reverbPort}`
    }
  }
  return `127.0.0.1:${reverbPort}`
}

// ─── Composable ───────────────────────────────────────────────────────────────

/**
 * Vue 3 composable. Returns reactive runtime flags and URL helpers.
 *
 * @example
 * import { useMobileJump } from '@/composables/useMobileJump'
 * const { isRunner, apiBaseUrl } = useMobileJump()
 */
export function useMobileJump(options = {}) {
  const { apiPort = 8000, reverbPort = 8080 } = options

  const isRunner = readonly(ref(isRunnerApp()))
  const isLan    = readonly(ref(isLanSession()))

  const apiBaseUrl = computed(() => getApiBaseUrl(apiPort))
  const wsHost     = computed(() => getWsHost(reverbPort))

  return { isRunner, isLan, apiBaseUrl, wsHost, getApiBaseUrl, getWsHost }
}
