<?php

namespace Iamdevroyal\MobileJump\Storage;

use Iamdevroyal\MobileJump\Contracts\SessionStoreInterface;
use Iamdevroyal\MobileJump\Models\MobileSession;
use Illuminate\Support\Facades\Redis;

class RedisSessionStore implements SessionStoreInterface
{
    private const KEY_PREFIX = 'mobile_jump_session:';
    private const INDEX_KEY  = 'mobile_jump_index';

    public function __construct(private readonly string $connection = 'default') {}

    private function redis(): \Illuminate\Redis\Connections\Connection
    {
        return Redis::connection($this->connection);
    }

    public function put(MobileSession $session, int $ttlSeconds): void
    {
        $key  = self::KEY_PREFIX . $session->sessionId;
        $data = $session->toArray();

        $pipe = $this->redis()->pipeline();
        $pipe->hmset($key, $data);
        $pipe->expire($key, $ttlSeconds);
        $pipe->sadd(self::INDEX_KEY, $session->sessionId);
        $pipe->execute();
    }

    public function get(string $sessionId): ?MobileSession
    {
        $data = $this->redis()->hgetall(self::KEY_PREFIX . $sessionId);
        if (empty($data)) {
            return null;
        }
        return MobileSession::fromArray($data);
    }

    public function delete(string $sessionId): void
    {
        $this->redis()->del(self::KEY_PREFIX . $sessionId);
        $this->redis()->srem(self::INDEX_KEY, $sessionId);
    }

    public function allIds(): array
    {
        return $this->redis()->smembers(self::INDEX_KEY) ?: [];
    }
}
