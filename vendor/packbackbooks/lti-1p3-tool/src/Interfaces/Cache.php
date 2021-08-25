<?php
namespace Packback\Lti1p3\Interfaces;

interface Cache
{
    public function getLaunchData($key);
    public function cacheLaunchData($key, $jwtBody);
    public function cacheNonce($nonce);
    public function checkNonce($nonce);
}
