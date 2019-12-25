<?php

/* For licensing terms, see /license.txt */

namespace Chamilo\CourseBundle\Repository;

use Chamilo\CoreBundle\Repository\ResourceRepository;
use Chamilo\CourseBundle\Entity\CGroupInfo;

/**
 * Class CGroupInfoRepository.
 */
final class CGroupInfoRepository extends ResourceRepository
{
    /**
     * @param string $code
     */
    public function findOneByCode($code): ?CGroupInfo
    {
        return $this->getRepository()->findOneByCode($code);
    }

    /**
     * @param string $name
     */
    public function findOneByTitle($name): ?CGroupInfo
    {
        return $this->getRepository()->findOneByTitle($name);
    }
}
