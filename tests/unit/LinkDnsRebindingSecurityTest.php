<?php

/* For licensing terms, see /license.txt */

use Chamilo\CoreBundle\Component\Http\SafeHttp;
use PHPUnit\Framework\TestCase;

require_once dirname(__DIR__, 2).'/src/Chamilo/CoreBundle/Component/Http/SafeHttp.php';
require_once dirname(__DIR__, 2).'/main/inc/lib/model.lib.php';
require_once dirname(__DIR__, 2).'/main/inc/lib/link.lib.php';

class LinkDnsRebindingSecurityTest extends TestCase
{
    public function testPrivateAndUnsupportedUrlsAreRejectedBeforeAnyRequest()
    {
        foreach ([
            'http://127.0.0.1/',
            'http://10.0.0.1/',
            'http://169.254.169.254/latest/meta-data/',
            'http://[::1]/',
            'file:///etc/passwd',
            'http://user:password@example.com/',
            'http://example.com\\@127.0.0.1/',
        ] as $url) {
            $this->assertFalse(Link::checkUrl($url), $url);
        }
    }

    public function testPinnedOptionsDisableRedirectsAndProxies()
    {
        $options = $this->invokePrivate('buildSafeCheckOptions', [
            'https://example.com:8443/', '8.8.8.8',
        ]);

        $this->assertFalse($options['allow_redirects']);
        $this->assertFalse($options['http_errors']);
        $this->assertSame('', $options['proxy']);
        $this->assertSame(['example.com:8443:8.8.8.8'], $options['curl'][CURLOPT_RESOLVE]);
        $this->assertSame('', $options['curl'][CURLOPT_PROXY]);
        $this->assertFalse($options['curl'][CURLOPT_FOLLOWLOCATION]);
        $this->assertTrue($options['curl'][CURLOPT_SSL_VERIFYPEER]);
        $this->assertSame(2, $options['curl'][CURLOPT_SSL_VERIFYHOST]);
        if (defined('CURLOPT_NOPROXY')) {
            $this->assertSame('*', $options['curl'][CURLOPT_NOPROXY]);
        }
    }

    public function testIpv6ResolveEntryUsesBrackets()
    {
        $options = $this->invokePrivate('buildSafeCheckOptions', [
            'https://example.com/', '2606:4700:4700::1111',
        ]);
        $this->assertSame(
            ['example.com:443:[2606:4700:4700::1111]'],
            $options['curl'][CURLOPT_RESOLVE]
        );
    }

    public function testLiteralIpDoesNotRequireDnsPinning()
    {
        $options = $this->invokePrivate('buildSafeCheckOptions', [
            'https://[2606:4700:4700::1111]/', '2606:4700:4700::1111',
        ]);
        $this->assertArrayNotHasKey(CURLOPT_RESOLVE, $options['curl']);
    }

    public function testMalformedUrlsAreRejected()
    {
        foreach ([
            '', 'https://user:pass@example.com/',
            'http://example.com\\@127.0.0.1/',
            'https://example..com/', 'http://example.com:0/',
            "http://example.com/\nmalformed",
        ] as $url) {
            $this->assertFalse($this->invokePrivate('isCheckableUrl', [$url]), $url);
        }
        $this->assertTrue($this->invokePrivate('isCheckableUrl', ['https://example.com/']));
        $this->assertTrue($this->invokePrivate('isCheckableUrl', ['https://[2606:4700:4700::1111]/']));
    }

    private function invokePrivate(string $name, array $args)
    {
        $method = new ReflectionMethod(Link::class, $name);
        $method->setAccessible(true);

        return $method->invokeArgs(null, $args);
    }
}
