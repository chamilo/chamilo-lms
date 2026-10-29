<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\Tests\CourseBundle\Entity;

use Chamilo\CourseBundle\Entity\CQuiz;
use Doctrine\ORM\Mapping\Column;
use PHPUnit\Framework\TestCase;
use ReflectionProperty;

final class CQuizQuestionMaxScoreTest extends TestCase
{
    public function testExistingExerciseBehaviorRemainsVisibleByDefault(): void
    {
        self::assertTrue((new CQuiz())->isShowQuestionMaxScore());
    }

    public function testTeacherCanDisableAndRestoreTheFlag(): void
    {
        $quiz = new CQuiz();
        self::assertSame($quiz, $quiz->setShowQuestionMaxScore(false));
        self::assertFalse($quiz->isShowQuestionMaxScore());
        self::assertSame($quiz, $quiz->setShowQuestionMaxScore(true));
        self::assertTrue($quiz->isShowQuestionMaxScore());
    }

    public function testTheFlagIsPersistedPerQuizWithBackwardCompatibleDefault(): void
    {
        $property = new ReflectionProperty(CQuiz::class, 'showQuestionMaxScore');
        $attributes = $property->getAttributes(Column::class);
        self::assertCount(1, $attributes);
        $column = $attributes[0]->newInstance();
        self::assertSame('show_question_max_score', $column->name);
        self::assertSame('boolean', $column->type);
        self::assertFalse($column->nullable);
        self::assertSame(1, $column->options['default'] ?? null);
    }
}
