<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\Tests\CoreBundle\Service\Update;

use Chamilo\CoreBundle\Service\Update\Dto\UpdateManifest;
use Chamilo\CoreBundle\Service\Update\UpdateConfiguration;
use Chamilo\CoreBundle\Service\Update\UpdateTrustedKeyring;
use PHPUnit\Framework\TestCase;

final class UpdateTrustedKeyringTest extends TestCase
{
    private const string OFFICIAL_KEY_ID = 'A158EB4EFF622FB3';
    private const string OFFICIAL_PUBLIC_KEY = 'RWSzL2L/TutYoT4l8BsZxjjvH3yf7fpQE0XfkZpOT1TL6mExtsf6TbAG';

    public function testOfficialReleaseKeyIsTrustedInProduction(): void
    {
        $keyring = new UpdateTrustedKeyring(new UpdateConfiguration('prod'));

        self::assertTrue($keyring->hasTrustedPublicKeys());
        self::assertSame([self::OFFICIAL_KEY_ID], $keyring->getTrustedKeyIds());
        self::assertSame(self::OFFICIAL_PUBLIC_KEY, $keyring->getPublicKeyForManifest($this->createManifest(self::OFFICIAL_KEY_ID)));
    }

    public function testSingleOfficialKeyCanVerifyManifestWithoutKeyId(): void
    {
        $keyring = new UpdateTrustedKeyring(new UpdateConfiguration('prod'));

        self::assertSame(self::OFFICIAL_PUBLIC_KEY, $keyring->getPublicKeyForManifest($this->createManifest()));
    }

    public function testUnknownManifestKeyIdIsRejected(): void
    {
        $keyring = new UpdateTrustedKeyring(new UpdateConfiguration('prod'));

        self::assertNull($keyring->getPublicKeyForManifest($this->createManifest('0000000000000000')));
    }

    private function createManifest(?string $keyId = null): UpdateManifest
    {
        return new UpdateManifest(
            'stable',
            '3.0.1',
            '2026-09-05T22:33:19+00:00',
            'https://updates.chamilo.org/assets/chamilo-3.0.1.zip',
            str_repeat('a', 64),
            'minisign',
            'https://updates.chamilo.org/sign/chamilo-3.0.1.zip.minisig',
            $keyId,
            ['php' => '>=8.3'],
        );
    }
}
