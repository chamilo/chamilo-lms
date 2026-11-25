<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\Tests\CoreBundle\Legacy;

use Chamilo\CoreBundle\Framework\Container;
use Chamilo\Tests\AbstractApiTest;
use Chamilo\Tests\ChamiloTestTrait;
use Database;
use Doctrine\DBAL\Connection;
use ExtraFieldOption;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Session as HttpSession;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;

/**
 * Characterization of public/main/inc/lib/extra_field_option.lib.php (ExtraFieldOption): option count, bulk delete,
 * the two option lookups by display text and the next-order helper. Fixtures are raw prepared statements on
 * fields prefixed "xo_".
 *
 * INTENTIONAL (kept as is): getFieldOptionByFieldIdAndOptionDisplayTextAndOptionValue() compares the display text
 * with the FIELD's display_text, not the option's. searchByField() (and its caller getSearchOptionsByField(), which
 * nothing calls) selects a column that does not exist and always fails: not pinned.
 */
class ExtraFieldOptionTier3CharacterizationTest extends AbstractApiTest
{
    use ChamiloTestTrait;

    private const USER = 1;
    private const COURSE = 2;

    protected function setUp(): void
    {
        parent::setUp();

        $httpSession = new HttpSession(new MockArraySessionStorage());
        $request = new Request();
        $request->setSession($httpSession);
        self::getContainer()->get('request_stack')->push($request);
        Container::setContainer(self::getContainer());
        Container::setSession($httpSession);
        Container::setRequest($request);
        Database::setManager($this->getEntityManager());
        Database::setConnection($this->getEntityManager()->getConnection());
        $_GET = [];
    }

    private function conn(): Connection
    {
        return $this->getEntityManager()->getConnection();
    }

    private function field(int $itemType, string $variable, string $label): int
    {
        $this->conn()->executeStatement(
            'INSERT INTO extra_field (item_type, value_type, variable, display_text, field_order, visible_to_self,
                visible_to_others, changeable, filter, created_at, auto_remove)
             VALUES (?, 4, ?, ?, 0, 1, 1, 1, 0, \'2020-01-01 00:00:00\', 0)',
            [$itemType, 'xo_'.$variable, $label]
        );

        return (int) $this->conn()->lastInsertId();
    }

    private function option(int $fieldId, string $value, string $text, int $order): int
    {
        $this->conn()->executeStatement(
            'INSERT INTO extra_field_options (field_id, option_value, display_text, option_order) VALUES (?, ?, ?, ?)',
            [$fieldId, $value, $text, $order]
        );

        return (int) $this->conn()->lastInsertId();
    }

    private function optionCount(int $fieldId): int
    {
        return (int) $this->conn()->fetchOne('SELECT COUNT(*) FROM extra_field_options WHERE field_id = ?', [$fieldId]);
    }

    public function testCountCountsOnlyTheOptionsOfThatFieldAndOnlyForTheHandlersItemType(): void
    {
        $mine = $this->field(self::USER, 'cnt', 'Cnt');
        $other = $this->field(self::USER, 'cnt_other', 'Other');
        $this->option($mine, 'a', 'A', 1);
        $this->option($mine, 'b', 'B', 2);
        $this->option($other, 'c', 'C', 1);

        $this->assertEquals(2, (new ExtraFieldOption('user'))->get_count_by_field_id($mine));
        $this->assertEquals(1, (new ExtraFieldOption('user'))->get_count_by_field_id($other));
        $this->assertEquals(0, (new ExtraFieldOption('course'))->get_count_by_field_id($mine), 'Field of another item type.');
        $this->assertFalse((new ExtraFieldOption('user'))->get_count_by_field_id(0));
        $this->assertFalse((new ExtraFieldOption('user'))->get_count_by_field_id(''));
        $this->assertEquals(0, (new ExtraFieldOption('user'))->get_count_by_field_id(-1));
        $this->assertSame('2', (string) (new ExtraFieldOption('user'))->get_count_by_field_id($mine));
    }

