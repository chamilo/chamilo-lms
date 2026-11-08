<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\Tests\CoreBundle\Legacy;

use Chamilo\CoreBundle\Exception\NotAllowedException;
use Chamilo\CoreBundle\Framework\Container;
use Chamilo\Tests\AbstractApiTest;
use Chamilo\Tests\ChamiloTestTrait;
use ChamiloSession;
use Database;
use Doctrine\DBAL\Connection;
use ExtraField;
use stdClass;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Session as HttpSession;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;

/**
 * Characterization tests for the field-definition side of public/main/inc/lib/extra_field.lib.php (raw SQL
 * of the legacy ExtraField class: lookup by variable/id, listing, ordering, save/update/delete, tag helpers,
 * where-clause builder, grid listing).
 *
 * Liveness: LIVE are get_all, get_handler_field_info_by_field_variable, getFieldInfoByFieldId, save, update,
 * delete, get_max_field_order (through clean_parameters), getAllGrid, get_count, getDataAndFormattedValues,
 * getExtraFieldRules, get_where_clause, getAllUserPerTag, getAllSkillPerTag, searchOptionsFromTags,
 * get_handler_field_info_by_tags. DEAD (no caller under public/ src/ plugin/): get_all_extra_field_by_type,
 * get_handler_extra_data.
 *
 * The test database already carries installer extra fields: every assertion filters on fixture variables
 * prefixed "xf_". Fixtures are raw prepared statements.
 */
class ExtraFieldCharacterizationTest extends AbstractApiTest
{
    use ChamiloTestTrait;

    private const USER = 1;
    private const COURSE = 2;
    private const SESSION = 3;

    private int $seq = 0;

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

    protected function conn(): Connection
    {
        return $this->getEntityManager()->getConnection();
    }

    protected function fresh(): void
    {
        $this->getEntityManager()->clear();
    }

