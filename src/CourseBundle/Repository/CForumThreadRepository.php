<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CourseBundle\Repository;

use Chamilo\CoreBundle\Entity\ResourceInterface;
use Chamilo\CoreBundle\Repository\ResourceRepository;
use Chamilo\CourseBundle\Entity\CForumThread;
use Doctrine\Persistence\ManagerRegistry;

class CForumThreadRepository extends ResourceRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, CForumThread::class);
    }

    public function delete(ResourceInterface $resource): void
    {
        /** @var CForumThread $resource */
        $posts = $resource->getPosts();
        foreach ($posts as $post) {
            parent::delete($post);
        }

        parent::delete($resource);
    }
}
