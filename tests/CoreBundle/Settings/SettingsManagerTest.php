<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\Tests\CoreBundle\Settings;

use Chamilo\CoreBundle\Entity\SettingsCurrent;
use Chamilo\CoreBundle\Repository\SettingsCurrentRepository;
use Chamilo\CoreBundle\Settings\SettingsManager;
use Chamilo\Tests\AbstractApiTest;
use Chamilo\Tests\ChamiloTestTrait;
use InvalidArgumentException;
use Sylius\Bundle\SettingsBundle\Model\SettingsInterface;

class SettingsManagerTest extends AbstractApiTest
{
    use ChamiloTestTrait;

    public function testCreate(): void
    {
        $settingsManager = self::getContainer()->get(SettingsManager::class);

        $this->expectException(InvalidArgumentException::class);
        $settingsManager->getSetting('institution');

        $platform = $settingsManager->getSetting('platform.institution');
        $this->assertNotEmpty($platform);
        $this->assertTrue(\count($settingsManager->getSchemas()) > 0);
    }

    public function testGetSetting(): void
    {
        $settingsManager = self::getContainer()->get(SettingsManager::class);
        $this->expectException(InvalidArgumentException::class);
        $settingsManager->getSetting('platform.new_setting_aaa');

        $this->expectException(InvalidArgumentException::class);
        $settingsManager->getSetting('platform2222.institution');
    }

    public function testUpdate(): void
    {
        $settingsManager = self::getContainer()->get(SettingsManager::class);

        $oldPlatform = $settingsManager->getSetting('platform.institution');

        $settings = $settingsManager->load('platform');

        $this->assertInstanceOf(SettingsInterface::class, $settings);
        $this->assertSame('chamilo_core.settings.platform', $settings->getSchemaAlias());
        $this->assertNotEmpty($settings->getParameters()['institution']);
        $this->assertTrue($settings->has('institution'));
        $this->assertSame($oldPlatform, $settings->get('institution'));
    }

    public function testUpdateSetting(): void
    {
        $settingsManager = static::getContainer()->get(SettingsManager::class);
        $settingsManager->setUrl($this->getAccessUrl());

        $repo = self::getContainer()->get(SettingsCurrentRepository::class);
        /** @var SettingsCurrent $settingEntity */
        $settingEntity = $repo->findOneBy(['variable' => 'badge_assignation_notification']);
        $this->assertSame('false', $settingEntity->getSelectedValue());

        $badgeSetting = $settingsManager->getSetting('skill.badge_assignation_notification');
        $this->assertSame('false', $badgeSetting);

        // Update
        $settingsManager->updateSetting('skill.badge_assignation_notification', 'true');

        $badgeSetting = $settingsManager->getSetting('skill.badge_assignation_notification');
        $this->assertSame('false', $badgeSetting);

        $settingEntity = $repo->findOneBy(['variable' => 'badge_assignation_notification']);
        $this->assertSame('true', $settingEntity->getSelectedValue());

        $badgeSetting = $settingsManager->getSetting('skill.badge_assignation_notification', true);
        $this->assertSame('true', $badgeSetting);

        $this->expectException(InvalidArgumentException::class);
        $settingsManager->updateSetting('haha', 'true');

        $this->expectException(InvalidArgumentException::class);
        $settingsManager->updateSetting('skill.hoho', 'true');
    }

    public function testGetParametersFromKeyword(): void
    {
        $settingsManager = self::getContainer()->get(SettingsManager::class);
        $parameters = $settingsManager->getParametersFromKeyword('platform', 'institution');
        $this->assertCount(3, $parameters);
        $this->assertTrue(isset($parameters['institution']));

        $settingsManager = self::getContainer()->get(SettingsManager::class);
        $settings = $settingsManager->load('platform');
        $settingsCount = \count($settings);

        $parameters = $settingsManager->getParametersFromKeyword('platform');
        $this->assertCount($settingsCount, $parameters);
        $this->assertTrue(isset($parameters['institution']));
    }

    public function testGetParametersFromKeywordOrderedByCategory(): void
    {
        $settingsManager = self::getContainer()->get(SettingsManager::class);
        $parameters = $settingsManager->getParametersFromKeywordOrderedByCategory('institution');
        $this->assertTrue(isset($parameters['platform']));
    }
}
