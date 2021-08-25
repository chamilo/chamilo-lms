<?php
namespace Packback\Lti1p3\ImsStorage;

use Packback\Lti1p3\Interfaces\Cache;

class ImsCache implements Cache
{

    private $cache;

    public function getLaunchData($key)
    {
        $this->loadCache();
        return $this->cache[$key];
    }

    public function cacheLaunchData($key, $jwtBody)
    {
        $this->cache[$key] = $jwtBody;
        $this->saveCache();
        return $this;
    }

    public function cacheNonce($nonce)
    {
        $this->cache['nonce'][$nonce] = true;
        $this->saveCache();
        return $this;
    }

    public function checkNonce($nonce)
    {
        $this->loadCache();
        if (!isset($this->cache['nonce'][$nonce])) {
            return false;
        }
        return true;
    }

    private function loadCache() {
        $cache = file_get_contents(sys_get_temp_dir() . '/lti_cache.txt');
        if (empty($cache)) {
            file_put_contents(sys_get_temp_dir() . '/lti_cache.txt', '{}');
            $this->cache = [];
        }
        $this->cache = json_decode($cache, true);
    }

    private function saveCache() {
        file_put_contents(sys_get_temp_dir() . '/lti_cache.txt', json_encode($this->cache));
    }
}
