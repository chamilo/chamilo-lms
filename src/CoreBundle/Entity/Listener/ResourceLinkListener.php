<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Entity\Listener;

use Chamilo\CoreBundle\Entity\ResourceLink;
use Chamilo\CoreBundle\Repository\TrackEDefaultRepository;
use Doctrine\ORM\Event\PostRemoveEventArgs;
use Doctrine\ORM\Event\PostUpdateEventArgs;
use Doctrine\ORM\Exception\ORMException;
use Event;
use Symfony\Bundle\SecurityBundle\Security;

class ResourceLinkListener
{
    public function __construct(
        protected Security $security,
        protected TrackEDefaultRepository $trackEDefaultRepository
    ) {}

    public function postUpdate(ResourceLink $resourceLink, PostUpdateEventArgs $event): void
    {
        $changeSet = $event->getObjectManager()->getUnitOfWork()->getEntityChangeSet($resourceLink);

        if (isset($changeSet['visibility'])) {
            $this->trackEDefaultRepository->registerResourceEvent(
                $resourceLink->getResourceNode(),
                'visibility_change',
                $this->security->getUser()?->getId()
            );
        }
    }

    /**
     * @throws ORMException
     */
    public function postRemove(ResourceLink $resourceLink, PostRemoveEventArgs $args): void
    {
        $resourceNode = $resourceLink->getResourceNode();

        Event::addEvent(
            LOG_RESOURCE_LINK_DELETE,
            LOG_RESOURCE_NODE,
            $resourceNode->getId(),
        );
    }
}
