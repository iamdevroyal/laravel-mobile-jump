<?php

namespace Iamdevroyal\MobileJump\Services;

use Carbon\Carbon;
use Iamdevroyal\MobileJump\Contracts\SessionStoreInterface;
use Iamdevroyal\MobileJump\Models\MobileSession;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class MobileSessionService
{
    public function __construct(private readonly SessionStoreInterface $store) {}

    // ─────────────────────────────────────────────────────────────────────────
    // Create
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Generate and persist a new session.
     *
     * @return array{session: MobileSession, rawToken: string}
     */
    public function createSession(
        string $frontendUrl,
        string $apiUrl,
        int    $hmrPort  = 5173,
        int    $ttl      = 600,
    ): array {
        $rawToken  = Str::random(64);
        $sessionId = $this->generateSessionId();

        $session = new MobileSession(
            sessionId:   $sessionId,
            tokenHash:   Hash::make($rawToken),
            frontendUrl: $frontendUrl,
            apiUrl:      $apiUrl,
            hmrPort:     $hmrPort,
            expiresAt:   Carbon::now()->addSeconds($ttl),
        );

        $this->store->put($session, $ttl);

        return ['session' => $session, 'rawToken' => $rawToken];
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Validate & Connect
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Validate a session / token pair from the Android Runner's connect call.
     * Token is optional — compact-QR flow sends session_id only; legacy flow sends a token.
     */
    public function validateSession(string $sessionId, ?string $rawToken = null): ?MobileSession
    {
        $session = $this->store->get($sessionId);

        if ($session === null) {
            return null;
        }

        if ($session->isExpired()) {
            $this->store->delete($sessionId);
            return null;
        }

        if (! empty($rawToken) && ! Hash::check($rawToken, $session->tokenHash)) {
            return null;
        }

        return $session;
    }

    /**
     * Mark a session as connected and persist device info.
     */
    public function recordConnection(MobileSession $session, array $deviceInfo = []): MobileSession
    {
        $connected    = $session->withConnection(json_encode($deviceInfo));
        $remainingTtl = max(1, (int) $session->expiresAt->diffInSeconds(Carbon::now(), false) * -1);
        $this->store->put($connected, $remainingTtl);
        return $connected;
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Read
    // ─────────────────────────────────────────────────────────────────────────

    public function findById(string $sessionId): ?MobileSession
    {
        return $this->store->get($sessionId);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Destroy
    // ─────────────────────────────────────────────────────────────────────────

    public function destroySession(string $sessionId): void
    {
        $this->store->delete($sessionId);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Helpers
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Detect the LAN IP of the current machine via a UDP socket trick.
     * No data is actually sent — we just read the socket's local address.
     */
    public function detectLanIp(): string
    {
        $socket = @socket_create(AF_INET, SOCK_DGRAM, SOL_UDP);
        if ($socket === false) {
            return '127.0.0.1';
        }
        @socket_connect($socket, '8.8.8.8', 53);
        @socket_getsockname($socket, $ip);
        @socket_close($socket);
        return $ip ?? '127.0.0.1';
    }

    /**
     * Generate a human-readable session ID: JMP-XXXX-XXXX
     */
    private function generateSessionId(): string
    {
        return 'JMP-' . strtoupper(Str::random(4)) . '-' . strtoupper(Str::random(4));
    }
}
