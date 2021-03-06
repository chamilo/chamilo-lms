<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CourseBundle\Repository;

use Chamilo\CoreBundle\Entity\ResourceInterface;
use Chamilo\CoreBundle\Repository\ResourceRepository;
use Chamilo\CourseBundle\Entity\CForum;
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
        if (!empty($threads)) {
            foreach ($threads as $thread) {
                parent::delete($thread);
            }
        }

        parent::delete($resource);
    }
}
