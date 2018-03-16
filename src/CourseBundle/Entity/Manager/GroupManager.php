<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\CourseBundle\Entity\Manager;

use Chamilo\CourseBundle\Entity\CGroupInfo;
use Sonata\CoreBundle\Model\BaseEntityManager;

/**
 * Class CourseManager.
 *
 * @package Chamilo\CoreBundle\Entity\Manager
 */
class GroupManager extends BaseEntityManager
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
