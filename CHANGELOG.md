# Changelog

All notable changes to `laravel-mobile-jump` will be documented here.

## [Unreleased]

## [1.0.0] — 2026-08-11

### Added
- `mobile:jump` artisan command with terminal QR code display
- `mobile:jump:install` interactive install wizard
- `MobileJumpServiceProvider` with Laravel auto-discovery
- Pluggable session storage: Redis, Database, File backends
- `SessionStoreInterface` contract for custom store implementations
- `MobileSessionService` — store-agnostic, fully unit-testable
- API endpoints: `connect`, `status`, `qr`, `disconnect`
- Browser dashboard at `/{prefix}/dashboard`
- Database migration for `mobile_jump_sessions` table
- Pre-built Android APK (`android/MobileJump.apk`)
- Vue 3 composable stub: `useMobileJump.js`
- React hook stub: `useMobileJump.ts`
- Platform detection helpers: `platform.js` / `platform.ts`
- PHPUnit tests with Orchestra Testbench
