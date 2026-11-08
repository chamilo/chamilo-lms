<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\Tests\CoreBundle\Legacy;

use Chamilo\CoreBundle\Framework\Container;
use Chamilo\Tests\AbstractApiTest;
use Chamilo\Tests\ChamiloTestTrait;
use Database;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\Query\QueryException;
use ExtraField;
use ExtraFieldValue;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Session as HttpSession;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;

/**
 * Characterization tests for public/main/inc/lib/extra_field_value.lib.php (the values of the legacy extra
 * fields: lookups by item/field/variable/value, search, save/update, deletes, copy, scoping by item type).
 *
 * Liveness: LIVE (callers under public/ src/ plugin/) are saveFieldValues, save, get_values_by_handler_and_field_id,
 * get_values_by_handler_and_field_variable, get_item_id_from_field_variable_and_field_value, getValuesByFieldId,
 * getAllValuesByItemAndField, getAllValuesByItem, getAllValuesForAnItem, deleteValuesByItem,
 * deleteValuesByHandlerAndFieldAndValue, copy, get_count. DEAD: searchValuesByField (no caller),
 * delete_all_values_by_field_id (only ExtraField::delete), getAllValuesByItemAndFieldAndValue (only save() of tags).
 *
 * Fixtures are raw prepared statements; fields and values use the "xf_" prefix and item ids 4242+ that no
 * installer data uses. Another item type reuses the same item id to prove the scoping.
 */
class ExtraFieldValueCharacterizationTest extends AbstractApiTest
{
    use ChamiloTestTrait;

    private const USER = 1;
    private const COURSE = 2;
    private const SESSION = 3;
    private const ITEM = 424242;

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

    private function fresh(): void
    {
        $this->getEntityManager()->clear();
    }

    private function field(int $itemType, string $variable, int $valueType = ExtraField::FIELD_TYPE_TEXT, array $extra = []): int
    {
        $this->conn()->executeStatement(
            'INSERT INTO extra_field (item_type, value_type, variable, display_text, field_order, visible_to_self,
                visible_to_others, changeable, filter, created_at, auto_remove)
             VALUES (?, ?, ?, ?, ?, ?, 1, 1, ?, \'2020-01-01 00:00:00\', 0)',
            [
                $itemType,
                $valueType,
                'xf_'.$variable,
                'xf_'.$variable.' label',
                $extra['order'] ?? 0,
                $extra['visible_to_self'] ?? 1,
                $extra['filter'] ?? 0,
            ]
        );

        return (int) $this->conn()->lastInsertId();
    }

