<?php

declare(strict_types=1);

namespace Chamilo\Tests\CoreBundle\Legacy;

use QuestionOptionsEvaluationPlugin;
use ReflectionMethod;

/**
 * Pins the direct extra_field_values access of QuestionOptionsEvaluationPlugin: the formula is read from the
 * LATEST row (highest id) of the exercise, written by updating that latest row only, or inserted when the exercise
 * has none; an empty stored value reads as null.
 */
final class QuestionOptionsEvaluationPluginTier3CharacterizationTest extends PluginCharacterizationTestCase
{
    private int $fieldId;

    protected function setUp(): void
    {
        parent::setUp();

        require_once api_get_path(SYS_PLUGIN_PATH).'QuestionOptionsEvaluation/QuestionOptionsEvaluationPlugin.php';
        $this->call('createExtraField');
        $this->fieldId = (int) $this->call('getFormulaExtraFieldId');
        $this->assertGreaterThan(0, $this->fieldId);
    }

    private function call(string $method, ...$args)
    {
        $m = new ReflectionMethod(QuestionOptionsEvaluationPlugin::class, $method);

        return $m->invoke(QuestionOptionsEvaluationPlugin::create(), ...$args);
    }

    private function value(int $itemId, ?int $fieldId = null, string $value = 'v', string $created = '2020-01-01 00:00:00'): int
    {
        $this->conn->executeStatement(
            'INSERT INTO extra_field_values (field_id, item_id, field_value, created_at, updated_at) VALUES (?, ?, ?, ?, ?)',
            [$fieldId ?? $this->fieldId, $itemId, $value, $created, $created]
        );

        return (int) $this->conn->lastInsertId();
    }

    private function rows(int $itemId): array
    {
        return $this->conn->fetchAllAssociative(
            'SELECT id, field_id, item_id, field_value, created_at, updated_at FROM extra_field_values WHERE item_id = ? ORDER BY id',
            [$itemId]
        );
    }

    public function testTheStoredValueIsTheLatestRowOfTheExerciseForTheFormulaField(): void
    {
        $item = 987001;
        $this->assertNull($this->call('getStoredFormulaValueDirectly', $item));

        $this->value($item, null, '1');
        $this->value($item, null, '3');
        $other = (int) $this->conn->fetchOne('SELECT MIN(id) FROM extra_field WHERE id <> ?', [$this->fieldId]);
        $this->value($item, $other, '2');
        $this->value($item + 1, null, '1');

        $this->assertSame('3', $this->call('getStoredFormulaValueDirectly', $item), 'Highest id of the formula field wins.');
        $this->assertSame('1', $this->call('getStoredFormulaValueDirectly', $item + 1));
    }

    public function testAnEmptyStoredValueReadsAsNull(): void
    {
        $item = 987002;
        $this->value($item, null, '');
        $this->assertNull($this->call('getStoredFormulaValueDirectly', $item));
    }

    public function testSavingUpdatesOnlyTheLatestRowAndInsertsWhenThereIsNone(): void
    {
        $item = 987003;
        $this->call('saveFormulaValueDirectly', $item, "2'x");
        $rows = $this->rows($item);
        $this->assertCount(1, $rows);
        $this->assertSame($this->fieldId, (int) $rows[0]['field_id']);
        $this->assertSame("2'x", $rows[0]['field_value']);
        $this->assertSame($rows[0]['created_at'], $rows[0]['updated_at']);
        $this->assertNotSame('2020-01-01 00:00:00', $rows[0]['created_at']);

        $second = $this->value($item, null, 'old', '2020-01-01 00:00:00');
        $this->call('saveFormulaValueDirectly', $item, '3');
        $rows = $this->rows($item);
        $this->assertCount(2, $rows, 'No third row: the latest one is updated.');
        $this->assertSame("2'x", $rows[0]['field_value'], 'The older row is untouched.');
        $this->assertSame($second, (int) $rows[1]['id']);
        $this->assertSame('3', $rows[1]['field_value']);
        $this->assertSame('2020-01-01 00:00:00', $rows[1]['created_at'], 'created_at is kept.');
        $this->assertNotSame('2020-01-01 00:00:00', $rows[1]['updated_at']);

        $this->assertSame([], $this->rows($item + 1), 'Other exercises are untouched.');
    }
}
