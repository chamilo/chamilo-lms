<?php

/*
 * This file is part of the Pagerfanta package.
 *
 * (c) Pablo Díez <pablodip@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Pagerfanta\Adapter;

use Doctrine\Common\Collections\Criteria;
use Doctrine\Common\Collections\Selectable;

/**
 * DoctrineSelectableAdapter.
 *
 * @author Boris Guéry <guery.b@gmail.com>
 */
class DoctrineSelectableAdapter implements AdapterInterface
{
    /**
     * @var Selectable
     */
    private $selectable;

    /**
     * @var Criteria
     */
    private $criteria;

    /**
     * Constructor.
     *
     * @param Selectable $selectable An implementation of the Selectable interface.
     * @param Criteria   $criteria   A Doctrine criteria.
     */
    public function __construct(Selectable $selectable, Criteria $criteria)
    {
        $this->selectable = $selectable;
        $this->criteria   = $criteria;
    }

    /**
     * {@inheritdoc}
     */
    public function getNbResults()
    {
        $firstResult = null;
        $maxResults = null;

        $criteria = $this->createCriteria($firstResult, $maxResults);

        return $this->selectable->matching($criteria)->count();
    }

    /**
     * {@inheritdoc}
     */
    public function getSlice($offset, $length)
    {
        $firstResult = $offset;
        $maxResults = $length;

        $criteria = $this->createCriteria($firstResult, $maxResults);

        return $this->selectable->matching($criteria);
    }

    private function createCriteria($firstResult, $maxResult)
    {
        $criteria = clone $this->criteria;
        $criteria->setFirstResult($firstResult);
        $criteria->setMaxResults($maxResult);

        return $criteria;
    }
}
