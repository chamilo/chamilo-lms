<?php

declare(strict_types=1);

namespace Chamilo\Tests\CoreBundle\Legacy;

use PDO;
use Positioning;

/**
 * Pins the initial/final exercise bookkeeping of the Positioning plugin (table plugin_positioning_exercise):
 * lookup by (exercise, course, session), the "only one initial / one final per course+session" rule of
 * setOption(), the swap of the opposite flag, and the "no row" result (false).
 */
final class PositioningPluginTier3CharacterizationTest extends PluginCharacterizationTestCase
{
    private const TABLE = 'plugin_positioning_exercise';

    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();
        // The plugin installer also touches the course settings of every course: create the table only (plain
        // PDO, outside the DAMA transaction), with the DDL of Positioning::install().
        $dsn = \sprintf(
            'mysql:host=%s;port=%s;dbname=%s',
            $_SERVER['DATABASE_HOST'] ?? '127.0.0.1',
            $_SERVER['DATABASE_PORT'] ?? '3306',
            ($_SERVER['DATABASE_NAME'] ?? 'chamilo').($_SERVER['DATABASE_TEST_SUFFIX'] ?? '_test').($_SERVER['TEST_TOKEN'] ?? '')
        );
        $pdo = new PDO($dsn, $_SERVER['DATABASE_USER'] ?? 'root', $_SERVER['DATABASE_PASSWORD'] ?? '');
        $pdo->exec(
            'CREATE TABLE IF NOT EXISTS '.self::TABLE.' (
                id INT unsigned NOT NULL auto_increment PRIMARY KEY,
                exercise_id INT unsigned NOT NULL,
                c_id INT unsigned NOT NULL,
                session_id INT unsigned DEFAULT NULL,
                is_initial TINYINT(1) NOT NULL,
                is_final TINYINT(1) NOT NULL
            )'
        );
    }

    protected function setUp(): void
    {
        parent::setUp();
        require_once api_get_path(SYS_PLUGIN_PATH).'Positioning/src/Positioning.php';
        $this->conn->executeStatement('DELETE FROM '.self::TABLE);
    }

    private function plugin(): Positioning
    {
        return Positioning::create();
    }

    private function rows(): array
    {
        return $this->conn->fetchAllAssociative('SELECT exercise_id, c_id, session_id, is_initial, is_final FROM '.self::TABLE.' ORDER BY id');
    }

    public function testNoRowGivesFalse(): void
    {
        $this->assertFalse($this->plugin()->getPositionData(11, 1, 0));
        $this->assertFalse($this->plugin()->isInitialExercise(11, 1, 0));
        $this->assertNull($this->plugin()->isFinalExercise(11, 1, 0));
        $this->assertFalse($this->plugin()->getInitialExercise(1, 0));
        $this->assertFalse($this->plugin()->getFinalExercise(1, 0));
    }

    public function testSettingTheInitialExerciseIsScopedByCourseAndSession(): void
    {
        $this->plugin()->setInitialExercise(11, 1, 0);

        $data = $this->plugin()->getPositionData(11, 1, 0);
        $this->assertSame(11, (int) $data['exercise_id']);
        $this->assertSame(1, (int) $data['is_initial']);
        $this->assertSame(0, (int) $data['is_final']);
        $this->assertTrue($this->plugin()->isInitialExercise(11, 1, 0));
        $this->assertFalse($this->plugin()->isInitialExercise(11, 2, 0), 'Other course.');
        $this->assertFalse($this->plugin()->isInitialExercise(11, 1, 5), 'Other session.');
        $this->assertFalse($this->plugin()->isInitialExercise(12, 1, 0), 'Other exercise.');
        $this->assertSame(11, (int) $this->plugin()->getInitialExercise(1, 0)['exercise_id']);
        $this->assertFalse($this->plugin()->getFinalExercise(1, 0));
        $this->assertFalse($this->plugin()->getInitialExercise(2, 0));
    }

    public function testASecondInitialExerciseReplacesTheFirstOfThatCourseAndSessionOnly(): void
    {
        $this->plugin()->setInitialExercise(11, 1, 0);
        $this->plugin()->setInitialExercise(21, 2, 0);
        $this->plugin()->setInitialExercise(31, 1, 7);
        $this->plugin()->setInitialExercise(12, 1, 0);

        $this->assertFalse($this->plugin()->getPositionData(11, 1, 0), 'The previous initial exercise row is deleted.');
        $this->assertSame(12, (int) $this->plugin()->getInitialExercise(1, 0)['exercise_id']);
        $this->assertSame(21, (int) $this->plugin()->getInitialExercise(2, 0)['exercise_id']);
        $this->assertSame(31, (int) $this->plugin()->getInitialExercise(1, 7)['exercise_id']);
        $this->assertCount(3, $this->rows());
    }

    public function testMarkingAnExistingExerciseAsFinalSwapsItsFlagsAndDeletesTheOldFinal(): void
    {
        $this->plugin()->setInitialExercise(11, 1, 0);
        $this->plugin()->setFinalExercise(12, 1, 0);
        $this->assertTrue($this->plugin()->isFinalExercise(12, 1, 0));
        $this->assertTrue($this->plugin()->isInitialExercise(11, 1, 0));

        // Exercise 11 (initial) becomes the final one: flags swapped, the previous final (12) is removed.
        $this->plugin()->setFinalExercise(11, 1, 0);

        $this->assertTrue($this->plugin()->isFinalExercise(11, 1, 0));
        $this->assertFalse($this->plugin()->isInitialExercise(11, 1, 0));
        $this->assertFalse($this->plugin()->getPositionData(12, 1, 0));
        $this->assertSame(11, (int) $this->plugin()->getFinalExercise(1, 0)['exercise_id']);
        $this->assertFalse($this->plugin()->getInitialExercise(1, 0));
        $this->assertCount(1, $this->rows());
    }

    public function testGetPositionDataReturnsTheRowWithItsColumns(): void
    {
        $this->plugin()->setFinalExercise(41, 1, 3);
        $data = $this->plugin()->getPositionData('41', '1', '3');
        $this->assertSame(['id', 'exercise_id', 'c_id', 'session_id', 'is_initial', 'is_final'], array_keys($data));
        $this->assertSame(3, (int) $data['session_id']);
    }
}
