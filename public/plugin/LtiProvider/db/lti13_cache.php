<?php
/* For license terms, see /license.txt */

use Packback\Lti1p3\Interfaces\ICache as Lti1p3Cache;

class Lti13Cache implements Lti1p3Cache
{
    private const CACHE_FILE = 'lti_cache.json';
    private const LAUNCH_DATA_KEY = 'launch_data';
    private const NONCE_KEY = 'nonce';
    private const ACCESS_TOKEN_KEY = 'access_tokens';

    /**
     * @var array<string, mixed>|null
     */
    private ?array $cache = null;

    public function getLaunchData(string $key): ?array
    {
        $cache = $this->loadCache();

        $data = $cache[self::LAUNCH_DATA_KEY][$key] ?? null;

        return is_array($data) ? $data : null;
    }

    public function cacheLaunchData(string $key, array $jwtBody): void
    {
        $cache = $this->loadCache();
        $cache[self::LAUNCH_DATA_KEY][$key] = $jwtBody;
        $this->saveCache($cache);
    }

    public function cacheNonce(string $nonce, string $state): void
    {
        $cache = $this->loadCache();
        $cache[self::NONCE_KEY][$nonce] = $state;
        $this->saveCache($cache);
    }

    public function checkNonceIsValid(string $nonce, string $state): bool
    {
        $cache = $this->loadCache();

        if (!isset($cache[self::NONCE_KEY][$nonce])) {
            return false;
        }

        $isValid = hash_equals((string) $cache[self::NONCE_KEY][$nonce], $state);

        // One-time use nonce
        unset($cache[self::NONCE_KEY][$nonce]);
        $this->saveCache($cache);

        return $isValid;
    }

    public function cacheAccessToken(string $key, string $accessToken): void
    {
        $cache = $this->loadCache();
        $cache[self::ACCESS_TOKEN_KEY][$key] = $accessToken;
        $this->saveCache($cache);
    }

    public function getAccessToken(string $key): ?string
    {
        $cache = $this->loadCache();

        $token = $cache[self::ACCESS_TOKEN_KEY][$key] ?? null;

        return is_string($token) && '' !== $token ? $token : null;
    }

    public function clearAccessToken(string $key): void
    {
        $cache = $this->loadCache();

        if (isset($cache[self::ACCESS_TOKEN_KEY][$key])) {
            unset($cache[self::ACCESS_TOKEN_KEY][$key]);
            $this->saveCache($cache);
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function loadCache(): array
    {
        if (null !== $this->cache) {
            return $this->cache;
        }

        $file = $this->getCacheFilePath();

        if (!is_file($file)) {
            $this->cache = $this->getEmptyCache();
            $this->saveCache($this->cache);

            return $this->cache;
        }

        $raw = file_get_contents($file);
        if (false === $raw || '' === trim($raw)) {
            $this->cache = $this->getEmptyCache();
            $this->saveCache($this->cache);

            return $this->cache;
        }

        $decoded = json_decode($raw, true);

        if (!is_array($decoded)) {
            $this->cache = $this->getEmptyCache();
            $this->saveCache($this->cache);

            return $this->cache;
        }

        $this->cache = array_merge($this->getEmptyCache(), $decoded);

        return $this->cache;
    }

    /**
     * @param array<string, mixed> $cache
     */
    private function saveCache(array $cache): void
    {
        $this->cache = array_merge($this->getEmptyCache(), $cache);

        file_put_contents(
            $this->getCacheFilePath(),
            json_encode($this->cache, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES),
            LOCK_EX
        );
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    private function getEmptyCache(): array
    {
        return [
            self::LAUNCH_DATA_KEY => [],
            self::NONCE_KEY => [],
            self::ACCESS_TOKEN_KEY => [],
        ];
    }

    private function getCacheFilePath(): string
    {
        return api_get_path(SYS_ARCHIVE_PATH).self::CACHE_FILE;
    }
}
