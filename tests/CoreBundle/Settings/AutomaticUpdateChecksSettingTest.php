<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\Tests\CoreBundle\Settings;

use Chamilo\CoreBundle\Settings\AdminSettingsSchema;
use PHPUnit\Framework\TestCase;
use Sylius\Bundle\SettingsBundle\Schema\SettingsBuilder;

final class AutomaticUpdateChecksSettingTest extends TestCase
{
    public function testAutomaticUpdateChecksDefaultToDisabledIndependentlyOfNews(): void
    {
        $builder = new SettingsBuilder();
        (new AdminSettingsSchema())->buildSettings($builder);
        $defaults = $builder->resolve([]);

        self::assertSame('false', $defaults['automatic_update_checks']);
        self::assertSame('true', $defaults['chamilo_latest_news']);
    }
}
