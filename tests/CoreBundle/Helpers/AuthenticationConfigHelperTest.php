<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\Tests\CoreBundle\Helpers;

use Chamilo\CoreBundle\Helpers\AuthenticationConfigHelper;
use Generator;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

/**
 * Regression test for GH #8455 (Azure Entra ID login not working).
 *
 * TheNetworg\OAuth2\Client\Provider\Azure builds every endpoint by string
 * concatenation of urlLogin with the tenant id (see Azure.php: urlLogin.tenant.
 * '/.well-known/openid-configuration'), so the configured value MUST end with a
 * slash. An admin copying `url_login: "https://login.microsoftonline.com"` from
 * the documentation produced "login.microsoftonline.comTENANT", an unresolvable
 * host, and the login failed with "cURL error 6: Could not resolve host".
 *
 * getOAuthProviderOptions() is therefore responsible for normalizing the value
 * before it reaches the provider. These cases lock that contract: any trailing
 * slash variant an admin may write must yield the same working URL, and an
 * unconfigured value must stay out of the options array so the provider keeps
 * using its own (already correct) default.
 */
final class AuthenticationConfigHelperTest extends TestCase
{
    /**
     * @dataProvider urlLoginProvider
     */
    public function testUrlLoginAlwaysEndsWithASlash(string $configured, string $expected): void
    {
        $options = $this->createHelperWithoutDependencies()->getOAuthProviderOptions('azure', [
            'client_id' => 'client-id',
            'client_secret' => 'client-secret',
            'url_login' => $configured,
        ]);

        $this->assertSame($expected, $options['urlLogin']);
    }

    /**
     * @psalm-return Generator<string, list{string, string}, mixed, void>
     */
    public static function urlLoginProvider(): Generator
    {
        yield 'missing trailing slash' => [
            'https://login.microsoftonline.com',
            'https://login.microsoftonline.com/',
        ];

        yield 'trailing slash already present' => [
            'https://login.microsoftonline.com/',
            'https://login.microsoftonline.com/',
        ];

        yield 'surrounding whitespace' => [
            ' https://login.microsoftonline.com ',
            'https://login.microsoftonline.com/',
        ];

        yield 'national cloud endpoint' => [
            'https://login.microsoftonline.us',
            'https://login.microsoftonline.us/',
        ];
    }

    /**
     * @dataProvider unsetUrlLoginProvider
     */
    public function testUnconfiguredUrlLoginIsNotSentToTheProvider(array $config): void
    {
        $options = $this->createHelperWithoutDependencies()->getOAuthProviderOptions('azure', [
            'client_id' => 'client-id',
            'client_secret' => 'client-secret',
            ...$config,
        ]);

        $this->assertArrayNotHasKey('urlLogin', $options);
    }

    /**
     * @psalm-return Generator<string, list{array}, mixed, void>
     */
    public static function unsetUrlLoginProvider(): Generator
    {
        yield 'key absent' => [[]];

        yield 'null value' => [['url_login' => null]];

        yield 'empty string' => [['url_login' => '']];

        yield 'whitespace only' => [['url_login' => '   ']];
    }

    /**
     * urlAPI is the mirror image of urlLogin: the references Chamilo sends to Graph
     * (AzureAuthenticator, AzureSync*Command) already start with a slash, and the
     * provider appends them verbatim, so the configured value must NOT end with one
     * or every Graph call is issued against a double-slashed path.
     *
     * @dataProvider urlApiProvider
     */
    public function testUrlApiNeverEndsWithASlash(string $configured, string $expected): void
    {
        $options = $this->createHelperWithoutDependencies()->getOAuthProviderOptions('azure', [
            'client_id' => 'client-id',
            'client_secret' => 'client-secret',
            'url_api' => $configured,
        ]);

        $this->assertSame($expected, $options['urlAPI']);
        $this->assertSame('https://graph.microsoft.com/v1.0/users', $options['urlAPI'].'/v1.0/users');
    }

    /**
     * @psalm-return Generator<string, list{string, string}, mixed, void>
     */
    public static function urlApiProvider(): Generator
    {
        yield 'no trailing slash' => [
            'https://graph.microsoft.com',
            'https://graph.microsoft.com',
        ];

        yield 'trailing slash stripped' => [
            'https://graph.microsoft.com/',
            'https://graph.microsoft.com',
        ];

        yield 'surrounding whitespace' => [
            ' https://graph.microsoft.com/ ',
            'https://graph.microsoft.com',
        ];
    }

    /**
     * @dataProvider unsetUrlApiProvider
     */
    public function testUnconfiguredUrlApiIsNotSentToTheProvider(array $config): void
    {
        $options = $this->createHelperWithoutDependencies()->getOAuthProviderOptions('azure', [
            'client_id' => 'client-id',
            'client_secret' => 'client-secret',
            ...$config,
        ]);

        $this->assertArrayNotHasKey('urlAPI', $options);
    }

    /**
     * @psalm-return Generator<string, list{array}, mixed, void>
     */
    public static function unsetUrlApiProvider(): Generator
    {
        yield 'key absent' => [[]];

        yield 'null value' => [['url_api' => null]];

        yield 'empty string' => [['url_api' => '']];

        yield 'whitespace only' => [['url_api' => '   ']];
    }

    public function testNormalizedUrlLoginBuildsAResolvableDiscoveryUrl(): void
    {
        $options = $this->createHelperWithoutDependencies()->getOAuthProviderOptions('azure', [
            'client_id' => 'client-id',
            'client_secret' => 'client-secret',
            'url_login' => 'https://login.microsoftonline.com',
            'tenant' => '6d53d383-2e87-49dd-a756-d6156a484698',
        ]);

        // Same concatenation the provider performs in getOpenIdConfiguration().
        $discoveryUrl = $options['urlLogin'].$options['tenant'].'/.well-known/openid-configuration';

        $this->assertSame(
            'https://login.microsoftonline.com/6d53d383-2e87-49dd-a756-d6156a484698/.well-known/openid-configuration',
            $discoveryUrl
        );
    }

    /**
     * getOAuthProviderOptions() is a pure mapper: it touches neither the parameter
     * bag nor the access URL helper, and the latter is a readonly class PHPUnit 9
     * cannot double.
     */
    private function createHelperWithoutDependencies(): AuthenticationConfigHelper
    {
        $reflection = new ReflectionClass(AuthenticationConfigHelper::class);

        return $reflection->newInstanceWithoutConstructor();
    }
}
