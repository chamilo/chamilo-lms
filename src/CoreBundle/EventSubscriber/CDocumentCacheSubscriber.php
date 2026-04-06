<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\EventSubscriber;

use Chamilo\CoreBundle\Cache\DocumentListCacheInvalidator;
use Chamilo\CourseBundle\Entity\CDocument;
use Doctrine\Bundle\DoctrineBundle\Attribute\AsDoctrineListener;
use Doctrine\ORM\Event\PostPersistEventArgs;
use Doctrine\ORM\Event\PostRemoveEventArgs;
use Doctrine\ORM\Event\PostUpdateEventArgs;
use Doctrine\ORM\Events;

#[AsDoctrineListener(event: Events::postPersist)]
#[AsDoctrineListener(event: Events::postUpdate)]
#[AsDoctrineListener(event: Events::postRemove)]
final class CDocumentCacheSubscriber
{
    public function __construct(
        private readonly DocumentListCacheInvalidator $invalidator,
    ) {}

    public function postPersist(PostPersistEventArgs $args): void
    {
        if ($args->getObject() instanceof CDocument) {
            $this->invalidator->invalidate();
        }
    }

    public function postUpdate(PostUpdateEventArgs $args): void
    {
        if ($args->getObject() instanceof CDocument) {
            $this->invalidator->invalidate();
        }
    }

    public function postRemove(PostRemoveEventArgs $args): void
    {
        if ($args->getObject() instanceof CDocument) {
            $this->invalidator->invalidate();
        }
    }
}
