<?php

/* For licensing terms, see /license.txt */

namespace Chamilo\CourseBundle\Repository;

use Chamilo\CoreBundle\Entity\AbstractResource;
use Chamilo\CoreBundle\Repository\ResourceRepository;
use Chamilo\CourseBundle\Entity\CForumThread;
use Doctrine\Persistence\ManagerRegistry;

/**
 * Class CForumThreadRepository.
 */
class CForumThreadRepository extends ResourceRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, CForumThread::class);
    }

    public function delete(AbstractResource $resource)
    {
        /** @var CForumThread $resource */
        $posts = $resource->getPosts();
        if (!empty($posts)) {
            foreach ($posts as $post) {
                parent::delete($post);
            }
        }

        parent::delete($resource);
    }
}
