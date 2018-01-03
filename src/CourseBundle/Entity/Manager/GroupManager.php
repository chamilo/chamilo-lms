<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\CourseBundle\Entity\Manager;

use Chamilo\CoreBundle\Entity\Repository\CourseRepository;
use Chamilo\CourseBundle\Entity\CGroupInfo;
use Sonata\CoreBundle\Model\BaseEntityManager;
use Chamilo\CoreBundle\Entity\Course;
use Chamilo\UserBundle\Entity\User;
use Sonata\DatagridBundle\Pager\Doctrine\pager;
use Sonata\DatagridBundle\ProxyQuery\Doctrine\ProxyQuery;
use Doctrine\Common\Collections\Criteria;

/**
 * Class CourseManager
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
     * @return mixed
     */
    public function findOneByCode($code)
    {
        return $this->getRepository()->findOneByCode($code);
    }

    /**
     * @param string $name
     * @return mixed
     */
    public function findOneByTitle($name)
    {
        return $this->getRepository()->findOneByTitle($name);
    }
}
