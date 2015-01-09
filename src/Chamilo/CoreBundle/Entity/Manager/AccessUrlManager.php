<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Entity\Manager;

use Sonata\CoreBundle\Model\BaseEntityManager;
use Chamilo\CoreBundle\Entity\Course;
use Chamilo\UserBundle\Entity\User;
use Sonata\DatagridBundle\Pager\Doctrine\pager;
use Sonata\DatagridBundle\ProxyQuery\Doctrine\ProxyQuery;
use Doctrine\Common\Collections\Criteria;

/**
 * Class AccessUrlManager
 * @package Chamilo\CoreBundle\Entity\Manager
 */
class AccessUrlManager extends BaseEntityManager
{
    /**
     * @return Course
     */
    public function createUrl()
    {
        return $this->create();
    }
}
