<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Entity\Manager;

use Chamilo\CoreBundle\Entity\Repository\CourseRepository;
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
class CourseManager extends BaseEntityManager
{
    /**
     * @return Course
     */
    public function createCourse()
    {
        return $this->create();
    }

    /**
     * @param $code
     * @return mixed
     */
    public function findOneByCode($code)
    {
        return $this->getRepository()->findOneByCode($code);
    }

    /**
     * @param $name
     * @return mixed
     */
    public function findOneByTitle($name)
    {
        return $this->getRepository()->findOneByTitle($name);
    }
}
