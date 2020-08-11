<?php

/*
 * This file is part of the Sonata Project package.
 *
 * (c) Thomas Rabaix <thomas.rabaix@sonata-project.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Sonata\UserBundle\Model;

use FOS\UserBundle\Model\UserManagerInterface as BaseInterface;
use Sonata\CoreBundle\Model\PageableManagerInterface;

/**
 * @author Hugo Briand <briand@ekino.com>
 */
interface UserManagerInterface extends BaseInterface, PageableManagerInterface
{
    /**
     * Alias for the repository method.
     *
     * @param array|null $criteria
     * @param array|null $orderBy
     * @param int|null   $limit
     * @param int|null   $offset
     *
     * @return UserInterface[]
     */
    public function findUsersBy(array $criteria = null, array $orderBy = null, $limit = null, $offset = null);
}
