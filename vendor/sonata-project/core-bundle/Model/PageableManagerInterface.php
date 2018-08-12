<?php

/*
 * This file is part of the Sonata Project package.
 *
 * (c) Thomas Rabaix <thomas.rabaix@sonata-project.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Sonata\CoreBundle\Model;

use Sonata\DatagridBundle\Pager\PagerInterface;

/**
 * @author Raphaël Benitte <benitteraphael@gmail.com>
 */
interface PageableManagerInterface
{
    /**
     * @param array $criteria
     * @param int   $page
     * @param int   $limit
     * @param array $sort
     *
     * @return PagerInterface
     */
    public function getPager(array $criteria, $page, $limit = 10, array $sort = array());
}
