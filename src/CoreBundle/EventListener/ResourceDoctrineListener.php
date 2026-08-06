<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\EventListener;

use Chamilo\CoreBundle\Entity\AbstractResource;
use Chamilo\CoreBundle\Entity\TrackEDefault;
use Chamilo\CoreBundle\Helpers\ResourceHelper;
use Doctrine\Bundle\DoctrineBundle\Attribute\AsDoctrineListener;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Event\PostFlushEventArgs;
use Doctrine\ORM\Event\PostPersistEventArgs;
use Doctrine\ORM\Event\PostRemoveEventArgs;
use Doctrine\ORM\Event\PostUpdateEventArgs;
use Doctrine\ORM\Events;

#[AsDoctrineListener(event: Events::postPersist, connection: 'default')]
#[AsDoctrineListener(event: Events::postUpdate, connection: 'default')]
#[AsDoctrineListener(event: Events::postRemove, connection: 'default')]
#[AsDoctrineListener(event: Events::postFlush, connection: 'default')]
class ResourceDoctrineListener
{
    /**
     * @var array<int, TrackEDefault>
     */
    private array $trackDefaultEvents = [];

    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly ResourceHelper $trackEDefaultHelper,
    ) {}

    public function postPersist(PostPersistEventArgs $args): void
    {
        $resource = $args->getObject();

        if (!$resource instanceof AbstractResource) {
            return;
        }

        if ($resourceNode = $resource->getResourceNode()) {
            $trackDefault = $this->trackEDefaultHelper->createResourceEvent(
                $resourceNode,
                'creation'
            );

            if ($trackDefault) {
                $this->trackDefaultEvents[] = $trackDefault;
            }
        }
    }

    public function postUpdate(PostUpdateEventArgs $args): void
    {
        $resource = $args->getObject();

        if (!$resource instanceof AbstractResource) {
            return;
        }

        if ($resourceNode = $resource->getResourceNode()) {
            $trackDefault = $this->trackEDefaultHelper->createResourceEvent(
                $resourceNode,
                'edition'
            );

            if ($trackDefault) {
                $this->trackDefaultEvents[] = $trackDefault;
            }
        }
    }

    public function postRemove(PostRemoveEventArgs $args): void
    {
        $resource = $args->getObject();

        if (!$resource instanceof AbstractResource) {
            return;
        }

        $resourceNode = $resource->getResourceNode();

        if (!$resourceNode) {
            return;
        }

        $trackDefault = $this->trackEDefaultHelper->createResourceEvent(
            $resourceNode,
            'deletion'
        );

        if ($trackDefault) {
            $this->trackDefaultEvents[] = $trackDefault;
        }
    }

    public function postFlush(PostFlushEventArgs $args): void
    {
        if (empty($this->trackDefaultEvents)) {
            return;
        }

        $pending = $this->trackDefaultEvents;

        $this->trackDefaultEvents = [];

        // Confirmed live: persisting these through the ORM (persist() then
        // a nested flush() from inside postFlush) re-triggers Doctrine's
        // full changeset computation over the WHOLE identity map, not just
        // these new TrackEDefault rows — and if the flush that's still
        // wrapping up touched an entity with a relationship to something
        // else also being removed in the same operation (e.g. deleting a
        // CLinkCategory that still has a CLink pointing back at it via
        // CLink#category), that now-stale in-memory reference gets
        // rediscovered and thrown as
        // ORMInvalidArgumentException::newEntitiesFoundThroughRelationships
        // — a real, reproducible 500 on "delete a link category that has a
        // link nested inside it". TrackEDefault is a plain audit-log row
        // with no entity relationships of its own, so writing it via a
        // direct DBAL insert sidesteps the ORM's UnitOfWork/changeset
        // machinery entirely for this side effect, instead of triggering a
        // second, re-entrant flush() nested inside this flush's own
        // listener.
        $connection = $this->entityManager->getConnection();
        foreach ($pending as $event) {
            $connection->insert('track_e_default', [
                'default_user_id' => $event->getDefaultUserId(),
                'c_id' => $event->getCId(),
                'default_date' => $event->getDefaultDate()->format('Y-m-d H:i:s'),
                'default_event_type' => $event->getDefaultEventType(),
                'default_value_type' => $event->getDefaultValueType(),
                'default_value' => $event->getDefaultValue(),
                'session_id' => $event->getSessionId(),
            ]);
        }
    }
}
