<?php

namespace Chamilo\CoreBundle\Entity;

use Sonata\CoreBundle\Model\BaseEntityManager;

use Sonata\DatagridBundle\Pager\Doctrine\pager;
use Sonata\DatagridBundle\ProxyQuery\Doctrine\ProxyQuery;

/**
 * Class CourseManager
 * @package Chamilo\CoreBundle\Entity
 */
class CourseManager extends BaseEntityManager
{

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
    public function findByTitle($name)
    {
        return $this->getRepository()->findByTitle($name);
    }
}
