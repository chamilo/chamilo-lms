<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\EventListener;

use Chamilo\CoreBundle\Entity\ResourceLink;
use Doctrine\Bundle\DoctrineBundle\Attribute\AsDoctrineListener;
use Doctrine\ORM\Exception\ORMException;
use Event;
use Gedmo\SoftDeleteable\Event\PostSoftDeleteEventArgs;
use Gedmo\SoftDeleteable\SoftDeleteableListener;

#[AsDoctrineListener(event: SoftDeleteableListener::POST_SOFT_DELETE, connection: 'default')]
class ResourceLinkListener
{
    /**
     * @throws ORMException
     */
    public function postSoftDelete(PostSoftDeleteEventArgs $args): void
    {
        $object = $args->getObject();

        if (!$object instanceof ResourceLink) {
            return;
        }

        Event::addEvent(
            LOG_RESOURCE_LINK_SOFT_DELETE,
            LOG_RESOURCE_LINK,
            $object->getId(),
        );
    }
}
