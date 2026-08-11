<?php

namespace Iamdevroyal\MobileJump\Storage;

use Iamdevroyal\MobileJump\Contracts\SessionStoreInterface;
use Iamdevroyal\MobileJump\Models\MobileSession;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

/**
 * Database-backed session store.
 * Requires: php artisan vendor:publish --tag=mobile-jump-migrations && php artisan migrate
 */
class DatabaseSessionStore implements SessionStoreInterface
{
    private const TABLE = 'mobile_jump_sessions';

    public function put(MobileSession $session, int $ttlSeconds): void
    {
        $expiresAt = Carbon::now()->addSeconds($ttlSeconds)->toDateTimeString();

        DB::table(self::TABLE)->upsert(
            [
                'session_id'   => $session->sessionId,
                'token_hash'   => $session->tokenHash,
                'frontend_url' => $session->frontendUrl,
                'api_url'      => $session->apiUrl,
                'hmr_port'     => $session->hmrPort,
                'expires_at'   => $expiresAt,
                'device_info'  => $session->deviceInfo,
                'connected_at' => $session->connectedAt?->toDateTimeString(),
                'updated_at'   => Carbon::now()->toDateTimeString(),
            ],
            ['session_id'],
            ['token_hash', 'frontend_url', 'api_url', 'hmr_port', 'expires_at', 'device_info', 'connected_at', 'updated_at'],
        );
    }

    public function get(string $sessionId): ?MobileSession
    {
        $row = DB::table(self::TABLE)
            ->where('session_id', $sessionId)
            ->where('expires_at', '>', Carbon::now())
            ->first();

        if ($row === null) {
            return null;
        }

        return MobileSession::fromArray((array) $row);
    }

    public function delete(string $sessionId): void
    {
        DB::table(self::TABLE)->where('session_id', $sessionId)->delete();
    }

    public function allIds(): array
    {
        return DB::table(self::TABLE)
            ->where('expires_at', '>', Carbon::now())
            ->pluck('session_id')
            ->toArray();
    }
}
