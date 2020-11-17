<?php

/* For licensing terms, see /license.txt */

namespace Chamilo\CourseBundle\Repository;

use Chamilo\CoreBundle\Repository\ResourceRepository;
use Chamilo\CourseBundle\Entity\CGroup;

/**
 * Class CGroupRepository.
 */
final class CGroupRepository extends ResourceRepository
{
    /**
     * @param string $code
     */
    public function findOneByCode($code): ?CGroup
    {
        return $this->getRepository()->findOneByCode($code);
    }

    /**
     * @param string $name
     */
    public function findOneByTitle($name): ?CGroup
    {
        return $this->getRepository()->findOneByTitle($name);
    }
}
