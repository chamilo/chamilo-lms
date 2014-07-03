<?php
/*
 * This file is part of the Sonata package.
 *
 * (c) Thomas Rabaix <thomas.rabaix@sonata-project.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Sonata\CoreBundle\Model;

/**
 * Class PageableManagerInterface.
 *
 * @author Raphaël Benitte <benitteraphael@gmail.com>
 */
interface PageableManagerInterface
{
    /**
     * @param array   $criteria
     * @param integer $page
     * @param integer $limit
     * @param array   $sort
     *
     * @return \Sonata\DatagridBundle\Pager\PagerInterface
     */
    public function getPager(array $criteria, $page, $limit = 10, array $sort = array());
}
