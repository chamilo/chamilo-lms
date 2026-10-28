<?php

/* For licensing terms, see /license.txt */

use PHPUnit\Framework\TestCase;

if (!interface_exists('Packback\Lti1p3\Interfaces\Cookie')) {
    eval('namespace Packback\Lti1p3\Interfaces; interface Cookie { public function getCookie($name); public function setCookie($name, $value, $exp = 3600, $options = []); }');
}

require_once dirname(__DIR__, 2).'/plugin/lti_provider/db/lti13_cookie.php';

class LtiProviderCookieSecurityTest extends TestCase
{
    protected function tearDown(): void
    {
        $_REQUEST = [];
        $_COOKIE = [];
    }

    public function testStateSentByTheClientIsNotTrustedAsTheStoredCookie()
    {
        $_REQUEST['state'] = 'forged';
        $_COOKIE = [];

        $this->assertFalse((new Lti13Cookie())->getCookie('lti1p3_forged'));
    }

    public function testStoredCookieAndLegacyFallbackAreReturned()
    {
        $cookie = new Lti13Cookie();

        $_COOKIE = ['lti1p3_abc' => 'main'];
        $this->assertSame('main', $cookie->getCookie('lti1p3_abc'));

        $_COOKIE = ['LEGACY_lti1p3_abc' => 'legacy'];
        $this->assertSame('legacy', $cookie->getCookie('lti1p3_abc'));
    }
}
