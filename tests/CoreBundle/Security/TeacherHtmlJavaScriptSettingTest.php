<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\Tests\CoreBundle\Security;

use Chamilo\CoreBundle\DataFixtures\SettingsCurrentFixtures;
use Chamilo\CoreBundle\Settings\SecuritySettingsSchema;
use PHPUnit\Framework\TestCase;
use Security;
use Sylius\Bundle\SettingsBundle\Schema\SettingsBuilder;

final class TeacherHtmlJavaScriptSettingTest extends TestCase
{
    /**
     * @dataProvider policyValues
     */
    public function testOnlyAnExplicitDisableEnforcesFiltering(mixed $setting, bool $mustFilter): void
    {
        if (!class_exists(Security::class, false)) {
            require_once \dirname(__DIR__, 3).'/public/main/inc/lib/security.lib.php';
        }

        self::assertSame($mustFilter, Security::shouldFilterTeacherHtml($setting));
    }
    public static function policyValues(): array
    {
        return [
            'default enabled' => ['true', false],
            'boolean enabled' => [true, false],
            'missing setting' => [null, false],
            'empty setting' => ['', false],
            'disabled' => ['false', true],
            'boolean disabled' => [false, true],
            'numeric zero' => [0, true],
            'string zero' => ['0', true],
        ];
    }

    public function testSecuritySchemaDefaultsToTrustedTeacherBehavior(): void
    {
        $builder = new SettingsBuilder();
        (new SecuritySettingsSchema())->buildSettings($builder);
        $defaults = $builder->resolve([]);

        self::assertSame('true', $defaults['teacher_use_js']);
    }

    public function testSettingMetadataIsRegistered(): void
    {
        $settings = SettingsCurrentFixtures::getNewConfigurationSettings()['security'];
        $metadata = array_values(array_filter($settings, static fn (array $setting): bool => 'teacher_use_js' === $setting['name']));

        self::assertCount(1, $metadata);
        self::assertSame('true', $metadata[0]['selected_value']);
        self::assertSame('Teachers can use JavaScript in WYSIWYG editors', $metadata[0]['title']);
    }
}
