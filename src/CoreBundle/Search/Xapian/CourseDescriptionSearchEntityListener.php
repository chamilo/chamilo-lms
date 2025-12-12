<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Search\Xapian;

use Chamilo\CourseBundle\Entity\CCourseDescription;
use Doctrine\Bundle\DoctrineBundle\Attribute\AsEntityListener;
use Doctrine\ORM\Events;
use Doctrine\Persistence\Event\LifecycleEventArgs;

/**
 * Doctrine entity listener for CCourseDescription to trigger Xapian indexing.
 */
#[AsEntityListener(event: Events::postPersist, entity: CCourseDescription::class)]
#[AsEntityListener(event: Events::postUpdate,  entity: CCourseDescription::class)]
#[AsEntityListener(event: Events::postRemove,  entity: CCourseDescription::class)]
final class CourseDescriptionSearchEntityListener
{
    public function __construct(
        private readonly CourseDescriptionXapianIndexer $indexer,
    ) {
    }

    public function postPersist(CCourseDescription $description, LifecycleEventArgs $args): void
    {
        $this->indexer->indexCourseDescription($description);
    }

    public function postUpdate(CCourseDescription $description, LifecycleEventArgs $args): void
    {
        $this->indexer->indexCourseDescription($description);
    }

    public function postRemove(CCourseDescription $description, LifecycleEventArgs $args): void
    {
        $this->indexer->deleteCourseDescriptionIndex($description);
    }
}
