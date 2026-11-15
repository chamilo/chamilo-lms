<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\Tests\CoreBundle\Service\PersonalDataExport;

use Chamilo\CoreBundle\Service\PersonalDataExport\PersonalDataExportSigner;
use Chamilo\CoreBundle\Settings\SettingsManager;
use PHPUnit\Framework\TestCase;

final class PersonalDataExportSignerTest extends TestCase
{
    public function testItSignsTheCanonicalPayload(): void
    {
        $settings = $this->createMock(SettingsManager::class);
        $settings->method('getSetting')->with(PersonalDataExportSigner::SETTING_SECRET, true)->willReturn('unit-test-secret');

        $signer = new PersonalDataExportSigner($settings);
        $body = '{"format":"chamilo-personal-data"}';
        $signed = $signer->sign($body, 1_700_000_000, 'deadbeef');

        $expected = hash_hmac('sha256', "1700000000\ndeadbeef\n".hash('sha256', $body), 'unit-test-secret');

        $this->assertSame($expected, $signed['signature']);
        $this->assertSame('sha256='.$expected, $signed['headers'][PersonalDataExportSigner::HEADER_SIGNATURE]);
        $this->assertSame('1700000000', $signed['headers'][PersonalDataExportSigner::HEADER_TIMESTAMP]);
        $this->assertSame('deadbeef', $signed['headers'][PersonalDataExportSigner::HEADER_NONCE]);
        $this->assertTrue(hash_equals($expected, $signer->computeSignature($body, 1_700_000_000, 'deadbeef')));
    }
}
