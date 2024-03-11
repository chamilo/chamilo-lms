<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Repository;

use Chamilo\CoreBundle\Entity\ResourceLink;
use Doctrine\ORM\EntityManagerInterface;
use Gedmo\Sortable\Entity\Repository\SortableRepository;

class ResourceLinkRepository extends SortableRepository
{
    public function __construct(EntityManagerInterface $em)
    {
        parent::__construct($em, $em->getClassMetadata(ResourceLink::class));
    }

    public function remove(ResourceLink $resourceLink): void
    {
        $em = $this->getEntityManager();

        // To move the resource link at the end to reorder the list
        $resourceLink->setDisplayOrder(-1);

        $em->flush();
        // soft delete handled by Gedmo\SoftDeleteable
        $em->remove($resourceLink);
        $em->flush();
    }
}
