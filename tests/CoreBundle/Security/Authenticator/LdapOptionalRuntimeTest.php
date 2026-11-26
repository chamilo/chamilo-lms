<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\Tests\CoreBundle\Security\Authenticator;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Ldap\Exception\LdapException;
use Symfony\Component\Ldap\Ldap;

use const LDAP_ESCAPE_FILTER;

final class LdapOptionalRuntimeTest extends WebTestCase
{
    /**
     * Run this test with and without ext-ldap. An unrelated firewall request must
     * never instantiate the native adapter merely because LDAP is registered.
     */
    public function testNonLdapFirewallRequestDoesNotRequireTheExtension(): void
    {
        $client = self::createClient();
        $client->request('GET', '/login/2fa');

        self::assertResponseRedirects('/login');
        self::assertInstanceOf(Ldap::class, self::getContainer()->get(Ldap::class));

        // Exercise the reset path explicitly rather than relying only on teardown.
        self::getContainer()->get(Ldap::class)->reset();
        $client->request('GET', '/login/2fa');
        self::assertResponseRedirects('/login');
    }

    public function testNativeAdapterIsOnlyRequiredByLdapOperations(): void
    {
        self::bootKernel();
        $ldap = self::getContainer()->get(Ldap::class);
        self::assertInstanceOf(Ldap::class, $ldap);
        $ldap->reset();

        if (!\extension_loaded('ldap')) {
            $this->expectException(LdapException::class);
            $this->expectExceptionMessage('The LDAP PHP extension is not enabled.');

            try {
                $ldap->escape('test*');
            } finally {
                $ldap->reset();
            }

            return;
        }

        self::assertSame('test\2a', $ldap->escape('test*', '', LDAP_ESCAPE_FILTER));
        $ldap->reset();
        self::assertSame('test\2a', $ldap->escape('test*', '', LDAP_ESCAPE_FILTER));
    }
}
