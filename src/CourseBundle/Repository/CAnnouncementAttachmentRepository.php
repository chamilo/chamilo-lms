<?php

/* For licensing terms, see /license.txt */

namespace Chamilo\CourseBundle\Repository;

use Chamilo\CoreBundle\Repository\ResourceRepository;
use Chamilo\CourseBundle\Entity\CAnnouncementAttachment;
use Doctrine\Persistence\ManagerRegistry;

/**
 * Class CAnnouncementRepository.
 */
final class CAnnouncementAttachmentRepository extends ResourceRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, CAnnouncementAttachment::class);
    }
}
