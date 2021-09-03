<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\Tests\CourseBundle\Settings;

use Chamilo\CourseBundle\Settings\SettingsCourseManager;
use Chamilo\Tests\AbstractApiTest;
use Chamilo\Tests\ChamiloTestTrait;
use InvalidArgumentException;

class SettingsCourseManagerTest extends AbstractApiTest
{
    use ChamiloTestTrait;

    public function testCreate(): void
    {
        self::bootKernel();

        $em = $this->getManager();
        $settingsManager = self::getContainer()->get(SettingsCourseManager::class);

        $this->expectException(InvalidArgumentException::class);
        $settingsManager->getSetting('institution');

        $platform = $settingsManager->getSetting('platform.institution');
        $this->assertNotEmpty($platform);
        $this->assertTrue(\count($settingsManager->getSchemas()) > 0);
    }
}
