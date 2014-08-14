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
     * @param $name
     * @return mixed
     */
    public function findByTitle($name)
    {
        return $this->getRepository()->findByTitle($name);
    }

    public function saveTools($courseId)
    {

    }

    /**
     * {@inheritdoc}
     */
    public function getPager(array $criteria, $page, $limit = 10, array $sort = array())
    {
        $parameters = array();

        $query = $this->getRepository()
            ->createQueryBuilder('t')
            ->select('t');

        /*if (isset($criteria['enabled'])) {
            $query->andWhere('t.enabled = :enabled');
            $parameters['enabled'] = (bool) $criteria['enabled'];
        }*/

        $query->setParameters($parameters);

        $pager = new Pager();
        $pager->setMaxPerPage($limit);
        $pager->setQuery(new ProxyQuery($query));
        $pager->setPage($page);
        $pager->init();

        return $pager;
    }
}
