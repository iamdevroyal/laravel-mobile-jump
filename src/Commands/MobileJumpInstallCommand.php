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

        // ── 6. Summary ────────────────────────────────────────────────────
        $this->newLine();
        $this->line('  <fg=green;options=bold>✓ Mobile Jump is ready!</>');
        $this->newLine();
        $this->line('  <fg=gray>Next steps:</>');
        $this->line('  1. Start your Laravel server:   <fg=cyan>php artisan serve --host=0.0.0.0</>');
        $this->line('  2. Start your frontend server:  <fg=cyan>npm run dev -- --host 0.0.0.0</>');
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
