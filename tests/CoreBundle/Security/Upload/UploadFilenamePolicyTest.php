<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\Tests\CoreBundle\Security\Upload;

use Chamilo\CoreBundle\Security\Upload\UploadFilenamePolicy;
use Chamilo\CoreBundle\Settings\SettingsManager;
use PHPUnit\Framework\TestCase;

final class UploadFilenamePolicyTest extends TestCase
{
    public function testOfficeExerciseFormatsAreAllowedByTheBuiltInWhitelist(): void
    {
        $policy = new UploadFilenamePolicy($this->settingsManager([
            'document.upload_extensions_list_type' => 'whitelist',
            'document.upload_extensions_skip' => 'true',
            'document.upload_extensions_whitelist' => '',
            'document.upload_extensions_blacklist' => '',
            'document.upload_extensions_replace_by' => 'txt',
            'editor.enabled_support_svg' => 'false',
        ]));

        foreach (['answer.doc', 'answer.docx', 'answer.xls', 'answer.xlsx'] as $fileName) {
            self::assertTrue($policy->filter($fileName)['allowed'], $fileName.' should be allowed.');
        }

        self::assertFalse($policy->filter('answer.exe')['allowed']);
    }

    public function testExplicitBlacklistStillOverridesBuiltInOfficeSupport(): void
    {
        $policy = new UploadFilenamePolicy($this->settingsManager([
            'document.upload_extensions_list_type' => 'blacklist',
            'document.upload_extensions_skip' => 'true',
            'document.upload_extensions_whitelist' => '',
            'document.upload_extensions_blacklist' => 'docx;xlsx',
            'document.upload_extensions_replace_by' => 'txt',
            'editor.enabled_support_svg' => 'false',
        ]));

        self::assertFalse($policy->filter('answer.docx')['allowed']);
        self::assertFalse($policy->filter('answer.xlsx')['allowed']);
        self::assertTrue($policy->filter('answer.doc')['allowed']);
    }

    /**
     * @param array<string, mixed> $values
     */
    private function settingsManager(array $values): SettingsManager
    {
        $settingsManager = $this->createMock(SettingsManager::class);
        $settingsManager
            ->method('getSetting')
            ->willReturnCallback(
                static fn (string $name, mixed ...$unused): mixed => $values[$name] ?? ''
            )
        ;

        return $settingsManager;
    }
}
