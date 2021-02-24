<?php

/* For licensing terms, see /license.txt */

namespace Chamilo\CourseBundle\Repository;

use Chamilo\CoreBundle\Entity\ResourceInterface;
use Chamilo\CoreBundle\Repository\ResourceRepository;
use Chamilo\CourseBundle\Entity\CForumForum;
use Doctrine\Persistence\ManagerRegistry;

final class CForumForumRepository extends ResourceRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, CForumForum::class);
    }

    public function delete(ResourceInterface $resource): void
    {
        /** @var CForumForum $resource */
        $threads = $resource->getThreads();
        if (!empty($threads)) {
            foreach ($threads as $thread) {
                parent::delete($thread);
            }
        }

        parent::delete($resource);
    }
}
