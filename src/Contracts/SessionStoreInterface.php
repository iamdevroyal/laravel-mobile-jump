<?php

namespace Iamdevroyal\MobileJump\Contracts;

use Iamdevroyal\MobileJump\Models\MobileSession;

/**
 * Contract for the pluggable session storage backend.
 *
 * Implementations: RedisSessionStore, DatabaseSessionStore, FileSessionStore
 */
interface SessionStoreInterface
{
    /**
     * Persist a session for the given number of seconds.
     */
    public function put(MobileSession $session, int $ttlSeconds): void;

    /**
     * Retrieve a session by ID. Returns null if not found or expired.
     */
    public function get(string $sessionId): ?MobileSession;

    /**
     * Delete a session permanently.
     */
    public function delete(string $sessionId): void;

    /**
     * List all active session IDs (used for debugging / dashboard index).
     *
     * @return string[]
     */
    public function allIds(): array;
}
