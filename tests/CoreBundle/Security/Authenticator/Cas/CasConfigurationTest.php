<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\Tests\CoreBundle\Security\Authenticator\Cas;

use Chamilo\CoreBundle\Security\Authenticator\Cas\CasConfiguration;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;

final class CasConfigurationTest extends TestCase
{
    public function testLegacyProtocolAndCreationModes(): void
    {
        $config = CasConfiguration::normalize('cas.example.org', '/cas/', '443', 'CAS2', 'platform', false);
        self::assertSame([
            'base' => 'https://cas.example.org/cas',
            'validation' => '/serviceValidate',
            'creation' => 'platform',
            'ldap' => false,
        ], $config);
        self::assertSame('/validate', CasConfiguration::normalize('localhost', '', '9443', 'CAS1', 'false', false)['validation']);
        self::assertSame('/p3/serviceValidate', CasConfiguration::normalize('localhost', 'cas', '', 'CAS3', 'extldap', true)['validation']);
        self::assertSame('extldap', CasConfiguration::normalize('localhost', '', '', 'CAS2', 'extldap', false)['creation']);
        self::assertSame('false', CasConfiguration::normalize('localhost', '', '', 'CAS2', 'unknown', false)['creation']);
    }

    public function testUnsafeAndUnsupportedConfigurationsAreRejected(): void
    {
        foreach ([
            ['http://cas.example.org', '/cas', '443', 'CAS2'],
            ['cas.example.org@evil.example', '/cas', '443', 'CAS2'],
            ['cas.example.org', '/cas?redirect=x', '443', 'CAS2'],
            ['cas.example.org', '/cas/../other', '443', 'CAS2'],
            ['cas.example.org', '/cas', '65536', 'CAS2'],
            ['cas.example.org', '/cas', '443', 'SAML'],
        ] as [$host, $path, $port, $protocol]) {
            self::assertNull(CasConfiguration::normalize($host, $path, $port, $protocol, 'false', false));
        }
    }

    public function testCanonicalPortalMustMatchTheRequest(): void
    {
        self::assertTrue(CasConfiguration::matchesPortal('https://portal.example.org/', Request::create('https://portal.example.org/connect/cas')));
        self::assertFalse(CasConfiguration::matchesPortal('https://portal.example.org/', Request::create('https://other.example.org/connect/cas')));
        self::assertFalse(CasConfiguration::matchesPortal('https://portal.example.org/', Request::create('http://portal.example.org/connect/cas')));
    }
}
