<?php

declare(strict_types=1);

namespace Chamilo\CoreBundle\Search\Xapian;

use Chamilo\CourseBundle\Entity\CQuiz;
use Doctrine\Bundle\DoctrineBundle\Attribute\AsEntityListener;
use Doctrine\ORM\Events;
use Doctrine\Persistence\Event\LifecycleEventArgs;
use Throwable;

/**
 * Doctrine entity listener for CQuiz to trigger Xapian indexing.
 */
#[AsEntityListener(event: Events::postPersist, entity: CQuiz::class)]
#[AsEntityListener(event: Events::postUpdate, entity: CQuiz::class)]
#[AsEntityListener(event: Events::postRemove, entity: CQuiz::class)]
final class QuizSearchEntityListener
{
    public function __construct(
        private readonly QuizXapianIndexer $indexer,
    ) {}

    public function postPersist(CQuiz $quiz, LifecycleEventArgs $args): void
    {
        $this->indexSafely($quiz, 'postPersist');
    }

    public function postUpdate(CQuiz $quiz, LifecycleEventArgs $args): void
    {
        $this->indexSafely($quiz, 'postUpdate');
    }

    public function postRemove(CQuiz $quiz, LifecycleEventArgs $args): void
    {
        try {
            $this->indexer->deleteQuizIndex($quiz);
        } catch (Throwable $exception) {
            error_log(
                '[Xapian] QuizSearchEntityListener postRemove: deletion failed: '
                .$exception->getMessage()
            );
        }
    }

    private function indexSafely(CQuiz $quiz, string $event): void
    {
        try {
            $this->indexer->indexQuiz($quiz);
        } catch (Throwable $exception) {
            error_log(
                '[Xapian] QuizSearchEntityListener '.$event.': indexing failed: '
                .$exception->getMessage()
            );
        }
    }
}
