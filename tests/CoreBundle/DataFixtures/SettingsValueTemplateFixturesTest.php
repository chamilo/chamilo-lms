<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\Tests\CoreBundle\DataFixtures;

use Chamilo\CoreBundle\DataFixtures\SettingsValueTemplateFixtures;
use PHPUnit\Framework\TestCase;

final class SettingsValueTemplateFixturesTest extends TestCase
{
    public function testSecurityTemplatesIncludeMcpAllowedRoles(): void
    {
        $securityTemplates = SettingsValueTemplateFixtures::getTemplatesGrouped()['security'];
        $templatesByVariable = array_column($securityTemplates, null, 'variable');

        self::assertArrayHasKey('mcp_allowed_roles', $templatesByVariable);
        self::assertSame(
            [
                'ADMIN' => true,
                'COURSEMANAGER' => true,
                'STUDENT' => false,
                'DRH' => false,
                'SESSIONADMIN' => false,
                'STUDENT_BOSS' => false,
                'INVITEE' => false,
            ],
            $templatesByVariable['mcp_allowed_roles']['json_example']
        );
    }
}