    private function field(int $itemType, string $variable, int $valueType = 1, array $extra = []): int
    {
        $this->conn()->executeStatement(
            'INSERT INTO extra_field (item_type, value_type, variable, display_text, field_order, visible_to_self,
                visible_to_others, changeable, filter, created_at, auto_remove)
             VALUES (?, ?, ?, ?, ?, ?, 1, 1, ?, \'2020-01-01 00:00:00\', 0)',
            [
                $itemType,
                $valueType,
                'xf_'.$variable,
                $extra['display_text'] ?? 'xf_'.$variable.' label',
                $extra['order'] ?? 0,
                $extra['visible_to_self'] ?? 1,
                $extra['filter'] ?? 0,
            ]
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

    private function mine(array $fields): array
    {
        return array_values(array_filter($fields, static fn ($f) => str_starts_with((string) $f['variable'], 'xf_')));
    }

    // ---------------------------------------------------------------- types

    public function testTypeListMapsTheVariableToTheItemTypeIntegerBothWays(): void
    {
        $this->assertSame((string) self::USER, (string) ExtraField::getExtraFieldTypeFromString('user'));
        $this->assertSame((string) self::COURSE, (string) ExtraField::getExtraFieldTypeFromString('course'));
        $this->assertSame((string) self::SESSION, (string) ExtraField::getExtraFieldTypeFromString('session'));
        $this->assertSame('session', ExtraField::getExtraFieldTypeFromInt(self::SESSION));
        $this->assertSame(self::COURSE, (new ExtraField('course'))->getItemType());
    }

    // ---------------------------------------------------------------- get_all

    public function testGetAllListsTheFieldsOfTheTypeOrderedByFieldOrder(): void
    {
        $this->field(self::USER, 'ord_b', 1, ['order' => 20]);
        $this->field(self::USER, 'ord_a', 1, ['order' => 10]);
        $this->field(self::USER, 'ord_c', 1, ['order' => 30]);
        $this->field(self::COURSE, 'ord_other_type', 1, ['order' => 5]);

        $user = array_column($this->mine((new ExtraField('user'))->get_all()), 'variable');
        $course = array_column($this->mine((new ExtraField('course'))->get_all()), 'variable');

        $this->assertSame(['xf_ord_a', 'xf_ord_b', 'xf_ord_c'], $user);
        $this->assertSame(['xf_ord_other_type'], $course, 'Fields of another item type never leak in.');
    }

    public function testGetAllAttachesOptionsOrderedByOptionOrderOrFalseWithoutOptions(): void
    {
        $withOptions = $this->field(self::USER, 'opt_field', ExtraField::FIELD_TYPE_SELECT);
        $this->field(self::USER, 'no_opt_field');
        $this->option($withOptions, 'two', 'Two', 2);
        $this->option($withOptions, 'one', 'One', 1);

        $fields = array_column($this->mine((new ExtraField('user'))->get_all()), null, 'variable');

        // PINNED QUIRK: get_all() asks for no explicit option order, so options come back in id (insertion) order,
        // unlike get_handler_field_info_by_field_variable() which sorts by option_order.
        $this->assertSame(['two', 'one'], array_column($fields['xf_opt_field']['options'], 'option_value'));
        $this->assertFalse($fields['xf_no_opt_field']['options']);
    }

    public function testGetAllAcceptsWhereConditionsAndKeepsTheTypeScope(): void
    {
        $this->field(self::USER, 'flt_on', 1, ['filter' => 1]);
        $this->field(self::USER, 'flt_off', 1, ['filter' => 0]);
        $this->field(self::COURSE, 'flt_other', 1, ['filter' => 1]);

        $filtered = array_column($this->mine((new ExtraField('user'))->get_all(['filter = ?' => 1])), 'variable');

        $this->assertSame(['xf_flt_on'], $filtered);
    }

    // ---------------------------------------------------------------- lookups

    public function testGetHandlerFieldInfoByVariableReturnsTheRowWithOptionsKeyedByIdInOptionOrder(): void
    {
        $id = $this->field(self::USER, 'lookup', ExtraField::FIELD_TYPE_SELECT);
        $second = $this->option($id, 'b', 'B', 2);
        $first = $this->option($id, 'a', 'A', 1);

        $info = (new ExtraField('user'))->get_handler_field_info_by_field_variable('xf_lookup');

        $this->assertSame($id, (int) $info['id']);
        $this->assertSame('xf_lookup label', $info['display_text']);
        $this->assertSame([$first, $second], array_keys($info['options']), 'Options keyed by id, ordered by option_order.');
        $this->assertSame('A', $info['options'][$first]['display_text']);
    }

    public function testGetHandlerFieldInfoByVariableIsScopedByItemTypeAndFalseWhenMissing(): void
    {
        $this->field(self::COURSE, 'scoped');

        $this->assertFalse((new ExtraField('user'))->get_handler_field_info_by_field_variable('xf_scoped'));
        $this->assertIsArray((new ExtraField('course'))->get_handler_field_info_by_field_variable('xf_scoped'));
        $this->assertFalse((new ExtraField('user'))->get_handler_field_info_by_field_variable("x' OR '1'='1"));
    }

    public function testGetFieldInfoByFieldIdIsScopedByItemType(): void
    {
        $id = $this->field(self::SESSION, 'byid', ExtraField::FIELD_TYPE_SELECT);
        $this->option($id, 'y', 'Y', 2);
        $this->option($id, 'x', 'X', 1);

        $info = (new ExtraField('session'))->getFieldInfoByFieldId($id);

        $this->assertSame('xf_byid', $info['variable']);
        $this->assertSame(['x', 'y'], array_values(array_column($info['options'], 'option_value')));
        $this->assertFalse((new ExtraField('user'))->getFieldInfoByFieldId($id), 'Another item type: not found.');
        $this->assertFalse((new ExtraField('session'))->getFieldInfoByFieldId(999999999));
    }

    public function testGetAllExtraFieldByTypeReturnsTheIdsOfTheFieldsOfOneValueType(): void
    {
        $text = $this->field(self::COURSE, 'bytype_text', ExtraField::FIELD_TYPE_TEXT);
        $date = $this->field(self::COURSE, 'bytype_date', ExtraField::FIELD_TYPE_DATE);
        $otherType = $this->field(self::USER, 'bytype_user_date', ExtraField::FIELD_TYPE_DATE);

        $ids = (new ExtraField('course'))->get_all_extra_field_by_type((string) ExtraField::FIELD_TYPE_DATE);

        $this->assertContains((string) $date, array_map('strval', $ids));
        $this->assertNotContains((string) $text, array_map('strval', $ids));
        $this->assertNotContains((string) $otherType, array_map('strval', $ids));
    }

    public function testGetHandlerFieldInfoByTagsListsTheTagsOfTheFieldOrderedById(): void
    {
        $id = $this->field(self::USER, 'tagfield', ExtraField::FIELD_TYPE_TAG);
        $this->conn()->executeStatement('INSERT INTO tag (tag, field_id, count) VALUES (\'xf_tag_b\', ?, 1)', [$id]);
        $b = (int) $this->conn()->lastInsertId();
        $this->conn()->executeStatement('INSERT INTO tag (tag, field_id, count) VALUES (\'xf_tag_a\', ?, 1)', [$id]);
        $a = (int) $this->conn()->lastInsertId();

        $info = (new ExtraField('user'))->get_handler_field_info_by_tags('xf_tagfield');

        $this->assertSame([$b, $a], array_keys($info['options']), 'Tags are ordered by id, not alphabetically.');
        $this->assertSame('xf_tag_b', $info['options'][$b]['tag']);
        $this->assertFalse((new ExtraField('course'))->get_handler_field_info_by_tags('xf_tagfield'));
    }

    // ---------------------------------------------------------------- ordering / save / update / delete

    public function testGetMaxFieldOrderIsAlwaysOneBecauseTheItemTypeIsQuotedAsADotNumberDot(): void
    {
        // PINNED QUIRK: the query reads "item_type = '.$this->itemType.'" inside a double-quoted PHP string, so it
        // compares item_type with the text ".3." (0.3), never matching: MAX() is NULL and the next order is 1,
        // whatever the existing fields are.
        $this->field(self::COURSE, 'maxorder', 1, ['order' => 9000]);

        $this->assertSame(1, (new ExtraField('course'))->get_max_field_order());
    }

    private function saveParams(string $variable, string $displayText = 'Save me'): array
    {
        return [
            'variable' => $variable,
            'display_text' => $displayText,
            'value_type' => ExtraField::FIELD_TYPE_TEXT,
            'visible_to_self' => 1,
            'visible_to_others' => 0,
            'changeable' => 1,
            'filter' => 0,
            'auto_remove' => 0,
        ];
    }

    public function testSaveCreatesTheFieldAndIsIdempotentOnAnAlreadyNormalisedVariable(): void
    {
        $extraField = new ExtraField('course');

        $id = $extraField->save($this->saveParams('xf_save_me'));

        $row = $this->conn()->fetchAssociative('SELECT * FROM extra_field WHERE id = ?', [$id]);
        $this->assertSame('xf_save_me', $row['variable']);
        $this->assertSame(self::COURSE, (int) $row['item_type']);
        $this->assertSame(ExtraField::FIELD_TYPE_TEXT, (int) $row['value_type']);
        $this->assertSame(1, (int) $row['field_order'], 'Order comes from get_max_field_order() (see its pinned quirk).');

        $again = $extraField->save($this->saveParams('xf_save_me'));
        $this->assertSame($id, (int) $again, 'Saving an existing variable returns the existing id.');
        $this->assertSame(1, (int) $this->conn()->fetchOne("SELECT COUNT(*) FROM extra_field WHERE variable = 'xf_save_me'"));
    }

    public function testSaveLowercasesTheVariableAndReplacesSpaces(): void
    {
        $id = (new ExtraField('session'))->save($this->saveParams('XF Spaced Name'));

        $this->assertSame('xf_spaced_name', $this->conn()->fetchOne('SELECT variable FROM extra_field WHERE id = ?', [$id]));
        $this->assertSame(self::SESSION, (int) $this->conn()->fetchOne('SELECT item_type FROM extra_field WHERE id = ?', [$id]));
    }

    public function testSameVariableCanExistOnAnotherItemType(): void
    {
        $user = (new ExtraField('user'))->save($this->saveParams('xf_twice'));
        $course = (new ExtraField('course'))->save($this->saveParams('xf_twice'));

        $this->assertNotSame($user, $course);
        $this->assertSame(2, (int) $this->conn()->fetchOne("SELECT COUNT(*) FROM extra_field WHERE variable = 'xf_twice'"));
    }

    public function testSaveOfAVariableThatNeedsNormalisationIsNotIdempotent(): void
    {
        // PINNED QUIRK: the "already exists" lookup uses the raw variable, but the row is stored normalised, so a
        // second save of "XF Again" misses the first row and the unique (variable, item_type) key rejects it.
        $extraField = new ExtraField('course');
        $extraField->save($this->saveParams('XF Again'));

        $this->expectException(NotAllowedException::class);
        $extraField->save($this->saveParams('XF Again'));
    }

    public function testSaveWithoutAVariableFailsOnTheLookupInsteadOfUsingTheDisplayText(): void
    {
        // PINNED QUIRK: clean_parameters() would derive the variable from display_text, but save() reads
        // $params['variable'] for the lookup before cleaning, so the call raises "Undefined array key".
        $params = $this->saveParams('ignored');
        unset($params['variable']);

        set_error_handler(static function (int $no, string $str): never {
            throw new \ErrorException($str, 0, $no);
        });
        try {
            $this->expectException(\ErrorException::class);
            $this->expectExceptionMessage('Undefined array key "variable"');
            (new ExtraField('session'))->save($params);
        } finally {
            restore_error_handler();
        }
    }

    public function testSaveKeepsAnExplicitFieldOrder(): void
    {
        $params = $this->saveParams('xf_explicit_order');
        $params['field_order'] = '4';

        $id = (new ExtraField('session'))->save($params);

        $this->assertSame(4, (int) $this->conn()->fetchOne('SELECT field_order FROM extra_field WHERE id = ?', [$id]));
    }

    public function testUpdateChangesTheRowById(): void
    {
        $id = $this->field(self::COURSE, 'upd', 1, ['order' => 3]);

        (new ExtraField('course'))->update([
            'id' => $id,
            'variable' => 'xf_upd',
            'display_text' => 'Changed label',
            'field_order' => 8,
            'value_type' => ExtraField::FIELD_TYPE_TEXT,
        ]);

        $row = $this->conn()->fetchAssociative('SELECT display_text, field_order, variable FROM extra_field WHERE id = ?', [$id]);
        $this->assertSame('Changed label', $row['display_text']);
        $this->assertSame(8, (int) $row['field_order']);
        $this->assertSame('xf_upd', $row['variable']);
    }

    public function testUpdateWithoutValueTypeWritesTheTypeNameIntoTheIntegerColumnAndFails(): void
    {
        // PINNED QUIRK: update() defaults an empty value_type to $this->type ("course") for the option step and
        // then leaves that string in the params it passes to the UPDATE of an integer column.
        $id = $this->field(self::COURSE, 'upd_novt', 1, ['order' => 3]);

        $this->expectException(NotAllowedException::class);
        (new ExtraField('course'))->update(['id' => $id, 'variable' => 'xf_upd_novt', 'display_text' => 'x', 'field_order' => 1]);
    }

    public function testDeleteRemovesTheFieldItsOptionsAndItsValuesButNotOtherFields(): void
    {
        $id = $this->field(self::COURSE, 'del', ExtraField::FIELD_TYPE_SELECT);
        $keep = $this->field(self::COURSE, 'del_keep');
        $this->option($id, 'o', 'O', 1);
        $this->conn()->executeStatement(
            "INSERT INTO extra_field_values (field_id, field_value, item_id, created_at, updated_at) VALUES (?, 'v', 1, NOW(), NOW()), (?, 'k', 1, NOW(), NOW())",
            [$id, $keep]
        );

        $this->assertTrue((new ExtraField('course'))->delete($id));

        $this->assertSame(0, (int) $this->conn()->fetchOne('SELECT COUNT(*) FROM extra_field WHERE id = ?', [$id]));
        $this->assertSame(0, (int) $this->conn()->fetchOne('SELECT COUNT(*) FROM extra_field_options WHERE field_id = ?', [$id]));
        $this->assertSame(0, (int) $this->conn()->fetchOne('SELECT COUNT(*) FROM extra_field_values WHERE field_id = ?', [$id]));
        $this->assertSame(1, (int) $this->conn()->fetchOne('SELECT COUNT(*) FROM extra_field WHERE id = ?', [$keep]));
        $this->assertSame(1, (int) $this->conn()->fetchOne('SELECT COUNT(*) FROM extra_field_values WHERE field_id = ?', [$keep]));
    }

    public function testDeleteOfAnUnknownIdReturnsFalse(): void
    {
        $this->assertFalse((new ExtraField('course'))->delete(999999999));
        $this->assertFalse((new ExtraField('course'))->delete(0));
    }

    // ---------------------------------------------------------------- grid / count

    public function testGetCountCountsTheFieldsOfTheItemType(): void
    {
        $extraField = new ExtraField('session');
        $before = (int) $extraField->get_count();
        $this->field(self::SESSION, 'cnt1');
        $this->field(self::SESSION, 'cnt2');
        $this->field(self::USER, 'cnt_other');

        $this->assertSame($before + 2, (int) $extraField->get_count());
    }

    public function testGetAllGridSortsByTheMappedColumnAndPages(): void
    {
        $this->field(self::SESSION, 'grid_b', 1, ['order' => 2, 'display_text' => 'xf zzz']);
        $this->field(self::SESSION, 'grid_a', 1, ['order' => 1, 'display_text' => 'xf aaa']);
        $extraField = new ExtraField('session');

        $byOrder = array_map(static fn ($f) => $f->getVariable(), $extraField->getAllGrid('field_order', 'ASC', 0, 1000));
        $byText = array_map(static fn ($f) => $f->getVariable(), $extraField->getAllGrid('display_text', 'DESC', 0, 1000));
        $byVariable = array_map(static fn ($f) => $f->getVariable(), $extraField->getAllGrid('variable', 'ASC', 0, 1000));

        $this->assertSame(['xf_grid_a', 'xf_grid_b'], array_values(array_filter($byOrder, static fn ($v) => str_starts_with($v, 'xf_'))));
        $this->assertSame(['xf_grid_b', 'xf_grid_a'], array_values(array_filter($byText, static fn ($v) => str_starts_with($v, 'xf_'))));
        $this->assertSame(['xf_grid_a', 'xf_grid_b'], array_values(array_filter($byVariable, static fn ($v) => str_starts_with($v, 'xf_'))));
        $this->assertCount(1, $extraField->getAllGrid('variable', 'ASC', 0, 1));
        $this->assertCount(1, $extraField->getAllGrid('variable', 'ASC', 1, 1));
    }

    // ---------------------------------------------------------------- tags

    public function testGetAllUserPerTagReturnsTheUserIdsOfThatTagOnThatField(): void
    {
        $fieldId = $this->field(self::USER, 'usertag', ExtraField::FIELD_TYPE_TAG);
        $other = $this->field(self::USER, 'usertag_other', ExtraField::FIELD_TYPE_TAG);
        $this->conn()->executeStatement('INSERT INTO tag (tag, field_id, count) VALUES (\'xf_hello\', ?, 1)', [$fieldId]);
        $tag = (int) $this->conn()->lastInsertId();
        $this->conn()->executeStatement('INSERT INTO tag (tag, field_id, count) VALUES (\'xf_hello\', ?, 1)', [$other]);
        $tagOther = (int) $this->conn()->lastInsertId();
        $this->conn()->executeStatement('INSERT INTO user_rel_tag (user_id, tag_id) VALUES (1, ?), (3, ?), (2, ?)', [$tag, $tag, $tagOther]);

        $rows = (new ExtraField('user'))->getAllUserPerTag($fieldId, 'xf_hello');
        $ids = array_map('intval', array_column($rows, 'user_id'));
        sort($ids);

        $this->assertSame([1, 3], $ids, 'The same tag text on another field is a different tag.');
        $this->assertSame([], (new ExtraField('user'))->getAllUserPerTag($fieldId, 'xf_nothing'));
        $this->assertSame([], (new ExtraField('user'))->getAllUserPerTag($fieldId, "' OR '1'='1"));
    }

    public function testGetAllSkillPerTagReturnsSkillIdsKeyedByThemselves(): void
    {
        $fieldId = $this->field(self::USER, 'skilltag', ExtraField::FIELD_TYPE_TAG);
        $this->conn()->executeStatement('INSERT INTO tag (tag, field_id, count) VALUES (\'xf_skill\', ?, 1)', [$fieldId]);
        $tag = (int) $this->conn()->lastInsertId();
        $skillId = (int) $this->conn()->fetchOne('SELECT id FROM skill ORDER BY id LIMIT 1');
        if (0 === $skillId) {
            $this->markTestSkipped('No skill row in the test database.');
        }
        $this->conn()->executeStatement('INSERT INTO extra_field_rel_tag (field_id, tag_id, item_id) VALUES (?, ?, ?)', [$fieldId, $tag, $skillId]);

        $this->assertSame([$skillId => $skillId], (new ExtraField('user'))->getAllSkillPerTag($fieldId, $tag));
        $this->assertSame([], (new ExtraField('user'))->getAllSkillPerTag($fieldId, $tag + 1000));
    }

    public function testSearchOptionsFromTagsJoinsTagsValuesAndOptions(): void
    {
        $from = $this->field(self::USER, 'from_select', ExtraField::FIELD_TYPE_SELECT);
        $tagField = $this->field(self::USER, 'from_tag', ExtraField::FIELD_TYPE_TAG);
        $this->option($from, 'xf_o1', 'Option 1', 1);
        $this->option($from, 'xf_o2', 'Option 2', 2);
        $this->conn()->executeStatement('INSERT INTO tag (tag, field_id, count) VALUES (\'xf_t\', ?, 1)', [$tagField]);
        $tag = (int) $this->conn()->lastInsertId();
        $this->conn()->executeStatement('INSERT INTO extra_field_rel_tag (field_id, tag_id, item_id) VALUES (?, ?, 1)', [$tagField, $tag]);
        $this->conn()->executeStatement(
            "INSERT INTO extra_field_values (field_id, field_value, item_id, created_at, updated_at) VALUES (?, 'xf_o1', 1, NOW(), NOW())",
            [$from]
        );
        $extraField = new ExtraField('user');

        $rows = $extraField->searchOptionsFromTags('extra_xf_from_select', 'extra_xf_from_tag', ['xf_o1', 'xf_o2']);

        $this->assertCount(1, $rows);
        $this->assertSame('xf_t', $rows[0]['tag']);
        $this->assertSame('xf_o1', $rows[0]['value']);
        $this->assertSame('Option 1', $rows[0]['display_text']);
        $this->assertSame([], $extraField->searchOptionsFromTags('extra_xf_from_select', 'extra_xf_from_tag', ['xf_o2']), 'No value stored for that option.');
        $this->assertSame([], $extraField->searchOptionsFromTags('extra_xf_from_select', 'extra_xf_from_tag', []));
        $this->assertSame([], $extraField->searchOptionsFromTags('extra_xf_unknown', 'extra_xf_from_tag', ['xf_o1']));
    }

    // ---------------------------------------------------------------- where clause / rules

    public function testGetWhereClauseBuildsEscapedComparisons(): void
    {
        $extraField = new ExtraField('user');

        $this->assertSame(" variable = 'abc' ", $extraField->get_where_clause('variable', 'eq', 'abc'));
        $this->assertSame(" variable <> 'abc' ", $extraField->get_where_clause('variable', 'ne', 'abc'));
        $this->assertSame(" variable LIKE 'abc%' ", $extraField->get_where_clause('variable', 'bw', 'abc'));
        $this->assertSame(" variable LIKE '%abc' ", $extraField->get_where_clause('variable', 'ew', 'abc'));
        $this->assertSame(" variable LIKE '%abc%' ", $extraField->get_where_clause('variable', 'cn', 'abc'));
        $this->assertSame(" variable NOT LIKE '%abc%' ", $extraField->get_where_clause('variable', 'nc', 'abc'));
        $this->assertSame(" variable >= '3' ", $extraField->get_where_clause('variable', 'ge', '3'));
        $this->assertSame('', $extraField->get_where_clause('', 'eq', 'abc'));
    }

    public function testGetWhereClauseEscapesQuotesInTheValue(): void
    {
        $clause = (new ExtraField('user'))->get_where_clause('variable', 'eq', "o'brien");

        $this->assertSame(" variable = 'o\\'brien' ", $clause);
    }

    public function testGetWhereClauseWithAnArrayMatchesAnyOfTheValues(): void
    {
        $extraField = new ExtraField('user');

        $this->assertSame(
            ' v LIKE "%a;b%" OR v LIKE "%a%" OR v LIKE "%b%" ',
            $extraField->get_where_clause('v', 'cn', ['a', 'b'])
        );
        $this->assertSame(
            ' v LIKE "%a;b%" AND v LIKE "%a%" AND v LIKE "%b%" ',
            $extraField->get_where_clause('v', 'cn', ['a', 'b'], 'AND')
        );
        $this->assertSame(
            ' v LIKE "%a%" OR v LIKE "%a%" ',
            $extraField->get_where_clause('v', 'cn', ['a'], 'DROP TABLE'),
            'An unknown connector falls back to OR.'
        );
    }

    public function testGetExtraFieldRulesSplitsNormalFieldsFromExtraFieldRules(): void
    {
        $this->field(self::USER, 'rule_text', ExtraField::FIELD_TYPE_TEXT);
        $filters = new stdClass();
        $normal = new stdClass();
        $normal->field = 'firstname';
        $normal->op = 'eq';
        $normal->data = 'Ann';
        $extra = new stdClass();
        $extra->field = 'extra_xf_rule_text';
        $extra->op = 'cn';
        $extra->data = 'zed';
        $filters->rules = [$normal, $extra];

        $result = (new ExtraField('user'))->getExtraFieldRules($filters, 'extra_');

        $this->assertSame(
            [" firstname = 'Ann' ", " (  extra_xf_rule_text LIKE '%zed%'  ) "],
            $result['condition_array']
        );
        $this->assertCount(1, $result['extra_fields']);
        $this->assertSame('extra_xf_rule_text', $result['extra_fields'][0]['field'], 'The field name is sanitised, not quoted.');
        $this->assertSame('zed', $result['extra_fields'][0]['data']);
        $this->assertSame(
            (int) $this->conn()->fetchOne("SELECT id FROM extra_field WHERE variable = 'xf_rule_text'"),
            (int) $result['extra_fields'][0]['id']
        );
    }

    public function testGetExtraFieldRulesWithoutRulesIsEmpty(): void
    {
        $this->assertSame(
            ['extra_fields' => [], 'condition_array' => []],
            (new ExtraField('user'))->getExtraFieldRules(new stdClass())
        );
    }

    // ---------------------------------------------------------------- formatted values

    public function testGetDataAndFormattedValuesOnlyShowsVisibleFieldsWithAValue(): void
    {
        $visible = $this->field(self::COURSE, 'fmt_visible', ExtraField::FIELD_TYPE_TEXT, ['order' => 1]);
        $hidden = $this->field(self::COURSE, 'fmt_hidden', ExtraField::FIELD_TYPE_TEXT, ['visible_to_self' => 0]);
        $empty = $this->field(self::COURSE, 'fmt_empty', ExtraField::FIELD_TYPE_TEXT);
        $check = $this->field(self::COURSE, 'fmt_check', ExtraField::FIELD_TYPE_CHECKBOX, ['order' => 2]);
        foreach ([[$visible, 'hello'], [$hidden, 'secret'], [$check, '1']] as [$fid, $value]) {
            $this->conn()->executeStatement(
                'INSERT INTO extra_field_values (field_id, field_value, item_id, created_at, updated_at) VALUES (?, ?, 4242, NOW(), NOW())',
                [$fid, $value]
            );
        }
        $this->assertGreaterThan(0, $empty);
        $this->fresh();

        $data = $this->mine_formatted((new ExtraField('course'))->getDataAndFormattedValues(4242));

        $this->assertSame(['xf_fmt_visible', 'xf_fmt_check'], array_column($data, 'variable'));
        $this->assertSame('hello', $data[0]['value']);
        $this->assertSame(get_lang('Yes'), $data[1]['value']);
        $this->assertSame([], $data[0]['value_as_array']);
        $this->assertSame(
            ['xf_fmt_check'],
            array_column($this->mine_formatted((new ExtraField('course'))->getDataAndFormattedValues(4242, false, ['xf_fmt_check'])), 'variable'),
            'The onlyShow list restricts the fields.'
        );
    }

    private function mine_formatted(array $rows): array
    {
        return array_values(array_filter($rows, static fn ($r) => str_starts_with($r['variable'], 'xf_')));
    }

    // ---------------------------------------------------------------- dead helper (cheap pin)

    public function testGetHandlerExtraDataOfAnEmptyItemIsEmptyAndReadsTheStoredValueOtherwise(): void
    {
        $fid = $this->field(self::COURSE, 'hed', ExtraField::FIELD_TYPE_TEXT);
        $this->conn()->executeStatement(
            "INSERT INTO extra_field_values (field_id, field_value, item_id, created_at, updated_at) VALUES (?, 'stored', 5151, NOW(), NOW())",
            [$fid]
        );
        $this->fresh();
        $extraField = new ExtraField('course');

        $this->assertSame([], $extraField->get_handler_extra_data(0));
        $this->assertSame('stored', $extraField->get_handler_extra_data(5151)['extra_xf_hed']);
    }
}