    public function testDeleteAllOptionsRemovesOnlyTheOptionsOfThatField(): void
    {
        $a = $this->field(self::USER, 'del_a', 'A');
        $b = $this->field(self::USER, 'del_b', 'B');
        $this->option($a, 'x', 'X', 1);
        $this->option($a, 'y', 'Y', 2);
        $this->option($b, 'z', 'Z', 1);

        (new ExtraFieldOption('user'))->delete_all_options_by_field_id($a);

        $this->assertSame(0, $this->optionCount($a));
        $this->assertSame(1, $this->optionCount($b));
    }

    public function testDeleteAllOptionsIgnoresAnUnknownFieldId(): void
    {
        $a = $this->field(self::USER, 'del_none', 'A');
        $this->option($a, 'x', 'X', 1);

        (new ExtraFieldOption('user'))->delete_all_options_by_field_id(-1);

        $this->assertSame(1, $this->optionCount($a));
    }

    public function testLookupByDisplayTextReturnsTheRawRowOrFalse(): void
    {
        $field = $this->field(self::USER, 'lk', 'Field label');
        $id = $this->option($field, 'val1', 'Shown', 3);
        $this->option($field, 'val2', 'Other', 4);
        $handler = new ExtraFieldOption('user');

        $row = $handler->get_field_option_by_field_id_and_option_display_text($field, 'Shown');

        $this->assertSame(
            [
                'id' => $id,
                'field_id' => $field,
                'option_value' => 'val1',
                'display_text' => 'Shown',
                'priority' => null,
                'priority_message' => null,
                'option_order' => 3,
            ],
            $row
        );
        $this->assertFalse($handler->get_field_option_by_field_id_and_option_display_text($field, 'Nope'));
        $this->assertFalse($handler->get_field_option_by_field_id_and_option_display_text($field + 1000, 'Shown'));
        $this->assertFalse(
            (new ExtraFieldOption('course'))->get_field_option_by_field_id_and_option_display_text($field, 'Shown'),
            'Field of another item type.'
        );
        $this->assertFalse($handler->get_field_option_by_field_id_and_option_display_text(0, ''));
        $this->assertFalse(
            $handler->get_field_option_by_field_id_and_option_display_text($field, "x' OR '1'='1"),
            'A quote in the text is data.'
        );
    }

    public function testLookupByTextAndValueComparesTheTextWithTheFieldsOwnDisplayText(): void
    {
        $field = $this->field(self::USER, 'lk2', 'Field label');
        $id = $this->option($field, 'val1', 'Option text', 1);
        $this->option($field, 'val2', 'Option text', 2);
        $handler = new ExtraFieldOption('user');

        $row = $handler->getFieldOptionByFieldIdAndOptionDisplayTextAndOptionValue($field, 'Field label', 'val1');

        $this->assertSame($id, $row['id']);
        $this->assertSame('val1', $row['option_value']);
        $this->assertSame(1, $row['option_order']);
        $this->assertFalse(
            $handler->getFieldOptionByFieldIdAndOptionDisplayTextAndOptionValue($field, 'Option text', 'val1'),
            'The option text is not what is compared.'
        );
        $this->assertFalse($handler->getFieldOptionByFieldIdAndOptionDisplayTextAndOptionValue($field, 'Field label', 'nope'));
        $this->assertFalse(
            (new ExtraFieldOption('course'))->getFieldOptionByFieldIdAndOptionDisplayTextAndOptionValue($field, 'Field label', 'val1')
        );
        $this->assertFalse($handler->getFieldOptionByFieldIdAndOptionDisplayTextAndOptionValue(0, '', ''));
        $this->assertFalse(
            $handler->getFieldOptionByFieldIdAndOptionDisplayTextAndOptionValue($field, 'Field label', "x' OR '1'='1")
        );
    }

    public function testMaxOrderIsTheHighestOptionOrderPlusOneOrOneWithoutOptions(): void
    {
        $field = $this->field(self::USER, 'ord', 'Ord');
        $empty = $this->field(self::USER, 'ord_empty', 'Empty');
        $this->option($field, 'a', 'A', 4);
        $this->option($field, 'b', 'B', 9);
        $this->option($field, 'c', 'C', 2);
        $handler = new ExtraFieldOption('user');

        $this->assertSame(10, $handler->get_max_order($field));
        $this->assertSame(1, $handler->get_max_order($empty));
        $this->assertSame(1, $handler->get_max_order(0));
    }
}
