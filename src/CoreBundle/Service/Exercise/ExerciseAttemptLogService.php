<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Service\Exercise;

use Chamilo\CoreBundle\Entity\TrackEAttemptLog;
use Chamilo\CoreBundle\Entity\TrackEExercise;
use Doctrine\DBAL\Exception\TableNotFoundException;
use Doctrine\ORM\EntityManagerInterface;
use JsonException;

use const JSON_INVALID_UTF8_SUBSTITUTE;
use const JSON_THROW_ON_ERROR;

/**
 * Appends answer history without changing the current attempt or its score.
 */
final readonly class ExerciseAttemptLogService
{
    private const int MAX_ANSWER_BYTES = 65536;
    private const array EVENT_TYPES = ['changed', 'saved', 'cleared', 'uploaded'];

    public function __construct(
        private EntityManagerInterface $entityManager
    ) {}

    public function record(
        TrackEExercise $attempt,
        int $questionId,
        string $eventType,
        mixed $answer,
        int $secondsSpent = 0,
    ): bool {
        if ($questionId <= 0 || !\in_array($eventType, self::EVENT_TYPES, true)) {
            return false;
        }

        try {
            $encodedAnswer = json_encode(['answer' => $answer], JSON_INVALID_UTF8_SUBSTITUTE | JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            return false;
        }

        if (!\is_string($encodedAnswer) || \strlen($encodedAnswer) > self::MAX_ANSWER_BYTES) {
            return false;
        }

        try {
            $previous = $this->entityManager->getRepository(TrackEAttemptLog::class)->findOneBy(
                ['trackExercise' => $attempt, 'questionId' => $questionId],
                ['id' => 'DESC'],
            );
        } catch (TableNotFoundException) {
            // Exercise saves must remain available until the new migration has been applied.
            return false;
        }

        if ($previous instanceof TrackEAttemptLog
            && $previous->getEventType() === $eventType
            && $previous->getAnswer() === $encodedAnswer
        ) {
            return false;
        }

        $log = (new TrackEAttemptLog())
            ->setTrackExercise($attempt)
            ->setQuestionId($questionId)
            ->setEventType($eventType)
            ->setAnswer($encodedAnswer)
            ->setSecondsSpent(min(2147483647, max(0, $secondsSpent)))
        ;
        $this->entityManager->persist($log);

        return true;
    }
}
