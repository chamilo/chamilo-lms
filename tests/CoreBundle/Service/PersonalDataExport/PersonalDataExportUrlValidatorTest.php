<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\Tests\CoreBundle\Service\PersonalDataExport;

use Chamilo\CoreBundle\Service\PersonalDataExport\DnsHostResolver;
use Chamilo\CoreBundle\Service\PersonalDataExport\PersonalDataExportUrlValidator;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class PersonalDataExportUrlValidatorTest extends TestCase
{
    /**
     * @dataProvider blockedUrlProvider
     *
     * @param list<string> $resolvedIps
     */
    public function testItRejectsDisallowedDestinations(string $url, array $resolvedIps = []): void
    {
        $validator = new PersonalDataExportUrlValidator($this->resolver($resolvedIps));

        $this->expectException(InvalidArgumentException::class);
        $validator->validate($url);
    }

    /**
     * @return iterable<string, array{0: string, 1?: list<string>}>
     */
    public static function blockedUrlProvider(): iterable
    {
        yield 'http' => ['http://example.org/export'];

        yield 'loopback ipv4' => ['https://127.0.0.1/export'];

        yield 'private 10.x' => ['https://10.1.2.3/export'];

        yield 'link-local metadata' => ['https://169.254.169.254/latest/meta-data'];

        yield 'ipv6 loopback' => ['https://[::1]/export'];

        yield 'localhost hostname' => ['https://localhost/export'];

        yield 'hostname resolving to private ip' => ['https://internal.example.org/export', ['10.0.0.8']];

        yield 'hostname resolving to metadata ip' => ['https://meta.example.org/export', ['169.254.169.254']];

        yield 'hostname resolving to loopback' => ['https://local.example.org/export', ['127.0.0.1']];

        yield 'credentials in url' => ['https://user:pass@example.org/export', ['93.184.216.34']];

        yield 'empty' => [''];
    }

    public function testItAcceptsAPublicHttpsUrlAndPinsTheResolvedIp(): void
    {
        $validator = new PersonalDataExportUrlValidator($this->resolver(['93.184.216.34']));
        $result = $validator->validate('https://example.org/gdpr-import?x=1');

        $this->assertSame('https://example.org/gdpr-import?x=1', $result['url']);
        $this->assertSame('example.org', $result['host']);
        $this->assertSame(443, $result['port']);
        $this->assertSame(['93.184.216.34'], $result['ips']);
        $this->assertSame('93.184.216.34', $result['pin_ip']);
    }

    /**
     * @param list<string> $ips
     */
    private function resolver(array $ips): DnsHostResolver
    {
        return new class($ips) implements DnsHostResolver {
            /**
             * @param list<string> $ips
             */
            public function __construct(
                private readonly array $ips
            ) {}

            public function resolve(string $host): array
            {
                return $this->ips;
            }
        };
    }
}
