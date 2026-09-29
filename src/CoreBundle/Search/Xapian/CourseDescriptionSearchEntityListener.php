<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Search\Xapian;

use Chamilo\CourseBundle\Entity\CCourseDescription;
use Doctrine\Bundle\DoctrineBundle\Attribute\AsEntityListener;
use Doctrine\ORM\Events;
use Doctrine\Persistence\Event\LifecycleEventArgs;
use Throwable;

/**
 * Doctrine entity listener for CCourseDescription to trigger Xapian indexing.
 */
#[AsEntityListener(event: Events::postPersist, entity: CCourseDescription::class)]
#[AsEntityListener(event: Events::postUpdate, entity: CCourseDescription::class)]
#[AsEntityListener(event: Events::postRemove, entity: CCourseDescription::class)]
final class CourseDescriptionSearchEntityListener
{
    public function __construct(
        private readonly CourseDescriptionXapianIndexer $indexer,
    ) {}

    public function postPersist(CCourseDescription $description, LifecycleEventArgs $args): void
    {
        $this->indexSafely($description, 'postPersist');
    }

    public function postUpdate(CCourseDescription $description, LifecycleEventArgs $args): void
    {
        $this->indexSafely($description, 'postUpdate');
    }

    public function postRemove(CCourseDescription $description, LifecycleEventArgs $args): void
    {
        try {
            $this->indexer->deleteCourseDescriptionIndex($description);
        } catch (Throwable $exception) {
            error_log(
                '[Xapian] CourseDescriptionSearchEntityListener postRemove: deletion failed: '
                .$exception->getMessage()
            );
        }
    }

    private function indexSafely(CCourseDescription $description, string $event): void
    {
        try {
            $this->indexer->indexCourseDescription($description);
        } catch (Throwable $exception) {
            error_log(
                '[Xapian] CourseDescriptionSearchEntityListener '.$event.': indexing failed: '
                .$exception->getMessage()
            );
        }
    }
}
