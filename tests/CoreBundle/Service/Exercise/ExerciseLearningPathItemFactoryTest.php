<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\Tests\CoreBundle\Service\Exercise;

use Chamilo\CoreBundle\Service\Exercise\ExerciseLearningPathItemFactory;
use Chamilo\CourseBundle\Entity\CLp;
use Chamilo\CourseBundle\Entity\CLpItem;
use Chamilo\CourseBundle\Entity\CQuiz;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class ExerciseLearningPathItemFactoryTest extends TestCase
{
    public function testCreateBuildsModernQuizLearningPathItem(): void
    {
        $learningPath = new CLp();
        $parent = (new CLpItem())
            ->setTitle('Root')
            ->setItemType('root')
        ;
        $quiz = (new CQuiz())->setTitle('Modern exercise');

        $item = (new ExerciseLearningPathItemFactory())->create(
            $learningPath,
            $quiz,
            107,
            $parent,
            3,
        );

        self::assertSame($learningPath, $item->getLp());
        self::assertSame($parent, $item->getParent());
        self::assertSame('Modern exercise', $item->getTitle());
        self::assertSame('quiz', $item->getItemType());
        self::assertSame('107', $item->getPath());
        self::assertSame('', $item->getRef());
        self::assertSame('', $item->getDescription());
        self::assertSame(0.0, $item->getMaxScore());
        self::assertSame('0', $item->getMaxTimeAllowed());
        self::assertSame('0', $item->getPrerequisite());
        self::assertSame(3, $item->getDisplayOrder());
    }

    public function testCreateRejectsInvalidExerciseId(): void
    {
        $this->expectException(InvalidArgumentException::class);

        (new ExerciseLearningPathItemFactory())->create(
            new CLp(),
            (new CQuiz())->setTitle('Exercise'),
            0,
            (new CLpItem())->setTitle('Root')->setItemType('root'),
            2,
        );
    }

    public function testCreateRejectsInvalidDisplayOrder(): void
    {
        $this->expectException(InvalidArgumentException::class);

        (new ExerciseLearningPathItemFactory())->create(
            new CLp(),
            (new CQuiz())->setTitle('Exercise'),
            107,
            (new CLpItem())->setTitle('Root')->setItemType('root'),
            0,
        );
    }
}
