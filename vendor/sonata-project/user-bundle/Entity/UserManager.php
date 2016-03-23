<?php
/*
 * This file is part of the Sonata package.
 *
 * (c) Thomas Rabaix <thomas.rabaix@sonata-project.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */


namespace Sonata\UserBundle\Entity;

use FOS\UserBundle\Doctrine\UserManager as BaseUserManager;
use Sonata\UserBundle\Model\UserManagerInterface;

/**
 * Class UserManager
 *
 * @package Sonata\UserBundle\Entity
 *
 * @author Hugo Briand <briand@ekino.com>
 */
class UserManager extends BaseUserManager implements UserManagerInterface
{
    /**
     * {@inheritdoc}
     */
    public function findUsersBy(array $criteria = null, array $orderBy = null, $limit = null, $offset = null)
    {
        return $this->repository->findBy($criteria, $orderBy, $limit, $offset);
    }
}