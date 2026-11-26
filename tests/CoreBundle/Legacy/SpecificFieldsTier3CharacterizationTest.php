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

    public function testCodeFromNameSkipsAnOccupiedLetterRegardlessOfCase(): void
    {
        $this->field('s', 'spf3_a');

        self::assertSame('A', get_specific_field_code_from_name('science'));
    }

    public function testCreateAndRenamePreserveTheCodeAndLegacyTableColumns(): void
    {
        $this->field('k', 'Existing keywords');
        $id = add_specific_field('Keywords');
        self::assertIsInt($id);
        self::assertGreaterThan(0, $id);
        $created = get_specific_field_list(['id' => $id])[0];
        self::assertSame('A', $created['code']);
        self::assertSame('Keywords', $created['title']);
        self::assertSame('Keywords', $created[2]);
        self::assertTrue(edit_specific_field($id, "Learner's keywords"));
        $updated = get_specific_field_list(['id' => $id])[0];
        self::assertSame('A', $updated['code']);
        self::assertSame("Learner's keywords", $updated['title']);
        self::assertSame($updated['title'], $updated[2]);
        self::assertSame($created['id'], $updated['id']);
        self::assertSame('Existing keywords', get_specific_field_list(['code' => "'k'"])[0]['title']);
    }

    public function testInvalidInputAndExhaustedCodesDoNotCreateFields(): void
    {
        self::assertFalse(add_specific_field('   '));
        self::assertFalse(edit_specific_field(0, 'Invalid'));
        foreach (str_split('ABDEFGHIJKLMNPQRSTUVWXY') as $code) {
            $this->field($code, 'Existing field');
        }
        self::assertFalse(get_specific_field_code_from_name('Keywords'));
        self::assertFalse(add_specific_field('Keywords'));
        self::assertCount(23, $this->codes());
    }
}
