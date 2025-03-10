<?php
// src/Service/RedisService.php  backend\src\Shared\Service\RedisService.php
namespace App\Shared\Service;

use Predis\Client;

class RedisService
{
    private Client $redis;

    public function __construct(string $redisDsn)
    {
        $this->redis = new Client($redisDsn);
    }

    public function setToken(string $key, string $value, int $ttl): void
    {
        $this->redis->set($key, $value);
        $this->redis->expire($key, $ttl);
    }

    public function getToken(string $key): ?string
    {
        return $this->redis->get($key);
    }

    public function deleteToken(string $key): void
    {
        $this->redis->del([$key]);
    }
}