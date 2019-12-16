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
     * @return CGroupInfo
     */
    public function createGroup()
    {
        return $this->create();
    }

    /**
     * @param string $code
     *
     * @return mixed
     */
    public function findOneByCode($code)
    {
        return $this->getRepository()->findOneByCode($code);
    }

    /**
     * @param string $name
     *
     * @return mixed
     */
    public function findOneByTitle($name)
    {
        return $this->getRepository()->findOneByTitle($name);
    }
}
