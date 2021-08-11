<?php
/* For license terms, see /license.txt */

use Packback\Lti1p3\Interfaces\Cache as Lti1p3Cache;

class Lti13Cache implements Lti1p3Cache
{
    public const NONCE_PREFIX = 'nonce_';

    private $cache;

    public function getLaunchData($key)
    {
        $this->loadCache();

        return $this->cache[$key];
    }

    public function cacheLaunchData($key, $jwtBody): Lti13Cache
    {
        $this->cache[$key] = $jwtBody;
        $this->saveCache();

        return $this;
    }

    public function cacheNonce($nonce): Lti13Cache
    {
        $this->cache['nonce'][$nonce] = true;
        $this->saveCache();

        return $this;
    }

    public function checkNonce($nonce): bool
    {
        $this->loadCache();
        if (!isset($this->cache['nonce'][$nonce])) {
            return false;
        }

        return true;
    }

    private function loadCache()
    {
        $cache = file_get_contents(api_get_path(SYS_ARCHIVE_PATH).'lti_cache.txt');
        if (empty($cache)) {
            file_put_contents(api_get_path(SYS_ARCHIVE_PATH).'lti_cache.txt', '{}');
            $this->cache = [];
        }
        $this->cache = json_decode($cache, true);
    }

    private function saveCache()
    {
        file_put_contents(api_get_path(SYS_ARCHIVE_PATH).'lti_cache.txt', json_encode($this->cache));
    }
}
