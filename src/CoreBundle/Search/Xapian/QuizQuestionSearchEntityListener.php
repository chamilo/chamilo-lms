<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Search\Xapian;

use Chamilo\CourseBundle\Entity\CQuizQuestion;
use Doctrine\Bundle\DoctrineBundle\Attribute\AsEntityListener;
use Doctrine\ORM\Events;
use Doctrine\Persistence\Event\LifecycleEventArgs;

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
        $this->indexer->indexQuestion($question);
    }

    public function postUpdate(CQuizQuestion $question, LifecycleEventArgs $args): void
    {
        $this->indexer->indexQuestion($question);
    }

    public function postRemove(CQuizQuestion $question, LifecycleEventArgs $args): void
    {
        $this->indexer->deleteQuestionIndex($question);
    }
}
