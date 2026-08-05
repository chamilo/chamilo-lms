<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Search\Xapian;

use Chamilo\CourseBundle\Entity\CQuizQuestion;
use Doctrine\Bundle\DoctrineBundle\Attribute\AsEntityListener;
use Doctrine\ORM\Events;
use Doctrine\Persistence\Event\LifecycleEventArgs;
use Throwable;

/**
 * Doctrine entity listener for CQuizQuestion to trigger Xapian indexing.
 */
#[AsEntityListener(event: Events::postPersist, entity: CQuizQuestion::class)]
#[AsEntityListener(event: Events::postUpdate, entity: CQuizQuestion::class)]
#[AsEntityListener(event: Events::postRemove, entity: CQuizQuestion::class)]
final class QuizQuestionSearchEntityListener
{
    public function __construct(
        private readonly QuestionXapianIndexer $indexer,
    ) {}

    public function postPersist(CQuizQuestion $question, LifecycleEventArgs $args): void
    {
        $this->indexSafely($question, 'postPersist');
    }

    public function postUpdate(CQuizQuestion $question, LifecycleEventArgs $args): void
    {
        $this->indexSafely($question, 'postUpdate');
    }

    public function postRemove(CQuizQuestion $question, LifecycleEventArgs $args): void
    {
        try {
            $this->indexer->deleteQuestionIndex($question);
        } catch (Throwable $exception) {
            error_log(
                '[Xapian] QuizQuestionSearchEntityListener postRemove: deletion failed: '
                .$exception->getMessage()
            );
        }
    }

    private function indexSafely(CQuizQuestion $question, string $event): void
    {
        try {
            $this->indexer->indexQuestion($question);
        } catch (Throwable $exception) {
            error_log(
                '[Xapian] QuizQuestionSearchEntityListener '.$event.': indexing failed: '
                .$exception->getMessage()
            );
        }
    }
}
