<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\Tests\CoreBundle\Service\Exercise;

use Chamilo\CoreBundle\Entity\TrackEAttemptLog;
use Chamilo\CoreBundle\Entity\TrackEExercise;
use Chamilo\CoreBundle\Service\Exercise\ExerciseAttemptLogService;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use PHPUnit\Framework\TestCase;

final class ExerciseAttemptLogServiceTest extends TestCase
{
    public function testRecordsAnswerChangeWithoutModifyingExerciseAttempt(): void
    {
        $attempt = new TrackEExercise();
        $repository = $this->createMock(EntityRepository::class);
        $repository->method('findOneBy')->willReturn(null);

        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->method('getRepository')->with(TrackEAttemptLog::class)->willReturn($repository);
        $entityManager->expects(self::once())->method('persist')->with(self::callback(
            static function (mixed $log) use ($attempt): bool {
                return $log instanceof TrackEAttemptLog
                    && $log->getTrackExercise() === $attempt
                    && 24 === $log->getQuestionId()
                    && 'changed' === $log->getEventType()
                    && '{"answer":{"choice":3}}' === $log->getAnswer()
                    && 15 === $log->getSecondsSpent();
            }
        ));
        $entityManager->expects(self::never())->method('flush');

        $service = new ExerciseAttemptLogService($entityManager);

        self::assertTrue($service->record($attempt, 24, 'changed', ['choice' => 3], 15));
        self::assertSame('incomplete', $attempt->getStatus());
    }

    public function testDoesNotRecordDuplicateAnswerEvent(): void
    {
        $attempt = new TrackEExercise();
        $previous = (new TrackEAttemptLog())
            ->setTrackExercise($attempt)
            ->setQuestionId(24)
            ->setEventType('saved')
            ->setAnswer('{"answer":{"choice":3}}')
        ;

        $repository = $this->createMock(EntityRepository::class);
        $repository->method('findOneBy')->willReturn($previous);

        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->method('getRepository')->willReturn($repository);
        $entityManager->expects(self::never())->method('persist');

        $service = new ExerciseAttemptLogService($entityManager);

        self::assertFalse($service->record($attempt, 24, 'saved', ['choice' => 3]));
    }

    public function testRecordsChangedChoiceAfterPreviousSave(): void
    {
        $attempt = new TrackEExercise();
        $previous = (new TrackEAttemptLog())
            ->setTrackExercise($attempt)
            ->setQuestionId(24)
            ->setEventType('saved')
            ->setAnswer('{"answer":{"choice":3}}')
        ;

        $repository = $this->createMock(EntityRepository::class);
        $repository->method('findOneBy')->willReturn($previous);

        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->method('getRepository')->willReturn($repository);
        $entityManager->expects(self::once())->method('persist');

        $service = new ExerciseAttemptLogService($entityManager);

        self::assertTrue($service->record($attempt, 24, 'changed', ['choice' => 5]));
    }

    public function testRejectsInvalidEventAndOversizedAnswers(): void
    {
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->expects(self::never())->method('persist');
        $entityManager->expects(self::never())->method('getRepository');

        $service = new ExerciseAttemptLogService($entityManager);
        $attempt = new TrackEExercise();

        self::assertFalse($service->record($attempt, 0, 'changed', ['choice' => 3]));
        self::assertFalse($service->record($attempt, 24, 'unknown', ['choice' => 3]));
        self::assertFalse($service->record($attempt, 24, 'saved', ['text' => str_repeat('a', 70000)]));
    }
}
