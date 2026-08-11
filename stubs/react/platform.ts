/**
 * platform.ts — Lightweight platform detection helpers for React / TypeScript projects.
 * Published by Mobile Jump (https://github.com/iamdevroyal/laravel-mobile-jump)
 */

declare global {
  interface Window {
    MobileJumpNative?: Record<string, unknown>
  }
}

/** True when running inside the Mobile Jump Android WebView. */
export const IS_RUNNER_APP: boolean =
  typeof window !== 'undefined' && Boolean(window.MobileJumpNative)

/** True when accessed from a LAN IP (phone/runner) rather than localhost. */
export const IS_LAN_SESSION: boolean = (() => {
  if (typeof window === 'undefined') return false
  const host = window.location.hostname
  return host !== 'localhost' && host !== '127.0.0.1'
})()

/** The current LAN IP, or null if on localhost. */
export const LAN_HOST: string | null = IS_LAN_SESSION ? window.location.hostname : null
