<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Entity\Manager;

use Chamilo\CoreBundle\Entity\Repository\CourseRepository;
use Chamilo\CoreBundle\Entity\Session;
use Sonata\CoreBundle\Model\BaseEntityManager;
use Chamilo\CoreBundle\Entity\Course;
use Chamilo\UserBundle\Entity\User;
use Sonata\DatagridBundle\Pager\Doctrine\pager;
use Sonata\DatagridBundle\ProxyQuery\Doctrine\ProxyQuery;
use Doctrine\Common\Collections\Criteria;

/**
 * Class SessionManager
 * @package Chamilo\CoreBundle\Entity\Manager
 */
class SessionManager extends BaseEntityManager
{
    /**
     * @return Session
     */
    public function createSession()
    {
        return $this->create();
    }

    /**
     * @param $name
     * @return Session
     */
    public function findOneByName($name)
    {
        return $this->getRepository()->findOneByName($name);
    }

}
