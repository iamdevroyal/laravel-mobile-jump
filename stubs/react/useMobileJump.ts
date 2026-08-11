/**
 * useMobileJump — React hook for the Mobile Jump package.
 *
 * Drop this into your project (e.g. src/hooks/useMobileJump.ts)
 * and import it wherever you need runner detection or dynamic URLs.
 *
 * Published by: php artisan mobile:jump:install
 */

import { useMemo } from 'react'

// ─── Runtime detection ────────────────────────────────────────────────────────

declare global {
  interface Window {
    MobileJumpNative?: Record<string, unknown>
  }
}

/** True when running inside the Mobile Jump Android WebView. */
export function isRunnerApp(): boolean {
  return typeof window !== 'undefined' && Boolean(window.MobileJumpNative)
}

/** True when accessed from a LAN IP (phone/runner) rather than localhost. */
export function isLanSession(): boolean {
  if (typeof window === 'undefined') return false
  const host = window.location.hostname
  return host !== 'localhost' && host !== '127.0.0.1'
}

// ─── URL helpers ──────────────────────────────────────────────────────────────

/**
 * Derives the API base URL dynamically.
 * - LAN session  → uses the serving host (same as the page)
 * - Localhost    → uses 127.0.0.1 regardless of env vars
 */
export function getApiBaseUrl(apiPort = 8000): string {
  if (typeof window !== 'undefined') {
    const host = window.location.hostname
    if (host !== 'localhost' && host !== '127.0.0.1') {
      return `http://${host}:${apiPort}/api/v1`
    }
  }
  return (import.meta as any)?.env?.VITE_API_URL ?? `http://127.0.0.1:${apiPort}/api/v1`
}

/** Returns the WebSocket / Reverb host string. */
export function getWsHost(reverbPort = 8080): string {
  if (typeof window !== 'undefined') {
    const host = window.location.hostname
    if (host !== 'localhost' && host !== '127.0.0.1') {
      return `${host}:${reverbPort}`
    }
  }
  return `127.0.0.1:${reverbPort}`
}

// ─── Hook ─────────────────────────────────────────────────────────────────────

interface UseMobileJumpOptions {
  apiPort?: number
  reverbPort?: number
}

interface UseMobileJumpReturn {
  isRunner: boolean
  isLan: boolean
  apiBaseUrl: string
  wsHost: string
}

/**
 * React hook. Returns memoised runtime flags and URL helpers.
 *
 * @example
 * import { useMobileJump } from './hooks/useMobileJump'
 * const { isRunner, apiBaseUrl } = useMobileJump()
 */
export function useMobileJump(options: UseMobileJumpOptions = {}): UseMobileJumpReturn {
  const { apiPort = 8000, reverbPort = 8080 } = options

  const isRunner = useMemo(() => isRunnerApp(), [])
  const isLan    = useMemo(() => isLanSession(), [])
  const apiBaseUrl = useMemo(() => getApiBaseUrl(apiPort), [apiPort])
  const wsHost     = useMemo(() => getWsHost(reverbPort), [reverbPort])

  return { isRunner, isLan, apiBaseUrl, wsHost }
}
