<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Repository;

use Chamilo\CoreBundle\Entity\AbstractResource;
use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\ResourceLink;
use Chamilo\CoreBundle\Entity\Session;
use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Entity\Usergroup;
use Chamilo\CourseBundle\Entity\CGroup;
use Doctrine\ORM\EntityManagerInterface;
use Gedmo\Sortable\Entity\Repository\SortableRepository;

/**
 * @template-extends SortableRepository<ResourceLink>
 */
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

    public function removeByResourceInContext(
        AbstractResource $resource,
        Course $course,
        ?Session $session = null,
        ?CGroup $group = null,
        ?Usergroup $usergroup = null,
        ?User $user = null,
    ): void {
        $link = $resource->getResourceNode()->getResourceLinkByContext($course, $session, $group, $usergroup, $user);

        if ($link) {
            $this->remove($link);
        }
    }
}
