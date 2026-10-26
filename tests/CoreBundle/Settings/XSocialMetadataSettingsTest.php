<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\Tests\CoreBundle\Settings;

use Chamilo\CoreBundle\DataFixtures\SettingsCurrentFixtures;
use Chamilo\CoreBundle\Settings\TrackingSettingsSchema;
use PHPUnit\Framework\TestCase;
use Sylius\Bundle\SettingsBundle\Schema\SettingsBuilder;

final class XSocialMetadataSettingsTest extends TestCase
{
    public function testExistingSettingKeysArePreservedForCardCompatibility(): void
    {
        $builder = new SettingsBuilder();
        (new TrackingSettingsSchema())->buildSettings($builder);
        $defaults = $builder->resolve([]);

        self::assertArrayHasKey('meta_twitter_site', $defaults);
        self::assertArrayHasKey('meta_twitter_creator', $defaults);
        self::assertArrayHasKey('meta_image_path', $defaults);
    }

    public function testSocialMetadataLabelsAndHelpUseXBranding(): void
    {
        $tracking = [];
        foreach (SettingsCurrentFixtures::getExistingSettings()['tracking'] as $setting) {
            $tracking[$setting['name']] = $setting;
        }

        self::assertSame('X Site account', $tracking['meta_twitter_site']['title']);
        self::assertSame('X Creator account', $tracking['meta_twitter_creator']['title']);

        foreach (['meta_twitter_site', 'meta_twitter_creator', 'meta_image_path'] as $name) {
            self::assertStringContainsString('X', $tracking[$name]['comment']);
            self::assertStringNotContainsString('Twitter', $tracking[$name]['title']);
            self::assertStringNotContainsString('Twitter', $tracking[$name]['comment']);
        }
    }
}