    private function value(int $fieldId, int $itemId, ?string $value, ?string $comment = null): int
    {
        $this->conn()->executeStatement(
            'INSERT INTO extra_field_values (field_id, field_value, item_id, comment, created_at, updated_at)
             VALUES (?, ?, ?, ?, \'2020-01-01 00:00:00\', \'2020-01-01 00:00:00\')',
            [$fieldId, $value, $itemId, $comment]
        );

        return (int) $this->conn()->lastInsertId();
    }

    private function rows(int $fieldId, ?int $itemId = null): int
    {
        if (null === $itemId) {
            return (int) $this->conn()->fetchOne('SELECT COUNT(*) FROM extra_field_values WHERE field_id = ?', [$fieldId]);
        }

        return (int) $this->conn()->fetchOne('SELECT COUNT(*) FROM extra_field_values WHERE field_id = ? AND item_id = ?', [$fieldId, $itemId]);
    }

    // ---------------------------------------------------------------- reads by item and field

    public function testGetValuesByHandlerAndFieldIdReturnsTheFormattedRowOrFalse(): void
    {
        $f = $this->field(self::COURSE, 'one');
        $id = $this->value($f, self::ITEM, 'hello', 'a comment');
        $this->fresh();

        $row = (new ExtraFieldValue('course'))->get_values_by_handler_and_field_id(self::ITEM, $f);

        $this->assertSame($id, (int) $row['id']);
        $this->assertSame($f, (int) $row['field_id']);
        $this->assertSame('hello', $row['field_value']);
        $this->assertSame(self::ITEM, (int) $row['item_id']);
        $this->assertSame('a comment', $row['comment']);
        $this->assertSame('2020-01-01 00:00:00', $row['created_at']);
        $this->assertSame(ExtraField::FIELD_TYPE_TEXT, (int) $row['value_type']);
        $this->assertFalse((new ExtraFieldValue('course'))->get_values_by_handler_and_field_id(self::ITEM + 1, $f));
        $this->assertFalse((new ExtraFieldValue('course'))->get_values_by_handler_and_field_id(-1, -1));
    }

    public function testGetValuesByHandlerAndFieldIdIsScopedByTheItemTypeOfTheHandler(): void
    {
        $f = $this->field(self::COURSE, 'scoped');
        $this->value($f, self::ITEM, 'course value');
        $this->fresh();

        $this->assertFalse((new ExtraFieldValue('session'))->get_values_by_handler_and_field_id(self::ITEM, $f));
    }

    public function testGetValuesByHandlerAndFieldVariableReturnsTheFirstRowWithAValueAlias(): void
    {
        $f = $this->field(self::SESSION, 'byvar');
        $this->value($f, self::ITEM, 'first');
        $other = $this->field(self::COURSE, 'byvar_other_type');
        $this->value($other, self::ITEM, 'course side');

        $row = (new ExtraFieldValue('session'))->get_values_by_handler_and_field_variable(self::ITEM, 'xf_byvar');

        $this->assertSame('first', $row['field_value']);
        $this->assertSame('first', $row['value'], 'value is an alias of field_value.');
        $this->assertSame(ExtraField::FIELD_TYPE_TEXT, (int) $row['value_type']);
        $this->assertFalse((new ExtraFieldValue('session'))->get_values_by_handler_and_field_variable(self::ITEM, 'xf_byvar_other_type'));
        $this->assertFalse((new ExtraFieldValue('session'))->get_values_by_handler_and_field_variable(self::ITEM + 1, 'xf_byvar'));
        $this->assertFalse((new ExtraFieldValue('session'))->get_values_by_handler_and_field_variable(self::ITEM, "x' OR '1'='1"));
    }

    public function testGetValuesByVariableFiltersOnVisibleToSelfEqualToTheGivenFlag(): void
    {
        $visible = $this->field(self::SESSION, 'vis_on', ExtraField::FIELD_TYPE_TEXT, ['visible_to_self' => 1]);
        $hidden = $this->field(self::SESSION, 'vis_off', ExtraField::FIELD_TYPE_TEXT, ['visible_to_self' => 0]);
        $this->value($visible, self::ITEM, 'v');
        $this->value($hidden, self::ITEM, 'h');
        $efv = new ExtraFieldValue('session');

        // PINNED QUIRK: the "visibility" argument defaults to 0, so asking to filter by visibility without
        // passing the flag returns the HIDDEN fields only; pass 1 for the visible ones.
        $this->assertFalse($efv->get_values_by_handler_and_field_variable(self::ITEM, 'xf_vis_on', false, true));
        $this->assertSame('h', $efv->get_values_by_handler_and_field_variable(self::ITEM, 'xf_vis_off', false, true)['field_value']);
        $this->assertSame('v', $efv->get_values_by_handler_and_field_variable(self::ITEM, 'xf_vis_on', false, true, 1)['field_value']);
    }

    public function testGetItemIdFromFieldVariableAndFieldValueFindsTheItemOrFalse(): void
    {
        $f = $this->field(self::COURSE, 'reverse');
        $this->value($f, 424301, 'xf-needle');
        $this->value($f, 424302, 'xf-needle');
        $this->value($f, 424303, 'xf-haystack-needle-inside');
        $this->value($this->field(self::USER, 'reverse'), 424399, 'xf-needle');
        $efv = new ExtraFieldValue('course');

        $this->assertSame(['item_id' => 424301], array_map('intval', $efv->get_item_id_from_field_variable_and_field_value('xf_reverse', 'xf-needle')));
        $this->assertSame(['item_id' => 424302], array_map('intval', $efv->get_item_id_from_field_variable_and_field_value('xf_reverse', 'xf-needle', false, true)), 'last = highest item id.');
        $all = $efv->get_item_id_from_field_variable_and_field_value('xf_reverse', 'xf-needle', false, false, true);
        $this->assertSame([424301, 424302], array_map('intval', array_column($all, 'item_id')));
        $like = $efv->get_item_id_from_field_variable_and_field_value('xf_reverse', 'needle', false, false, true, true);
        $this->assertSame([424301, 424302, 424303], array_map('intval', array_column($like, 'item_id')));
        $this->assertFalse($efv->get_item_id_from_field_variable_and_field_value('xf_reverse', 'needle'), 'Exact match without LIKE.');
        $this->assertFalse($efv->get_item_id_from_field_variable_and_field_value('xf_reverse', "x' OR '1'='1"));
    }

    public function testGetItemIdFromFieldVariableLastFlagReversesTheOrderOfAllResults(): void
    {
        $f = $this->field(self::COURSE, 'reverse2');
        $this->value($f, 424311, 'xf-dup');
        $this->value($f, 424312, 'xf-dup');

        $all = (new ExtraFieldValue('course'))->get_item_id_from_field_variable_and_field_value('xf_reverse2', 'xf-dup', false, true, true);

        $this->assertSame([424312, 424311], array_map('intval', array_column($all, 'item_id')));
    }

    public function testGetValuesByFieldIdListsAllValuesOfTheFieldSortedByValue(): void
    {
        $f = $this->field(self::COURSE, 'allvals');
        $this->value($f, 424321, 'b');
        $this->value($f, 424322, 'a');
        $this->value($f, 424323, 'c');

        $rows = (new ExtraFieldValue('course'))->getValuesByFieldId($f);

        $this->assertSame(['a', 'b', 'c'], array_column($rows, 'field_value'));
        $this->assertFalse((new ExtraFieldValue('user'))->getValuesByFieldId($f), 'Another item type.');
        $this->assertFalse((new ExtraFieldValue('course'))->getValuesByFieldId(999999999));
    }

    public function testGetAllValuesByItemAndFieldReturnsEveryRowOfThatItemAndFieldSortedByValue(): void
    {
        $f = $this->field(self::COURSE, 'multi', ExtraField::FIELD_TYPE_TAG);
        $this->value($f, self::ITEM, 'zeta');
        $this->value($f, self::ITEM, 'alpha');
        $this->value($f, self::ITEM + 1, 'other item');

        $rows = (new ExtraFieldValue('course'))->getAllValuesByItemAndField(self::ITEM, $f);

        $this->assertSame(['alpha', 'zeta'], array_column($rows, 'field_value'));
        $this->assertFalse((new ExtraFieldValue('course'))->getAllValuesByItemAndField(self::ITEM + 5, $f));
        $this->assertFalse((new ExtraFieldValue('user'))->getAllValuesByItemAndField(self::ITEM, $f));
    }

    public function testGetAllValuesByItemAndFieldAndValueMatchesTheExactValueOnly(): void
    {
        $f = $this->field(self::COURSE, 'exactval');
        $this->value($f, self::ITEM, 'alpha');
        $this->value($f, self::ITEM, 'alphabet');
        $efv = new ExtraFieldValue('course');

        $rows = $efv->getAllValuesByItemAndFieldAndValue(self::ITEM, $f, 'alpha');

        $this->assertCount(1, $rows);
        $this->assertSame('alpha', $rows[0]['field_value']);
        $this->assertFalse($efv->getAllValuesByItemAndFieldAndValue(self::ITEM, $f, "alpha' OR '1'='1"));
    }

    public function testGetAllValuesByItemOnlyReturnsFilterFlaggedFieldsAndFalsyOtherwise(): void
    {
        $filtered = $this->field(self::COURSE, 'bi_filtered', ExtraField::FIELD_TYPE_TEXT, ['filter' => 1]);
        $plain = $this->field(self::COURSE, 'bi_plain', ExtraField::FIELD_TYPE_TEXT, ['filter' => 0]);
        $this->value($filtered, self::ITEM, 'seen');
        $this->value($plain, self::ITEM, 'never listed');
        $this->fresh();

        $rows = (new ExtraFieldValue('course'))->getAllValuesByItem(self::ITEM);
        $mine = array_values(array_filter($rows, static fn ($r) => str_starts_with((string) $r['variable'], 'xf_')));

        // PINNED QUIRK: the final merge walks get_all(['filter = ?' => 1]), so a value of a field whose
        // "filter" flag is 0 is selected by the SQL and then dropped: only filter-flagged fields come back.
        $this->assertSame(['xf_bi_filtered'], array_column($mine, 'variable'));
        $this->assertSame('seen', $mine[0]['value']);
        $this->assertSame('seen', $mine[0]['field_value']);
        $this->assertSame('', $mine[0]['url']);
    }

    // ---------------------------------------------------------------- search / count

    public function testSearchValuesByFieldReturnsDistinctMatchingValuesSortedAndLimited(): void
    {
        $f = $this->field(self::COURSE, 'search');
        $this->value($f, 424331, 'xf-pear');
        $this->value($f, 424332, 'xf-apple');
        $this->value($f, 424333, 'xf-apple');
        $this->value($f, 424334, 'xf-grape');
        $this->value($this->field(self::USER, 'search'), 424335, 'xf-apple-user');
        $efv = new ExtraFieldValue('course');

        $this->assertSame(['xf-apple', 'xf-grape', 'xf-pear'], array_column($efv->searchValuesByField('xf-', $f), 'field_value'));
        $this->assertSame(['xf-apple'], array_column($efv->searchValuesByField('app', $f), 'field_value'));
        $this->assertSame(['xf-apple', 'xf-grape'], array_column($efv->searchValuesByField('xf-', $f, 2), 'field_value'));
        $this->assertSame([], $efv->searchValuesByField("xf' OR '1'='1", $f));
    }

    public function testGetCountAlwaysThrowsBecauseTheValueEntityHasNoItemTypeField(): void
    {
        // PINNED QUIRK: the DQL filters ExtraFieldValues on "itemType", which lives on the ExtraField entity.
        $this->expectException(QueryException::class);
        (new ExtraFieldValue('session'))->get_count();
    }

    // ---------------------------------------------------------------- save

    public function testSaveInsertsThenUpdatesTheSameRow(): void
    {
        $f = $this->field(self::COURSE, 'save');
        $efv = new ExtraFieldValue('course');

        $first = $efv->save(['item_id' => self::ITEM, 'field_id' => $f, 'field_value' => 'one']);
        $this->assertGreaterThan(0, (int) $first);
        $this->assertSame(1, $this->rows($f, self::ITEM));

        $efv->save(['item_id' => self::ITEM, 'field_id' => $f, 'field_value' => 'two']);

        $this->assertSame(1, $this->rows($f, self::ITEM), 'The second save updates, it does not add a row.');
        $this->assertSame('two', $this->conn()->fetchOne('SELECT field_value FROM extra_field_values WHERE field_id = ? AND item_id = ?', [$f, self::ITEM]));
    }

    public function testSaveAcceptsTheVariableInsteadOfTheFieldId(): void
    {
        $f = $this->field(self::COURSE, 'savevar');

        (new ExtraFieldValue('course'))->save(['item_id' => self::ITEM, 'variable' => 'xf_savevar', 'field_value' => 'by variable']);

        $this->assertSame('by variable', $this->conn()->fetchOne('SELECT field_value FROM extra_field_values WHERE field_id = ?', [$f]));
    }

    public function testSaveJoinsArrayValuesWithSemicolons(): void
    {
        $f = $this->field(self::COURSE, 'savearray', ExtraField::FIELD_TYPE_SELECT_MULTIPLE);

        (new ExtraFieldValue('course'))->save(['item_id' => self::ITEM, 'field_id' => $f, 'field_value' => ['a', 'b', 'c']]);

        $this->assertSame('a;b;c', $this->conn()->fetchOne('SELECT field_value FROM extra_field_values WHERE field_id = ?', [$f]));
    }

    public function testSaveJoinsDoubleSelectPairsWithDoubleColon(): void
    {
        $f = $this->field(self::COURSE, 'savedouble', ExtraField::FIELD_TYPE_DOUBLE_SELECT);

        (new ExtraFieldValue('course'))->save([
            'item_id' => self::ITEM,
            'field_id' => $f,
            'field_value' => ['extra_xf_savedouble' => '12', 'extra_xf_savedouble_second' => '34'],
        ]);

        $this->assertSame('12::34', $this->conn()->fetchOne('SELECT field_value FROM extra_field_values WHERE field_id = ?', [$f]));
    }

    public function testSaveOfAnUnknownFieldWritesNothing(): void
    {
        $before = (int) $this->conn()->fetchOne('SELECT COUNT(*) FROM extra_field_values');

        $result = (new ExtraFieldValue('course'))->save(['item_id' => self::ITEM, 'variable' => 'xf_no_such_field', 'field_value' => 'x']);

        $this->assertNull($result);
        $this->assertSame($before, (int) $this->conn()->fetchOne('SELECT COUNT(*) FROM extra_field_values'));
    }

    // ---------------------------------------------------------------- saveFieldValues

    public function testSaveFieldValuesWritesTheSubmittedExtraParametersOfTheType(): void
    {
        $text = $this->field(self::COURSE, 'sfv_text');
        $other = $this->field(self::COURSE, 'sfv_untouched');
        $this->value($other, self::ITEM, 'keep me');

        (new ExtraFieldValue('course'))->saveFieldValues(
            ['item_id' => self::ITEM, 'extra_xf_sfv_text' => 'typed', 'extra_xf_sfv_text_comment' => 'note'],
            true
        );

        $this->assertSame(
            ['typed', 'note'],
            array_values($this->conn()->fetchNumeric('SELECT field_value, comment FROM extra_field_values WHERE field_id = ?', [$text]))
        );
        $this->assertSame('keep me', $this->conn()->fetchOne('SELECT field_value FROM extra_field_values WHERE field_id = ?', [$other]), 'onlySubmittedFields leaves the other fields alone.');
    }

    public function testSaveFieldValuesWithoutItemIdIsFalse(): void
    {
        $this->assertFalse((new ExtraFieldValue('course'))->saveFieldValues(['extra_xf_whatever' => 'x']));
    }

    public function testSaveFieldValuesWithoutOnlySubmittedFieldsBlanksTheMissingFields(): void
    {
        $f = $this->field(self::COURSE, 'sfv_blank');
        $this->value($f, self::ITEM, 'old');

        (new ExtraFieldValue('course'))->saveFieldValues(['item_id' => self::ITEM]);

        $this->assertSame('', $this->conn()->fetchOne('SELECT field_value FROM extra_field_values WHERE field_id = ?', [$f]));
    }

    public function testSaveFieldValuesHonoursAvoidAndSaveOnlyLists(): void
    {
        $a = $this->field(self::COURSE, 'sfv_a');
        $b = $this->field(self::COURSE, 'sfv_b');
        $c = $this->field(self::COURSE, 'sfv_c');
        $params = ['item_id' => self::ITEM, 'extra_xf_sfv_a' => 'A', 'extra_xf_sfv_b' => 'B', 'extra_xf_sfv_c' => 'C'];
        $efv = new ExtraFieldValue('course');

        $efv->saveFieldValues($params, true, false, [], ['xf_sfv_b']);
        $this->assertSame([1, 0, 1], [$this->rows($a), $this->rows($b), $this->rows($c)], 'avoidFields skips b.');

        $efv->saveFieldValues($params, true, false, ['xf_sfv_b']);
        $this->assertSame([1, 1, 1], [$this->rows($a), $this->rows($b), $this->rows($c)], 'saveOnlyThisFields writes only b.');
    }

    public function testSaveFieldValuesStoresCheckboxAsOneOrZero(): void
    {
        $f = $this->field(self::COURSE, 'sfv_check', ExtraField::FIELD_TYPE_CHECKBOX);
        $efv = new ExtraFieldValue('course');

        $efv->saveFieldValues(['item_id' => self::ITEM, 'extra_xf_sfv_check' => ['extra_xf_sfv_check' => '1']], true);
        $this->assertSame('1', (string) $this->conn()->fetchOne('SELECT field_value FROM extra_field_values WHERE field_id = ?', [$f]));

        $efv->saveFieldValues(['item_id' => self::ITEM, 'extra_xf_sfv_check' => []], true);
        $this->assertSame('0', (string) $this->conn()->fetchOne('SELECT field_value FROM extra_field_values WHERE field_id = ?', [$f]));
    }

    public function testSaveFieldValuesOnlyStoresWellFormedDates(): void
    {
        $date = $this->field(self::COURSE, 'sfv_date', ExtraField::FIELD_TYPE_DATE);
        $dateTime = $this->field(self::COURSE, 'sfv_datetime', ExtraField::FIELD_TYPE_DATETIME);
        $efv = new ExtraFieldValue('course');

        $efv->saveFieldValues(['item_id' => self::ITEM, 'extra_xf_sfv_date' => '2024-02-30', 'extra_xf_sfv_datetime' => '2024-02-10 25:00'], true);
        $this->assertSame([0, 0], [$this->rows($date), $this->rows($dateTime)], 'Impossible dates are ignored.');

        $efv->saveFieldValues(['item_id' => self::ITEM, 'extra_xf_sfv_date' => '2024-02-10', 'extra_xf_sfv_datetime' => '2024-02-10 14:30'], true);
        $this->assertSame('2024-02-10', $this->conn()->fetchOne('SELECT field_value FROM extra_field_values WHERE field_id = ?', [$date]));
        $this->assertSame('2024-02-10 14:30', $this->conn()->fetchOne('SELECT field_value FROM extra_field_values WHERE field_id = ?', [$dateTime]));
    }

    public function testSaveFieldValuesStoresHiddenFieldsWhenRunFromTheCommandLine(): void
    {
        // A field that is not visible to its owner is only writable by admins, or by a CLI script (PHP_SAPI cli,
        // which is how PHPUnit runs). A web request by a non admin would skip it.
        $hidden = $this->field(self::COURSE, 'sfv_hidden', ExtraField::FIELD_TYPE_TEXT, ['visible_to_self' => 0]);

        (new ExtraFieldValue('course'))->saveFieldValues(['item_id' => self::ITEM, 'extra_xf_sfv_hidden' => 'cli'], true);

        $this->assertSame('cli', $this->conn()->fetchOne('SELECT field_value FROM extra_field_values WHERE field_id = ?', [$hidden]));
    }

    // ---------------------------------------------------------------- deletes

    public function testDeleteValuesByItemRemovesOnlyTheValuesOfThatItemAndItemType(): void
    {
        $course = $this->field(self::COURSE, 'del_course');
        $user = $this->field(self::USER, 'del_user');
        $this->value($course, self::ITEM, 'gone');
        $this->value($course, self::ITEM + 1, 'stays');
        $this->value($user, self::ITEM, 'other type stays');

        (new ExtraFieldValue('course'))->deleteValuesByItem(self::ITEM);

        $this->assertSame(0, $this->rows($course, self::ITEM));
        $this->assertSame(1, $this->rows($course, self::ITEM + 1));
        $this->assertSame(1, $this->rows($user, self::ITEM));
    }

    public function testDeleteValuesByHandlerAndFieldAndValueRemovesOneValueOfOneTag(): void
    {
        $f = $this->field(self::COURSE, 'del_value', ExtraField::FIELD_TYPE_TAG);
        $this->value($f, self::ITEM, 'drop');
        $this->value($f, self::ITEM, 'keep');
        $this->value($f, self::ITEM + 1, 'drop');
        $efv = new ExtraFieldValue('course');

        $this->assertTrue($efv->deleteValuesByHandlerAndFieldAndValue(self::ITEM, $f, 'drop'));

        $this->assertSame(['keep'], $this->conn()->fetchFirstColumn('SELECT field_value FROM extra_field_values WHERE field_id = ? AND item_id = ?', [$f, self::ITEM]));
        $this->assertSame(1, $this->rows($f, self::ITEM + 1), 'Same value on another item stays.');
        $this->assertFalse($efv->deleteValuesByHandlerAndFieldAndValue(self::ITEM, 999999999, 'drop'));
    }

    public function testDeleteAllValuesByFieldIdEmptiesTheField(): void
    {
        $f = $this->field(self::COURSE, 'del_all');
        $keep = $this->field(self::COURSE, 'del_all_keep');
        $this->value($f, 424351, 'a');
        $this->value($f, 424352, 'b');
        $this->value($keep, 424351, 'c');

        (new ExtraFieldValue('course'))->delete_all_values_by_field_id($f);

        $this->assertSame(0, $this->rows($f));
        $this->assertSame(1, $this->rows($keep));
    }

    // ---------------------------------------------------------------- ORM-backed reads and copy

    public function testGetAllValuesForAnItemReturnsEntityValuesAndFiltersOnVisibility(): void
    {
        $visible = $this->field(self::COURSE, 'ai_visible');
        $private = $this->field(self::COURSE, 'ai_private');
        $this->conn()->executeStatement('UPDATE extra_field SET visible_to_others = 0 WHERE id = ?', [$private]);
        $this->value($visible, self::ITEM, 'v');
        $this->value($private, self::ITEM, 'p');
        $this->value($visible, self::ITEM + 1, 'other item');
        $this->fresh();
        $efv = new ExtraFieldValue('course');

        $all = array_map(static fn ($i) => $i['value']->getFieldValue(), $efv->getAllValuesForAnItem(self::ITEM));
        sort($all);
        $this->assertSame(['p', 'v'], $all);

        $onlyOthers = array_map(static fn ($i) => $i['value']->getFieldValue(), $efv->getAllValuesForAnItem(self::ITEM, null, true));
        $this->assertSame(['v'], $onlyOthers);
        $this->assertSame([], $efv->getAllValuesForAnItem(self::ITEM + 9));
    }

    public function testCopyDuplicatesTheNonEmptyValuesToAnotherItem(): void
    {
        $filled = $this->field(self::COURSE, 'copy_filled');
        $empty = $this->field(self::COURSE, 'copy_empty');
        $this->value($filled, self::ITEM, 'copied');
        $this->value($empty, self::ITEM, '');
        $this->fresh();
        $efv = new ExtraFieldValue('course');

        $this->assertTrue($efv->copy(self::ITEM, self::ITEM + 7));

        $this->assertSame('copied', $this->conn()->fetchOne('SELECT field_value FROM extra_field_values WHERE field_id = ? AND item_id = ?', [$filled, self::ITEM + 7]));
        $this->assertSame(0, $this->rows($empty, self::ITEM + 7), 'Empty values are not copied.');
        $this->assertFalse($efv->copy(0, 5));
        $this->assertFalse($efv->copy(5, 0));
    }
}
