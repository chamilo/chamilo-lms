<?php

/*
 * This file is part of the Sonata Project package.
 *
 * (c) Thomas Rabaix <thomas.rabaix@sonata-project.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Sonata\DatagridBundle\Pager\Elastica;

use Sonata\DatagridBundle\Pager\BasePager;

/**
 * Elastica pager class.
 *
 * @author Vincent Composieux <vincent.composieux@gmail.com>
 */
class Pager extends BasePager
{
    /**
     * @return int
     */
    public function computeNbResult()
    {
        $countQuery = clone $this->getQuery();
        $countQuery->execute();

        return $countQuery->getResults()->getTotalHits();
    }

    /**
     * {@inheritdoc}
     */
    public function getResults()
    {
        return $this->getQuery()->execute();
    }

    /**
     * {@inheritdoc}
     */
    public function getQuery()
    {
        return $this->query;
    }

    /**
     * {@inheritdoc}
     */
    public function init()
    {
        $this->resetIterator();

        $this->getQuery()->setMaxResults($this->getMaxPerPage());

        $this->setNbResults($this->computeNbResult());

        if (count($this->getParameters()) > 0) {
            $this->getQuery()->setParameters($this->getParameters());
        }

        if (0 == $this->getPage() || 0 == $this->getMaxPerPage() || 0 == $this->getNbResults()) {
            $this->setLastPage(0);
        } else {
            $offset = ($this->getPage() - 1) * $this->getMaxPerPage();

            $this->setLastPage(ceil($this->getNbResults() / $this->getMaxPerPage()));

            $this->getQuery()->setFirstResult($offset);
            $this->getQuery()->setMaxResults($this->getMaxPerPage());
        }
    }
}
