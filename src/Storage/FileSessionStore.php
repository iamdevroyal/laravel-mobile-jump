<?php

namespace Iamdevroyal\MobileJump\Storage;

use Iamdevroyal\MobileJump\Contracts\SessionStoreInterface;
use Iamdevroyal\MobileJump\Models\MobileSession;
use Carbon\Carbon;

/**
 * File-backed session store — zero-dependency fallback.
 * Stores one JSON file per session under storage/framework/mobile-jump/.
 */
class FileSessionStore implements SessionStoreInterface
{
    private string $dir;

    public function __construct()
    {
        $this->dir = storage_path('framework/mobile-jump');
        if (! is_dir($this->dir)) {
            mkdir($this->dir, 0755, true);
        }
    }

    private function path(string $sessionId): string
    {
        return $this->dir . DIRECTORY_SEPARATOR . $sessionId . '.json';
    }

    public function put(MobileSession $session, int $ttlSeconds): void
    {
        $data = $session->toArray();
        $data['_expires_unix'] = time() + $ttlSeconds;
        file_put_contents($this->path($session->sessionId), json_encode($data));
    }

    public function get(string $sessionId): ?MobileSession
    {
        $file = $this->path($sessionId);
        if (! file_exists($file)) {
            return null;
        }

        $data = json_decode(file_get_contents($file), true);
        if (! $data || time() > ($data['_expires_unix'] ?? 0)) {
            @unlink($file);
            return null;
        }

        return MobileSession::fromArray($data);
    }

    public function delete(string $sessionId): void
    {
        @unlink($this->path($sessionId));
    }

    public function allIds(): array
    {
        $ids = [];
        foreach (glob($this->dir . DIRECTORY_SEPARATOR . '*.json') as $file) {
            $data = json_decode(file_get_contents($file), true);
            if ($data && time() <= ($data['_expires_unix'] ?? 0)) {
                $ids[] = $data['session_id'] ?? basename($file, '.json');
            } else {
                @unlink($file);
            }
        }
        return $ids;
    }
}
