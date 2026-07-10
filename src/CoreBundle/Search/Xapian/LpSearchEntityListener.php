<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Search\Xapian;

use Chamilo\CourseBundle\Entity\CLp;
use Doctrine\Bundle\DoctrineBundle\Attribute\AsEntityListener;
use Doctrine\ORM\Events;
use Doctrine\Persistence\Event\LifecycleEventArgs;
use Throwable;

/**
 * Doctrine entity listener for CLp to trigger Xapian indexing.
 */
#[AsEntityListener(event: Events::postPersist, entity: CLp::class)]
#[AsEntityListener(event: Events::postUpdate, entity: CLp::class)]
#[AsEntityListener(event: Events::postRemove, entity: CLp::class)]
final class LpSearchEntityListener
{
    public function __construct(
        private readonly LpXapianIndexer $indexer,
    ) {}

    public function postPersist(CLp $lp, LifecycleEventArgs $args): void
    {
        $this->indexSafely($lp, 'postPersist');
    }

    public function postUpdate(CLp $lp, LifecycleEventArgs $args): void
    {
        $this->indexSafely($lp, 'postUpdate');
    }

    public function postRemove(CLp $lp, LifecycleEventArgs $args): void
    {
        try {
            $this->indexer->deleteLpIndex($lp);
        } catch (Throwable $exception) {
            error_log(
                '[Xapian] LpSearchEntityListener postRemove: deletion failed: '
                .$exception->getMessage()
            );
        }
    }

    private function indexSafely(CLp $lp, string $event): void
    {
        try {
            $this->indexer->indexLp($lp);
        } catch (Throwable $exception) {
            error_log(
                '[Xapian] LpSearchEntityListener '.$event.': indexing failed: '
                .$exception->getMessage()
            );
        }
    }

}
