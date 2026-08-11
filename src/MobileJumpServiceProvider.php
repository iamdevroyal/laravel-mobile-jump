<?php

namespace Iamdevroyal\MobileJump;

use Iamdevroyal\MobileJump\Commands\MobileJumpCommand;
use Iamdevroyal\MobileJump\Commands\MobileJumpInstallCommand;
use Iamdevroyal\MobileJump\Contracts\SessionStoreInterface;
use Iamdevroyal\MobileJump\Services\MobileSessionService;
use Iamdevroyal\MobileJump\Storage\DatabaseSessionStore;
use Iamdevroyal\MobileJump\Storage\FileSessionStore;
use Iamdevroyal\MobileJump\Storage\RedisSessionStore;
use Illuminate\Support\ServiceProvider;

class MobileJumpServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Merge package config with app config
        $this->mergeConfigFrom(__DIR__ . '/../config/mobile-jump.php', 'mobile-jump');

        // Bind the session store based on the configured driver
        $this->app->singleton(SessionStoreInterface::class, function ($app) {
            $driver = config('mobile-jump.storage', 'redis');

            return match ($driver) {
                'database' => new DatabaseSessionStore(),
                'file'     => new FileSessionStore(),
                default    => new RedisSessionStore(
                    config('mobile-jump.redis_connection', 'default')
                ),
            };
        });

        // Bind the service — receives SessionStoreInterface via the container
        $this->app->singleton(MobileSessionService::class, function ($app) {
            return new MobileSessionService($app->make(SessionStoreInterface::class));
        });
    }

    public function boot(): void
    {
        // ── Routes ─────────────────────────────────────────────────────────
        if (config('mobile-jump.register_routes', true)) {
            $this->loadRoutesFrom(__DIR__ . '/Routes/api.php');
            $this->loadRoutesFrom(__DIR__ . '/Routes/web.php');
        }

        // ── Publishable assets ─────────────────────────────────────────────
        if ($this->app->runningInConsole()) {
            // Config
            $this->publishes([
                __DIR__ . '/../config/mobile-jump.php' => config_path('mobile-jump.php'),
            ], 'mobile-jump-config');

            // Database migration
            $this->publishes([
                __DIR__ . '/../database/migrations/2024_01_01_000000_create_mobile_jump_sessions_table.php'
                    => database_path('migrations/2024_01_01_000000_create_mobile_jump_sessions_table.php'),
            ], 'mobile-jump-migrations');

            // Android APK
            $this->publishes([
                __DIR__ . '/../android/MobileJump.apk'
                    => public_path('vendor/mobile-jump/MobileJump.apk'),
            ], 'mobile-jump-android');

            // Commands
            $this->commands([
                MobileJumpCommand::class,
                MobileJumpInstallCommand::class,
            ]);
        } else {
            // Register commands in non-console too (so they appear in artisan list)
            $this->commands([
                MobileJumpCommand::class,
                MobileJumpInstallCommand::class,
            ]);
        }
    }
}
