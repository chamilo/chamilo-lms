<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\Tests\CoreBundle\HRM;

use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Service\Mcp\McpAccessPolicy;
use Chamilo\CoreBundle\Settings\SettingsManager;
use PHPUnit\Framework\TestCase;

class McpHrmRoleCompatibilityTest extends TestCase
{
    public function testLegacyDrhSettingIsStillHonored(): void
    {
        $policy = $this->makePolicy('{"DRH":true}');

        self::assertTrue($policy->getAllowedRoles()['HRM']);
    }

    public function testCanonicalHrmSettingIsHonored(): void
    {
        $policy = $this->makePolicy('{"HRM":true}');

        self::assertTrue($policy->getAllowedRoles()['HRM']);
    }

    public function testCanonicalHrmSettingOverridesLegacyDrh(): void
    {
        $policy = $this->makePolicy('{"DRH":true,"HRM":false}');

        self::assertFalse($policy->getAllowedRoles()['HRM']);
    }

    public function testHrmAccessUsesLegacyPermissionWithoutExpandingRights(): void
    {
        $user = new User();
        $user->setRoles(['ROLE_HR']);

        self::assertTrue($this->makePolicy('{"DRH":true}')->canUse($user));
        self::assertFalse($this->makePolicy('{"DRH":true,"HRM":false}')->canUse($user));
        self::assertFalse($this->makePolicy('{"HRM":true}', false)->canUse($user));
    }

    public function testMalformedSettingDeniesAllRoles(): void
    {
        $policy = $this->makePolicy('{invalid');

        self::assertSame([], array_keys(array_filter($policy->getAllowedRoles())));
    }

    private function makePolicy(string $json, bool $enabled = true): McpAccessPolicy
    {
        $settingsManager = $this->createMock(SettingsManager::class);
        $settingsManager->method('getSetting')
            ->willReturnCallback(static function (string $key) use ($json, $enabled): string {
                return 'security.mcp_enabled' === $key ? ($enabled ? 'true' : 'false') : $json;
            })
        ;

        return new McpAccessPolicy($settingsManager);
    }
}
