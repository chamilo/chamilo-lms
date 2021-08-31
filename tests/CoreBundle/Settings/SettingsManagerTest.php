<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\Tests\CoreBundle\Settings;

use Chamilo\CoreBundle\Settings\SettingsManager;
use Chamilo\Tests\AbstractApiTest;
use Chamilo\Tests\ChamiloTestTrait;
use InvalidArgumentException;

class SettingsManagerTest extends AbstractApiTest
{
    use ChamiloTestTrait;

    public function testCreate(): void
    {
        self::bootKernel();

        $settingsManager = self::getContainer()->get(SettingsManager::class);

        $this->expectException(InvalidArgumentException::class);
        $settingsManager->getSetting('institution');

        $platform = $settingsManager->getSetting('platform.institution');
        $this->assertNotEmpty($platform);
        $this->assertTrue(\count($settingsManager->getSchemas()) > 0);
    }
}
