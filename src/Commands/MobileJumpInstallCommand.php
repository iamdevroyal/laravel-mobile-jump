<?php

namespace Iamdevroyal\MobileJump\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Redis;

class MobileJumpInstallCommand extends Command
{
    protected $signature   = 'mobile:jump:install';
    protected $description = 'Publish config, APK, and scaffold frontend stubs for Mobile Jump';

    public function handle(): int
    {
        $this->newLine();
        $this->line('  <fg=magenta;options=bold>📱 Mobile Jump — Installation Wizard</>');
        $this->line('  <fg=gray>─────────────────────────────────────────────</>');
        $this->newLine();

        // ── 1. Publish config ──────────────────────────────────────────────
        $this->info('  ✓ Publishing config file...');
        $this->callSilent('vendor:publish', [
            '--tag'   => 'mobile-jump-config',
            '--force' => false,
        ]);

        // ── 2. Publish APK ────────────────────────────────────────────────
        $this->info('  ✓ Publishing Android APK to public/vendor/mobile-jump/...');
        $this->callSilent('vendor:publish', [
            '--tag'   => 'mobile-jump-android',
            '--force' => false,
        ]);

        // ── 3. Optionally publish migration ───────────────────────────────
        $driver = config('mobile-jump.storage', 'redis');
        if ($driver === 'database') {
            $this->info('  ✓ Publishing database migration...');
            $this->callSilent('vendor:publish', [
                '--tag'   => 'mobile-jump-migrations',
                '--force' => false,
            ]);
            $this->info('  Run <fg=cyan>php artisan migrate</> to create the sessions table.');
            $this->newLine();
        }

        // ── 4. Check chosen storage backend ──────────────────────────────
        $this->line("  Storage driver: <fg=cyan>{$driver}</>");
        $this->checkStorageBackend($driver);

        // ── 5. Frontend stub ──────────────────────────────────────────────
        $framework = $this->choice(
            '  Which frontend framework are you using?',
            ['vue', 'react', 'none'],
            'none',
        );

        if ($framework !== 'none') {
            $this->publishFrontendStubs($framework);
        }

        // ── 6. Patch Vite config so dev server binds to all interfaces ───
        $this->patchViteConfig($framework);

        // ── 7. Summary ────────────────────────────────────────────────────
        $this->newLine();
        $this->line('  <fg=green;options=bold>✓ Mobile Jump is ready!</>');
        $this->newLine();
        $this->line('  <fg=gray>Next steps:</>');
        $this->line('  1. Start your Laravel server:   <fg=cyan>php artisan serve --host=0.0.0.0</>');
        $this->line('  2. Start your frontend server:  <fg=cyan>npm run dev</>');
        $this->line('  3. Start a Mobile Jump session: <fg=cyan>php artisan mobile:jump</>');
        $this->line('  4. Install the APK on your phone from: <fg=cyan>public/vendor/mobile-jump/MobileJump.apk</>');
        $this->newLine();

        return self::SUCCESS;
    }

    // ─────────────────────────────────────────────────────────────────────────

    private function checkStorageBackend(string $driver): void
    {
        if ($driver === 'redis') {
            try {
                Redis::connection(config('mobile-jump.redis_connection', 'default'))->ping();
                $this->line('  Redis connection: <fg=green>✓ OK</>');
            } catch (\Throwable $e) {
                $this->warn('  Redis connection failed: ' . $e->getMessage());
                $this->warn('  Set MOBILE_JUMP_STORAGE=database or MOBILE_JUMP_STORAGE=file in .env as a fallback.');
            }
        } elseif ($driver === 'file') {
            $dir = storage_path('framework/mobile-jump');
            if (! is_dir($dir)) {
                mkdir($dir, 0755, true);
            }
            $this->line("  File storage directory: <fg=green>{$dir}</>");
        }
        $this->newLine();
    }

    /**
     * Patch package.json and vite.config.* to ensure the dev server
     * listens on all network interfaces (0.0.0.0) so Mobile Jump can
     * reach it from the Android WebView over Wi-Fi.
     */
    private function patchViteConfig(string $framework): void
    {
        if ($framework === 'none') {
            return;
        }

        $this->newLine();
        $this->line('  <fg=cyan>Patching Vite / frontend config for network access...</>');

        // ── patch package.json dev script ────────────────────────────────
        $pkgPath = base_path('package.json');
        if (file_exists($pkgPath)) {
            $pkg = json_decode(file_get_contents($pkgPath), true);
            $devScript = $pkg['scripts']['dev'] ?? 'vite';

            // Only add --host if not already present
            if (str_contains($devScript, 'vite') && ! str_contains($devScript, '--host')) {
                $pkg['scripts']['dev'] = rtrim($devScript) . ' --host';
                file_put_contents($pkgPath, json_encode($pkg, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . PHP_EOL);
                $this->line('  ✓ <fg=green>package.json</> — dev script updated to <fg=cyan>' . $pkg['scripts']['dev'] . '</>');
            } else {
                $this->line('  <fg=gray>package.json</> dev script already has <fg=cyan>--host</> (skipped)');
            }
        }

        // ── patch vite.config.js / vite.config.ts ────────────────────────
        foreach (['vite.config.js', 'vite.config.ts', 'vite.config.mjs'] as $configFile) {
            $configPath = base_path($configFile);
            if (! file_exists($configPath)) {
                continue;
            }

            $content = file_get_contents($configPath);

            // Check if server.host is already configured
            if (str_contains($content, "host:") && str_contains($content, 'server')) {
                $this->line("  <fg=gray>{$configFile}</> already has a server host config (skipped)");
                break;
            }

            // Inject server block before the closing brace of defineConfig return
            // We inject just before the last closing } }) pattern
            $injection = "\n    server: {\n      host: '0.0.0.0',\n      port: 5173,\n    },";

            // Try to inject before the last `}` in a `return {` block
            $patched = preg_replace(
                '/(\}\s*\}\s*\)\s*;\?\s*$)/m',
                $injection . "\n$1",
                $content,
                1,
                $count
            );

            if ($count > 0 && $patched !== $content) {
                file_put_contents($configPath, $patched);
                $this->line("  ✓ <fg=green>{$configFile}</> — server.host set to <fg=cyan>0.0.0.0</>");
            } else {
                $this->warn("  Could not auto-patch {$configFile}. Add manually:");
                $this->warn("    server: { host: '0.0.0.0', port: 5173 }");
            }
            break;
        }

        // ── Vite config patch for React (next.config.js / similar) ───────
        if ($framework === 'react') {
            $this->line('  <fg=gray>React detected — if using Next.js, Mobile Jump requires a custom server. See README.</>');
        }

        $this->newLine();
    }

    private function publishFrontendStubs(string $framework): void
    {
        $srcDir  = __DIR__ . '/../../stubs/' . $framework;
        $destDir = base_path('vendor-stubs/mobile-jump');

        if (! is_dir($destDir)) {
            mkdir($destDir, 0755, true);
        }

        foreach (glob($srcDir . '/*') as $file) {
            $dest = $destDir . '/' . basename($file);
            if (! file_exists($dest)) {
                copy($file, $dest);
                $this->line("  Stub created: <fg=cyan>vendor-stubs/mobile-jump/" . basename($file) . "</>");
            } else {
                $this->line("  Stub already exists (skipped): <fg=gray>vendor-stubs/mobile-jump/" . basename($file) . "</>");
            }
        }
        $this->line("  <fg=gray>Copy these stubs into your src/ directory and import where needed.</>");
        $this->newLine();
    }
}
