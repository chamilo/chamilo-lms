<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\Tests\CoreBundle\Service\Update;

use Chamilo\CoreBundle\Service\Update\UpdateConfiguration;
use PHPUnit\Framework\TestCase;

final class UpdateConfigurationTest extends TestCase
{
    public function testUsesOfficialManifestByDefault(): void
    {
        $configuration = new UpdateConfiguration('prod');

        self::assertSame(UpdateConfiguration::OFFICIAL_MANIFEST_SOURCE, $configuration->getDefaultManifestSource());
        self::assertFalse($configuration->allowsDevelopmentUpdateTools());
        self::assertFalse($configuration->allowsLocalPaths());
        self::assertFalse($configuration->allowsSkipSignature());
    }

    /**
     * @dataProvider enabledBooleanValues
     */
    public function testDevelopmentToolsCanBeEnabledFromServerEnvironmentValue(string $value): void
    {
        $configuration = new UpdateConfiguration('dev', $value);

        self::assertTrue($configuration->allowsDevelopmentUpdateTools());
        self::assertTrue($configuration->allowsLocalPaths());
        self::assertTrue($configuration->allowsSkipSignature());
        self::assertNotNull($configuration->getLocalTestManifestSource());
        self::assertNotNull($configuration->getLocalTestPackagePath());
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function enabledBooleanValues(): iterable
    {
        yield 'one' => ['1'];

        yield 'true' => ['true'];

        yield 'yes' => ['yes'];

        yield 'on' => ['on'];

        yield 'uppercase' => ['TRUE'];
    }

    public function testUnknownDevelopmentValueFailsClosed(): void
    {
        $configuration = new UpdateConfiguration('dev', 'invalid');

        self::assertFalse($configuration->allowsDevelopmentUpdateTools());
    }

    public function testOfficialManifestUsesVersionIndependentStablePath(): void
    {
        $configuration = new UpdateConfiguration('prod');

        self::assertSame('https://updates.chamilo.org/latest-stable.json', $configuration->getOfficialManifestSource());
        self::assertSame('https://updates.chamilo.org', $configuration->getOfficialManifestOrigin());
    }

    public function testOfficialManifestPathCanChangeWithoutChangingOrigin(): void
    {
        $configuration = new UpdateConfiguration('prod');

        self::assertTrue($configuration->isAllowedOfficialManifestUrl('https://updates.chamilo.org/latest-stable.json'));
        self::assertTrue($configuration->isAllowedOfficialManifestUrl('https://updates.chamilo.org/channels/latest-unstable.json'));
        self::assertTrue($configuration->isAllowedOfficialUpdateUrl('https://updates.chamilo.org/assets/chamilo-3.0.1.zip'));
        self::assertTrue($configuration->isAllowedOfficialUpdateUrl('https://updates.chamilo.org/sign/chamilo-3.0.1.zip.minisig'));
    }

    public function testOfficialManifestRejectsProtocolOrDomainChanges(): void
    {
        $configuration = new UpdateConfiguration('prod');

        self::assertFalse($configuration->isAllowedOfficialManifestUrl('http://updates.chamilo.org/latest-stable.json'));
        self::assertFalse($configuration->isAllowedOfficialManifestUrl('https://example.org/latest-stable.json'));
        self::assertFalse($configuration->isAllowedOfficialManifestUrl('https://updates.chamilo.org.example.org/latest-stable.json'));
        self::assertFalse($configuration->isAllowedOfficialManifestUrl('https://user@updates.chamilo.org/latest-stable.json'));
        self::assertFalse($configuration->isAllowedOfficialUpdateUrl('https://example.org/chamilo-3.0.1.zip'));
        self::assertFalse($configuration->isAllowedOfficialUpdateUrl('http://updates.chamilo.org/assets/chamilo-3.0.1.zip'));
    }
}
