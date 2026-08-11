<?php

namespace Iamdevroyal\MobileJump\Models;

use Carbon\Carbon;

/**
 * Value object representing a single Mobile Jump session.
 * Immutable after construction — use withConnection() to produce a new instance.
 */
class MobileSession
{
    public function __construct(
        public readonly string  $sessionId,
        public readonly string  $tokenHash,
        public readonly string  $frontendUrl,
        public readonly string  $apiUrl,
        public readonly int     $hmrPort,
        public readonly Carbon  $expiresAt,
        public readonly ?string $deviceInfo  = null,
        public readonly ?Carbon $connectedAt = null,
    ) {}

    // ─────────────────────────────────────────────────────────────────────────
    // Factory
    // ─────────────────────────────────────────────────────────────────────────

    public static function fromArray(array $data): self
    {
        return new self(
            sessionId:   $data['session_id'],
            tokenHash:   $data['token_hash'],
            frontendUrl: $data['frontend_url'],
            apiUrl:      $data['api_url'],
            hmrPort:     (int) ($data['hmr_port'] ?? 5173),
            expiresAt:   Carbon::parse($data['expires_at']),
            deviceInfo:  (isset($data['device_info']) && $data['device_info'] !== '')
                ? $data['device_info'] : null,
            connectedAt: (isset($data['connected_at']) && $data['connected_at'] !== '')
                ? Carbon::parse($data['connected_at']) : null,
        );
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Serialisation
    // ─────────────────────────────────────────────────────────────────────────

    public function toArray(): array
    {
        return [
            'session_id'   => $this->sessionId,
            'token_hash'   => $this->tokenHash,
            'frontend_url' => $this->frontendUrl,
            'api_url'      => $this->apiUrl,
            'hmr_port'     => $this->hmrPort,
            'expires_at'   => $this->expiresAt->toIso8601String(),
            'device_info'  => $this->deviceInfo ?? '',
            'connected_at' => $this->connectedAt?->toIso8601String() ?? '',
        ];
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Derived state
    // ─────────────────────────────────────────────────────────────────────────

    public function isExpired(): bool
    {
        return Carbon::now()->isAfter($this->expiresAt);
    }

    public function isConnected(): bool
    {
        return $this->connectedAt !== null;
    }

    public function withConnection(string $deviceInfoJson): self
    {
        return new self(
            sessionId:   $this->sessionId,
            tokenHash:   $this->tokenHash,
            frontendUrl: $this->frontendUrl,
            apiUrl:      $this->apiUrl,
            hmrPort:     $this->hmrPort,
            expiresAt:   $this->expiresAt,
            deviceInfo:  $deviceInfoJson,
            connectedAt: Carbon::now(),
        );
    }
}
