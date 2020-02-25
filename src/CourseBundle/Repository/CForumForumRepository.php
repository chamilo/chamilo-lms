<?php

/* For licensing terms, see /license.txt */

namespace Chamilo\CourseBundle\Repository;

use Chamilo\CoreBundle\Entity\Resource\AbstractResource;
use Chamilo\CoreBundle\Repository\ResourceRepository;
use Chamilo\CourseBundle\Entity\CForumForum;

final class CForumForumRepository extends ResourceRepository
{
    public function delete(AbstractResource $resource)
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
