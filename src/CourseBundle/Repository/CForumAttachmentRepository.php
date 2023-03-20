<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CourseBundle\Repository;

use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\ResourceNode;
use Chamilo\CoreBundle\Entity\Session;
use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Repository\ResourceRepository;
use Chamilo\CourseBundle\Entity\CForumAttachment;
use Chamilo\CourseBundle\Entity\CGroup;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;

class CForumAttachmentRepository extends ResourceRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, CForumAttachment::class);
    }

    /*public function getResources(User $user, ResourceNode $parentNode, Course $course = null, Session $session = null, CGroup $group = null): QueryBuilder
    {
        return $this->getResourcesByCourse($course, $session, $group, $parentNode);
    }*/
}
