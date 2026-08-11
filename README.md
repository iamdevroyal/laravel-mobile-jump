# 📱 Laravel Mobile Jump

[![Latest Version on Packagist](https://img.shields.io/packagist/v/iamdevroyal/laravel-mobile-jump.svg?style=flat-square)](https://packagist.org/packages/iamdevroyal/laravel-mobile-jump)
[![Total Downloads](https://img.shields.io/packagist/dt/iamdevroyal/laravel-mobile-jump.svg?style=flat-square)](https://packagist.org/packages/iamdevroyal/laravel-mobile-jump)
[![License: MIT](https://img.shields.io/badge/License-MIT-green.svg?style=flat-square)](LICENSE)
[![PHP Version](https://img.shields.io/badge/PHP-8.2%2B-blue.svg?style=flat-square)](https://php.net)
[![Laravel](https://img.shields.io/badge/Laravel-11%2F12%2F13-red.svg?style=flat-square)](https://laravel.com)

> **Scan a QR code. See your Laravel app on your phone. Instantly.**

Mobile Jump bridges the gap between your local Laravel development server and any Android device on the same Wi-Fi network. Run one command, scan a QR code with the companion app, and your phone loads your local Vue or React frontend in seconds — with hot module replacement, API access, and a full native bridge for camera, microphone, location, biometrics, and more.

No tunnelling services. No internet required. No configuration headaches.

---

## ✨ Features

| Feature | Description |
|---|---|
| 🔌 **Zero-config auto-detection** | Automatically detects your LAN IP — just run and scan |
| 📦 **Universal** | Works with any Laravel app and any frontend (Vue, React, Svelte, vanilla JS) |
| 🗄️ **Pluggable storage** | Redis (fastest), Database, or File fallback — choose what fits your stack |
| 🖥️ **Browser dashboard** | Live session monitor at `/mobile-jump/dashboard` |
| 📱 **Android companion APK** | Pre-built and bundled — no Play Store, no build tools needed |
| 🧩 **Frontend stubs** | Ready-to-use Vue 3 composable and React hook |
| 🔐 **Native permissions** | Camera, microphone, location, biometrics — requested on demand |
| 🌉 **Rich native bridge** | 20+ native Android capabilities accessible from JavaScript |
| ✅ **Fully tested** | PHPUnit + Orchestra Testbench test suite included |

---

## 📋 Requirements

- **PHP** 8.2+
- **Laravel** 11.x, 12.x, or 13.x
- **Android phone** on the same Wi-Fi network as your laptop
- **One of**: Redis (recommended), MySQL/SQLite, or a writable filesystem
- **Vite** (Vue/React frontend) configured to listen on `0.0.0.0` — see [Frontend Setup](#-frontend-setup-required)

---

## 🚀 Installation

### Step 1 — Install via Composer

```bash
composer require iamdevroyal/laravel-mobile-jump
```

The service provider is auto-discovered — no manual registration needed.

### Step 2 — Run the Install Wizard

```bash
php artisan mobile:jump:install
```

The wizard will:
- ✅ Publish `config/mobile-jump.php`
- ✅ Copy the companion Android APK to `public/vendor/mobile-jump/MobileJump.apk`
- ✅ Check your configured storage backend (Redis / Database / File)
- ✅ Optionally scaffold a Vue 3 or React frontend stub
- ✅ **Automatically patch** your `package.json` dev script and `vite.config.js` to bind to `0.0.0.0`

### Step 3 — (Optional) Database Migration

Only needed if `MOBILE_JUMP_STORAGE=database`:

```bash
php artisan vendor:publish --tag=mobile-jump-migrations
php artisan migrate
```

---

## 🛠 Frontend Setup (Required)

Mobile Jump needs your **Vite dev server** to listen on all network interfaces so your Android device can reach it over Wi-Fi.

> **Note:** The install wizard (`php artisan mobile:jump:install`) attempts to do this automatically. Only follow these manual steps if you skipped the wizard or see `Frontend not reachable` warnings.

### Vue 3 / Vite

**Option A — vite.config.js (recommended):**

Add a `server` block to your `vite.config.js`:

```js
// vite.config.js
export default defineConfig({
  // ... your existing config
  server: {
    host: '0.0.0.0',
    port: 5173,
  },
})
```

Then simply run:

```bash
npm run dev
```

**Option B — package.json dev script:**

Update your `package.json` to include `--host`:

```json
{
  "scripts": {
    "dev": "vite --host"
  }
}
```

> ⚠️ **PowerShell users:** Do NOT use `npm run dev -- --host 0.0.0.0`. PowerShell passes `0.0.0.0` as a positional argument (root directory) rather than a `--host` value. Use one of the options above instead.

### React / Vite

Same as Vue — add `server.host: '0.0.0.0'` to your `vite.config.js`:

```js
// vite.config.js
import { defineConfig } from 'vite'
import react from '@vitejs/plugin-react'

export default defineConfig({
  plugins: [react()],
  server: {
    host: '0.0.0.0',
    port: 5173,
  },
})
```

### Next.js (React)

Next.js uses its own server. Run it with:

```bash
npx next dev --hostname 0.0.0.0
# or set it in next.config.js via experimental.serverComponentsExternalPackages
```

Then set `MOBILE_JUMP_FRONTEND_PORT=3000` in your `.env`.

---

## 🎯 Usage

### Start a Development Session

```bash
# 1. Start Laravel API on all interfaces:
php artisan serve --host=0.0.0.0

# 2. Start your Vite frontend (--host baked in via install wizard or vite.config.js):
npm run dev

# 3. Start Mobile Jump:
php artisan mobile:jump
```

> **Requires Vite configured for `host: '0.0.0.0'`** — see [Frontend Setup](#-frontend-setup-required) above.

A compact QR code renders in your terminal. You'll see something like:

```
╔══════════════════════════════════════════════════╗
║          📱 Mobile Jump — Dev Session            ║
║══════════════════════════════════════════════════║
║  Session: JMP-A4B2-9F1C     TTL: 10 min          ║
║  API:     http://192.168.1.45:8000               ║
║  App:     http://192.168.1.45:5173               ║
╚══════════════════════════════════════════════════╝

  ▀▄ ▀▄ ▀▄ █▀▄ ▄▀█ ▄▀█ ▀▄ ▀▄▀ ▄▀█ ...
  [QR code renders here in your terminal]

  Waiting for device to connect...
```

### Command Options

```
php artisan mobile:jump [options]

Options:
  --host=          Override the detected LAN IP address
  --frontend-port= Vite dev server port  (default: 5173)
  --api-port=      Laravel API port      (default: 8000)
  --ttl=           Session lifetime in seconds (default: 600)
```

### Browser Dashboard

Open the URL shown in the terminal banner, or navigate directly to:

```
http://<your-lan-ip>:8000/mobile-jump/dashboard?session=JMP-XXXX-XXXX
```

The dashboard shows live session status, connection time, device info, and a button to disconnect the session.

---

## 📱 Android Companion App

The companion APK is bundled directly in the package. After installation it is copied to:

```
public/vendor/mobile-jump/MobileJump.apk
```

**Transfer it to your phone:**
- Share the file via Bluetooth, USB, or serve it locally: `http://<your-lan-ip>:8000/vendor/mobile-jump/MobileJump.apk`
- Install it (allow "Install from unknown sources" in Android settings)

**On first launch**, the app requests:
- Camera (for QR scanning and live capture)
- Microphone (for voice recording)
- Location (for geolocation sharing)

---

## ⚙️ Configuration

Publish the config file:

```bash
php artisan vendor:publish --tag=mobile-jump-config
```

`config/mobile-jump.php`:

```php
return [
    // URL prefix for all routes (e.g. /mobile-jump/api/connect)
    'route_prefix'     => env('MOBILE_JUMP_PREFIX', 'mobile-jump'),

    // Session lifetime in seconds
    'session_ttl'      => env('MOBILE_JUMP_TTL', 600),

    // Storage driver: 'redis' | 'database' | 'file'
    'storage'          => env('MOBILE_JUMP_STORAGE', 'redis'),

    // Redis connection name (from config/database.php)
    'redis_connection' => env('MOBILE_JUMP_REDIS_CONNECTION', 'default'),

    // Whether to register routes automatically
    'register_routes'  => true,

    // Middleware applied to API routes
    'middleware'       => ['api'],

    // Override the APK download URL (null = use bundled APK)
    'apk_url'          => null,
];
```

### Environment Variables

Add these to your `.env` as needed:

```env
# Storage backend (redis is fastest, file works everywhere)
MOBILE_JUMP_STORAGE=redis

# Session TTL in seconds (default 10 minutes)
MOBILE_JUMP_TTL=600

# Custom route prefix
MOBILE_JUMP_PREFIX=mobile-jump
```

---

## 🌐 Frontend Integration

### Vue 3

After running `php artisan mobile:jump:install` and choosing Vue, stubs are placed in `vendor-stubs/mobile-jump/`. Copy them to your project:

```js
// src/composables/useMobileJump.js
import { useMobileJump } from '@/composables/useMobileJump'

const { isRunner, isLan, apiBaseUrl, wsHost } = useMobileJump()

// apiBaseUrl automatically resolves to:
//   - http://192.168.x.x:8000/api/v1  (when accessed from phone)
//   - http://127.0.0.1:8000/api/v1    (when on localhost)
```

### React / TypeScript

```ts
// src/hooks/useMobileJump.ts
import { useMobileJump } from './hooks/useMobileJump'

const { isRunner, isLan, apiBaseUrl, wsHost } = useMobileJump()
```

### Direct Bridge Access (Any Framework)

The companion app exposes a native bridge on `window.MobileJumpNative`:

```js
const bridge = window.MobileJumpNative || window.KorpaBeeNative  // null on desktop

if (bridge) {
  // Device info
  const device = JSON.parse(bridge.getDeviceInfo())
  // → { platform: "android", model: "Samsung Galaxy S23", os_version: "14", runner_version: "v1.0.0" }

  // Haptic feedback
  bridge.vibrate(50)

  // Clipboard
  bridge.copyToClipboard('Hello from my app!')
  const text = bridge.readFromClipboard()

  // Toast notifications
  bridge.showToast('File uploaded!')

  // Native share sheet
  bridge.shareText('Check this out!', 'https://myapp.com/something')

  // Battery & network
  console.log('Battery:', bridge.getBatteryLevel() + '%')
  console.log('Online:', bridge.isNetworkAvailable())

  // Location
  const loc = JSON.parse(bridge.getLastKnownLocation())
  // → { status: "ok", latitude: 6.5244, longitude: 3.3792, accuracy: 12.5, provider: "gps" }

  // Biometrics
  const bio = JSON.parse(bridge.getBiometricStatus())
  // → { available: true, status: "ready" }

  // Persistent key-value storage (survives page reloads)
  bridge.setStorageItem('user_prefs', JSON.stringify({ theme: 'dark' }))
  const prefs = JSON.parse(bridge.getStorageItem('user_prefs'))
  bridge.removeStorageItem('user_prefs')
}
```

---

## 🔐 Native Bridge — Full Reference

All methods available at `window.MobileJumpNative` (also aliased as `window.KorpaBeeNative`):

### Device & Platform

| Method | Returns | Description |
|---|---|---|
| `getDeviceInfo()` | `string` (JSON) | Device metadata — model, OS version, runner version |
| `getPlatform()` | `"android"` | Platform identifier |
| `isRunnerEnvironment()` | `"true"` | Feature detection flag |

### Permissions

| Method | Returns | Description |
|---|---|---|
| `requestPermissions()` | `void` | Triggers Android system permission dialog for camera, mic, and location |
| `hasCameraPermission()` | `boolean` | Whether camera permission is currently granted |
| `hasAudioPermission()` | `boolean` | Whether microphone permission is currently granted |
| `hasLocationPermission()` | `boolean` | Whether location permission is currently granted |

### Location

| Method | Returns | Description |
|---|---|---|
| `getLastKnownLocation()` | `string` (JSON) | Last cached GPS/network location |

### Biometrics

| Method | Returns | Description |
|---|---|---|
| `getBiometricStatus()` | `string` (JSON) | Fingerprint/Face ID availability — `ready`, `not_enrolled`, `no_hardware` |

### Native UI

| Method | Returns | Description |
|---|---|---|
| `showToast(message)` | `void` | Short Android Toast notification |
| `showToastLong(message)` | `void` | Long Android Toast notification |
| `shareText(title, text)` | `boolean` | Opens the native Android share sheet |

### Clipboard

| Method | Returns | Description |
|---|---|---|
| `copyToClipboard(text)` | `boolean` | Writes text to Android system clipboard |
| `readFromClipboard()` | `string` | Reads current clipboard text |

### Haptics

| Method | Returns | Description |
|---|---|---|
| `vibrate(milliseconds)` | `void` | Haptic vibration (e.g. `vibrate(40)` for a button tap feel) |

### Diagnostics

| Method | Returns | Description |
|---|---|---|
| `getBatteryLevel()` | `string` | Battery percentage e.g. `"85"`, or `"-1"` if unavailable |
| `isNetworkAvailable()` | `"true"` / `"false"` | Whether any network connection is active |

### Persistent Storage

| Method | Returns | Description |
|---|---|---|
| `setStorageItem(key, value)` | `boolean` | Stores a string value in Android SharedPreferences |
| `getStorageItem(key)` | `string` | Retrieves a stored value |
| `removeStorageItem(key)` | `boolean` | Removes a stored value |

---

## 🔌 API Endpoints

All endpoints are prefixed with the configured `route_prefix` (default: `mobile-jump`).

| Method | Endpoint | Description |
|---|---|---|
| `POST` | `/mobile-jump/api/connect` | Device registers connection with session ID |
| `GET` | `/mobile-jump/api/status/{id}` | Poll session status (`waiting` / `connected`) |
| `GET` | `/mobile-jump/api/qr/{id}` | Returns the QR code as an SVG image |
| `DELETE` | `/mobile-jump/api/disconnect/{id}` | Ends the session |
| `GET` | `/mobile-jump/dashboard` | Browser-based session dashboard |

---

## 🧪 Testing

Run the included test suite:

```bash
composer install
./vendor/bin/phpunit
```

The tests use **Orchestra Testbench** and the **File session store** — no Redis or database needed to run tests.

---

## 🛠️ How It Works

```
┌─────────────────────────────────────────────────────┐
│  Your Laptop                                         │
│                                                      │
│  php artisan serve --host=0.0.0.0 (port 8000)       │
│  npm run dev -- --host 0.0.0.0    (port 5173)       │
│  php artisan mobile:jump           ← creates session │
│         │                                            │
│  Redis/DB/File ← stores session token + URLs         │
│         │                                            │
│  QR Code rendered in terminal                        │
└──────────────────────┬──────────────────────────────┘
                       │ scan
                       ▼
┌─────────────────────────────────────────────────────┐
│  Android Phone (same Wi-Fi)                          │
│                                                      │
│  MobileJump.apk reads QR → extracts JMP-XXXX-XXXX   │
│  POST /mobile-jump/api/connect  ← registers device   │
│  WebView loads http://192.168.x.x:5173               │
│  window.MobileJumpNative = NativeBridge instance     │
│                                                      │
│  Your Vue/React app runs natively with:              │
│  - Full HMR (live code reload)                       │
│  - Real API access                                   │
│  - Camera, microphone, location, biometrics          │
└─────────────────────────────────────────────────────┘
```

---

## 📦 Changelog

See [CHANGELOG.md](CHANGELOG.md) for release history.

---

## 🤝 Contributing

Contributions, issues, and feature requests are welcome. Please open an issue first to discuss major changes.

---

## 📄 License

MIT — see [LICENSE](LICENSE).

---

## 🙏 Credits

Built by [iamdevroyal](https://github.com/iamdevroyal).

Inspired by the need to preview complex Laravel + Vue apps on real Android devices during development — without the overhead of cloud tunnels, staging environments, or APK rebuilds.

---

## u{1F4F1} Android App Repository

The companion Android app source code lives in its own repository:

**[iamdevroyal/android-mobile-jump](https://github.com/iamdevroyal/android-mobile-jump)**
