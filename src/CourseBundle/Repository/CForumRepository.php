<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CourseBundle\Repository;

use Chamilo\CoreBundle\Entity\ResourceInterface;
use Chamilo\CoreBundle\Repository\ResourceRepository;
use Chamilo\CourseBundle\Entity\CForum;
use Chamilo\CourseBundle\Entity\CForumThread;
use Doctrine\Persistence\ManagerRegistry;

final class CForumRepository extends ResourceRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, CForum::class);
    }

    public function delete(ResourceInterface $resource): void
    {
        /** @var CForum $resource */
        $threads = $resource->getThreads();
        $repo = $this->getEntityManager()->getRepository(CForumThread::class);
        if (!empty($threads)) {
            foreach ($threads as $thread) {
                /** @var CForumThread $thread */
                $repo->delete($thread);
            }
        }

        parent::delete($resource);
    }
}
