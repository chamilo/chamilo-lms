<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\EventListener;

use Chamilo\CoreBundle\Entity\AbstractResource;
use Chamilo\CoreBundle\Entity\TrackEDefault;
use Chamilo\CoreBundle\Helpers\UserHelper;
use Chamilo\CoreBundle\Helpers\ResourceHelper;
use Doctrine\Bundle\DoctrineBundle\Attribute\AsDoctrineListener;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Event\PostFlushEventArgs;
use Doctrine\ORM\Event\PostRemoveEventArgs;
use Doctrine\ORM\Events;

#[AsDoctrineListener(event: Events::postRemove, connection: 'default')]
#[AsDoctrineListener(event: Events::postFlush, connection: 'default')]
class ResourceDoctrineListener
{
    /** @var array<int, TrackEDefault> */
    private array $trackDefaultEvents = [];

    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly ResourceHelper $trackEDefaultHelper,
    ) {}

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

        foreach ($pending as $event) {
            $this->entityManager->persist($event);
        }

        $this->entityManager->flush();
    }
}

