<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Entity\Listener;

use Chamilo\CoreBundle\Entity\ResourceLink;
use Doctrine\ORM\Event\PostRemoveEventArgs;
use Doctrine\ORM\Exception\ORMException;
use Event;

class ResourceLinkListener
{
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
