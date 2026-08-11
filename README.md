# Mobile Jump — Laravel Package

[![Latest Version on Packagist](https://img.shields.io/packagist/v/iamdevroyal/laravel-mobile-jump.svg)](https://packagist.org/packages/iamdevroyal/laravel-mobile-jump)
[![License: MIT](https://img.shields.io/badge/License-MIT-green.svg)](LICENSE)

**Scan a QR code and instantly preview your Laravel app on any Android phone over local Wi-Fi.**

Mobile Jump creates a secure, ephemeral development session between your laptop and an Android device — no tunnelling services, no internet required. Run `php artisan mobile:jump`, scan the QR with the companion app, and your phone loads your local app in seconds.

---

## Features

- 🔌 **Zero-config** — auto-detects your LAN IP, no manual configuration needed
- 📦 **Pluggable storage** — Redis (fastest), Database, or File fallback
- 🖥️ **Browser dashboard** — live session status at `/mobile-jump/dashboard`
- 📱 **Android companion app** — pre-built APK included, no Play Store required
- 🧩 **Frontend stubs** — ready-to-use Vue 3 composable and React hook
- ✅ **Fully tested** — PHPUnit + Orchestra Testbench

---

## Requirements

- PHP **8.2+**
- Laravel **11.x** or **12.x**
- An Android phone on the same Wi-Fi network as your laptop

---

## Installation

### 1. Install via Composer

```bash
composer require iamdevroyal/laravel-mobile-jump
```

The service provider is auto-discovered — no manual registration needed.

### 2. Run the install wizard

```bash
php artisan mobile:jump:install
```

This will:
- Publish `config/mobile-jump.php`
- Copy the Android APK to `public/vendor/mobile-jump/MobileJump.apk`
- Check your storage backend (Redis / DB / File)
- Scaffold a Vue or React frontend stub (optional)

### 3. (Optional) Publish and run the database migration

Only needed when `MOBILE_JUMP_STORAGE=database`:

```bash
php artisan vendor:publish --tag=mobile-jump-migrations
php artisan migrate
```

---

## Usage

### Start a session

```bash
# Ensure your servers are bound to all interfaces:
php artisan serve --host=0.0.0.0
npm run dev -- --host 0.0.0.0

# Then start Mobile Jump:
php artisan mobile:jump
```

A compact QR code appears in your terminal. Scan it with the Mobile Jump app on your Android phone.

### Command options

```
Options:
  --host=           Override the detected LAN IP address
  --frontend-port=  Vite dev server port  (default: 5173)
  --api-port=       Laravel API port      (default: 8000)
  --ttl=            Session lifetime in seconds (default: 600)
```

### Browser dashboard

Open the URL printed in the terminal banner, or navigate to:

```
http://<your-lan-ip>:8000/mobile-jump/dashboard?session=JMP-XXXX-XXXX
```

---

## Android App

Transfer the APK from `public/vendor/mobile-jump/MobileJump.apk` to your Android phone via USB, Bluetooth, or a local web link, then install it (you'll need to allow installs from unknown sources).

---

## Configuration

Publish the config file:

```bash
php artisan vendor:publish --tag=mobile-jump-config
```

`config/mobile-jump.php`:

```php
return [
    'route_prefix'     => env('MOBILE_JUMP_PREFIX', 'mobile-jump'),
    'session_ttl'      => env('MOBILE_JUMP_TTL', 600),
    'storage'          => env('MOBILE_JUMP_STORAGE', 'redis'),  // redis | database | file
    'redis_connection' => env('MOBILE_JUMP_REDIS_CONNECTION', 'default'),
    'register_routes'  => true,
    'middleware'       => ['api'],
    'apk_url'          => null,
];
```

---

## Frontend Integration

### Vue 3

```js
// src/composables/useMobileJump.js (copy from vendor-stubs/mobile-jump/)
import { useMobileJump } from '@/composables/useMobileJump'

const { isRunner, apiBaseUrl, wsHost } = useMobileJump()
```

### React

```ts
// src/hooks/useMobileJump.ts (copy from vendor-stubs/mobile-jump/)
import { useMobileJump } from './hooks/useMobileJump'

const { isRunner, apiBaseUrl, wsHost } = useMobileJump()
```

---

## Testing

```bash
composer install
./vendor/bin/phpunit
```

---

## License

MIT — see [LICENSE](LICENSE).
