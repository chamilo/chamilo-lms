<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\Tests\CoreBundle\Twig;

use Chamilo\CoreBundle\Twig\SettingsHelper;
use Chamilo\Tests\AbstractApiTest;
use Chamilo\Tests\ChamiloTestTrait;
use Sylius\Bundle\SettingsBundle\Model\SettingsInterface;

class SettingsHelperTest extends AbstractApiTest
{
    use ChamiloTestTrait;

    public function testGetSettings(): void
    {
        self::bootKernel();

        $helper = self::getContainer()->get(SettingsHelper::class);

        $settings = $helper->getSettings('admin');

        $this->assertInstanceOf(SettingsInterface::class, $settings);
        $this->assertSame('chamilo_settings', $helper->getName());

        $defaultTheme = $helper->getSettingsParameter('platform.theme');

        $this->assertSame('chamilo', $defaultTheme);
    }
}
