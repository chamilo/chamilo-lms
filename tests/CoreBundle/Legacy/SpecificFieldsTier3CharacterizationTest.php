<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\Tests\CoreBundle\Legacy;

use Chamilo\CoreBundle\Framework\Container;
use Database;
use Doctrine\DBAL\Connection;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;

/**
 * Characterization tests for public/main/inc/lib/specific_fields_manager.lib.php (table search_engine_field).
 */
final class SpecificFieldsTier3CharacterizationTest extends KernelTestCase
{
    protected function setUp(): void
    {
        self::bootKernel();
        $container = self::getContainer();

        Container::setContainer($container);
        Container::setLegacyServices($container);
        $session = new Session(new MockArraySessionStorage());
        Container::setSession($session);
        $request = Request::create('/main/inc/lib/specific_fields_manager.lib.php');
        $request->setSession($session);
        Container::setRequest($request);
        if (!\function_exists('delete_specific_field')) {
            require_once api_get_path(SYS_CODE_PATH).'inc/lib/specific_fields_manager.lib.php';
        }
        $this->conn()->executeStatement('DELETE FROM search_engine_field');
    }

    private function conn(): Connection
    {
        return Database::getManager()->getConnection();
    }

    private function field(string $code, string $title): int
    {
        $this->conn()->executeStatement('INSERT INTO search_engine_field (code, title) VALUES (?, ?)', [$code, $title]);

        return (int) $this->conn()->lastInsertId();
    }

    private function codes(): array
    {
        return $this->conn()->fetchFirstColumn('SELECT code FROM search_engine_field ORDER BY code');
    }

    public function testDeleteSpecificFieldRemovesOnlyThatRow(): void
    {
        $a = $this->field('S', 'spf3_a');
        $b = $this->field('T', 'spf3_b');

        $this->assertNull(delete_specific_field($a));

        $this->assertSame(['T'], $this->codes());
        $this->assertSame(
            1,
            (int) $this->conn()->fetchOne('SELECT COUNT(*) FROM search_engine_field WHERE id = ?', [$b])
        );
    }

    public function testDeleteSpecificFieldCastsItsArgumentToAnInteger(): void
    {
        $a = $this->field('S', 'spf3_a');
        $this->field('T', 'spf3_b');

        delete_specific_field($a.' OR 1=1');

        $this->assertSame(['T'], $this->codes());
    }

    public function testCodeFromNameIsTheUppercasedFirstLetterWhenNothingIsStored(): void
    {
        $this->assertSame('M', get_specific_field_code_from_name('math'));
    }

    public function testCodeFromNameIsTheUppercasedFirstLetterWhenNotYetStored(): void
    {
        $this->field('S', 'spf3_a');

        $this->assertSame('M', get_specific_field_code_from_name('math'));
        $this->assertSame('T', get_specific_field_code_from_name('tools'));
    }

    public function testCodeFromNameWhenTheLetterIsStoredReturnsTheBrokenIndexLookup(): void
    {
        // Pre-existing bug: the loop returns $idx[$i] (an integer indexed like an array), so the
        // result is NULL with a warning instead of the next free letter.
        $this->field('S', 'spf3_a');

        $this->assertNull(@get_specific_field_code_from_name('science'));
    }
}
