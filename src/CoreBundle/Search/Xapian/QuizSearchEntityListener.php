<?php

declare(strict_types=1);

namespace Chamilo\CoreBundle\Search\Xapian;

use Chamilo\CourseBundle\Entity\CQuiz;
use Doctrine\Bundle\DoctrineBundle\Attribute\AsEntityListener;
use Doctrine\ORM\Events;
use Doctrine\Persistence\Event\LifecycleEventArgs;

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
        $this->indexer->indexQuiz($quiz);
    }

    public function postUpdate(CQuiz $quiz, LifecycleEventArgs $args): void
    {
        $this->indexer->indexQuiz($quiz);
    }

    public function postRemove(CQuiz $quiz, LifecycleEventArgs $args): void
    {
        $this->indexer->deleteQuizIndex($quiz);
    }
}
