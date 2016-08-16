<?php

/*
 * This file is part of the Sonata Project package.
 *
 * (c) Thomas Rabaix <thomas.rabaix@sonata-project.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Sonata\UserBundle\Twig;

use Sonata\AdminBundle\Admin\AdminInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * GlobalVariables.
 *
 * @author Thomas Rabaix <thomas.rabaix@sonata-project.org>
 */
class GlobalVariables
{
    protected $container;

    /**
     * @param ContainerInterface $container
     */
    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    /**
     * @return string
     */
    public function getImpersonating()
    {
        return $this->container->getParameter('sonata.user.impersonating');
    }

    /**
     * @return string
     */
    public function getDefaultAvatar()
    {
        return $this->container->getParameter('sonata.user.default_avatar');
    }

    /**
     * @return AdminInterface
     */
    public function getUserAdmin()
    {
        return $this->container->get('sonata.user.admin.user');
    }
}
