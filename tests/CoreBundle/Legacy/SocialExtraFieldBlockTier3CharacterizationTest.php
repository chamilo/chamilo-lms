<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\Tests\CoreBundle\Legacy;

use Chamilo\CoreBundle\Framework\Container;
use Chamilo\Tests\ChamiloTestTrait;
use ChamiloSession;
use Doctrine\DBAL\Connection;
use SocialManager;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Session as HttpSession;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;

/**
 * Characterization of SocialManager::getExtraFieldBlock() (social.lib.php), double-select branch: the stored
 * value is "optionId::optionId" and each id is resolved to the display text of extra_field_options.
 *
 * Fixtures: the admin account (id 1), one user extra field of type double select (xf3_ prefix) and raw options.
 */
final class SocialExtraFieldBlockTier3CharacterizationTest extends KernelTestCase
{
    use ChamiloTestTrait;

    private Connection $conn;

    protected function setUp(): void
    {
        self::bootKernel();
        $container = self::getContainer();
        Container::setContainer($container);
        Container::setLegacyServices($container);
        $httpSession = new HttpSession(new MockArraySessionStorage());
        Container::setSession($httpSession);
        $request = Request::create('/main/social/profile.php');
        $request->setSession($httpSession);
        Container::setRequest($request);
        ChamiloSession::erase('_user');
        $this->conn = $container->get('doctrine')->getConnection();
        // Only this test's fields and values are visible to the block.
        $this->conn->executeStatement('DELETE FROM extra_field_values');
        $this->conn->executeStatement('DELETE FROM extra_field_options');
        $this->conn->executeStatement('DELETE FROM extra_field WHERE item_type = 1');
    }

    protected function tearDown(): void
    {
        ChamiloSession::erase('_user');
        self::getContainer()->get('security.token_storage')->setToken(null);
        parent::tearDown();
    }

    private function field(string $variable, string $label, int $visibleToOthers = 1): int
    {
        $this->conn->executeStatement(
            'INSERT INTO extra_field (item_type, value_type, variable, display_text, default_value, field_order,
                visible_to_self, visible_to_others, changeable, filter, created_at, auto_remove)
             VALUES (1, 8, ?, ?, NULL, 1, 1, ?, 1, 0, NOW(), 0)',
            [$variable, $label, $visibleToOthers]
        );

        return (int) $this->conn->lastInsertId();
    }

    private function option(int $fieldId, string $text, int $order): int
    {
        $this->conn->executeStatement(
            'INSERT INTO extra_field_options (field_id, option_value, display_text, option_order) VALUES (?, ?, ?, ?)',
            [$fieldId, $text, $text, $order]
        );

        return (int) $this->conn->lastInsertId();
    }

    private function value(int $fieldId, string $value): void
    {
        $this->conn->executeStatement(
            "INSERT INTO extra_field_values (field_id, item_id, field_value, created_at, updated_at)
             VALUES (?, 1, ?, NOW(), NOW())",
            [$fieldId, $value]
        );
    }

    public function testDoubleSelectIdsAreResolvedToTheirDisplayTexts(): void
    {
        $field = $this->field('xf3_double', 'region');
        $a = $this->option($field, 'Europe', 1);
        $b = $this->option($field, 'France', 2);
        $this->option($field, 'Asia', 3);
        $this->value($field, $a.'::'.$b);

        $this->assertSame(
            [['variable' => 'xf3_double', 'label' => 'Region', 'value' => ['Europe', 'France']]],
            SocialManager::getExtraFieldBlock(1, true)
        );

        $html = SocialManager::getExtraFieldBlock(1);
        $this->assertIsString($html);
        $this->assertStringContainsString('Region:  Europe France</li>', $html);
    }

    public function testAnUnknownOptionIdGivesANullEntry(): void
    {
        $field = $this->field('xf3_double', 'region');
        $a = $this->option($field, 'Europe', 1);
        $this->value($field, $a.'::999999');

        $this->assertSame(
            [['variable' => 'xf3_double', 'label' => 'Region', 'value' => ['Europe', null]]],
            // (The old code raised an "Undefined array key 0" warning here; the entry is null either way.)
            SocialManager::getExtraFieldBlock(1, true)
        );
    }

    public function testAFieldHiddenFromOthersIsNotListed(): void
    {
        $field = $this->field('xf3_double', 'region', 0);
        $a = $this->option($field, 'Europe', 1);
        $this->value($field, $a.'::'.$a);

        $this->assertSame([], SocialManager::getExtraFieldBlock(1, true));
    }

    public function testASingleIdWithoutSeparatorIsResolved(): void
    {
        $field = $this->field('xf3_double', 'region');
        $this->option($field, 'Europe', 1);
        $b = $this->option($field, 'France', 2);
        $this->value($field, (string) $b);

        $this->assertSame('France', SocialManager::getExtraFieldBlock(1, true)[0]['value'][0]);
    }

    public function testASqlFragmentInTheStoredValueIsNotInterpreted(): void
    {
        $field = $this->field('xf3_double', 'region');
        $this->option($field, 'Europe', 1);
        $b = $this->option($field, 'France', 2);
        $this->value($field, "x' OR '1'='1");

        // Security: the stored value is user data. The old string-spliced query matched every option row
        // (so the first one, 'Europe', was returned); the bound parameter matches nothing.
        $this->assertSame([null], SocialManager::getExtraFieldBlock(1, true)[0]['value']);
        $this->assertNotSame($b, 0);
    }
}
